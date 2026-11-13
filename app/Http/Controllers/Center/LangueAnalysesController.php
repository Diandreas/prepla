<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use App\Models\LanguageCenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * La langue des analyses IA du centre (francais ou anglais), par le responsable.
 * A part du formulaire de personnalisation, comme le reglage du parcours.
 */
class LangueAnalysesController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        abort_unless($request->user()->centerRoleIs('center_admin'), 403);

        /** @var LanguageCenter $centre */
        $centre = $request->attributes->get('center');
        $valide = $request->validate(['langue_analyses' => ['required', Rule::in(['fr', 'en'])]]);
        $centre->choisirLangueAnalyses($valide['langue_analyses']);

        return back()->with('success', $valide['langue_analyses'] === 'en'
            ? 'Les analyses IA seront rédigées en anglais. Elles se mettent à jour à la prochaine ouverture de chaque fiche.'
            : 'Les analyses IA seront rédigées en français.');
    }
}
