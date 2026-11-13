<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\CommissionDelegue;
use App\Models\Delegue;
use App\Models\User;
use App\Services\Billing\Delegues;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Les delegues vus par l'equipe : qui a amene combien d'etudiants, combien ils
 * ont paye, ce qui est du a chacun, et le versement.
 */
class DelegueController extends Controller
{
    public function index(): Response
    {
        $delegues = Delegue::with('user:id,email')->orderByDesc('actif')->orderBy('nom')->get();

        return Inertia::render('admin/delegues', [
            'delegues' => $delegues->map(fn (Delegue $d) => [
                'id' => $d->id,
                'nom' => $d->nom,
                'telephone' => $d->telephone,
                'code' => $d->code,
                'salle' => $d->salle,
                'taux' => $d->taux,
                'reduction' => $d->reduction,
                'prix_mensuel' => $d->prix_mensuel,
                'prix_barre' => $d->prix_barre,
                'actif' => $d->actif,
                'email' => $d->user?->email,
                'lien' => route('delegue.lien', $d->code),
                'bilan' => Delegues::bilan($d),
                'etudiants' => User::where('delegue_id', $d->id)->latest('delegue_at')->take(50)->get()
                    ->map(fn (User $u) => [
                        'nom' => $u->name,
                        'email' => $u->email,
                        'le' => $u->delegue_at?->toIso8601String(),
                        'paye' => (int) CommissionDelegue::where('user_id', $u->id)->where('delegue_id', $d->id)->whereNull('annulee_le')->sum('montant'),
                        'acces_actif' => $u->aUnAccesMobileMoney(),
                    ]),
            ]),
            'totaux' => [
                'du' => (int) CommissionDelegue::due()->sum('commission'),
                'verse' => (int) CommissionDelegue::whereNull('annulee_le')->whereNotNull('versee_le')->sum('commission'),
                'encaisse' => (int) CommissionDelegue::whereNull('annulee_le')->sum('montant'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->valider($request);
        Delegue::create($data);

        return back()->with('success', "Délégué {$data['nom']} créé, code {$data['code']}.");
    }

    public function update(Request $request, Delegue $delegue)
    {
        $delegue->update($this->valider($request, $delegue));

        return back()->with('success', "Délégué {$delegue->nom} mis à jour.");
    }

    /** Tout ce qui est du a ce delegue est marque verse, aujourd'hui. */
    public function verser(Delegue $delegue)
    {
        $total = (int) CommissionDelegue::due()->where('delegue_id', $delegue->id)->sum('commission');
        CommissionDelegue::due()->where('delegue_id', $delegue->id)->update(['versee_le' => now()]);

        return back()->with('success', $total > 0
            ? number_format($total, 0, ',', ' ')." FCFA marqués comme versés à {$delegue->nom}."
            : "Rien à verser à {$delegue->nom}.");
    }

    private function valider(Request $request, ?Delegue $delegue = null): array
    {
        $request->merge(['code' => Delegue::normaliser($request->input('code')) ?? $request->input('code')]);

        $request->validate([
            'nom' => ['required', 'string', 'max:120'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'code' => ['required', 'string', 'regex:/^[A-Z0-9]{3,20}$/', Rule::unique('delegues', 'code')->ignore($delegue?->id)],
            'salle' => ['nullable', 'string', 'max:160'],
            'taux' => ['required', 'integer', 'min:0', 'max:100'],
            'reduction' => ['required', 'integer', 'min:0', 'max:90'],
            // Offre a prix fixe (QR code) : le prix du mois, et le prix affiche barre.
            'prix_mensuel' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'prix_barre' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'actif' => ['boolean'],
            'email' => ['nullable', 'email', Rule::exists('users', 'email')],
        ], [
            'code.regex' => 'Le code : 3 à 20 lettres ou chiffres, sans espace.',
            'code.unique' => 'Ce code est déjà pris par un autre délégué.',
            'email.exists' => "Aucun compte PrePla avec cet e-mail : le délégué doit d'abord s'inscrire, ou laisse ce champ vide.",
        ]);

        return $this->avecCompte($request);
    }

    private function avecCompte(Request $request): array
    {
        $data = $request->only(['nom', 'telephone', 'code', 'salle', 'taux', 'reduction']);
        foreach (['prix_mensuel', 'prix_barre'] as $champ) {
            $data[$champ] = filled($request->input($champ)) ? (int) $request->input($champ) : null;
        }
        $data['actif'] = $request->boolean('actif', true);
        $email = trim((string) $request->input('email'));
        $data['user_id'] = $email !== '' ? User::where('email', $email)->value('id') : null;

        return $data;
    }
}
