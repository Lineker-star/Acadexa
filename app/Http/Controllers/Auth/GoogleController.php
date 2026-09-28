<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use App\Services\LoginFlow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

/**
 * "Continue with Google" for registration and login.
 *  - existing account (same Google account, or same e-mail) → linked and signed in,
 *  - unknown e-mail → a student account is created (if registrations are open),
 *  - two-factor authentication still applies after Google,
 *  - back-office accounts must use the admin login (password + 2FA).
 */
class GoogleController extends Controller
{
    public function __construct(private LoginFlow $flow) {}

    public static function enabled(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }

    public function redirect()
    {
        if (! self::enabled()) {
            return redirect()->route('login')->withErrors(['email' => __('learn.google_unavailable')]);
        }
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request)
    {
        if (! self::enabled()) {
            return redirect()->route('login');
        }

        try {
            $google = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('login')->withErrors(['email' => __('learn.google_failed')]);
        }

        $email = Str::lower((string) $google->getEmail());
        $verified = (bool) ($google->user['email_verified'] ?? $google->user['verified_email'] ?? false);
        if (! $email || ! $verified) {
            return redirect()->route('login')->withErrors(['email' => __('learn.google_email_unverified')]);
        }

        $user = User::where('google_id', $google->getId())->first()
            ?? User::where('email', $email)->first();

        if ($user) {
            if ($user->isAdmin()) {
                return redirect()->route('login')->withErrors(['email' => __('learn.google_admin_refused')]);
            }
            $user->forceFill([
                'google_id' => $user->google_id ?: $google->getId(),
                'avatar'    => $user->avatar ?: $google->getAvatar(),
            ])->save();

            return $this->flow->start($request, $user, true, emailTrusted: true);
        }

        if (Setting::get('allow_registration', '1') !== '1') {
            return redirect()->route('login')->withErrors(['email' => __('learn.google_registration_closed')]);
        }

        $user = User::create([
            'name'               => $google->getName() ?: Str::before($email, '@'),
            'email'              => $email,
            // Random password: the person signs in with Google and can choose a password later in the profile.
            'password'           => Hash::make(Str::random(40)),
            'has_password'       => false,
            'google_id'          => $google->getId(),
            'avatar'             => $google->getAvatar(),
            'role'               => 'student',
            'trial_started_at'   => now(),
            'preferred_language' => app()->getLocale(),
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();
        $user->notify(new WelcomeNotification());

        return $this->flow->start($request, $user, true, emailTrusted: true);
    }
}
