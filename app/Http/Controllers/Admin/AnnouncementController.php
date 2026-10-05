<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AnnouncementTranslation;
use App\Models\User;
use App\Notifications\AnnouncementPublished;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::with(['creator', 'translations'])->latest()->paginate(20);
        return view('admin.announcements.index', compact('announcements'));
    }

    public function create()
    {
        return view('admin.announcements.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        $announcement = Announcement::create([
            'title'      => $data['translations']['en']['title'],
            'body'       => $data['translations']['en']['body'],
            'audience'   => $data['audience'],
            'created_by' => auth()->id(),
            'is_active'  => true,
        ]);
        $this->saveTranslations($announcement, $data['translations']);

        if ($request->boolean('notify', true)) {
            $count = $this->notifyAudience($announcement);
            return redirect()->route('admin.announcements.index')
                ->with('success', trans_choice('learn.announcement_sent', $count, ['count' => $count]));
        }

        return redirect()->route('admin.announcements.index')->with('success', __('Announcement created.'));
    }

    public function edit(Announcement $announcement)
    {
        $announcement->load('translations');
        return view('admin.announcements.edit', compact('announcement'));
    }

    public function update(Request $request, Announcement $announcement)
    {
        $data = $request->validate($this->rules() + ['is_active' => ['nullable', 'boolean']]);

        $announcement->update([
            'title'     => $data['translations']['en']['title'],
            'body'      => $data['translations']['en']['body'],
            'audience'  => $data['audience'],
            'is_active' => $request->boolean('is_active'),
        ]);
        $this->saveTranslations($announcement, $data['translations']);

        return redirect()->route('admin.announcements.index')->with('success', __('Announcement updated.'));
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();
        return redirect()->route('admin.announcements.index')->with('success', __('Announcement deleted.'));
    }

    /**
     * Bell notification + e-mail (according to each user's preferences) to the audience:
     * everyone, students only or instructors only. Sent by the queue, in each reader's language.
     */
    private function notifyAudience(Announcement $announcement): int
    {
        $announcement->load('translations');
        $count = 0;
        User::where('is_active', true)->whereNull('banned_at')
            ->when($announcement->audience === 'students', fn ($q) => $q->where('role', 'student'))
            ->when($announcement->audience === 'instructors', fn ($q) => $q->where('role', 'instructor'))
            ->where('id', '!=', auth()->id())
            ->chunkById(500, function ($users) use ($announcement, &$count) {
                Notification::send($users, new AnnouncementPublished($announcement));
                $count += $users->count();
            });
        return $count;
    }

    private function rules(): array
    {
        return [
            'audience'               => ['required', 'in:all,students,instructors'],
            'notify'                 => ['nullable', 'boolean'],
            'translations'           => ['required', 'array'],
            'translations.en.title'  => ['required', 'string', 'max:255'],
            'translations.en.body'   => ['required', 'string', 'max:10000'],
            'translations.*.title'   => ['nullable', 'string', 'max:255'],
            'translations.*.body'    => ['nullable', 'string', 'max:10000'],
        ];
    }

    private function saveTranslations(Announcement $announcement, array $translations): void
    {
        foreach (config('app.supported_locales') as $locale) {
            $title = trim((string) ($translations[$locale]['title'] ?? ''));
            if ($title === '') {
                AnnouncementTranslation::where('announcement_id', $announcement->id)->where('locale', $locale)->delete();
                continue;
            }
            AnnouncementTranslation::updateOrCreate(
                ['announcement_id' => $announcement->id, 'locale' => $locale],
                ['title' => $title, 'body' => (string) ($translations[$locale]['body'] ?? '')]
            );
        }
    }
}
