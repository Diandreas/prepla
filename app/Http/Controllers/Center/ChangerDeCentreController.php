<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Passer d'un centre a l'autre, pour qui en gere plusieurs.
 *
 * Hors du groupe reserve au personnel, a dessein : si le centre courant vient
 * d'etre ferme, la personne doit pouvoir en rejoindre un autre plutot que de
 * buter sur un refus.
 */
class ChangerDeCentreController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $valide = $request->validate(['center_id' => 'required|integer']);

        // On ne choisit que parmi les centres qu'on encadre : un identifiant
        // saisi a la main ne fait entrer chez personne d'autre.
        abort_unless($request->user()->choisirCentre((int) $valide['center_id']), 403);

        $nom = $request->user()->center()?->name;

        return redirect()->route('center.dashboard')->with('success', "Vous êtes dans « {$nom} ».");
    }
}
