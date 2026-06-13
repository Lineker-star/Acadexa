<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $query = Course::published()
            ->with(['translations', 'instructor', 'category.translations', 'reviews']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('translations', fn($q) => $q->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%"));
        }

        if ($request->filled('category')) {
            $query->whereHas('category', fn($q) => $q->where('slug', $request->category));
        }

        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        if ($request->filled('price')) {
            if ($request->price === 'free') {
                $query->where('price', 0);
            } elseif ($request->price === 'paid') {
                $query->where('price', '>', 0);
            }
        }

        $sort = $request->get('sort', 'newest');
        match ($sort) {
            'popular'  => $query->withCount('enrollments')->orderByDesc('enrollments_count'),
            'rating'   => $query->withAvg('reviews', 'rating')->orderByDesc('reviews_avg_rating'),
            'price_asc'=> $query->orderBy('price'),
            default    => $query->latest(),
        };

        $courses    = $query->paginate(12)->withQueryString();
        $categories = Category::active()->roots()->with('translations')->orderBy('order')->get();

        return view('courses.index', compact('courses', 'categories'));
    }

    public function show(Course $course)
    {
        abort_if($course->status !== 'published', 404);

        $course->load([
            'translations',
            'instructor',
            'category.translations',
            'modules.lessons.translations',
            'reviews.user',
        ]);

        $isEnrolled = false;
        $enrollment = null;
        if (auth()->check()) {
            $enrollment = auth()->user()->enrollments()->where('course_id', $course->id)->first();
            $isEnrolled = (bool) $enrollment;
        }

        $relatedCourses = Course::published()
            ->where('category_id', $course->category_id)
            ->where('id', '!=', $course->id)
            ->with(['translations', 'instructor'])
            ->take(4)
            ->get();

        return view('courses.show', compact('course', 'isEnrolled', 'enrollment', 'relatedCourses'));
    }

    public function instructorProfile(User $user)
    {
        abort_if($user->instructor_status !== 'confirmed', 404);

        $courses = Course::published()
            ->where('instructor_id', $user->id)
            ->with(['translations', 'category.translations', 'reviews'])
            ->paginate(9);

        return view('courses.instructor-profile', compact('user', 'courses'));
    }
}
