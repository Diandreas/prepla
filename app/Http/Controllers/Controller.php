<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * Jeton a usage unique remis avec une seance servie.
     *
     * Renvoyer la MEME fin de seance — bouton Retour, double envoi, actualisation —
     * creditait a nouveau l'XP, une tentative par exercice et une progression. Le
     * jeton est consomme a la correction. Les trois ecrans qui servent le lecteur
     * (parcours, pratique libre, synthese de chapitre) doivent en poser un : sans
     * jeton, la correction laisse passer le renvoi.
     */
    protected function jetonDeSeance(int $userId): string
    {
        $jeton = \Illuminate\Support\Str::random(32);

        \Illuminate\Support\Facades\Cache::put("session-token:{$userId}:{$jeton}", true, now()->addHours(4));

        return $jeton;
    }
}
