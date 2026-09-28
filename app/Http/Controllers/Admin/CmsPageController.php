<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Models\CmsPageTranslation;
use App\Support\HtmlSanitizer;
use Illuminate\Http\Request;

class CmsPageController extends Controller
{
    public function index()
    {
        $pages = CmsPage::with('translations')->get();
        return view('admin.cms-pages.index', compact('pages'));
    }

    public function create()
    {
        return view('admin.cms-pages.create');
    }

    public function store(Request $request)
    {
        $request->validate(['slug' => ['required', 'string', 'regex:/^[a-z0-9-]+$/', 'max:100', 'unique:cms_pages,slug']] + $this->rules());

        $page = CmsPage::create(['slug' => $request->slug]);
        $this->storeHero($request, $page);
        $this->saveTranslations($page, $request->input('translations', []));

        return redirect()->route('admin.cms-pages.edit', $page)->with('success', __('Page created.'));
    }

    public function edit(CmsPage $cmsPage)
    {
        $cmsPage->load('translations');
        return view('admin.cms-pages.edit', compact('cmsPage'));
    }

    public function update(Request $request, CmsPage $cmsPage)
    {
        $request->validate($this->rules());

        $this->storeHero($request, $cmsPage);
        $this->saveTranslations($cmsPage, $request->input('translations', []));

        return back()->with('success', __('Page updated.'));
    }

    public function destroy(CmsPage $cmsPage)
    {
        if (in_array($cmsPage->slug, ['about', 'privacy', 'terms'], true)) {
            return back()->with('error', __('System pages cannot be deleted.'));
        }
        $cmsPage->delete();
        return redirect()->route('admin.cms-pages.index')->with('success', __('Page deleted.'));
    }

    private function rules(): array
    {
        return [
            'translations'              => ['required', 'array'],
            'translations.en.title'     => ['required', 'string', 'max:255'],
            'translations.en.content'   => ['required', 'string'],
            'translations.*.title'      => ['nullable', 'string', 'max:255'],
            'translations.*.content'    => ['nullable', 'string', 'max:500000'],
            'hero_image'                => ['nullable', 'image', 'max:4096'],
        ];
    }

    private function storeHero(Request $request, CmsPage $page): void
    {
        if ($request->hasFile('hero_image')) {
            $page->update(['hero_image' => $request->file('hero_image')->store('cms', 'public')]);
        }
    }

    /** One translation per language; a language left without title is removed (falls back to English). */
    private function saveTranslations(CmsPage $page, array $translations): void
    {
        foreach (config('app.supported_locales') as $locale) {
            $title = trim((string) ($translations[$locale]['title'] ?? ''));
            if ($title === '') {
                if ($locale !== 'en') {
                    CmsPageTranslation::where('cms_page_id', $page->id)->where('locale', $locale)->delete();
                }
                continue;
            }
            CmsPageTranslation::updateOrCreate(
                ['cms_page_id' => $page->id, 'locale' => $locale],
                ['title' => $title, 'content' => HtmlSanitizer::clean($translations[$locale]['content'] ?? '')]
            );
        }
    }
}
