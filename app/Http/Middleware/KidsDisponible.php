<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Une instance en marque blanche peut retirer PrePla Kids (MARQUE_MASQUER_KIDS) :
 * l'entree du menu disparait, et ses adresses repondent 404 au lieu d'afficher
 * l'espace Kids de PrePla.
 */
class KidsDisponible
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if((bool) config('marque.masquer_kids'), 404);

        return $next($request);
    }
}
