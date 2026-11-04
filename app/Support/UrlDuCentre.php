<?php

namespace App\Support;

use App\Models\LanguageCenter;

/**
 * Les liens d'un centre fabriques hors de toute requete (courriels,
 * notifications en file, commandes planifiees) : hors requete, route() part
 * de APP_URL. Un centre qui a un domaine principal verifie recoit ses liens
 * sur ce domaine ; les autres restent sur l'hote principal.
 *
 * Le schema (https en production) et le port viennent de APP_URL.
 */
class UrlDuCentre
{
    /** L'adresse de base du centre, sans barre finale : « https://ecole.prepla.mirlab.cloud ». */
    public static function racine(?LanguageCenter $centre): string
    {
        $domaine = $centre?->domainePrincipal();
        if ($domaine === null) {
            return rtrim((string) config('app.url'), '/');
        }

        $app = (string) config('app.url');
        $schema = parse_url($app, PHP_URL_SCHEME) ?: 'https';
        $port = parse_url($app, PHP_URL_PORT);

        return $schema.'://'.$domaine->host.($port ? ':'.$port : '');
    }

    /**
     * L'URL absolue d'une route nommee, sur le domaine du centre s'il en a un.
     *
     * @param  array<string, mixed>|mixed  $parametres
     */
    public static function route(?LanguageCenter $centre, string $nom, mixed $parametres = []): string
    {
        // Sans domaine, exactement le lien d'avant (route() absolue).
        if ($centre?->domainePrincipal() === null) {
            return route($nom, $parametres);
        }

        return self::racine($centre).route($nom, $parametres, false);
    }
}
