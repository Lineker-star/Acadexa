<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Course;

class CategoryController extends Controller
{
    public function show(Category $category)
    {
        $courses = Course::published()
            ->where('category_id', $category->id)
            ->with(['translations', 'instructor', 'reviews'])
            ->paginate(12);

        $category->load(['translations', 'children.translations', 'parent.translations']);

        return view('categories.show', compact('category', 'courses'));
    }
}
