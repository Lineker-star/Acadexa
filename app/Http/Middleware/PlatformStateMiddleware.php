<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the admin settings "maintenance_mode" and "allow_registration".
 * Admins, the back-office and the PWA files stay reachable during maintenance.
 */
class PlatformStateMiddleware
{
    private const ALWAYS_OPEN = [
        'admin.*', 'login', 'logout', 'password.*', 'pwa.*', 'offline', 'sitemap', 'robots', 'locale.switch',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (Setting::get('maintenance_mode', '0') === '1'
            && ! $request->user()?->isAdmin()
            && ! $request->routeIs(...self::ALWAYS_OPEN)) {
            return response()->view('errors.503', [], 503)->header('Retry-After', '3600');
        }

        if ($request->routeIs('register') && Setting::get('allow_registration', '1') !== '1') {
            return redirect()->route('login')->with('warning', __('security.registration_closed'));
        }

        return $next($request);
    }
}
