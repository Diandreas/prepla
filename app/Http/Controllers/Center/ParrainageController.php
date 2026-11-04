<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use App\Listeners\EnregistrerCommission;
use App\Models\CenterCommission;
use App\Models\LanguageCenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Le parrainage, vu par le responsable du centre : son lien, les comptes qu'il
 * a amenes, et les commissions du mois.
 *
 * Tout est lu depuis le centre injecte par EnsureCenterStaff, jamais depuis un
 * identifiant de l'URL : un centre ne voit que ses propres chiffres.
 */
class ParrainageController extends Controller
{
    public function __invoke(Request $request): Response
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');

        // Reserve au responsable, comme l'Equipe : un enseignant n'a pas a voir l'argent du centre.
        abort_unless($request->user()->centerRoleIs('center_admin'), 403);

        $periode = now(EnregistrerCommission::FUSEAU)->format('Y-m');

        $lignes = CenterCommission::where('center_id', $center->id)
            ->where('periode', $periode)
            ->orderByDesc('id')
            ->get(['id', 'amount_paid', 'currency', 'commission', 'type', 'created_at']);

        // Une ligne par devise : on n'additionne pas des euros et des francs.
        $totaux = $lignes->groupBy('currency')->map(fn ($l, $devise) => [
            'devise' => $devise,
            'encaisse' => (int) $l->sum('amount_paid'),
            'commission' => (int) $l->sum('commission'),
        ])->values();

        return Inertia::render('center/parrainage', [
            'centre' => ['id' => $center->id, 'name' => $center->name],
            'lien' => route('parrainage.lien', $center->codeParrainage()),
            'code' => $center->codeParrainage(),
            'taux' => CenterCommission::TAUX,
            'comptesParraines' => $center->comptesParraines()->count(),
            'periode' => $periode,
            'totaux' => $totaux,
            // Pas de nom ni d'adresse : le centre voit le montant, pas qui a paye.
            'lignes' => $lignes->map(fn (CenterCommission $c) => [
                'id' => $c->id,
                'type' => $c->type,
                'encaisse' => $c->amount_paid,
                'commission' => $c->commission,
                'devise' => $c->currency,
                'date' => $c->created_at?->toIso8601String(),
            ])->values(),
        ]);
    }
}
