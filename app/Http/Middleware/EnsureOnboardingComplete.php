<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user || $user->profile?->onboarding_completed_at) {
            return $next($request);
        }

        // Le personnel d'un centre n'a pas de parcours d'apprentissage a configurer :
        // l'envoyer choisir « son objectif » et « son niveau » est un cul-de-sac, et
        // plusieurs liens en dur (logo, page d'accueil publique, favoris) y menaient.
        if ($user->isSuperAdmin() || $user->isCenterStaff()) {
            return $next($request);
        }

        // Native language must be set first — lesson explanations are localized to it
        if (!$user->profile?->native_language) {
            return redirect()->route('onboarding.native-language');
        }

        return redirect()->route('onboarding.exam');
    }
}
