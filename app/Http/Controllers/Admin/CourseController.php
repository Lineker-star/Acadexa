<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Course;
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
            'modules.lessons.translations', 'reviews.user', 'enrollments.user']);
        return view('admin.courses.show', compact('course'));
    }

    public function approve(Course $course)
    {
        $course->update(['status' => 'published']);
        ActivityLog::record('course_approve', "Approved course #{$course->id}");
        return back()->with('success', 'Course approved and published.');
    }

    public function reject(Request $request, Course $course)
    {
        $request->validate(['feedback' => ['required', 'string', 'max:1000']]);
        $course->update(['status' => 'rejected', 'admin_feedback' => $request->feedback]);
        ActivityLog::record('course_reject', "Rejected course #{$course->id}");
        return back()->with('success', 'Course rejected with feedback.');
    }

    public function feature(Course $course)
    {
        $course->update(['featured' => ! $course->featured]);
        ActivityLog::record('course_feature', "Toggled featured on course #{$course->id}");
        return back()->with('success', 'Course featured status updated.');
    }

    public function unpublish(Course $course)
    {
        $course->update(['status' => 'unpublished']);
        ActivityLog::record('course_unpublish', "Unpublished course #{$course->id}");
        return back()->with('success', 'Course unpublished.');
    }

    public function destroy(Course $course)
    {
        ActivityLog::record('course_delete', "Deleted course #{$course->id}: {$course->slug}");
        $course->delete();
        return redirect()->route('admin.courses.index')->with('success', 'Course deleted.');
    }
}
