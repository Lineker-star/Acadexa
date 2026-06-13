<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('student.profile.edit', ['user' => $request->user()]);
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
            'current_password'   => ['nullable', 'string'],
            'password'           => ['nullable', 'confirmed', Password::defaults()],
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete('avatars/' . $user->avatar);
            }
            $filename = time() . '_' . $request->file('avatar')->getClientOriginalName();
            $request->file('avatar')->storeAs('avatars', $filename, 'public');
            $data['avatar'] = $filename;
        }

        if ($request->filled('password')) {
            if (! Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => __('messages.wrong_password')]);
            }
            $data['password'] = Hash::make($request->password);
        }

        unset($data['current_password']);
        $user->update(array_filter($data, fn($v) => $v !== null));

        if ($request->filled('preferred_language')) {
            session(['locale' => $request->preferred_language]);
        }

        return back()->with('success', __('messages.profile_updated'));
    }
}
