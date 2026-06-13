<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->get('q', '');

        $courses = Course::published()
            ->with(['translations', 'instructor', 'category.translations', 'reviews'])
            ->when($query, function ($q) use ($query) {
                $q->whereHas('translations', fn($t) =>
                    $t->where('title', 'like', "%{$query}%")
                      ->orWhere('description', 'like', "%{$query}%")
                );
            })
            ->paginate(12)
            ->withQueryString();

        return view('search', compact('courses', 'query'));
    }

    public function suggestions(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $results = Course::published()
            ->whereHas('translations', fn($t) =>
                $t->where('title', 'like', "%{$query}%")
            )
            ->with('translations')
            ->take(6)
            ->get()
            ->map(fn($course) => [
                'title' => $course->title(),
                'slug'  => $course->slug,
                'url'   => route('courses.show', $course),
            ]);

        return response()->json($results);
    }
}
