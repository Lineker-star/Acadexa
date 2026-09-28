<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Two-factor authentication for back-office accounts.
 *  - setup/confirm/disable: from the admin's own security page
 *  - challenge/verify: second step of the admin login
 */
class TwoFactorController extends Controller
{
    public function __construct(private Totp $totp) {}

    public function show(Request $request)
    {
        $user = $request->user();
        $pending = null;

        if (! $user->hasTwoFactorEnabled()) {
            $secret = $request->session()->get('2fa_setup_secret') ?? $this->totp->generateSecret();
            $request->session()->put('2fa_setup_secret', $secret);
            $pending = ['secret' => $secret, 'uri' => $this->totp->uri($secret, $user->email)];
        }

        return view('admin.security', compact('user', 'pending'));
    }

    public function enable(Request $request)
    {
        $request->validate(['code' => ['required', 'string']]);
        $secret = $request->session()->get('2fa_setup_secret');

        if (! $secret || ! $this->totp->verify($secret, $request->code)) {
            return back()->withErrors(['code' => __('security.invalid_code')]);
        }

        $request->user()->forceFill(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => now()])->save();
        $request->session()->forget('2fa_setup_secret');
        ActivityLog::record('2fa_enabled', 'Two-factor authentication enabled.');

        return back()->with('success', __('security.enabled'));
    }

    public function disable(Request $request)
    {
        $request->validate(['password' => ['required', 'string'], 'code' => ['required', 'string']]);
        $user = $request->user();

        if (! Hash::check($request->password, $user->password) || ! $this->totp->verify($user->two_factor_secret, $request->code)) {
            return back()->withErrors(['code' => __('security.invalid_credentials')]);
        }

        $user->forceFill(['two_factor_secret' => null, 'two_factor_confirmed_at' => null])->save();
        ActivityLog::record('2fa_disabled', 'Two-factor authentication disabled.');

        return back()->with('success', __('security.disabled'));
    }

    /** Super admin: reset 2FA of an admin who lost their phone. */
    public function reset(Request $request, User $user)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $user->forceFill(['two_factor_secret' => null, 'two_factor_confirmed_at' => null])->save();
        ActivityLog::record('2fa_reset', "Reset 2FA for user #{$user->id}: {$user->email}");

        return back()->with('success', __('security.reset_done'));
    }

    public function challenge(Request $request)
    {
        abort_unless($request->session()->has('2fa_login_id'), 404);
        return view('admin.two-factor-challenge');
    }

    public function verify(Request $request)
    {
        $request->validate(['code' => ['required', 'string']]);
        $userId = $request->session()->get('2fa_login_id');
        abort_unless($userId, 419);

        $key = '2fa:' . $userId . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['code' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($key), 'minutes' => 1])]);
        }

        $user = User::find($userId);
        if (! $user || ! $user->hasTwoFactorEnabled() || ! $this->totp->verify($user->two_factor_secret, $request->code)) {
            RateLimiter::hit($key, 300);
            return back()->withErrors(['code' => __('security.invalid_code')]);
        }

        RateLimiter::clear($key);
        $remember = (bool) $request->session()->pull('2fa_remember');
        $request->session()->forget('2fa_login_id');
        Auth::login($user, $remember);
        $request->session()->regenerate();
        ActivityLog::record('admin_login', 'Admin logged in (2FA): ' . $user->email);

        return redirect()->intended(route('admin.dashboard'));
    }
}
