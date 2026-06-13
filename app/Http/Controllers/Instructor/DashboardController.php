<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $courses = $user->courses()
            ->with(['translations', 'enrollments', 'reviews'])
            ->withCount(['enrollments', 'reviews'])
            ->latest()
            ->get();

        $totalStudents    = $user->courses()->withCount('enrollments')->get()->sum('enrollments_count');
        $totalCourses     = $courses->count();
        $publishedCourses = $courses->where('status', 'published')->count();
        $avgRating        = $user->courses()->with('reviews')->get()
            ->flatMap->reviews->avg('rating');

        $courseIds    = $courses->pluck('id');
        $recentReviews = \App\Models\Review::with(['user', 'course.translations'])
            ->whereIn('course_id', $courseIds)->latest()->take(5)->get();

        $announcements = \App\Models\Announcement::with('translations')
            ->where(fn($q) => $q->where('audience', 'all')->orWhere('audience', 'instructors'))
            ->latest()->take(5)->get();

        return view('instructor.dashboard', compact(
            'courses', 'totalStudents', 'totalCourses', 'publishedCourses', 'avgRating',
            'recentReviews', 'announcements'
        ));
    }

    public function earnings(Request $request)
    {
        $courses = $request->user()->courses()
            ->with(['enrollments', 'reviews'])
            ->withCount('enrollments')
            ->get();

        return view('instructor.earnings', compact('courses'));
    }
}
