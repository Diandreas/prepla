<?php

namespace App\Http\Middleware;

use App\Services\Center\Domaines;
use Illuminate\Http\Middleware\TrustHosts;

/**
 * N'accepte que nos propres adresses (lot B).
 *
 * Des que nginx sert *.prepla.mirlab.cloud et les domaines des centres, il
 * transmet n'importe quel en-tete Host. Sans cette liste, un lien fabrique
 * pendant la requete — celui de reinitialisation du mot de passe d'abord —
 * pourrait pointer vers l'hote choisi par un attaquant. Un hote refuse donne
 * une 400 (Symfony : « Untrusted Host »), avant toute route.
 *
 * Acceptes : l'hote principal, un niveau sous la racine, et les domaines
 * propres verifies (Domaines::motifsDeConfiance). Le controle s'applique aussi
 * pendant les tests, pour qu'ils le prouvent ; seul le developpement local en
 * est dispense, comme dans TrustHosts.
 */
class FaireConfianceAuxHotes extends TrustHosts
{
    /** @return array<int, string> */
    public function hosts()
    {
        return Domaines::motifsDeConfiance();
    }

    protected function shouldSpecifyTrustedHosts()
    {
        return ! $this->app->environment('local');
    }
}
