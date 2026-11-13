<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\UserProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Le bouton FR / EN de l'en-tete : ne change QUE la langue de l'interface.
 * (La page Profil l'enregistre avec l'examen vise, dont le changement remet la
 * progression a zero : on ne passe pas par elle pour un simple choix de langue.)
 */
class LangueInterfaceController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $valide = $request->validate(['langue' => ['required', Rule::in(['fr', 'en'])]]);
        UserProfile::firstOrCreate(['user_id' => $request->user()->id])
            ->forceFill(['interface_language' => $valide['langue']])->save();

        return back();
    }
}
