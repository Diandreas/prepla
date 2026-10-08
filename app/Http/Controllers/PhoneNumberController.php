<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Le numéro de téléphone de ceux qui ne sont jamais passés par le formulaire
 * d'inscription — une entrée par Google ne demande rien d'autre qu'un clic.
 *
 * On ne le demande qu'une fois : `phone_prompted_at` retient la question posée, que
 * la personne réponde ou décline. Rien n'est bloqué en attendant, un apprenant qui
 * veut travailler n'a pas à donner son numéro pour y arriver.
 */
class PhoneNumberController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Pas de format impose : les numeros s'ecrivent +237 6XX comme 06 XX, et
            // un refus de format ferait perdre la reponse pour rien.
            'phone' => 'required|string|min:6|max:32',
        ]);

        $request->user()->update([
            'phone' => $validated['phone'],
            'phone_prompted_at' => now(),
        ]);

        return back()->with('success', 'Merci, ton numéro est enregistré.');
    }

    public function dismiss(Request $request): RedirectResponse
    {
        // Question posée, réponse déclinée : on ne la repose pas à chaque visite.
        $request->user()->update(['phone_prompted_at' => now()]);

        return back();
    }
}
