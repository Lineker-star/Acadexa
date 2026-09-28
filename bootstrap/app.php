<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Hosting platforms (Railway…) terminate HTTPS at a proxy: trust its X-Forwarded-* headers
        // so that generated URLs stay https (required by the service worker / offline mode).
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            \App\Http\Middleware\LocaleMiddleware::class,
            \App\Http\Middleware\PlatformStateMiddleware::class,
        ]);

        $middleware->alias([
            'role'  => \App\Http\Middleware\RoleMiddleware::class,
            'trial' => \App\Http\Middleware\TrialMiddleware::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            return response()->view('errors.404', [], 404);
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
            if (app()->environment('production') && !$request->expectsJson()) {
                return response()->view('errors.500', [], 500);
            }
        });
    })->create();
