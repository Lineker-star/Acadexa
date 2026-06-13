<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class LocaleMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = config('app.supported_locales', ['en', 'fr', 'es', 'pt', 'zh', 'ar']);

        // Priority: session → auth user preference → browser → default
        if (session()->has('locale') && in_array(session('locale'), $supported)) {
            App::setLocale(session('locale'));
        } elseif ($request->user() && in_array($request->user()->preferred_language, $supported)) {
            App::setLocale($request->user()->preferred_language);
        } else {
            $browserLocale = substr($request->getPreferredLanguage($supported) ?? 'en', 0, 2);
            App::setLocale(in_array($browserLocale, $supported) ? $browserLocale : config('app.locale'));
        }

        return $next($request);
    }
}
