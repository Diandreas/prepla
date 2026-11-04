<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Listeners\EnregistrerCommission;
use App\Models\CenterCommission;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Commissions de parrainage, pour le super administrateur : par centre et par
 * mois. Lecture seule ; le rapprochement et le paiement passent par le releve
 * mensuel (center_statements).
 */
class CommissionController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $periode = $request->string('periode')->toString();
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $periode)) {
            $periode = now(EnregistrerCommission::FUSEAU)->format('Y-m');
        }

        $parCentre = CenterCommission::query()
            ->where('periode', $periode)
            ->with('center:id,name')
            ->selectRaw('center_id, currency, count(*) as lignes, sum(amount_paid) as encaisse, sum(commission) as commission, count(distinct user_id) as comptes')
            ->groupBy('center_id', 'currency')
            ->get()
            ->map(fn ($r) => [
                'center_id' => $r->center_id,
                'centre' => $r->center?->name ?? '—',
                'devise' => $r->currency,
                'lignes' => (int) $r->lignes,
                'comptes' => (int) $r->comptes,
                'encaisse' => (int) $r->encaisse,
                'commission' => (int) $r->commission,
            ])
            ->sortBy('centre')
            ->values();

        $periodes = CenterCommission::query()->distinct()->orderByDesc('periode')->pluck('periode');
        if (! $periodes->contains($periode)) {
            $periodes->prepend($periode);
        }

        return Inertia::render('admin/commissions', [
            'periode' => $periode,
            'periodes' => $periodes->values(),
            'taux' => CenterCommission::TAUX,
            'parCentre' => $parCentre,
        ]);
    }
}
