<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonWatchState;
use App\Models\Quiz;
use App\Services\KnowledgeService;
use App\Services\ProgressService;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function __construct(private ProgressService $progress) {}

    public function index(Request $request)
    {
        $user = $request->user();

        $enrollments = $user->enrollments()
            ->with(['course.translations', 'course.instructor', 'course.category.translations'])
            ->latest('enrolled_at')
            ->paginate(12);

        $certificates = $user->certificates()->pluck('id', 'course_id');

        return view('student.courses.index', compact('enrollments', 'certificates'));
    }

    public function enroll(Request $request, Course $course)
    {
        $user = $request->user();

        abort_if($course->status !== 'published', 404);

        $existing = $user->enrollments()->where('course_id', $course->id)->first();
        if ($existing) {
            return redirect()->route('student.courses.player', $existing)->with('info', __('messages.already_enrolled'));
        }

        $enrollment = Enrollment::create([
            'user_id'     => $user->id,
            'course_id'   => $course->id,
            'enrolled_at' => now(),
        ]);

        $user->notify(new \App\Notifications\EnrollmentConfirmed($enrollment));
        if ($course->instructor && $course->instructor_id !== $user->id) {
            $course->instructor->notify(new \App\Notifications\NewStudentEnrolled($enrollment));
        }

        return redirect()->route('student.courses.player', $enrollment)
            ->with('success', __('messages.enrolled_success'));
    }

    /**
     * The course player. It shows one step of the path at a time: a lesson (?lesson=ID)
     * or an assessment (?assessment=QUIZ_ID, with &mode=diagnostic|retake for the final evaluation).
     */
    public function player(Request $request, Enrollment $enrollment)
    {
        abort_if($enrollment->user_id !== $request->user()->id, 403);

        $enrollment->load([
            'course.translations',
            'course.instructor',
            'course.modules.translations',
            'course.modules.lessons.translations',
            'course.books',
        ]);
        $course = $enrollment->course;
        $userId = $request->user()->id;

        $state        = $this->progress->state($enrollment);
        $steps        = $state['steps'];
        $completedIds = $state['completedIds'];
        $doneKeys     = $state['done'];
        $unlocked     = $state['unlocked'];
        $ordered      = $steps->where('type', 'lesson')->pluck('lesson')->values();

        $final = $course->finalExam && $course->finalExam->isReady() ? $course->finalExam : null;
        $finalMode = $this->finalMode($request, $final, $userId);

        $step = $finalMode ? $steps->firstWhere('type', 'final') : $this->pickStep($request, $enrollment, $steps, $doneKeys, $unlocked);
        $currentLesson = ($step['type'] ?? null) === 'lesson' ? $step['lesson'] : null;
        $currentQuiz = $step && $step['type'] !== 'lesson' ? $step['quiz'] : null;

        $context = [];
        if ($step) {
            $index = $steps->search(fn ($s) => $s['key'] === $step['key']);
            $context['prevStep'] = $index > 0 ? $steps[$index - 1] : null;
            $context['nextStep'] = $steps[$index + 1] ?? null;
            $context['stepPosition'] = $index + 1;
        }

        if ($currentLesson) {
            $currentLesson->load(['translations', 'resources', 'quiz.questions.options', 'comments.user']);
            $enrollment->update(['last_lesson_id' => $currentLesson->id]);
            $enrollment->touch(); // last activity, used by the inactivity reminder
            $lessonQuiz = $currentLesson->studentQuiz();

            $context += [
                'watchState' => LessonWatchState::where('user_id', $userId)->where('lesson_id', $currentLesson->id)->first(),
                'submission' => $currentLesson->type === 'assignment'
                    ? AssignmentSubmission::where('user_id', $userId)->where('lesson_id', $currentLesson->id)->first()
                    : null,
                'lessonQuiz' => $lessonQuiz,
                'quizState'  => $lessonQuiz ? $this->quizState($lessonQuiz, $userId) : null,
            ];
        }

        if ($currentQuiz) {
            $currentQuiz->load(['questions.options', 'module.translations']);
            $context['quizState'] = $this->quizState($currentQuiz, $userId);
        }

        $knowledge = $final ? app(KnowledgeService::class)->profile($enrollment) : null;
        $libraryBookIds = $request->user()->libraryItems()->whereIn('book_id', $course->books->pluck('id'))->pluck('book_id')->all();

        return view('student.courses.player', array_merge(
            compact('enrollment', 'course', 'completedIds', 'doneKeys', 'unlocked', 'steps', 'ordered',
                'currentLesson', 'currentQuiz', 'final', 'finalMode', 'knowledge', 'libraryBookIds'),
            [
                'prevStep' => null, 'nextStep' => null, 'stepPosition' => 0, 'watchState' => null,
                'submission' => null, 'lessonQuiz' => null, 'quizState' => null,
                'canDiagnose' => $final && ! $final->attempts()->where('user_id', $userId)->exists(),
                'retakeAt' => $final && $final->hasPassed($userId) ? QuizController::nextRetakeAt($final, $userId) : null,
            ],
            $context
        ));
    }

    /** "My results": knowledge level over time, measured with the final evaluation. */
    public function results(Request $request, Enrollment $enrollment, KnowledgeService $knowledge)
    {
        abort_if($enrollment->user_id !== $request->user()->id, 403);
        $enrollment->load(['course.translations', 'course.finalExam', 'course.modules.translations', 'course.modules.exam']);

        $profile = $knowledge->profile($enrollment);
        $final = $enrollment->course->finalExam;
        $retakeAt = $final && $final->hasPassed($enrollment->user_id) ? QuizController::nextRetakeAt($final, $enrollment->user_id) : null;
        $canDiagnose = $final && $final->isReady() && ! $final->attempts()->where('user_id', $enrollment->user_id)->exists();

        return view('student.courses.results', compact('enrollment', 'profile', 'final', 'retakeAt', 'canDiagnose'));
    }

    /** Opens a lesson from a notification link (lesson id only). */
    public function openLesson(Request $request, Lesson $lesson)
    {
        $enrollment = $this->progress->enrollmentFor($request->user(), $lesson);
        abort_unless($enrollment, 403);

        return redirect()->to(route('student.courses.player', $enrollment) . '?lesson=' . $lesson->id);
    }

    public function announcements(Request $request, Course $course)
    {
        $enrolled = $request->user()->enrollments()->where('course_id', $course->id)->first();
        abort_unless($enrolled || $this->progress->canManage($request->user(), $course), 403);

        $course->load('translations');
        $announcements = $course->announcements()->with('author')->paginate(10);

        return view('student.courses.announcements', compact('course', 'announcements', 'enrolled'));
    }

    /** Placement test or re-evaluation requested on the final evaluation, when allowed. */
    private function finalMode(Request $request, ?Quiz $final, int $userId): ?string
    {
        $mode = $request->query('mode');
        if (! $final || $request->integer('assessment') !== $final->id) {
            return null;
        }
        if ($mode === Quiz::MODE_DIAGNOSTIC && ! $final->attempts()->where('user_id', $userId)->exists()) {
            return Quiz::MODE_DIAGNOSTIC;
        }
        if ($mode === Quiz::MODE_RETAKE && $final->hasPassed($userId)) {
            return Quiz::MODE_RETAKE;
        }
        return null;
    }

    private function pickStep(Request $request, Enrollment $enrollment, $steps, array $doneKeys, array $unlocked): ?array
    {
        $requested = $request->integer('lesson')
            ? ProgressService::lessonKey($request->integer('lesson'))
            : ($request->integer('assessment') ? ProgressService::quizKey($request->integer('assessment')) : null);

        if ($requested) {
            $step = $steps->firstWhere('key', $requested);
            if ($step && ($unlocked[$step['key']] ?? false)) {
                return $step;
            }
            if ($step) {
                session()->flash('warning', $step['type'] === 'lesson' ? __('lms.lesson_locked') : __('learn.assessment_locked'));
            }
        }

        if ($enrollment->last_lesson_id && ! $requested) {
            $last = $steps->firstWhere('key', ProgressService::lessonKey($enrollment->last_lesson_id));
            // Resume where the student stopped, unless that lesson is done and the next step waits.
            if ($last && ($unlocked[$last['key']] ?? false) && ! in_array($last['key'], $doneKeys, true)) {
                return $last;
            }
        }

        // First step to do, otherwise the first step.
        return $steps->first(fn ($s) => ! in_array($s['key'], $doneKeys, true) && ($unlocked[$s['key']] ?? false))
            ?? $steps->first();
    }

    private function quizState(Quiz $quiz, int $userId): array
    {
        return [
            'last'          => $quiz->userAttempt($userId),
            'passed'        => $quiz->hasPassed($userId),
            'attempts'      => $quiz->attemptsCount($userId),
            'attempts_left' => $quiz->attemptsLeft($userId),
            'best'          => (float) $quiz->standardAttempts()->where('user_id', $userId)->max('score'),
        ];
    }
}
