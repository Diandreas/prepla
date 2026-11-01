<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * N'ecrit que dans le centre que la page affichait.
 *
 * Le centre courant est range en session : il vaut pour TOUS les onglets. Une
 * responsable de deux centres qui ouvre le formulaire d'invitation du centre A,
 * passe dans B depuis un autre onglet, puis envoie le premier formulaire,
 * invitait dans B — classe, exercice ou fichier compris pour les autres
 * creations. Le client joint donc a chaque ecriture le centre affiche par la
 * page (en-tete X-Prepla-Centre, voir resources/js/lib/centre-affiche.ts) ;
 * s'il n'est plus le centre courant, rien n'est ecrit et on le dit.
 *
 * Ce n'est pas une barriere de droits — les politiques s'en chargent, et on ne
 * gagne rien a omettre l'en-tete : c'est un garde-fou contre une erreur
 * d'onglet. Sans en-tete, la requete passe comme avant.
 */
class EnsureDisplayedCenter
{
    public const EN_TETE = 'X-Prepla-Centre';

    /** Changer de centre, en rejoindre un ou en ouvrir un : ces routes ne s'ecrivent pas DANS le centre courant. */
    private const EXEMPTEES = ['center.switch', 'center.join.store'];

    public function handle(Request $request, Closure $next): Response
    {
        $affiche = $request->header(self::EN_TETE);

        if ($request->isMethodSafe() || $affiche === null || $affiche === '' || ! $request->user() || ! $this->concerne($request)) {
            return $next($request);
        }

        $courant = $request->user()->center();
        if ($courant === null || (int) $affiche === $courant->id) {
            return $next($request);
        }

        $message = "Vous êtes passé dans « {$courant->name} » depuis un autre onglet : rien n'a été enregistré. "
            .'Rechargez la page pour continuer dans ce centre.';

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['message' => $message, 'centre_courant' => $courant->id], 409);
        }

        return back()->with('error', $message);
    }

    /** Les ecritures faites au nom du centre courant : tout l'espace centre, et l'invitation d'un enseignant. */
    private function concerne(Request $request): bool
    {
        $nom = (string) $request->route()?->getName();

        return ! in_array($nom, self::EXEMPTEES, true)
            && (str_starts_with($nom, 'center.') || $nom === 'teach.invitations.store');
    }
}
