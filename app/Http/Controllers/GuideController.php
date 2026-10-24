<?php

namespace App\Http\Controllers;

use App\Services\Content\ProgressionApprenant;
use Inertia\Inertia;
use Inertia\Response;

/**
 * « Comment apprendre ici » : la page qui manquait.
 *
 * La plateforme explique chaque écran une fois qu'on y est, mais personne ne
 * disait à l'apprenant COMMENT s'y prendre — par où commencer, à quel rythme,
 * quoi faire de ses erreurs. Un apprenant seul devant une liste d'exercices
 * tourne en rond : il refait ce qu'il réussit déjà et évite ce qui résiste.
 *
 * Le guide tient en une page, et finit sur SON étape à lui — pas sur un
 * conseil général.
 */
class GuideController extends Controller
{
    public function show(ProgressionApprenant $progression): Response
    {
        $user = auth()->user();

        return Inertia::render('guide', [
            // Le portrait sert ici à deux choses : saluer l'apprenant par son
            // prénom, et terminer sur ce qu'il a vraiment à faire maintenant.
            'portrait' => $user ? $progression->portrait($user) : null,
        ]);
    }
}
