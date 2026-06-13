<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategoryTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::with(['translations', 'children.translations'])
            ->roots()->orderBy('order')->get();
        return view('admin.categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'icon'      => ['nullable', 'string', 'max:100'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'names'     => ['nullable', 'array'],
        ]);

        $category = Category::create([
            'name'      => $data['name'],
            'slug'      => Str::slug($data['name']) . '-' . Str::random(4),
            'icon'      => $data['icon'] ?? null,
            'parent_id' => $data['parent_id'] ?? null,
            'order'     => Category::max('order') + 1,
        ]);

        CategoryTranslation::create(['category_id' => $category->id, 'locale' => 'en', 'name' => $data['name']]);

        if (! empty($data['names'])) {
            foreach ($data['names'] as $locale => $name) {
                if (! empty($name) && $locale !== 'en') {
                    CategoryTranslation::create(['category_id' => $category->id, 'locale' => $locale, 'name' => $name]);
                }
            }
        }

        Cache::forget('home_categories');
        return back()->with('success', 'Category created.');
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'icon'      => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
            'names'     => ['nullable', 'array'],
        ]);

        $category->update([
            'name'      => $data['name'],
            'icon'      => $data['icon'] ?? $category->icon,
            'is_active' => $request->boolean('is_active'),
        ]);

        if (! empty($data['names'])) {
            foreach ($data['names'] as $locale => $name) {
                if (! empty($name)) {
                    CategoryTranslation::updateOrCreate(
                        ['category_id' => $category->id, 'locale' => $locale],
                        ['name' => $name]
                    );
                }
            }
        }

        Cache::forget('home_categories');
        return back()->with('success', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        $category->delete();
        Cache::forget('home_categories');
        return back()->with('success', 'Category deleted.');
    }

    public function reorder(Request $request)
    {
        $request->validate(['order' => ['required', 'array']]);
        foreach ($request->order as $index => $id) {
            Category::where('id', $id)->update(['order' => $index]);
        }
        Cache::forget('home_categories');
        return response()->json(['message' => 'Reordered']);
    }
}
