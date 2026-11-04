<?php

namespace App\Http\Middleware;

use App\Models\CenterUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sur l'adresse d'un centre, seuls ses membres entrent (lot B).
 *
 * Un compte connecte qui n'en est pas membre ne voit rien de ce centre, ni
 * des siens depuis cette adresse : il est envoye vers « rejoindre une classe »
 * s'il n'appartient a aucun centre, sinon vers la page « ce compte suit un
 * autre etablissement ». Le super administrateur garde l'acces.
 *
 * Apres la session (le compte doit etre connu), dans le groupe web. Sans
 * centre de l'hote (hote principal), ne fait rien.
 */
class EnsureMembreDuCentreDeLHote
{
    /** Ce qu'un non-membre peut encore faire ici : partir, rejoindre, accepter une invitation, lire les mentions. */
    private const OUVERTES = [
        'logout',
        'center.join',
        'center.join.store',
        'centre.autre-etablissement',
        'teach.invitation.show',
        'teach.invitation.accept',
        'verification.notice',
        'verification.verify',
        'verification.send',
        'privacy',
        'terms',
        'account-deletion',
        'offline',
        'offline.app',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $centre = ResolveCenterFromHost::centre($request);
        if ($centre === null) {
            return $next($request);
        }

        $route = (string) $request->route()?->getName();
        $user = $request->user();

        // L'adresse d'un centre n'est pas une vitrine de PrePla : on y entre.
        if ($route === 'sitemap') {
            abort(404);
        }
        if ($route === 'home') {
            return redirect()->route($user ? 'dashboard' : 'login');
        }

        if ($user === null || $user->isSuperAdmin() || $route === '' || in_array($route, self::OUVERTES, true)) {
            return $next($request);
        }

        $membre = CenterUser::where('center_id', $centre->id)->where('user_id', $user->id)->exists();
        if ($membre) {
            return $next($request);
        }

        $ailleurs = CenterUser::where('user_id', $user->id)->exists();
        $cible = $ailleurs ? 'centre.autre-etablissement' : 'center.join';

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'message' => $ailleurs
                    ? "Ce compte suit un autre établissement."
                    : "Rejoignez d'abord une classe de cet établissement.",
            ], 403);
        }

        return redirect()->route($cible);
    }
}
