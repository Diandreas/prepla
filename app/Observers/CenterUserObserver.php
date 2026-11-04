<?php

namespace App\Observers;

use App\Models\CenterMembershipPeriod;
use App\Models\CenterUser;

/**
 * Tient a jour les periodes d'appartenance aux centres : une entree ouvre une
 * periode, une sortie la ferme, un changement de role ferme l'une et ouvre
 * l'autre.
 *
 * Seules les ecritures faites PAR LE MODELE passent ici. members()->attach()
 * et les suppressions par requete (->where()->delete()) l'evitent : les
 * controleurs passent donc par CenterUser::create() et $lien->delete(), et la
 * commande centres:actifs rattrape ce qui aurait echappe
 * (PeriodesAppartenance::reconcilier).
 */
class CenterUserObserver
{
    public function created(CenterUser $lien): void
    {
        $this->ouvrir($lien, $lien->joined_at ?? $lien->created_at ?? now());
    }

    public function updated(CenterUser $lien): void
    {
        if (! $lien->wasChanged('role')) {
            return;
        }

        $this->fermer($lien);
        $this->ouvrir($lien, now());
    }

    public function deleted(CenterUser $lien): void
    {
        $this->fermer($lien);
    }

    private function ouvrir(CenterUser $lien, $depuis): void
    {
        CenterMembershipPeriod::create([
            'center_id' => $lien->center_id,
            'user_id' => $lien->user_id,
            'role' => $lien->role,
            'started_at' => $depuis,
        ]);
    }

    private function fermer(CenterUser $lien): void
    {
        CenterMembershipPeriod::where('center_id', $lien->center_id)
            ->where('user_id', $lien->user_id)
            ->whereNull('ended_at')
            ->update(['ended_at' => now()]);
    }
}
