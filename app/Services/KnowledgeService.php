<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Support\Collection;

/**
 * Knowledge level of a student in a course, measured with the course's final evaluation:
 *  - diagnostic : placement test taken before studying (starting level),
 *  - standard   : the evaluation that closes the course,
 *  - retake     : re-evaluations taken later (knowledge retention).
 * Comparing these scores over time shows whether knowledge progresses or regresses.
 * Lesson quizzes and module exercises complete the picture (mastery while studying).
 */
class KnowledgeService
{
    public const TREND_PROGRESS   = 'progress';
    public const TREND_REGRESSION = 'regression';
    public const TREND_STABLE     = 'stable';
    public const TREND_SINGLE     = 'single';   // one evaluation only: no trend yet
    public const TREND_NONE       = 'none';     // not evaluated yet

    /** Knowledge level for a score in percent. */
    public static function level(?float $score): ?string
    {
        if ($score === null) {
            return null;
        }
        return match (true) {
            $score >= 85 => 'expert',
            $score >= 70 => 'advanced',
            $score >= 50 => 'intermediate',
            default      => 'beginner',
        };
    }

    /**
     * Profiles of several enrollments of the same course, in two queries.
     *
     * @return array<int, array> keyed by enrollment id
     */
    public function profiles(Course $course, Collection $enrollments): array
    {
        $course->loadMissing(['finalExam', 'modules.exam', 'modules.lessons.quiz', 'modules.translations']);
        $userIds = $enrollments->pluck('user_id')->unique()->values();

        $final = $course->finalExam;
        $finalAttempts = $final
            ? QuizAttempt::where('quiz_id', $final->id)->whereIn('user_id', $userIds)
                ->orderBy('attempted_at')->orderBy('id')->get()->groupBy('user_id')
            : collect();

        $lessonQuizIds = $course->modules->flatMap->lessons->pluck('quiz.id')->filter()->values();
        $examIds = $course->modules->pluck('exam.id')->filter()->values();
        $best = QuizAttempt::whereIn('quiz_id', $lessonQuizIds->merge($examIds))->whereIn('user_id', $userIds)
            ->where('mode', Quiz::MODE_STANDARD)
            ->selectRaw('user_id, quiz_id, MAX(score) as best, COUNT(*) as tries')
            ->groupBy('user_id', 'quiz_id')->get()->groupBy('user_id');

        $profiles = [];
        foreach ($enrollments as $enrollment) {
            $mine = $best->get($enrollment->user_id, collect());
            $profiles[$enrollment->id] = $this->build(
                $course,
                $finalAttempts->get($enrollment->user_id, collect()),
                $mine->whereIn('quiz_id', $lessonQuizIds->all()),
                $mine->whereIn('quiz_id', $examIds->all())->keyBy('quiz_id'),
            );
        }

        return $profiles;
    }

    public function profile(Enrollment $enrollment): array
    {
        return $this->profiles($enrollment->course, collect([$enrollment]))[$enrollment->id];
    }

    /** Course-wide summary for the instructor dashboard. */
    public function courseSummary(Course $course): array
    {
        $enrollments = $course->enrollments()->with('user:id,name')->get(['id', 'user_id', 'course_id', 'progress_percent']);
        $profiles = collect($this->profiles($course, $enrollments));
        $evaluated = $profiles->filter(fn ($p) => $p['current'] !== null);
        $withGain = $profiles->filter(fn ($p) => $p['gain'] !== null);

        return [
            'students'    => $enrollments->count(),
            'evaluated'   => $evaluated->count(),
            'avg_current' => $evaluated->isEmpty() ? null : round($evaluated->avg('current'), 1),
            'avg_gain'    => $withGain->isEmpty() ? null : round($withGain->avg('gain'), 1),
            'progress'    => $profiles->where('trend', self::TREND_PROGRESS)->count(),
            'regression'  => $profiles->where('trend', self::TREND_REGRESSION)->count(),
            'stable'      => $profiles->where('trend', self::TREND_STABLE)->count(),
            'profiles'    => $profiles,
            'enrollments' => $enrollments->keyBy('id'),
        ];
    }

    private function build(Course $course, Collection $attempts, Collection $lessonBest, Collection $examBest): array
    {
        $threshold = (float) config('lms.assessment.trend_threshold');
        $scores = $attempts->map(fn (QuizAttempt $a) => [
            'id'      => $a->id,
            'date'    => $a->attempted_at,
            'score'   => (float) $a->score,
            'mode'    => $a->mode,
            'passed'  => $a->passed,
            'modules' => $a->module_scores ?? [],
        ])->values();

        $diagnostic = $scores->firstWhere('mode', Quiz::MODE_DIAGNOSTIC);
        $baseline = $diagnostic ?? $scores->first();
        $current = $scores->last();
        $previous = $scores->count() > 1 ? $scores[$scores->count() - 2] : null;

        $trend = self::TREND_NONE;
        $delta = null;
        if ($current && $previous) {
            $delta = round($current['score'] - $previous['score'], 1);
            $trend = match (true) {
                $delta >= $threshold  => self::TREND_PROGRESS,
                $delta <= -$threshold => self::TREND_REGRESSION,
                default               => self::TREND_STABLE,
            };
        } elseif ($current) {
            $trend = self::TREND_SINGLE;
        }

        // Mastery per module, from the latest evaluation that covered it.
        $moduleMastery = [];
        foreach ($course->modules as $module) {
            $latest = $scores->reverse()->first(fn ($s) => isset($s['modules'][$module->id]));
            $moduleMastery[$module->id] = [
                'title'      => $module->title(),
                'evaluation' => $latest ? (float) $latest['modules'][$module->id] : null,
                'exercise'   => $module->exam && isset($examBest[$module->exam->id]) ? round((float) $examBest[$module->exam->id]->best, 1) : null,
            ];
        }

        $lessonAvg = $lessonBest->isEmpty() ? null : round((float) $lessonBest->avg('best'), 1);
        $examAvg = $examBest->isEmpty() ? null : round((float) $examBest->avg('best'), 1);

        return [
            'evaluations'   => $scores,
            'baseline'      => $baseline['score'] ?? null,
            'baseline_is_diagnostic' => (bool) $diagnostic,
            'current'       => $current['score'] ?? null,
            'best'          => $scores->isEmpty() ? null : $scores->max('score'),
            'gain'          => ($baseline && $current && $scores->count() > 1) ? round($current['score'] - $baseline['score'], 1) : null,
            'delta'         => $delta,
            'trend'         => $trend,
            'level'         => self::level($current['score'] ?? null),
            'last_date'     => $current['date'] ?? null,
            'lesson_avg'    => $lessonAvg,
            'exercise_avg'  => $examAvg,
            'modules'       => $moduleMastery,
        ];
    }
}
