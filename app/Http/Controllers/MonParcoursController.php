<?php

namespace App\Http\Controllers;

use App\Services\Center\ParcoursDeClasse;
use App\Services\Center\ParcoursEleveDeCentre;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * « Mon parcours » : les etapes que le centre a preparees pour la classe de
 * l'eleve, faites, en cours et a venir.
 *
 * Seulement quand le centre a choisi « Parcours créé par le centre » : sinon
 * c'est le parcours PrePla qui sert, et cette page n'a rien a montrer.
 */
class MonParcoursController extends Controller
{
    public function __construct(
        private ParcoursEleveDeCentre $eleve,
        private ParcoursDeClasse $parcours,
    ) {}

    public function __invoke(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        $centre = $this->eleve->centre($user);
        if (! $centre || ! $centre->reserveSesElevesASonContenu()) {
            return redirect()->route('dashboard');
        }

        $classe = $this->eleve->classeActuelle($user, $centre);

        return Inertia::render('parcours/index', [
            'classe' => $classe?->name,
            'parcours' => $classe ? $this->parcours->pourLEleve($user, $classe) : null,
            'devoirsEnAttente' => $this->eleve->devoirsEnAttente($user, $centre)->count(),
        ]);
    }
}
