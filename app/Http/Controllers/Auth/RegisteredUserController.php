<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LoginFlow;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request, LoginFlow $flow): RedirectResponse
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'country'  => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::create([
            'name'              => $request->name,
            'email'             => $request->email,
            'password'          => Hash::make($request->password),
            'country'           => $request->country,
            'role'              => 'student',
            'trial_started_at'  => now(),
            'preferred_language'=> app()->getLocale(),
        ]);

        // Registration step: a 6-digit code is e-mailed to confirm the address before the account opens
        // (it replaces the verification link, so the "Registered" event is not fired in that case).
        if ($flow->needsEmailConfirmation($user)) {
            return $flow->start($request, $user);
        }

        event(new Registered($user));
        $user->notify(new \App\Notifications\WelcomeNotification());
        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
