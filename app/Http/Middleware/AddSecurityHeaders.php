<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CloudPanel's nginx already sets X-Frame-Options, X-Content-Type-Options and
 * Referrer-Policy (confirmed via `curl -I` on production) — only
 * Strict-Transport-Security was missing. Added here rather than in nginx
 * config to keep it versioned with the app.
 */
class AddSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
