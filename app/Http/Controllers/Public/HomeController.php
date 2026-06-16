<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index()
    {
        $featuredCourses = Cache::remember('home_featured_courses', 600, fn() =>
            Course::published()->featured()
                ->with(['translations', 'instructor', 'category.translations', 'reviews'])
                ->latest()
                ->take(8)
                ->get()
        );

        $categories = Cache::remember('home_categories', 3600, fn() =>
            Category::active()->roots()
                ->with(['translations', 'children.translations'])
                ->orderBy('order')
                ->take(12)
                ->get()
        );

        $stats = Cache::remember('home_stats', 3600, fn() => [
            'courses'      => Course::published()->count(),
            'students'     => User::where('role', 'student')->count(),
            'instructors'  => User::where('instructor_status', 'confirmed')->count(),
            'certificates' => \App\Models\Certificate::count(),
        ]);

        $latestCourses = Course::published()
            ->with(['translations', 'instructor', 'category.translations'])
            ->latest()
            ->take(6)
            ->get();

        return view('home', compact('featuredCourses', 'categories', 'stats', 'latestCourses'));
    }

    public function sitemap()
    {
        $courses    = Course::published()->with('translations')->get();
        $categories = Category::active()->get();

        $pages = DB::table('cms_pages')->where('is_active', 1)->get();
        return response()->view('sitemap', compact('courses', 'categories', 'pages'))
            ->header('Content-Type', 'application/xml');
    }

    public function robots()
    {
        $content = "User-agent: *\nDisallow: /acadexa-control/\nDisallow: /dashboard\nDisallow: /my-courses\nAllow: /\nSitemap: " . url('/sitemap.xml');
        return response($content, 200)->header('Content-Type', 'text/plain');
    }
}
