<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AnnouncementTranslation;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::with('creator')->latest()->paginate(20);
        return view('admin.announcements.index', compact('announcements'));
    }

    public function create()
    {
        return view('admin.announcements.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'body'        => ['required', 'string'],
            'audience'    => ['required', 'in:all,students,instructors'],
            'translations'=> ['nullable', 'array'],
        ]);

        $announcement = Announcement::create([
            'title'      => $data['title'],
            'body'       => $data['body'],
            'audience'   => $data['audience'],
            'created_by' => auth()->id(),
            'is_active'  => true,
        ]);

        AnnouncementTranslation::create(['announcement_id' => $announcement->id, 'locale' => 'en', 'title' => $data['title'], 'body' => $data['body']]);

        if (! empty($data['translations'])) {
            foreach ($data['translations'] as $locale => $t) {
                if (! empty($t['title']) && $locale !== 'en') {
                    AnnouncementTranslation::create([
                        'announcement_id' => $announcement->id,
                        'locale' => $locale,
                        'title'  => $t['title'],
                        'body'   => $t['body'] ?? '',
                    ]);
                }
            }
        }

        return redirect()->route('admin.announcements.index')->with('success', 'Announcement created.');
    }

    public function edit(Announcement $announcement)
    {
        $announcement->load('translations');
        return view('admin.announcements.edit', compact('announcement'));
    }

    public function update(Request $request, Announcement $announcement)
    {
        $data = $request->validate([
            'title'    => ['required', 'string', 'max:255'],
            'body'     => ['required', 'string'],
            'audience' => ['required', 'in:all,students,instructors'],
            'is_active'=> ['boolean'],
        ]);

        $announcement->update([...$data, 'is_active' => $request->boolean('is_active')]);
        return redirect()->route('admin.announcements.index')->with('success', 'Announcement updated.');
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();
        return redirect()->route('admin.announcements.index')->with('success', 'Announcement deleted.');
    }
}
