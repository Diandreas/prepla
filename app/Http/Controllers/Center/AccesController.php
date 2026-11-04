<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use App\Models\CenterAccessGrant;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Services\Center\ReleveRevente;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Les acces vendus par un centre en formule « revente » a ses eleves.
 *
 * Le centre encaisse lui-meme (mobile money, especes) et ouvre l'acces ici ;
 * l'eleve n'a plus de limite d'exercices jusqu'a la date de fin. PrePla preleve
 * 30 % des montants ouverts dans le mois (commande centres:releves).
 *
 * Reserve au responsable : EnsureCenterStaff laisse passer un enseignant. Le
 * centre vient toujours de la requete (centre courant), jamais de l'URL : un
 * centre ne lit ni ne coupe les acces d'un autre.
 */
class AccesController extends Controller
{
    public function index(Request $request, ReleveRevente $releve): Response
    {
        $center = $this->centreDuResponsable($request);
        $mois = ReleveRevente::moisCourant();

        $acces = CenterAccessGrant::where('center_id', $center->id)
            ->nonTermines()
            ->orderBy('starts_at')
            ->get()
            ->groupBy('user_id');

        $eleves = $center->students()
            ->orderBy('users.name')
            ->get(['users.id', 'users.name', 'users.email'])
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'acces' => ($acces[$u->id] ?? collect())->map(fn (CenterAccessGrant $g) => [
                    'id' => $g->id,
                    'du' => $g->starts_at->toIso8601String(),
                    'au' => $g->ends_at->toIso8601String(),
                    'en_cours' => $g->estEnCours(),
                ])->values(),
            ]);

        $encaissements = $releve->calculer($center->id, $mois);

        return Inertia::render('center/acces', [
            'centre' => [
                'id' => $center->id,
                'name' => $center->name,
                'formule' => $center->formule(),
                'prix_acces' => $center->prix_acces,
                'duree_acces_jours' => $center->dureeAcces(),
                'devise' => CenterAccessGrant::DEVISE,
            ],
            'eleves' => $eleves,
            'mois' => [
                'periode' => $mois,
                'total' => $encaissements['montant_brut'],
                'part_prepla' => $encaissements['part_prepla'],
                'taux' => LanguageCenter::PART_PREPLA_REVENTE,
                'lignes' => $encaissements['lignes'],
            ],
            'moyens' => CenterAccessGrant::MOYENS_DE_PAIEMENT,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $center = $this->centreDuResponsable($request);

        if (! $center->estEnRevente()) {
            return back()->with('error', "La formule de votre centre ne prévoit pas d'accès vendus : vos élèves n'ont déjà pas de limite.");
        }

        $valide = $request->validate([
            'user_id' => ['required', 'integer'],
            'duree_jours' => ['required', 'integer', 'min:1', 'max:366'],
            'montant' => ['required', 'integer', 'min:0', 'max:10000000'],
            'moyen' => ['required', Rule::in(CenterAccessGrant::MOYENS_DE_PAIEMENT)],
            'reference' => ['nullable', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        // Un eleve DE CE CENTRE, et rien d'autre : ni le personnel (qui s'ouvrirait
        // un acces a lui-meme), ni le compte d'un autre centre. Meme message dans
        // tous les cas, pour ne rien apprendre des comptes des autres.
        if (! $center->aPourEleve((int) $valide['user_id'])) {
            throw ValidationException::withMessages([
                'user_id' => "Cette personne n'est pas élève de votre centre : on ne peut lui ouvrir un accès.",
            ]);
        }

        // Un acces deja en cours est prolonge : le nouveau commence a sa fin, on ne
        // perd pas les jours deja payes.
        $finActuelle = CenterAccessGrant::where('center_id', $center->id)
            ->where('user_id', $valide['user_id'])
            ->nonTermines()
            ->max('ends_at');
        $debut = CarbonImmutable::now();
        if ($finActuelle && ($fin = CarbonImmutable::parse($finActuelle))->gt($debut)) {
            $debut = $fin;
        }

        $acces = CenterAccessGrant::create([
            'center_id' => $center->id,
            'user_id' => $valide['user_id'],
            'starts_at' => $debut,
            'ends_at' => $debut->addDays((int) $valide['duree_jours']),
            'amount' => (int) $valide['montant'],
            'currency' => CenterAccessGrant::DEVISE,
            'payment_method' => $valide['moyen'],
            'reference' => $valide['reference'] ?? null,
            'note' => $valide['note'] ?? null,
            'granted_by' => $request->user()->id,
        ]);

        Log::info('acces-vendus: acces ouvert', [
            'acces_id' => $acces->id, 'center_id' => $center->id, 'user_id' => $acces->user_id,
            'par' => $request->user()->id, 'montant' => $acces->amount, 'jusqu_au' => $acces->ends_at->toIso8601String(),
        ]);

        $nom = User::find($acces->user_id)?->name ?? 'l\'élève';

        return back()->with('success', "Accès ouvert pour {$nom} jusqu'au {$acces->ends_at->setTimezone(ReleveRevente::FUSEAU)->format('d/m/Y')}.");
    }

    public function couper(Request $request, int $grant): RedirectResponse
    {
        $center = $this->centreDuResponsable($request);

        // Cherche DANS ce centre : l'acces d'un autre centre n'existe pas ici (404).
        $acces = CenterAccessGrant::where('center_id', $center->id)->findOrFail($grant);

        if ($acces->revoked_at !== null) {
            return back()->with('error', 'Cet accès est déjà coupé.');
        }

        if ($acces->ends_at->lte(now())) {
            return back()->with('error', 'Cet accès est déjà terminé.');
        }

        $valide = $request->validate(['note' => ['nullable', 'string', 'max:255']]);

        $acces->update([
            'revoked_at' => now(),
            'revoked_by' => $request->user()->id,
            'note' => $valide['note'] ?? $acces->note,
        ]);

        Log::info('acces-vendus: acces coupe', [
            'acces_id' => $acces->id, 'center_id' => $center->id, 'user_id' => $acces->user_id,
            'par' => $request->user()->id,
        ]);

        return back()->with('success', 'Accès coupé.');
    }

    /** Le centre courant, pour son responsable seulement. */
    private function centreDuResponsable(Request $request): LanguageCenter
    {
        abort_unless($request->user()->centerRoleIs('center_admin'), 403);

        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');

        return $center;
    }
}
