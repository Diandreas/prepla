<?php

namespace App\Http\Controllers;

use App\Models\CommissionDelegue;
use App\Models\Delegue;
use App\Models\User;
use App\Services\Billing\Delegues;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Le cote delegue et le cote etudiant des codes :
 *  - /d/{code} : le lien que le delegue partage, qui mene a l'inscription ;
 *  - le code saisi par l'etudiant sur la page d'abonnement ;
 *  - /mon-code : la page ou le delegue suit ses inscrits et ses gains.
 */
class DelegueController extends Controller
{
    public function lien(Request $request, string $code): RedirectResponse
    {
        $code = Delegue::normaliser($code);
        if ($code) {
            Delegues::memoriser($request, $code);
        }

        // Deja connecte : le code s'applique tout de suite, sur la page d'abonnement.
        if ($request->user() && ($delegue = Delegue::parCode($code))) {
            Delegues::rattacher($request->user(), $delegue);

            return redirect()->route('subscription.index');
        }

        return redirect()->route('register');
    }

    public function appliquer(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'max:30']]);
        $user = $request->user();
        $delegue = Delegue::parCode($request->input('code'));

        if (! $delegue) {
            return back()->withErrors(['code' => "Ce code n'existe pas ou n'est plus actif. Vérifie-le auprès de ton délégué."]);
        }
        if ($user->delegue_id && $user->delegue_id !== $delegue->id) {
            return back()->withErrors(['code' => 'Ton compte est déjà rattaché à un autre code.']);
        }
        if ($delegue->user_id === $user->id) {
            return back()->withErrors(['code' => "C'est ton propre code : partage-le à tes camarades."]);
        }

        Delegues::rattacher($user, $delegue);

        return back()->with('success', $delegue->reduction > 0
            ? "Code {$delegue->code} appliqué : {$delegue->reduction} % de réduction sur ton abonnement."
            : "Code {$delegue->code} enregistré.");
    }

    public function monCode(Request $request): Response
    {
        $delegue = Delegue::where('user_id', $request->user()->id)->firstOrFail();

        return Inertia::render('mon-code', [
            'delegue' => [
                'nom' => $delegue->nom,
                'code' => $delegue->code,
                'taux' => $delegue->taux,
                'reduction' => $delegue->reduction,
                'actif' => $delegue->actif,
                'lien' => route('delegue.lien', $delegue->code),
            ],
            'bilan' => Delegues::bilan($delegue),
            // Prenom et statut seulement : le delegue n'a pas a voir les e-mails.
            'etudiants' => User::where('delegue_id', $delegue->id)->latest('delegue_at')->take(100)->get()
                ->map(fn (User $u) => [
                    'prenom' => strtok((string) $u->name, ' ') ?: $u->name,
                    'le' => $u->delegue_at?->toIso8601String(),
                    'abonne' => CommissionDelegue::where('user_id', $u->id)->where('delegue_id', $delegue->id)->whereNull('annulee_le')->exists(),
                    'gain' => (int) CommissionDelegue::where('user_id', $u->id)->where('delegue_id', $delegue->id)->whereNull('annulee_le')->sum('commission'),
                ]),
        ]);
    }
}
