<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategoryTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::with(['translations', 'children.translations'])
            ->withCount('courses')
            ->roots()->orderBy('order')->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $category = Category::create([
            'name'      => $data['names']['en'],
            'slug'      => Str::slug($data['names']['en']) . '-' . Str::lower(Str::random(4)),
            'icon'      => $data['icon'] ?? 'bi-folder',
            'parent_id' => $data['parent_id'] ?? null,
            'order'     => (int) Category::max('order') + 1,
        ]);
        $this->saveNames($category, $data['names']);

        Cache::forget('home_categories');
        return back()->with('success', __('Category created.'));
    }

    public function update(Request $request, Category $category)
    {
        $data = $this->validated($request);

        $category->update([
            'name'      => $data['names']['en'],
            'icon'      => $data['icon'] ?? $category->icon,
            'is_active' => $request->boolean('is_active'),
        ]);
        $this->saveNames($category, $data['names']);

        Cache::forget('home_categories');
        return back()->with('success', __('Category updated.'));
    }

    public function destroy(Category $category)
    {
        $category->delete();
        Cache::forget('home_categories');
        return back()->with('success', __('Category deleted.'));
    }

    public function reorder(Request $request)
    {
        $request->validate(['order' => ['required', 'array']]);
        foreach ($request->order as $index => $id) {
            Category::where('id', $id)->update(['order' => $index]);
        }
        Cache::forget('home_categories');
        return response()->json(['message' => __('lms.order_saved')]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'names'     => ['required', 'array'],
            'names.en'  => ['required', 'string', 'max:255'],
            'names.*'   => ['nullable', 'string', 'max:255'],
            'icon'      => ['nullable', Rule::in(config('lms.category_icons'))],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    /** One translation per language; an emptied field removes that translation. */
    private function saveNames(Category $category, array $names): void
    {
        foreach (config('app.supported_locales') as $locale) {
            $name = trim((string) ($names[$locale] ?? ''));
            if ($name === '') {
                CategoryTranslation::where('category_id', $category->id)->where('locale', $locale)->delete();
                continue;
            }
            CategoryTranslation::updateOrCreate(['category_id' => $category->id, 'locale' => $locale], ['name' => $name]);
        }
    }
}
