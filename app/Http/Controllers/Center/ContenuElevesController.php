<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use App\Models\LanguageCenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Le reglage « Parcours des élèves » (Parcours PrePla par défaut / Parcours
 * créé par le centre), par le responsable seul.
 *
 * A part du formulaire de personnalisation : celui-ci exige les examens et la
 * marque, et changer ce reglage ne doit pas obliger a renvoyer le reste.
 */
class ContenuElevesController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        abort_unless($request->user()->centerRoleIs('center_admin'), 403);

        /** @var LanguageCenter $centre */
        $centre = $request->attributes->get('center');

        $valide = $request->validate([
            'contenu_eleves' => ['required', 'string', Rule::in(LanguageCenter::CONTENUS_ELEVES)],
        ], [
            'contenu_eleves.required' => 'Choisissez le parcours de vos élèves.',
            'contenu_eleves.in' => 'Choisissez le parcours de vos élèves.',
        ]);

        $centre->choisirContenuEleves($valide['contenu_eleves']);

        return back()->with('success', $valide['contenu_eleves'] === LanguageCenter::CONTENU_CENTRE
            ? 'Vos élèves suivent désormais le parcours que vous créez pour chaque classe, en plus de vos devoirs.'
            : 'Vos élèves suivent le parcours PrePla par défaut, en plus de vos devoirs.');
    }
}
