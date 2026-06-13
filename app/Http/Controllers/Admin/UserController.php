<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"));
        }
        if ($request->filled('role')) $query->where('role', $request->role);
        if ($request->filled('status')) $query->where('is_active', $request->status === 'active');

        $users = $query->latest()->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        $user->load(['courses.translations', 'enrollments.course.translations', 'certificates.course.translations']);
        return view('admin.users.show', compact('user'));
    }

    public function activate(User $user)
    {
        $user->update(['is_active' => true]);
        ActivityLog::record('user_activate', "Activated user #{$user->id}: {$user->email}");
        return back()->with('success', 'User activated.');
    }

    public function deactivate(User $user)
    {
        $user->update(['is_active' => false]);
        ActivityLog::record('user_deactivate', "Deactivated user #{$user->id}: {$user->email}");
        return back()->with('success', 'User deactivated.');
    }

    public function ban(User $user)
    {
        $user->update(['is_active' => false, 'email_verified_at' => null]);
        ActivityLog::record('user_ban', "Banned user #{$user->id}: {$user->email}");
        return back()->with('success', 'User banned.');
    }

    public function extendTrial(Request $request, User $user)
    {
        $request->validate(['days' => ['required', 'integer', 'min:1', 'max:365']]);
        $newStart = ($user->trial_started_at ?? now())->addDays($request->days);
        $user->update(['trial_started_at' => $newStart]);
        ActivityLog::record('trial_extended', "Extended trial for #{$user->id} by {$request->days} days.");
        return back()->with('success', "Trial extended by {$request->days} days.");
    }

    public function resetPassword(Request $request, User $user)
    {
        $request->validate(['password' => ['required', 'string', 'min:8', 'confirmed']]);
        $user->update(['password' => Hash::make($request->password)]);
        ActivityLog::record('password_reset', "Reset password for user #{$user->id}: {$user->email}");
        return back()->with('success', 'Password reset successfully.');
    }
}
