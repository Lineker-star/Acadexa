<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    public function showLogin()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            if (! $user->isAdmin()) {
                Auth::logout();
                return back()->withErrors(['email' => __('Access denied. Admin only.')]);
            }

            if (! $user->canAccess()) {
                Auth::logout();
                return back()->withErrors(['email' => __('messages.account_deactivated')]);
            }

            // Second factor: log out again until the authenticator code is checked.
            if ($user->hasTwoFactorEnabled()) {
                Auth::logout();
                $request->session()->regenerate();
                $request->session()->put('2fa_login_id', $user->id);
                $request->session()->put('2fa_remember', $request->boolean('remember'));
                return redirect()->route('admin.2fa.challenge');
            }

            $request->session()->regenerate();
            ActivityLog::record('admin_login', 'Admin logged in: ' . $user->email);

            return redirect()->route('admin.dashboard');
        }

        return back()->withErrors(['email' => __('auth.failed')]);
    }

    public function logout(Request $request)
    {
        ActivityLog::record('admin_logout', 'Admin logged out.');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }
}
