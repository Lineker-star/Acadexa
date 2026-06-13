<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Models\CmsPageTranslation;
use Illuminate\Http\Request;

class CmsPageController extends Controller
{
    public function index()
    {
        $pages = CmsPage::with('translations')->get();
        return view('admin.cms-pages.index', compact('pages'));
    }

    public function edit(CmsPage $cmsPage)
    {
        $cmsPage->load('translations');
        $locales = config('app.supported_locales');
        return view('admin.cms-pages.edit', compact('cmsPage', 'locales'));
    }

    public function create()
    {
        return view('admin.cms-pages.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'slug'    => ['required', 'string', 'regex:/^[a-z0-9-]+$/', 'unique:cms_pages,slug'],
            'title'   => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ]);

        $page = CmsPage::create([
            'slug'  => $request->slug,
            'title' => $request->title,
        ]);

        if ($request->hasFile('hero_image')) {
            $path = $request->file('hero_image')->store('cms', 'public');
            $page->update(['hero_image' => $path]);
        }

        $page->translations()->create([
            'locale'  => 'en',
            'title'   => $request->title,
            'content' => $request->content,
        ]);

        return redirect()->route('admin.cms-pages.edit', $page)->with('success', 'Page created.');
    }

    public function update(Request $request, CmsPage $cmsPage)
    {
        $request->validate([
            'title'   => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ]);

        $cmsPage->update(['title' => $request->title]);

        if ($request->hasFile('hero_image')) {
            $path = $request->file('hero_image')->store('cms', 'public');
            $cmsPage->update(['hero_image' => $path]);
        }

        CmsPageTranslation::updateOrCreate(
            ['cms_page_id' => $cmsPage->id, 'locale' => 'en'],
            ['title' => $request->title, 'content' => $request->content]
        );

        foreach ($request->input('titles', []) as $locale => $title) {
            if (empty($title)) continue;
            CmsPageTranslation::updateOrCreate(
                ['cms_page_id' => $cmsPage->id, 'locale' => $locale],
                ['title' => $title, 'content' => $request->input("contents.{$locale}", '')]
            );
        }

        return back()->with('success', 'Page updated.');
    }

    public function destroy(CmsPage $cmsPage)
    {
        if (in_array($cmsPage->slug, ['about', 'privacy', 'terms'])) {
            return back()->with('error', 'System pages cannot be deleted.');
        }
        $cmsPage->delete();
        return redirect()->route('admin.cms-pages.index')->with('success', 'Page deleted.');
    }
}
