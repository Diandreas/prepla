<?php

namespace App\Http\Middleware;

use App\Services\Center\Domaines;
use App\Services\Center\Marque;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Quel centre l'adresse designe-t-elle ? (lot B)
 *
 * Place en tete du groupe web, avant la session. Selon l'hote :
 *  - l'hote principal (APP_URL, prepla.mirlab.cloud) : rien ne change ;
 *  - un sous-domaine reserve (www, api, admin, mail, staging...) : 404 ;
 *  - l'hote verifie d'un centre : le centre est depose dans les attributs de
 *    la requete (cle « centre_hote », lue par Marque et User::appartenanceCourante) ;
 *    centre ferme : page « centre indisponible » ;
 *  - tout autre hote, domaine non verifie compris : 404.
 *
 * Les hotes des centres ne sont jamais indexes (X-Robots-Tag, et la balise
 * robots de partials/meta.blade.php).
 *
 * Qui est connecte et n'est pas membre du centre est traite plus loin, une
 * fois la session ouverte : EnsureMembreDuCentreDeLHote.
 */
class ResolveCenterFromHost
{
    public const ATTRIBUT = Marque::CENTRE_DE_L_HOTE;

    public const NOINDEX = 'noindex, nofollow';

    public function handle(Request $request, Closure $next): Response
    {
        $hote = strtolower($request->getHost());

        if (Domaines::estPrincipal($hote)) {
            return $next($request);
        }

        $etiquette = Domaines::etiquetteSousLaRacine($hote);
        if ($etiquette !== null && Domaines::estReserve($etiquette)) {
            abort(404);
        }

        $centre = Domaines::centreDeLHote($hote);
        if ($centre === null) {
            abort(404);
        }

        if (! $centre->is_active) {
            return response()
                ->view('centres.indisponible', ['nom' => $centre->nomAffiche()], 503)
                ->header('X-Robots-Tag', self::NOINDEX);
        }

        $request->attributes->set(self::ATTRIBUT, $centre);

        $reponse = $next($request);
        $reponse->headers->set('X-Robots-Tag', self::NOINDEX);

        return $reponse;
    }

    /** Le centre designe par l'hote de la requete, s'il y en a un. */
    public static function centre(?Request $request = null): ?\App\Models\LanguageCenter
    {
        $request ??= app('request');
        $centre = $request->attributes->get(self::ATTRIBUT);

        return $centre instanceof \App\Models\LanguageCenter ? $centre : null;
    }
}
