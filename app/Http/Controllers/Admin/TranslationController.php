<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\CmsPage;
use App\Models\CmsPageTranslation;
use Illuminate\Http\Request;

class TranslationController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->get('type', 'categories');
        $locales = config('app.supported_locales', ['en', 'fr', 'es', 'pt', 'zh', 'ar']);

        $data = match($type) {
            'categories' => Category::with('translations')->get(),
            'cms-pages'  => CmsPage::with('translations')->get(),
            default      => Category::with('translations')->get(),
        };

        return view('admin.translations.index', compact('data', 'type', 'locales'));
    }

    public function update(Request $request)
    {
        $type    = $request->input('type', 'categories');
        $entries = $request->input('translations', []);

        foreach ($entries as $id => $locales) {
            foreach ($locales as $locale => $name) {
                if (empty($name)) continue;
                if ($type === 'categories') {
                    CategoryTranslation::updateOrCreate(
                        ['category_id' => $id, 'locale' => $locale],
                        ['name' => $name]
                    );
                } elseif ($type === 'cms-pages') {
                    CmsPageTranslation::updateOrCreate(
                        ['cms_page_id' => $id, 'locale' => $locale],
                        ['title' => $name]
                    );
                }
            }
        }

        return back()->with('success', 'Translations updated.');
    }
}
