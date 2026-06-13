<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrialMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Admins and confirmed instructors bypass trial
        if ($user->isAdmin() || $user->isInstructor()) {
            return $next($request);
        }

        if (! $user->isTrialActive()) {
            return redirect()->route('student.subscription')
                ->with('warning', __('messages.trial_expired'));
        }

        return $next($request);
    }
}
