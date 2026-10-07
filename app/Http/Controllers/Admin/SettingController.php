<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Support\Mailing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::pluck('value', 'key')->except(Mailing::SECRET_SETTINGS)->toArray();
        return view('admin.settings.index', ['settings' => $settings, 'mail' => Mailing::status()]);
    }

    /** E-mail service: Brevo API key (kept encrypted, never shown again) and sender. */
    public function updateMail(Request $request)
    {
        $data = $request->validate([
            'brevo_api_key'     => ['nullable', 'string', 'max:255'],
            'remove_brevo_key'  => ['nullable', 'boolean'],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'mail_from_name'    => ['nullable', 'string', 'max:100'],
        ]);
        $key = trim((string) ($data['brevo_api_key'] ?? ''));
        $message = __('learn.mail_saved');

        if ($key !== '') {
            // Brevo gives two kinds of keys; only the API key (xkeysib-…) works here, not the SMTP one.
            if (! str_starts_with($key, 'xkeysib-')) {
                return back()->withInput($request->except('brevo_api_key'))->withErrors([
                    'brevo_api_key' => __(str_starts_with($key, 'xsmtpsib-') ? 'learn.mail_key_is_smtp' : 'learn.mail_key_format'),
                ]);
            }
            $check = Mailing::checkKey($key);
            if ($check['ok'] === false) {
                return back()->withInput($request->except('brevo_api_key'))->withErrors([
                    'brevo_api_key' => __('learn.mail_key_rejected', ['error' => $check['error']]),
                ]);
            }
            Mailing::saveKey($key);
            $message = $check['ok'] ? __('learn.mail_key_valid', ['account' => $check['account']]) : __('learn.mail_key_unchecked');
        } elseif ($request->boolean('remove_brevo_key')) {
            Mailing::saveKey(null);
        }

        Setting::set('mail_from_address', $data['mail_from_address'] ?? '');
        Setting::set('mail_from_name', $data['mail_from_name'] ?? '');
        ActivityLog::record('mail_settings_update', 'Admin updated the e-mail settings.');

        return back()->with('success', $message);
    }

    /** Puts the notifications that could not be sent (wrong sender, key refused…) back in the queue. */
    public function retryMail()
    {
        Artisan::call('queue:retry', ['id' => ['all']]);

        return back()->with('success', __('learn.mail_queue_retried'));
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
