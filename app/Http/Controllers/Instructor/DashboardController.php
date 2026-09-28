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

        $pendingSubmissions = \App\Models\AssignmentSubmission::where('status', 'submitted')
            ->whereHas('lesson.module', fn ($q) => $q->whereIn('course_id', $courseIds))->count();
        $unansweredQuestions = \App\Models\LessonComment::whereNull('parent_id')
            ->whereHas('lesson.module', fn ($q) => $q->whereIn('course_id', $courseIds))
            ->where('user_id', '!=', $user->id)
            ->whereDoesntHave('replies', fn ($q) => $q->where('user_id', $user->id))->count();

        // Knowledge progression of the students, per course (final evaluation).
        $knowledge = app(\App\Services\KnowledgeService::class);
        $knowledgeRows = collect();
        $regressions = collect();
        foreach ($courses->where('enrollments_count', '>', 0) as $course) {
            $summary = $knowledge->courseSummary($course);
            $knowledgeRows->push(['course' => $course, 'summary' => $summary]);
            foreach ($summary['profiles'] as $enrollmentId => $profile) {
                if ($profile['trend'] === \App\Services\KnowledgeService::TREND_REGRESSION) {
                    $regressions->push(['course' => $course, 'enrollment' => $summary['enrollments'][$enrollmentId], 'profile' => $profile]);
                }
            }
        }
        $regressions = $regressions->sortBy(fn ($r) => $r['profile']['delta'])->take(8)->values();

        return view('instructor.dashboard', compact(
            'courses', 'totalStudents', 'totalCourses', 'publishedCourses', 'avgRating',
            'recentReviews', 'announcements', 'pendingSubmissions', 'unansweredQuestions',
            'knowledgeRows', 'regressions'
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
