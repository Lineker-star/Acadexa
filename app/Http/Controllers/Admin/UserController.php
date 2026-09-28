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
        return back()->with('success', __('User activated.'));
    }

    public function deactivate(Request $request, User $user)
    {
        $this->guardTarget($request, $user);
        $user->update(['is_active' => false]);
        ActivityLog::record('user_deactivate', "Deactivated user #{$user->id}: {$user->email}");
        return back()->with('success', __('User deactivated.'));
    }

    /** A ban blocks sign-in everywhere and records why; unlike deactivation it is shown to the user. */
    public function ban(Request $request, User $user)
    {
        $this->guardTarget($request, $user);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        $user->update(['banned_at' => now(), 'ban_reason' => $data['reason'] ?? null]);
        // End the banned user's sessions right away.
        \Illuminate\Support\Facades\DB::table('sessions')->where('user_id', $user->id)->delete();
        ActivityLog::record('user_ban', "Banned user #{$user->id}: {$user->email}" . (! empty($data['reason']) ? " — {$data['reason']}" : ''));

        return back()->with('success', __('security.user_banned'));
    }

    public function unban(Request $request, User $user)
    {
        $this->guardTarget($request, $user);
        $user->update(['banned_at' => null, 'ban_reason' => null]);
        ActivityLog::record('user_unban', "Unbanned user #{$user->id}: {$user->email}");

        return back()->with('success', __('security.user_unbanned'));
    }

    /** Super admin only: role and back-office areas of an admin. */
    public function updatePermissions(Request $request, User $user)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        abort_if($user->id === $request->user()->id, 422, __('security.cannot_edit_self'));

        $data = $request->validate([
            'role'          => ['required', 'in:student,instructor,admin'],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['in:' . implode(',', User::ADMIN_PERMISSIONS)],
        ]);

        $attributes = ['role' => $data['role'], 'admin_permissions' => $data['role'] === 'admin' ? array_values($data['permissions'] ?? []) : null];
        if ($data['role'] === 'instructor') {
            $attributes['instructor_status'] = 'confirmed';
        }
        $user->update($attributes);
        ActivityLog::record('user_permissions', "Set role {$data['role']} for #{$user->id}: " . implode(',', $attributes['admin_permissions'] ?? []));

        return back()->with('success', __('security.permissions_saved'));
    }

    /** Nobody can act on themselves; only a super admin can act on another admin. */
    private function guardTarget(Request $request, User $user): void
    {
        abort_if($user->id === $request->user()->id, 422, __('security.cannot_edit_self'));
        abort_if($user->isSuperAdmin(), 403);
        abort_if($user->isAdmin() && ! $request->user()->isSuperAdmin(), 403);
    }

    public function extendTrial(Request $request, User $user)
    {
        $request->validate(['days' => ['required', 'integer', 'min:1', 'max:365']]);
        $newStart = ($user->trial_started_at ?? now())->addDays($request->days);
        $user->update(['trial_started_at' => $newStart]);
        ActivityLog::record('trial_extended', "Extended trial for #{$user->id} by {$request->days} days.");
        return back()->with('success', trans_choice('Trial extended by :count day.|Trial extended by :count days.', $request->days, ['count' => $request->days]));
    }

    public function resetPassword(Request $request, User $user)
    {
        $request->validate(['password' => ['required', 'string', 'min:8', 'confirmed']]);
        $user->update(['password' => Hash::make($request->password)]);
        ActivityLog::record('password_reset', "Reset password for user #{$user->id}: {$user->email}");
        return back()->with('success', __('Password reset successfully.'));
    }
}
