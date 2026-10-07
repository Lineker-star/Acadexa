<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::pluck('value', 'key')->toArray();
        return view('admin.settings.index', compact('settings'));
    }

    /** Sends a test message right now (not queued) and reports the provider's answer. */
    public function testMail(Request $request)
    {
        $data = $request->validate(['to' => ['required', 'email']]);

        try {
            \Illuminate\Support\Facades\Mail::raw(__('learn.mail_test_body', ['site' => Setting::get('site_name', 'ACADEXXA')]), function ($message) use ($data) {
                $message->to($data['to'])->subject(__('learn.mail_test_subject'));
            });
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['to' => __('learn.mail_test_failed', ['error' => $e->getMessage()])])->withInput();
        }

        ActivityLog::record('mail_test', 'Test e-mail sent to ' . $data['to']);
        return back()->with('success', __('learn.mail_test_sent', ['email' => $data['to']]));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'site_name'         => ['nullable', 'string', 'max:255'],
            'contact_email'     => ['nullable', 'email'],
            'contact_phone'     => ['nullable', 'string', 'max:50'],
            'contact_address'   => ['nullable', 'string', 'max:500'],
            'trial_days'        => ['nullable', 'integer', 'min:1', 'max:365'],
            'facebook_url'      => ['nullable', 'url'],
            'twitter_url'       => ['nullable', 'url'],
            'youtube_url'       => ['nullable', 'url'],
            'linkedin_url'      => ['nullable', 'url'],
            'instagram_url'     => ['nullable', 'url'],
            'cert_sig_name'     => ['nullable', 'string', 'max:255'],
            'cert_sig_title'    => ['nullable', 'string', 'max:255'],
            'maintenance_mode'           => ['nullable', 'in:0,1'],
            'allow_registration'         => ['nullable', 'in:0,1'],
            'require_email_verification' => ['nullable', 'in:0,1'],
            'registration_code'          => ['nullable', 'in:0,1'],
            'hero_image'        => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'remove_hero_image' => ['nullable', 'boolean'],
        ]);
        unset($data['hero_image'], $data['remove_hero_image']);

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        // Home page photo: stored on the public disk, the default photo is used otherwise.
        $current = Setting::get('hero_image');
        if ($request->hasFile('hero_image') || $request->boolean('remove_hero_image')) {
            if ($current) {
                Storage::disk('public')->delete($current);
            }
            Setting::set('hero_image', $request->hasFile('hero_image')
                ? $request->file('hero_image')->store('branding', 'public')
                : '');
        }

        Cache::forget('site_settings');
        ActivityLog::record('settings_update', 'Admin updated site settings.');

        return back()->with('success', __('Settings saved.'));
    }
}
