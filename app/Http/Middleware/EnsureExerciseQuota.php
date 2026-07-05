<?php

namespace App\Http\Middleware;

use App\Models\UserExerciseAttempt;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces the "3 free exercises per day" limit advertised on the landing page
 * for non-premium users. Previously this limit existed only in marketing copy
 * — no controller checked subscribed()/hasPremiumAccess() before serving an
 * exercise, so a free user had technically unlimited access.
 */
class EnsureExerciseQuota
{
    public const DAILY_FREE_LIMIT = 3;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->hasPremiumAccess()) {
            return $next($request);
        }

        // The count-then-act check below has a TOCTOU race: the controller's
        // AI scoring call happens between this count and the attempt insert,
        // so several concurrent submissions can all pass the check before
        // any row exists. A per-user lock serializes submissions for the
        // duration of the request, closing that window.
        $lock = Cache::lock("exercise-quota:{$user->id}", 30);

        if (!$lock->block(5)) {
            return redirect()
                ->route('subscription.index')
                ->with('error', 'Une autre soumission est en cours, réessaie dans quelques secondes.');
        }

        try {
            $completedToday = UserExerciseAttempt::where('user_id', $user->id)
                ->whereDate('created_at', today())
                ->count();

            if ($completedToday >= self::DAILY_FREE_LIMIT) {
                return redirect()
                    ->route('subscription.index')
                    ->with('error', "Tu as atteint ta limite de " . self::DAILY_FREE_LIMIT . " exercices gratuits aujourd'hui. Passe à PrePla Plus pour continuer sans limite.");
            }

            return $next($request);
        } finally {
            $lock->release();
        }
    }
}
