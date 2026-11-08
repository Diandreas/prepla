<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivationManuelle;
use App\Models\User;
use App\Services\Billing\ActivationMobileMoney;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * L'activation des comptes payes par Orange Money ou MTN MoMo : l'apprenant
 * envoie sa capture sur WhatsApp, l'equipe colle son e-mail ici et ouvre
 * l'acces en quelques secondes.
 */
class ActivationController extends Controller
{
    public function index(Request $request): Response
    {
        $email = trim((string) $request->query('email', ''));
        $compte = $email !== '' ? User::where('email', $email)->first() : null;

        return Inertia::render('admin/activations', [
            'recherche' => $email,
            'compte' => $compte ? [
                'id' => $compte->id,
                'name' => $compte->name,
                'email' => $compte->email,
                'inscrit_le' => $compte->created_at?->toIso8601String(),
                'premium_until' => $compte->premium_until?->toIso8601String(),
                'acces_actif' => $compte->hasPremiumAccess(),
                'abonne_stripe' => $compte->subscribed('default'),
            ] : null,
            'formules' => ActivationMobileMoney::FORMULES,
            'prix' => config('prepla.mobile_money.prix'),
            'activations' => ActivationManuelle::with('auteur:id,name')->latest('id')->take(30)->get()
                ->map(fn (ActivationManuelle $a) => [
                    'id' => $a->id,
                    'email' => $a->email,
                    'formule' => $a->formule,
                    'jours' => $a->jours,
                    'moyen' => $a->moyen,
                    'montant' => $a->montant,
                    'reference' => $a->reference,
                    'fin' => $a->fin->toIso8601String(),
                    'le' => $a->created_at?->toIso8601String(),
                    'par' => $a->auteur?->name,
                    'annulee' => $a->annulee_le !== null,
                ]),
        ]);
    }

    public function store(Request $request, ActivationMobileMoney $service)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'formule' => ['required', Rule::in([...array_keys(ActivationMobileMoney::FORMULES), 'jours'])],
            'jours' => ['nullable', 'required_if:formule,jours', 'integer', 'min:1', 'max:730'],
            'moyen' => ['required', Rule::in(['orange', 'mtn', 'autre'])],
            'montant' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'reference' => ['nullable', 'string', 'max:120'],
        ]);

        $compte = User::where('email', trim($data['email']))->first();
        if (! $compte) {
            return back()->withErrors(['email' => "Aucun compte avec l'e-mail « {$data['email']} ». Vérifie l'orthographe, ou demande à la personne de s'inscrire d'abord."]);
        }

        $activation = $service->activer($compte, $request->user(), $data['formule'], isset($data['jours']) ? (int) $data['jours'] : null,
            $data['moyen'], isset($data['montant']) ? (int) $data['montant'] : null, $data['reference'] ?? null);

        return redirect()->route('admin.activations.index', ['email' => $compte->email])
            ->with('success', "Accès PrePla Plus ouvert pour {$compte->email} jusqu'au ".$activation->fin->locale('fr')->isoFormat('D MMMM YYYY').'.');
    }

    public function destroy(ActivationManuelle $activation, ActivationMobileMoney $service)
    {
        $service->annuler($activation);

        return back()->with('success', "Activation annulée pour {$activation->email}.");
    }
}
