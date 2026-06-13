<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::pluck('value', 'key')->toArray();
        return view('admin.settings.index', compact('settings'));
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
            'maintenance_mode'  => ['nullable'],
        ]);

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        Cache::forget('site_settings');
        ActivityLog::record('settings_update', 'Admin updated site settings.');

        return back()->with('success', 'Settings saved.');
    }
}
