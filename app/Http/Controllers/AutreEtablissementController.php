<?php

namespace App\Http\Controllers;

use App\Http\Middleware\ResolveCenterFromHost;
use App\Models\CenterUser;
use App\Support\UrlDuCentre;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * « Ce compte suit un autre etablissement » (lot B).
 *
 * Montree sur l'adresse d'un centre a un compte connecte qui n'en est pas
 * membre mais appartient a un autre centre. Elle ne dit rien du centre de
 * l'adresse au-dela de son nom affiche, et propose d'aller chez soi, de se
 * deconnecter, ou de rejoindre une classe d'ici avec un code.
 */
class AutreEtablissementController extends Controller
{
    public function __invoke(Request $request)
    {
        $centre = ResolveCenterFromHost::centre($request);
        $user = $request->user();

        // Ailleurs que sur l'adresse d'un centre, ou deja membre : rien a expliquer.
        if ($centre === null || CenterUser::where('center_id', $centre->id)->where('user_id', $user->id)->exists()) {
            return redirect()->route('dashboard');
        }

        $siens = CenterUser::with('center')->where('user_id', $user->id)->get()
            ->filter(fn (CenterUser $a) => (bool) $a->center?->is_active)
            ->map(fn (CenterUser $a) => [
                'nom' => $a->center->nomAffiche(),
                'url' => UrlDuCentre::route($a->center, 'dashboard'),
            ])
            ->values()
            ->all();

        return Inertia::render('centre-autre-etablissement', [
            'centreIci' => $centre->nomAffiche(),
            'sesCentres' => $siens,
        ]);
    }
}
