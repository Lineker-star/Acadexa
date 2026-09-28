<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Notifications\CertificateIssued;
use Illuminate\Support\Collection;

/**
 * Single place for the pedagogical rules.
 *
 * The course path is a list of steps: every lesson of a module, then the exercise that
 * closes the module, and after the last module the final evaluation.
 *  - a video/text lesson with a quiz (>= 10 questions) is validated by passing that quiz (>= 7/10);
 *    without a quiz it completes manually (or when the video ends),
 *  - quiz lessons complete when passed, assignments when graded as passed,
 *  - a module exercise opens once the module's lessons are done,
 *  - the final evaluation opens once everything else is done,
 *  - sequential courses lock every step until the previous ones are done,
 *  - the certificate is issued automatically at 100 %.
 * Module exercises and final evaluations only count once they have enough questions.
 */
class ProgressService
{
    public function __construct(private CertificateService $certificates) {}

    /** Lessons of a course in curriculum order (module order, then lesson order). */
    public function orderedLessons(Course $course): Collection
    {
        $course->loadMissing('modules.lessons');
        return $course->modules->flatMap(fn ($module) => $module->lessons)->values();
    }

    /**
     * @return Collection<int, array{key: string, type: string, module: ?\App\Models\Module, lesson: ?Lesson, quiz: ?Quiz}>
     *         type is lesson | exam (module exercise) | final
     */
    public function steps(Course $course): Collection
    {
        $this->loadPath($course);

        $steps = collect();
        foreach ($course->modules as $module) {
            foreach ($module->lessons as $lesson) {
                $steps->push(['key' => self::lessonKey($lesson->id), 'type' => 'lesson', 'module' => $module, 'lesson' => $lesson, 'quiz' => null]);
            }
            if ($module->exam && $module->exam->isReady()) {
                $steps->push(['key' => self::quizKey($module->exam->id), 'type' => 'exam', 'module' => $module, 'lesson' => null, 'quiz' => $module->exam]);
            }
        }
        if ($course->finalExam && $course->finalExam->isReady()) {
            $steps->push(['key' => self::quizKey($course->finalExam->id), 'type' => 'final', 'module' => null, 'lesson' => null, 'quiz' => $course->finalExam]);
        }

        return $steps;
    }

    public static function lessonKey(int $id): string
    {
        return 'lesson:' . $id;
    }

    public static function quizKey(int $id): string
    {
        return 'quiz:' . $id;
    }

    public function completedLessonIds(Enrollment $enrollment): array
    {
        return $enrollment->lessonProgress()->pluck('lesson_id')->map(fn ($id) => (int) $id)->all();
    }

    /** Module exercises / final evaluations passed by the student (standard attempts only). */
    public function passedQuizIds(Enrollment $enrollment, Collection $steps): array
    {
        $ids = $steps->pluck('quiz.id')->filter()->values();
        if ($ids->isEmpty()) {
            return [];
        }
        return QuizAttempt::where('user_id', $enrollment->user_id)->whereIn('quiz_id', $ids)
            ->where('mode', Quiz::MODE_STANDARD)->where('passed', true)
            ->distinct()->pluck('quiz_id')->map(fn ($id) => (int) $id)->all();
    }

    /** Keys of the steps already done. */
    public function doneKeys(Enrollment $enrollment, Collection $steps, ?array $completedLessonIds = null): array
    {
        $completedLessonIds ??= $this->completedLessonIds($enrollment);
        $keys = array_map([self::class, 'lessonKey'], $completedLessonIds);
        foreach ($this->passedQuizIds($enrollment, $steps) as $quizId) {
            $keys[] = self::quizKey($quizId);
        }
        return $keys;
    }

    /** Map of step key => unlocked flag, computed in one pass. */
    public function unlockMap(Enrollment $enrollment, Collection $steps, array $doneKeys): array
    {
        $done = array_flip($doneKeys);
        $sequential = (bool) $enrollment->course->is_sequential;
        $map = [];
        $allPreviousDone = true;

        foreach ($steps as $step) {
            $open = match (true) {
                $sequential              => $allPreviousDone,
                $step['type'] === 'lesson' => true,
                $step['type'] === 'exam'   => $step['module']->lessons->every(fn ($l) => isset($done[self::lessonKey($l->id)])),
                default                  => $allPreviousDone, // final evaluation: everything before it
            };
            $map[$step['key']] = $open;
            if (! isset($done[$step['key']])) {
                $allPreviousDone = false;
            }
        }

        return $map;
    }

    /** Everything the player needs about the student's position in the path. */
    public function state(Enrollment $enrollment): array
    {
        $course = $enrollment->course;
        $steps = $this->steps($course);
        $completedIds = $this->completedLessonIds($enrollment);
        $done = $this->doneKeys($enrollment, $steps, $completedIds);

        return [
            'steps'        => $steps,
            'completedIds' => $completedIds,
            'done'         => $done,
            'unlocked'     => $this->unlockMap($enrollment, $steps, $done),
        ];
    }

    public function isUnlocked(Enrollment $enrollment, Lesson $lesson): bool
    {
        return $this->isStepUnlocked($enrollment, self::lessonKey($lesson->id));
    }

    public function isQuizUnlocked(Enrollment $enrollment, Quiz $quiz): bool
    {
        if ($quiz->scope === Quiz::SCOPE_LESSON) {
            return $this->isUnlocked($enrollment, $quiz->lesson);
        }
        return $this->isStepUnlocked($enrollment, self::quizKey($quiz->id));
    }

    private function isStepUnlocked(Enrollment $enrollment, string $key): bool
    {
        $state = $this->state($enrollment);
        return $state['unlocked'][$key] ?? false;
    }

    /** Share of the path done, in percent. */
    public function percent(Enrollment $enrollment): float
    {
        $steps = $this->steps($enrollment->course()->first());
        if ($steps->isEmpty()) {
            return 0.0;
        }
        $done = array_flip($this->doneKeys($enrollment, $steps));
        $count = $steps->filter(fn ($step) => isset($done[$step['key']]))->count();

        return round($count / $steps->count() * 100, 2);
    }

    /**
     * Records completion and updates progress; issues the certificate at 100 %.
     *
     * @return array{progress: float, completed: bool, certificate_issued: bool}
     */
    public function complete(Enrollment $enrollment, Lesson $lesson): array
    {
        LessonProgress::firstOrCreate(
            ['enrollment_id' => $enrollment->id, 'lesson_id' => $lesson->id],
            ['completed_at' => now()]
        );

        $enrollment->update(['last_lesson_id' => $lesson->id]);

        return $this->refresh($enrollment);
    }

    /** @return array{progress: float, completed: bool, certificate_issued: bool} */
    public function refresh(Enrollment $enrollment): array
    {
        $enrollment->recalculateProgress();
        $enrollment->refresh();

        $issued = false;
        if ($enrollment->progress_percent >= 100) {
            $alreadyHad = $enrollment->user->certificates()->where('course_id', $enrollment->course_id)->exists();
            $certificate = $this->certificates->issue($enrollment);
            if (! $alreadyHad) {
                $issued = true;
                $enrollment->user->notify(new CertificateIssued($certificate));
            }
        }

        return [
            'progress'           => (float) $enrollment->progress_percent,
            'completed'          => $enrollment->progress_percent >= 100,
            'certificate_issued' => $issued,
        ];
    }

    /** The enrollment giving this user access to the lesson's course, if any. */
    public function enrollmentFor(User $user, Lesson $lesson): ?Enrollment
    {
        return $this->enrollmentForCourse($user, $lesson->module->course_id);
    }

    public function enrollmentForCourse(User $user, int $courseId): ?Enrollment
    {
        return Enrollment::where('user_id', $user->id)->where('course_id', $courseId)->first();
    }

    /** Owner instructor or admin: can view any lesson of the course without enrollment. */
    public function canManage(User $user, Course $course): bool
    {
        return $user->isAdmin() || $course->instructor_id === $user->id;
    }

    /** Loads lessons, quizzes and question counts of the whole path in a few queries. */
    private function loadPath(Course $course): void
    {
        $course->loadMissing(['modules.lessons.quiz', 'modules.exam', 'finalExam']);

        $quizzes = $course->modules->flatMap(fn ($m) => $m->lessons->pluck('quiz')->push($m->exam))
            ->push($course->finalExam)->filter()
            ->reject(fn (Quiz $quiz) => $quiz->relationLoaded('questions') || $quiz->preloadedQuestionCount !== null);
        if ($quizzes->isEmpty()) {
            return;
        }

        $counts = QuizQuestion::whereIn('quiz_id', $quizzes->pluck('id'))
            ->selectRaw('quiz_id, COUNT(*) as n')->groupBy('quiz_id')->pluck('n', 'quiz_id');
        foreach ($quizzes as $quiz) {
            $quiz->preloadedQuestionCount = (int) ($counts[$quiz->id] ?? 0);
        }
    }
}
