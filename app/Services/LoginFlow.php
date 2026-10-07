<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\User;
use App\Support\Mailing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * What happens once the person is identified (password, Google or new registration):
 *  1. e-mail not confirmed yet → a code is sent to the address (registration step),
 *  2. two-factor enabled       → a code from the authenticator app or sent by e-mail,
 *  3. otherwise                → signed in.
 * Until the last step the person is NOT logged in: the pending account lives in the session.
 */
class LoginFlow
{
    public const SESSION_KEY = 'auth.pending';

    public function __construct(private EmailCode $codes) {}

    public function start(Request $request, User $user, bool $remember = false, bool $emailTrusted = false): RedirectResponse
    {
        // A just-created model does not carry the database defaults (is_active…): reload it.
        $user = $user->fresh() ?? $user;

        if (! $user->canAccess()) {
            return redirect()->route('login')->withErrors([
                'email' => $user->isBanned() ? __('security.banned') : __('messages.account_deactivated'),
            ]);
        }

        // An address confirmed by Google counts as verified.
        if ($emailTrusted && ! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        if ($this->needsEmailConfirmation($user)) {
            return $this->pending($request, $user, $remember, 'register');
        }
        if ($user->hasTwoFactorEnabled()) {
            return $this->pending($request, $user, $remember, 'login');
        }

        return $this->finish($request, $user, $remember);
    }

    /**
     * Registration step: confirm the e-mail address with a code (admin setting, on by default).
     * Skipped while no e-mail service is set up: the code could not reach anybody.
     */
    public function needsEmailConfirmation(User $user): bool
    {
        return ! $user->email_verified_at && Setting::get('registration_code', '1') === '1' && Mailing::configured();
    }

    public function finish(Request $request, User $user, bool $remember): RedirectResponse
    {
        $request->session()->forget(self::SESSION_KEY);
        Auth::login($user, $remember);
        $request->session()->regenerate();

        if ($user->isAdmin()) {
            ActivityLog::record('admin_login', 'Admin logged in: ' . $user->email);
            return redirect()->intended(route('admin.dashboard'));
        }
        return redirect()->intended($user->isInstructor() ? route('instructor.dashboard') : route('dashboard'));
    }

    /** @return array{user: User, purpose: string, method: string, remember: bool}|null */
    public function pendingState(Request $request): ?array
    {
        $state = $request->session()->get(self::SESSION_KEY);
        $user = $state ? User::find($state['user_id']) : null;
        if (! $user) {
            return null;
        }
        return ['user' => $user] + $state;
    }

    private function pending(Request $request, User $user, bool $remember, string $purpose): RedirectResponse
    {
        // "email" when the code is e-mailed (registration or e-mail 2FA), "app" for an authenticator.
        $method = $purpose === 'register' ? 'email' : $user->twoFactorMethod();
        $request->session()->put(self::SESSION_KEY, [
            'user_id'  => $user->id,
            'purpose'  => $purpose,
            'method'   => $method,
            'remember' => $remember,
        ]);
        if ($method === 'email' && $this->codes->waitBeforeResend($user, $purpose) === 0 && ! $this->codes->send($user, $purpose)) {
            return redirect()->route('two-factor.challenge')->withErrors(['code' => __('learn.code_send_failed')]);
        }

        return redirect()->route('two-factor.challenge');
    }
}
