<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /** Route name prefix => permission required (see User::ADMIN_PERMISSIONS). */
    private const AREAS = [
        'admin.users.'          => 'users',
        'admin.courses.'        => 'courses',
        'admin.categories.'     => 'categories',
        'admin.applications.'   => 'applications',
        'admin.announcements.'  => 'announcements',
        'admin.cms-pages.'      => 'cms',
        'admin.reviews.'        => 'reviews',
        'admin.certificates.'   => 'certificates',
        'admin.certificate.'    => 'certificates',
        'admin.contacts.'       => 'contacts',
        'admin.translations.'   => 'translations',
        'admin.settings.'       => 'settings',
        'admin.activity-logs.'  => 'logs',
        'admin.reports.'        => 'reports',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('admin.login');
        }

        if (! $user->isAdmin()) {
            abort(403, __('Admin access required.'));
        }

        if (! $user->canAccess()) {
            auth()->logout();
            return redirect()->route('admin.login')->with('error', __('messages.account_deactivated'));
        }

        $routeName = (string) $request->route()?->getName();
        foreach (self::AREAS as $prefix => $permission) {
            if (Str::startsWith($routeName, $prefix) && ! $user->hasAdminPermission($permission)) {
                abort(403, __('security.no_permission'));
            }
        }

        return $next($request);
    }
}
