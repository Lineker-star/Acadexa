<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $enrollments = $user->enrollments()
            ->with(['course.translations', 'course.instructor', 'lastLesson.translations'])
            ->latest()
            ->take(6)
            ->get();

        $completedCourses = $user->enrollments()->whereNotNull('completed_at')->count();
        $certificates     = $user->certificates()->with(['course.translations'])->latest()->take(3)->get();

        $announcements = Announcement::where('is_active', true)
            ->where(fn($q) => $q->where('audience', 'all')->orWhere('audience', 'students'))
            ->with('translations')
            ->latest()
            ->take(3)
            ->get();

        $unreadNotifications = $user->unreadNotifications()->take(5)->get();

        return view('student.dashboard', compact(
            'user', 'enrollments', 'completedCourses',
            'certificates', 'announcements', 'unreadNotifications'
        ));
    }
}
