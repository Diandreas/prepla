<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\InvitationEleveNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;

/**
 * Lien d'invitation d'un compte cree par import : l'URL signee (30 jours)
 * fabrique a l'ouverture un jeton de mot de passe frais, puis mene au
 * formulaire habituel. Une fois le mot de passe choisi, l'empreinte ne
 * correspond plus et le lien est mort.
 */
class InvitationMotDePasseController extends Controller
{
    public function __invoke(User $user, string $v): RedirectResponse
    {
        if (! hash_equals(InvitationEleveNotification::empreinte($user), $v)) {
            return redirect()->route('password.request')
                ->with('status', 'Ce lien a déjà servi. Demandez un nouveau lien de mot de passe si besoin.');
        }

        $jeton = Password::broker()->createToken($user);

        return redirect()->route('password.reset', ['token' => $jeton, 'email' => $user->email]);
    }
}
