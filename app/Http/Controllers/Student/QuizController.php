<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Notifications\KnowledgeRegression;
use App\Services\KnowledgeService;
use App\Services\ProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Lesson quizzes, module exercises and the final evaluation.
 * Answers are always graded here, never in the browser.
 */
class QuizController extends Controller
{
    /** Seconds tolerated after the time limit (network latency, auto-submit). */
    private const GRACE_SECONDS = 60;

    public function __construct(private ProgressService $progress, private KnowledgeService $knowledge) {}

    /** Starts the countdown of a timed quiz; the server keeps the start time. */
    public function start(Request $request, Quiz $quiz)
    {
        $user = $request->user();
        $mode = $this->mode($request);
        $enrollment = $this->enrollment($request, $quiz);

        if ($error = $this->refusal($enrollment, $quiz, $mode)) {
            return $error;
        }

        $key = $this->timerKey($user->id, $quiz->id);
        $startedAt = Cache::get($key) ?? now()->timestamp;
        Cache::put($key, $startedAt, now()->addMinutes(($quiz->time_limit_minutes ?? 60) + 10));

        $remaining = $quiz->time_limit_minutes
            ? max(0, $startedAt + $quiz->time_limit_minutes * 60 - now()->timestamp)
            : null;

        return response()->json(['remaining_seconds' => $remaining]);
    }

    public function attempt(Request $request, Quiz $quiz)
    {
        $data = $request->validate([
            'answers'    => ['present', 'array'],
            'answers.*'  => ['array'],
            'offline_at' => ['nullable', 'date'],
            'mode'       => ['nullable', 'in:standard,diagnostic,retake'],
        ]);

        $user = $request->user();
        $mode = $this->mode($request);
        $enrollment = $this->enrollment($request, $quiz);

        if ($error = $this->refusal($enrollment, $quiz, $mode)) {
            return $error;
        }

        $timedOut = false;
        if ($quiz->time_limit_minutes) {
            $startedAt = Cache::pull($this->timerKey($user->id, $quiz->id));
            if (! $startedAt) {
                return response()->json(['message' => __('lms.quiz_not_started')], 422);
            }
            $timedOut = now()->timestamp > $startedAt + $quiz->time_limit_minutes * 60 + self::GRACE_SECONDS;
        }

        $questions = $quiz->questions()->with('options')->get();
        $answers = $data['answers'];
        $correct = 0;
        $review = [];
        $perModule = [];
        // A placement test must not reveal the answers of the final evaluation.
        $reveal = $quiz->show_correct_answers && $mode !== Quiz::MODE_DIAGNOSTIC;

        foreach ($questions as $question) {
            $correctIds = $question->options->where('is_correct', true)->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
            $submitted = collect((array) ($answers[$question->id] ?? []))->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();
            $isRight = ! $timedOut && $correctIds === $submitted;
            if ($isRight) {
                $correct++;
            }
            if ($question->module_id) {
                $perModule[$question->module_id]['total'] = ($perModule[$question->module_id]['total'] ?? 0) + 1;
                $perModule[$question->module_id]['correct'] = ($perModule[$question->module_id]['correct'] ?? 0) + ($isRight ? 1 : 0);
            }
            $review[$question->id] = [
                'correct'     => $isRight,
                'answer'      => $reveal ? $correctIds : null,
                'explanation' => $reveal ? $question->explanation : null,
            ];
        }

        $total  = $questions->count();
        $score  = $total > 0 ? round(($correct / $total) * 100, 2) : 0;
        $passingScore = $quiz->effectivePassingScore();
        $passed = ! $timedOut && $score >= $passingScore;

        $attempt = QuizAttempt::create([
            'user_id'         => $user->id,
            'quiz_id'         => $quiz->id,
            'mode'            => $mode,
            'score'           => $score,
            'correct_count'   => $correct,
            'total_questions' => $total,
            'passed'          => $passed,
            'answers'         => $answers,
            'module_scores'   => $perModule ? array_map(fn ($m) => round($m['correct'] / $m['total'] * 100, 1), $perModule) : null,
            'attempted_at'    => $data['offline_at'] ?? now(),
        ]);

        $progress = null;
        if ($passed && $mode === Quiz::MODE_STANDARD) {
            $progress = $quiz->scope === Quiz::SCOPE_LESSON
                ? $this->progress->complete($enrollment, $quiz->lesson)
                : $this->progress->refresh($enrollment);
        }

        $knowledge = null;
        if ($quiz->isFinal()) {
            $profile = $this->knowledge->profile($enrollment);
            $knowledge = [
                'baseline' => $profile['baseline'],
                'current'  => $profile['current'],
                'gain'     => $profile['gain'],
                'delta'    => $profile['delta'],
                'trend'    => $profile['trend'],
                'level'    => $profile['level'] ? __('learn.knowledge_level_' . $profile['level']) : null,
            ];
            if ($profile['trend'] === KnowledgeService::TREND_REGRESSION) {
                $enrollment->course->instructor?->notify(new KnowledgeRegression($attempt, $profile['delta']));
            }
        }

        $message = match (true) {
            $timedOut                        => __('lms.quiz_timed_out'),
            $mode === Quiz::MODE_DIAGNOSTIC  => __('learn.diagnostic_done', ['score' => $score]),
            $mode === Quiz::MODE_RETAKE      => __('learn.retake_done', ['score' => $score]),
            $passed                          => __('messages.quiz_passed'),
            default                          => __('learn.quiz_failed_need', ['score' => $passingScore]),
        };

        return response()->json([
            'score'         => $score,
            'passed'        => $passed,
            'correct'       => $correct,
            'total'         => $total,
            'passing_score' => $passingScore,
            'mode'          => $mode,
            'attempts_left' => $quiz->attemptsLeft($user->id),
            'review'        => $review,
            'progress'      => $progress,
            'knowledge'     => $knowledge,
            'message'       => $message,
        ]);
    }

    private function mode(Request $request): string
    {
        return in_array($request->input('mode'), [Quiz::MODE_DIAGNOSTIC, Quiz::MODE_RETAKE], true)
            ? $request->input('mode')
            : Quiz::MODE_STANDARD;
    }

    private function enrollment(Request $request, Quiz $quiz): Enrollment
    {
        $enrollment = $this->progress->enrollmentForCourse($request->user(), $quiz->ownerCourse()->id);
        abort_unless($enrollment, 403);
        return $enrollment;
    }

    /** Why this attempt is not allowed, as a JSON response; null when allowed. */
    private function refusal(Enrollment $enrollment, Quiz $quiz, string $mode)
    {
        $userId = $enrollment->user_id;

        if ($mode !== Quiz::MODE_STANDARD) {
            if (! $quiz->isFinal() || ! $quiz->isReady()) {
                return response()->json(['message' => __('learn.mode_not_available')], 422);
            }
            if ($mode === Quiz::MODE_DIAGNOSTIC) {
                $taken = $quiz->attempts()->where('user_id', $userId)->exists();
                return $taken ? response()->json(['message' => __('learn.diagnostic_already_taken')], 422) : null;
            }
            // Retake: only after passing the evaluation, and not more than once per cooldown.
            if (! $quiz->hasPassed($userId)) {
                return response()->json(['message' => __('learn.retake_after_pass')], 422);
            }
            $next = self::nextRetakeAt($quiz, $userId);
            if ($next && $next->isFuture()) {
                return response()->json(['message' => __('learn.retake_available_on', ['date' => $next->isoFormat('LL')])], 422);
            }
            return null;
        }

        if ($quiz->attemptsLeft($userId) === 0) {
            return response()->json(['message' => __('lms.quiz_no_attempts_left')], 422);
        }
        if (! $this->progress->isQuizUnlocked($enrollment, $quiz)) {
            return response()->json(['message' => __('lms.lesson_locked')], 423);
        }
        return null;
    }

    /** Date from which the final evaluation can be retaken (null = now). */
    public static function nextRetakeAt(Quiz $quiz, int $userId): ?\Illuminate\Support\Carbon
    {
        $last = $quiz->attempts()->where('user_id', $userId)->max('attempted_at');
        return $last ? \Illuminate\Support\Carbon::parse($last)->addDays((int) config('lms.assessment.retake_cooldown_days')) : null;
    }

    private function timerKey(int $userId, int $quizId): string
    {
        return "quiz_timer:{$userId}:{$quizId}";
    }
}
