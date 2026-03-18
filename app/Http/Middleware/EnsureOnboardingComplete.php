<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && !$request->user()->profile?->onboarding_completed_at) {
            return redirect()->route('onboarding.exam');
        }

        return $next($request);
    }
}
