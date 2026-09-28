<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Instructor\Concerns\AuthorizesCourse;
use App\Models\AssignmentSubmission;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Notifications\AssignmentGraded;
use App\Services\ProgressService;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    use AuthorizesCourse;

    public function index(Request $request)
    {
        $user = $request->user();
        $courseIds = $user->isAdmin() && $request->boolean('all')
            ? null
            : $user->courses()->pluck('id');

        $query = AssignmentSubmission::with(['user', 'lesson.translations', 'lesson.module.course.translations'])
            ->when($courseIds !== null, fn ($q) => $q->whereHas('lesson.module', fn ($m) => $m->whereIn('course_id', $courseIds)))
            ->when($request->filled('course'), fn ($q) => $q->whereHas('lesson.module', fn ($m) => $m->where('course_id', $request->integer('course'))));

        $status = $request->get('status', 'submitted');
        if (in_array($status, ['submitted', 'graded'], true)) {
            $query->where('status', $status);
        }

        $submissions = $query->orderByRaw("CASE WHEN status = 'submitted' THEN 0 ELSE 1 END")
            ->latest('submitted_at')->paginate(20)->withQueryString();
        $courses = $user->courses()->with('translations')->get();

        return view('instructor.submissions.index', compact('submissions', 'courses', 'status'));
    }

    public function show(AssignmentSubmission $submission)
    {
        $submission->load(['user', 'grader', 'lesson.translations', 'lesson.module.course.translations']);
        $this->authorizeCourse($submission->lesson->module->course);

        return view('instructor.submissions.show', compact('submission'));
    }

    public function grade(Request $request, AssignmentSubmission $submission, ProgressService $progress)
    {
        $lesson = $submission->lesson;
        $course = $lesson->module->course;
        $this->authorizeCourse($course);

        $data = $request->validate([
            'score'    => ['required', 'numeric', 'min:0', 'max:' . $lesson->assignment_max_score],
            'feedback' => ['nullable', 'string', 'max:5000'],
        ]);

        $submission->update([
            'status'    => 'graded',
            'score'     => $data['score'],
            'feedback'  => $data['feedback'] ?? null,
            'graded_by' => $request->user()->id,
            'graded_at' => now(),
        ]);

        $enrollment = Enrollment::where('user_id', $submission->user_id)->where('course_id', $course->id)->first();
        if ($enrollment) {
            if ($data['score'] >= $lesson->assignment_pass_score) {
                $progress->complete($enrollment, $lesson);
            } else {
                // A re-grade below the pass mark withdraws a previous completion.
                LessonProgress::where('enrollment_id', $enrollment->id)->where('lesson_id', $lesson->id)->delete();
                $progress->refresh($enrollment);
            }
        }

        $submission->user->notify(new AssignmentGraded($submission->fresh(['lesson.module', 'user'])));

        return redirect()->route('instructor.submissions.index')->with('success', __('lms.submission_graded'));
    }
}
