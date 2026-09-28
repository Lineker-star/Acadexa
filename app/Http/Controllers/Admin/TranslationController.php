<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\CmsPage;
use App\Models\CmsPageTranslation;
use App\Support\TranslationCoverage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Translation dashboard: interface coverage per language, and an editor for the
 * names of categories and the titles of CMS pages in every language.
 */
class TranslationController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->get('type') === 'cms-pages' ? 'cms-pages' : 'categories';
        $locales = config('app.supported_locales');
        $coverage = TranslationCoverage::report();

        $items = $type === 'cms-pages'
            ? CmsPage::with('translations')->orderBy('slug')->get()
            : Category::with('translations')->orderBy('parent_id')->orderBy('order')->get();

        return view('admin.translations.index', compact('type', 'locales', 'coverage', 'items'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'type'             => ['required', 'in:categories,cms-pages'],
            'translations'     => ['required', 'array'],
            'translations.*'   => ['array'],
            'translations.*.*' => ['nullable', 'string', 'max:255'],
        ]);
        $locales = config('app.supported_locales');

        foreach ($data['translations'] as $id => $values) {
            foreach ($values as $locale => $text) {
                if (! in_array($locale, $locales, true) || blank($text)) {
                    continue;
                }
                if ($data['type'] === 'categories') {
                    CategoryTranslation::updateOrCreate(['category_id' => (int) $id, 'locale' => $locale], ['name' => $text]);
                } else {
                    CmsPageTranslation::updateOrCreate(['cms_page_id' => (int) $id, 'locale' => $locale], ['title' => $text]);
                }
            }
        }

        Cache::forget('home_categories');
        ActivityLog::record('translations_update', "Updated {$data['type']} translations");

        return back()->with('success', __('Translations updated.'));
    }
}
