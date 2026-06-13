<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\InstructorApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users'        => User::count(),
            'total_students'     => User::where('role', 'student')->count(),
            'total_instructors'  => User::where('instructor_status', 'confirmed')->count(),
            'total_courses'      => Course::count(),
            'published_courses'  => Course::where('status', 'published')->count(),
            'pending_courses'    => Course::where('status', 'pending')->count(),
            'total_enrollments'  => Enrollment::count(),
            'total_certificates' => Certificate::count(),
            'pending_applications' => InstructorApplication::where('status', 'pending')->count(),
        ];

        // Growth data for chart (last 7 months)
        $userGrowth = User::select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('YEAR(created_at) as year'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('year', 'month')
            ->orderBy('year')->orderBy('month')
            ->get();

        $enrollmentGrowth = Enrollment::select(
                DB::raw('MONTH(enrolled_at) as month'),
                DB::raw('YEAR(enrolled_at) as year'),
                DB::raw('COUNT(*) as count')
            )
            ->where('enrolled_at', '>=', now()->subMonths(6))
            ->groupBy('year', 'month')
            ->orderBy('year')->orderBy('month')
            ->get();

        $recentUsers = User::latest()->take(10)->get();
        $pendingCourses = Course::where('status', 'pending')
            ->with(['instructor', 'translations', 'category.translations'])
            ->latest()->take(5)->get();

        return view('admin.dashboard', compact(
            'stats', 'userGrowth', 'enrollmentGrowth', 'recentUsers', 'pendingCourses'
        ));
    }
}
