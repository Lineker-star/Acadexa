<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\EmailCode;
use App\Services\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * "My profile" for every signed-in account: personal information, password, two-factor
 * authentication (authenticator app or e-mail code) and the linked Google account.
 */
class ProfileController extends Controller
{
    public function __construct(private Totp $totp, private EmailCode $codes) {}

    public function edit(Request $request)
    {
        $user = $request->user();
        $pending = null;

        // Authenticator setup: a secret kept in the session until the first code is confirmed.
        if (! $user->hasTwoFactorEnabled() && ! $user->isAdmin()) {
            $secret = $request->session()->get('2fa_setup_secret') ?? $this->totp->generateSecret();
            $request->session()->put('2fa_setup_secret', $secret);
            $pending = ['secret' => $secret, 'uri' => $this->totp->uri($secret, $user->email)];
        }

        return view('student.profile.edit', [
            'user'          => $user,
            'pending'       => $pending,
            'googleEnabled' => \App\Http\Controllers\Auth\GoogleController::enabled(),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name'               => ['required', 'string', 'max:255'],
            'country'            => ['nullable', 'string', 'max:100'],
            'bio'                => ['nullable', 'string', 'max:1000'],
            'preferred_language' => ['nullable', 'string', 'in:en,fr,es,pt,zh,ar'],
            'avatar'             => ['nullable', 'image', 'max:2048', 'mimes:jpeg,png,jpg,gif,webp'],
            'remove_avatar'      => ['nullable', 'boolean'],
            'email_notifications' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('avatar') || $request->boolean('remove_avatar')) {
            if ($user->avatar && ! str_starts_with($user->avatar, 'http')) {
                Storage::disk('public')->delete('avatars/' . $user->avatar);
            }
            $data['avatar'] = null;
            if ($request->hasFile('avatar')) {
                $filename = time() . '_' . Str::random(8) . '.' . $request->file('avatar')->extension();
                $request->file('avatar')->storeAs('avatars', $filename, 'public');
                $data['avatar'] = $filename;
            }
        } else {
            unset($data['avatar']);
        }
        unset($data['remove_avatar']);
        $data['email_notifications'] = $request->boolean('email_notifications');

        $user->update($data);

        if ($request->filled('preferred_language')) {
            session(['locale' => $request->preferred_language]);
        }

        return back()->with('success', __('messages.profile_updated'));
    }

    /** Change the password — or choose one, for accounts created with Google. */
    public function password(Request $request)
    {
        $user = $request->user();
        $rules = ['password' => ['required', 'confirmed', Password::defaults()]];
        if ($user->has_password) {
            $rules['current_password'] = ['required', 'string'];
        }
        $request->validate($rules);

        if ($user->has_password && ! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => __('messages.wrong_password')])->withFragment('security');
        }

        $user->forceFill(['password' => Hash::make($request->password), 'has_password' => true])->save();

        return redirect()->to(route('student.profile.edit') . '#security')->with('success', __('learn.password_saved'));
    }

    /** Two-factor with an authenticator app: confirm the first code shown by the app. */
    public function enableApp(Request $request)
    {
        $request->validate(['code' => ['required', 'string']]);
        $user = $request->user();
        abort_if($user->isAdmin(), 403); // back-office accounts manage 2FA on their security page
        $secret = $request->session()->get('2fa_setup_secret');

        if (! $secret || ! $this->totp->verify($secret, $request->code)) {
            return back()->withErrors(['app_code' => __('security.invalid_code')])->withFragment('security');
        }

        $user->forceFill(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => now(), 'two_factor_method' => 'app'])->save();
        $request->session()->forget('2fa_setup_secret');

        return redirect()->to(route('student.profile.edit') . '#security')->with('success', __('security.enabled'));
    }

    /** Sends a code to the account's e-mail (to turn e-mail 2FA on, or to turn 2FA off). */
    public function sendEmailCode(Request $request)
    {
        $user = $request->user();
        $wait = $this->codes->waitBeforeResend($user, 'setup');
        if ($wait > 0) {
            return back()->withErrors(['email_code' => __('learn.code_wait', ['seconds' => $wait])])->withFragment('security');
        }
        $this->codes->send($user, 'setup');

        return redirect()->to(route('student.profile.edit') . '#security')
            ->with('success', __('learn.code_sent_to', ['email' => $user->email]))->with('email_code_sent', true);
    }

    /** Two-factor by e-mail: the code sent to the address proves it receives our messages. */
    public function enableEmail(Request $request)
    {
        $request->validate(['code' => ['required', 'string']]);
        $user = $request->user();
        abort_if($user->isAdmin(), 403);

        if (! $this->codes->verify($user, 'setup', $request->code)) {
            return back()->withErrors(['email_code' => __('security.invalid_code')])->with('email_code_sent', true)->withFragment('security');
        }

        $user->forceFill(['two_factor_method' => 'email', 'two_factor_secret' => null, 'two_factor_confirmed_at' => now()])->save();

        return redirect()->to(route('student.profile.edit') . '#security')->with('success', __('security.enabled'));
    }

    /** Turning 2FA off requires a valid code of the current method. */
    public function disableTwoFactor(Request $request)
    {
        $request->validate(['code' => ['required', 'string']]);
        $user = $request->user();
        abort_if($user->isAdmin(), 403);

        $valid = $user->usesAuthenticatorApp()
            ? $this->totp->verify((string) $user->two_factor_secret, $request->code)
            : $this->codes->verify($user, 'setup', $request->code);
        if (! $valid) {
            return back()->withErrors(['disable_code' => __('security.invalid_code')])->withFragment('security');
        }

        $user->forceFill(['two_factor_method' => null, 'two_factor_secret' => null, 'two_factor_confirmed_at' => null])->save();

        return redirect()->to(route('student.profile.edit') . '#security')->with('success', __('security.disabled'));
    }

    /** Unlinking Google is only possible once a password exists (otherwise the account is locked). */
    public function unlinkGoogle(Request $request)
    {
        $user = $request->user();
        if (! $user->has_password) {
            return back()->withErrors(['google' => __('learn.google_unlink_needs_password')])->withFragment('security');
        }
        $user->forceFill(['google_id' => null])->save();

        return redirect()->to(route('student.profile.edit') . '#security')->with('success', __('learn.google_unlinked'));
    }
}
