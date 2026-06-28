<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCenterStaff
{
    /**
     * Allow only center_admin / teacher members. Resolves the user's center
     * once and shares it on the request so controllers never trust an id from
     * the URL (isolation: a member only ever acts within their own center).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isCenterStaff()) {
            abort(403);
        }

        $center = $user->center();
        if (! $center || ! $center->is_active) {
            abort(403);
        }

        $request->attributes->set('center', $center);

        return $next($request);
    }
}
