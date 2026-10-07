<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Instructor\Concerns\AuthorizesCourse;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\QuizAttempt;
use App\Services\KnowledgeService;
use App\Services\ProgressService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CourseStudentController extends Controller
{
    use AuthorizesCourse;

    public function index(Request $request, Course $course, ProgressService $progress)
    {
        $this->authorizeCourse($course);
        $course->load(['translations', 'modules.lessons.translations', 'modules.lessons.quiz']);

        $query = $course->enrollments()->with(['user', 'lastLesson.translations'])->withCount('lessonProgress');
        if ($request->filled('search')) {
            $s = $request->search;
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"));
        }
        match ($request->get('filter')) {
            'completed'   => $query->where('progress_percent', '>=', 100),
            'in_progress' => $query->where('progress_percent', '>', 0)->where('progress_percent', '<', 100),
            'not_started' => $query->where('progress_percent', 0),
            default       => null,
        };
        $enrollments = $query->latest('enrolled_at')->paginate(25)->withQueryString();

        $stats = $this->stats($course, $progress);
        $profiles = app(KnowledgeService::class)->profiles($course, $enrollments->getCollection());

        return view('instructor.students.index', compact('course', 'enrollments', 'stats', 'profiles'));
    }

    public function show(Course $course, Enrollment $enrollment, ProgressService $progress)
    {
        $this->authorizeCourse($course);
        abort_if($enrollment->course_id !== $course->id, 404);

        $course->load(['translations', 'modules.translations', 'modules.lessons.translations', 'modules.lessons.quiz', 'modules.exam', 'finalExam']);
        $enrollment->load('user');

        $completed = LessonProgress::where('enrollment_id', $enrollment->id)->pluck('completed_at', 'lesson_id');
        $quizIds = $course->modules->flatMap->lessons->pluck('quiz.id')
            ->merge($course->modules->pluck('exam.id'))->push($course->finalExam?->id)->filter();
        $bestScores = QuizAttempt::where('user_id', $enrollment->user_id)->whereIn('quiz_id', $quizIds)
            ->where('mode', \App\Models\Quiz::MODE_STANDARD)
            ->selectRaw('quiz_id, MAX(score) as best, COUNT(*) as attempts, MAX(passed) as passed')->groupBy('quiz_id')->get()->keyBy('quiz_id');
        $submissions = $enrollment->user->submissions()
            ->whereIn('lesson_id', $course->modules->flatMap->lessons->pluck('id'))->get()->keyBy('lesson_id');

        $profile = app(KnowledgeService::class)->profile($enrollment);

        // What the student wrote in the module exercises (open questions), latest hand-in per exercise.
        $course->load('modules.exam.questions');
        $exerciseAnswers = QuizAttempt::where('user_id', $enrollment->user_id)
            ->whereIn('quiz_id', $course->modules->pluck('exam.id')->filter())
            ->where('mode', \App\Models\Quiz::MODE_STANDARD)->orderBy('id')->get()->keyBy('quiz_id');

        return view('instructor.students.show', compact('course', 'enrollment', 'completed', 'bestScores', 'submissions', 'profile', 'exerciseAnswers'));
    }

    /**
     * Knowledge tracking: level of every student measured with the final evaluation
     * (placement test, end of course, re-evaluations), with progression or regression.
     */
    public function knowledge(Request $request, Course $course, KnowledgeService $knowledge)
    {
        $this->authorizeCourse($course);
        $course->load(['translations', 'modules.translations', 'modules.exam', 'modules.lessons.quiz', 'finalExam']);

        $summary = $knowledge->courseSummary($course);
        $profiles = $summary['profiles'];

        $trend = $request->get('trend');
        $ids = $profiles->filter(fn ($p) => ! $trend || $p['trend'] === $trend)->keys();
        $enrollments = $course->enrollments()->whereIn('id', $ids)->with('user')
            ->orderByDesc('progress_percent')->paginate(25)->withQueryString();

        // Average mastery per module over the evaluated students.
        $moduleAverages = $course->modules->mapWithKeys(function ($module) use ($profiles) {
            $values = $profiles->map(fn ($p) => $p['modules'][$module->id]['evaluation'] ?? $p['modules'][$module->id]['exercise'] ?? null)->filter(fn ($v) => $v !== null);
            return [$module->id => ['title' => $module->title(), 'avg' => $values->isEmpty() ? null : round($values->avg(), 1), 'count' => $values->count()]];
        });

        return view('instructor.students.knowledge', compact('course', 'summary', 'profiles', 'enrollments', 'moduleAverages', 'trend'));
    }

    /** CSV export of the course's students and their progress. */
    public function export(Course $course): StreamedResponse
    {
        $this->authorizeCourse($course);
        $filename = 'etudiants-' . $course->slug . '-' . now()->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($course) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel opens accents correctly
            fputcsv($out, [__('lms.name'), 'E-mail', __('lms.enrolled_on'), __('lms.progress') . ' (%)', __('lms.completed_on'),
                __('learn.starting_level') . ' (%)', __('learn.current_level') . ' (%)', __('learn.knowledge_gain'), __('learn.trend')], ';');
            $knowledge = app(KnowledgeService::class);
            $course->enrollments()->with('user')->orderBy('id')->chunk(500, function ($rows) use ($out, $course, $knowledge) {
                $profiles = $knowledge->profiles($course, $rows);
                foreach ($rows as $e) {
                    $p = $profiles[$e->id];
                    fputcsv($out, [
                        $e->user->name, $e->user->email,
                        optional($e->enrolled_at)->format('Y-m-d'),
                        $e->progress_percent,
                        optional($e->completed_at)->format('Y-m-d'),
                        $p['baseline'], $p['current'], $p['gain'], __('learn.trend_' . $p['trend']),
                    ], ';');
                }
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function stats(Course $course, ProgressService $progress): array
    {
        $enrollments = $course->enrollments();
        $total = (clone $enrollments)->count();
        $completed = (clone $enrollments)->where('progress_percent', '>=', 100)->count();
        $lessons = $progress->orderedLessons($course);

        // Completion per lesson shows where students drop off.
        $perLesson = LessonProgress::whereIn('enrollment_id', (clone $enrollments)->select('id'))
            ->selectRaw('lesson_id, COUNT(*) as done')->groupBy('lesson_id')->pluck('done', 'lesson_id');

        $quizIds = $lessons->pluck('quiz.id')->filter();
        $avgQuiz = $quizIds->isEmpty() ? null : QuizAttempt::whereIn('quiz_id', $quizIds)->where('mode', \App\Models\Quiz::MODE_STANDARD)->avg('score');

        return [
            'total'           => $total,
            'completed'       => $completed,
            'completion_rate' => $total ? round($completed / $total * 100, 1) : 0,
            'avg_progress'    => round((float) (clone $enrollments)->avg('progress_percent'), 1),
            'avg_quiz'        => $avgQuiz !== null ? round((float) $avgQuiz, 1) : null,
            'rating'          => $course->avgRating(),
            'funnel'          => $lessons->map(fn ($lesson) => [
                'title'   => $lesson->title(),
                'percent' => $total ? round(($perLesson[$lesson->id] ?? 0) / $total * 100) : 0,
            ]),
        ];
    }
}
