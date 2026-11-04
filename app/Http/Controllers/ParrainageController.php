<?php

namespace App\Http\Controllers;

use App\Models\LanguageCenter;
use App\Services\Center\Parrainage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Le lien de parrainage d'un centre : https://.../r/{code}.
 *
 * Ouvert sans compte. Range le code puis mene a l'inscription ; il n'est lu
 * qu'a la creation d'un compte (App\Services\Center\Parrainage::rattacher).
 * La reponse est la meme que le code existe ou non : le lien ne permet pas de
 * deviner les codes des centres.
 */
class ParrainageController extends Controller
{
    public function __invoke(Request $request, string $code): RedirectResponse
    {
        $code = LanguageCenter::normaliserCodeParrainage($code);
        if ($code) {
            Parrainage::memoriser($request, $code);
        }

        return redirect()->route('register');
    }
}
