<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Course;
use App\Notifications\CourseReviewed;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $query = Course::with(['translations', 'instructor', 'category.translations'])
            ->withCount(['enrollments', 'reviews']);

        if ($request->filled('status'))   $query->where('status', $request->status);
        if ($request->filled('search')) {
            $s = $request->search;
            $query->whereHas('translations', fn($q) => $q->where('title', 'like', "%{$s}%"));
        }

        $courses = $query->latest()->paginate(20)->withQueryString();
        return view('admin.courses.index', compact('courses'));
    }

    public function show(Course $course)
    {
        $course->load(['translations', 'instructor', 'category.translations',
            'modules.lessons.translations', 'modules.lessons.quiz.questions', 'modules.exam.questions',
            'finalExam.questions', 'reviews.user', 'enrollments.user']);
        // What the instructor would still have to do before submitting: shown as a warning, the admin decides.
        $missing = collect(app(\App\Http\Controllers\Instructor\CourseController::class)->publishChecklist($course))
            ->reject(fn ($item) => $item['ok'])->pluck('label');

        return view('admin.courses.show', compact('course', 'missing'));
    }

    /** Approves a submitted course — or publishes directly a draft, rejected or unpublished one. */
    public function approve(Course $course)
    {
        if ($course->status === 'published') {
            return back();
        }
        $firstPublication = $course->published_at === null;
        $course->update(['status' => 'published', 'admin_feedback' => null, 'published_at' => $course->published_at ?? now()]);

        // News: tell the students a new course is available (once, at the first publication).
        if ($firstPublication) {
            $course->load(['translations', 'instructor']);
            \App\Models\User::where('role', 'student')->where('is_active', true)->whereNull('banned_at')
                ->chunkById(500, fn ($students) => \Illuminate\Support\Facades\Notification::send($students, new \App\Notifications\NewCoursePublished($course)));
        }
        ActivityLog::record('course_approve', "Approved course #{$course->id}");
        $course->instructor->notify(new CourseReviewed($course, true));
        return back()->with('success', __('Course approved and published.'));
    }

    public function reject(Request $request, Course $course)
    {
        $request->validate(['feedback' => ['required', 'string', 'max:1000']]);
        $course->update(['status' => 'rejected', 'admin_feedback' => $request->feedback]);
        ActivityLog::record('course_reject', "Rejected course #{$course->id}");
        $course->instructor->notify(new CourseReviewed($course, false, $request->feedback));
        return back()->with('success', __('Course rejected with feedback.'));
    }

    public function feature(Course $course)
    {
        $course->update(['featured' => ! $course->featured]);
        ActivityLog::record('course_feature', "Toggled featured on course #{$course->id}");
        return back()->with('success', __('Course featured status updated.'));
    }

    public function unpublish(Request $request, Course $course)
    {
        $request->validate(['feedback' => ['nullable', 'string', 'max:1000']]);
        $course->update(['status' => 'unpublished', 'admin_feedback' => $request->feedback ?: $course->admin_feedback]);
        $course->instructor->notify(new CourseReviewed($course, false, $request->feedback));
        ActivityLog::record('course_unpublish', "Unpublished course #{$course->id}");
        return back()->with('success', __('Course unpublished.'));
    }

    public function destroy(Course $course)
    {
        ActivityLog::record('course_delete', "Deleted course #{$course->id}: {$course->slug}");
        $course->delete();
        return redirect()->route('admin.courses.index')->with('success', __('Course deleted.'));
    }
}
