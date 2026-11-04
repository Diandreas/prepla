<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\CenterStatement;
use App\Models\LanguageCenter;
use App\Services\Center\ReleveRevente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Onglet « Revente » des releves (super administrateur seul, groupe admin.*) :
 * par mois, la part PrePla des acces vendus par chaque centre, et l'etat du
 * releve (brouillon, envoye, paye).
 *
 * Le mois en cours est calcule a la volee, sans rien ecrire ; un mois passe
 * affiche ce qu'a ecrit la commande centres:releves.
 */
class ReleveReventeController extends Controller
{
    public function index(Request $request, ReleveRevente $revente): Response
    {
        $courant = ReleveRevente::moisCourant();
        $mois = (string) $request->query('mois', '');
        if (! ReleveRevente::moisValide($mois) || $mois > $courant) {
            $mois = ReleveRevente::moisPrecedent();
        }

        $enCours = $mois === $courant;

        $releves = $enCours
            ? $this->aLaVolee($revente, $mois)
            : CenterStatement::with('center:id,name,slug')
                ->where('nature', ReleveRevente::NATURE)
                ->where('periode', $mois)
                ->orderBy('center_id')
                ->get()
                ->map(fn (CenterStatement $r) => [
                    'id' => $r->id,
                    'centre' => $r->center?->name,
                    'centre_id' => $r->center_id,
                    'montant_brut' => $r->montant_brut,
                    'part_prepla' => $r->part_prepla,
                    'statut' => $r->statut,
                    'computed_at' => $r->computed_at?->toIso8601String(),
                    'lignes' => $r->lignes ?? [],
                ])->values()->all();

        $moisDisponibles = CenterStatement::where('nature', ReleveRevente::NATURE)
            ->distinct()->pluck('periode')
            ->push($courant, ReleveRevente::moisPrecedent(), $mois)
            ->unique()->sortDesc()->values()->all();

        return Inertia::render('admin/releves-revente', [
            'mois' => $mois,
            'en_cours' => $enCours,
            'mois_disponibles' => $moisDisponibles,
            'taux' => LanguageCenter::PART_PREPLA_REVENTE,
            'releves' => $releves,
            'statuts' => CenterStatement::STATUTS,
        ]);
    }

    /** Recalcule un mois (meme regle que la commande : les brouillons seulement). */
    public function calculer(Request $request, ReleveRevente $revente): RedirectResponse
    {
        $valide = $request->validate([
            'mois' => ['required', 'string', 'regex:/^20\d\d-(0[1-9]|1[0-2])$/'],
        ]);

        if ($valide['mois'] > ReleveRevente::moisCourant()) {
            return back()->with('error', "Le mois {$valide['mois']} n'a pas commencé.");
        }

        $b = $revente->releverMois($valide['mois']);

        return back()->with('success', "Revente {$valide['mois']} : {$b['crees']} relevé(s) créé(s), {$b['mis_a_jour']} mis à jour, {$b['figes']} déjà envoyé(s) ou payé(s), laissé(s) tel(s) quel(s).");
    }

    /** Brouillon -> envoye -> paye. Un releve envoye ou paye n'est plus recalcule. */
    public function statut(Request $request, int $statement): RedirectResponse
    {
        $releve = CenterStatement::where('nature', ReleveRevente::NATURE)->findOrFail($statement);

        $valide = $request->validate([
            'statut' => ['required', Rule::in(CenterStatement::STATUTS)],
        ]);

        $releve->update(['statut' => $valide['statut']]);

        return back()->with('success', 'Statut du relevé mis à jour.');
    }

    /** @return array<int, array<string, mixed>> */
    private function aLaVolee(ReleveRevente $revente, string $mois): array
    {
        return $revente->centresConcernes($mois)
            ->map(fn (LanguageCenter $c) => ['id' => null, 'centre' => $c->name, 'centre_id' => $c->id, 'statut' => null, 'computed_at' => null]
                + $revente->calculer($c->id, $mois))
            ->values()->all();
    }
}
