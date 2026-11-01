<?php

namespace App\Http\Controllers;

use App\Services\Billing\TarifsAffiches;
use App\Services\Content\ExercicesDEssai;
use App\Support\VideoAccueil;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    public function __invoke(ExercicesDEssai $essais, TarifsAffiches $tarifs): Response
    {
        return Inertia::render('welcome', [
            'languages' => config('languages.languages'),
            // La video du haut de page, avec le poids reel de chaque fichier.
            'video' => VideoAccueil::donnees(),
            // Les vrais exercices ouverts a l'essai, par langue puis par examen :
            // la page d'accueil les propose directement, sans compte.
            'essai' => Cache::remember('accueil.essai', now()->addMinutes(10), fn () => $essais->catalogue()),
            // Lus chez Stripe, comme sur la page d'abonnement. Differes : une lenteur
            // de Stripe ne retarde jamais l'affichage de la page.
            'tarifs' => Inertia::defer(fn () => [
                'mensuel' => $tarifs->mensuel(),
                'annuel' => $tarifs->annuel(),
            ]),
        ]);
    }
}
