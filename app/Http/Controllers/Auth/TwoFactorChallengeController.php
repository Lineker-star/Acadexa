<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Notifications\WelcomeNotification;
use App\Services\EmailCode;
use App\Services\LoginFlow;
use App\Services\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/** Second step of registration (confirm the e-mail) and of login (two-factor code). */
class TwoFactorChallengeController extends Controller
{
    public function __construct(private LoginFlow $flow, private EmailCode $codes, private Totp $totp) {}

    public function show(Request $request)
    {
        $state = $this->flow->pendingState($request);
        if (! $state) {
            return redirect()->route('login');
        }

        return view('auth.two-factor', [
            'purpose'     => $state['purpose'],
            'method'      => $state['method'],
            'maskedEmail' => $this->mask($state['user']->email),
            'wait'        => $state['method'] === 'email' ? $this->codes->waitBeforeResend($state['user'], $state['purpose']) : 0,
        ]);
    }

    public function verify(Request $request)
    {
        $request->validate(['code' => ['required', 'string', 'max:20']]);
        $state = $this->flow->pendingState($request);
        if (! $state) {
            return redirect()->route('login');
        }
        $user = $state['user'];

        $key = 'two-factor:' . $user->id . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['code' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($key), 'minutes' => 1])]);
        }

        $valid = $state['method'] === 'app'
            ? $this->totp->verify((string) $user->two_factor_secret, $request->code)
            : $this->codes->verify($user, $state['purpose'], $request->code);

        if (! $valid) {
            RateLimiter::hit($key, 300);
            return back()->withErrors(['code' => __('security.invalid_code')]);
        }
        RateLimiter::clear($key);

        if ($state['purpose'] === 'register') {
            $firstConfirmation = ! $user->email_verified_at;
            $user->forceFill(['email_verified_at' => now()])->save();
            if ($firstConfirmation) {
                $user->notify(new WelcomeNotification());
            }
            // The e-mail is now confirmed; an account that also has 2FA still goes through it.
            return $this->flow->start($request, $user, $state['remember']);
        }

        return $this->flow->finish($request, $user, $state['remember']);
    }

    public function resend(Request $request)
    {
        $state = $this->flow->pendingState($request);
        if (! $state || $state['method'] !== 'email') {
            return redirect()->route('login');
        }
        $wait = $this->codes->waitBeforeResend($state['user'], $state['purpose']);
        if ($wait > 0) {
            return back()->withErrors(['code' => __('learn.code_wait', ['seconds' => $wait])]);
        }
        $this->codes->send($state['user'], $state['purpose']);

        return back()->with('success', __('learn.code_resent'));
    }

    public function cancel(Request $request)
    {
        $request->session()->forget(LoginFlow::SESSION_KEY);
        return redirect()->route('login');
    }

    /** j***@gmail.com */
    private function mask(string $email): string
    {
        [$name, $domain] = explode('@', $email, 2) + [1 => ''];
        return mb_substr($name, 0, 1) . str_repeat('*', max(2, mb_strlen($name) - 1)) . '@' . $domain;
    }
}
