<?php

namespace App\Services\Center;

use App\Models\CenterMembershipPeriod;
use App\Models\CenterUser;
use Illuminate\Support\Facades\DB;

/**
 * Filet de securite des periodes d'appartenance.
 *
 * CenterUserObserver ne voit que les ecritures faites par le modele. Une
 * appartenance creee par members()->attach(), ou supprimee par une requete
 * directe (le seeder de demonstration, une cascade en base), lui echappe.
 * Avant chaque releve, on aligne donc les periodes ouvertes sur center_user :
 * - une appartenance sans periode ouverte de meme role en recoit une, depuis
 *   joined_at (ou created_at) ;
 * - une periode ouverte sans appartenance correspondante est fermee
 *   maintenant : l'heure reelle du depart n'a pas ete vue.
 */
class PeriodesAppartenance
{
    /** @return array{ouvertes: int, fermees: int} */
    public function reconcilier(): array
    {
        $ouvertes = 0;
        $fermees = 0;

        DB::transaction(function () use (&$ouvertes, &$fermees) {
            $periodes = CenterMembershipPeriod::whereNull('ended_at')->get()
                ->groupBy(fn ($p) => $p->center_id.'-'.$p->user_id.'-'.$p->role);
            $liens = CenterUser::all()->keyBy(fn ($l) => $l->center_id.'-'.$l->user_id.'-'.$l->role);

            foreach ($liens as $cle => $lien) {
                if (! $periodes->has($cle)) {
                    CenterMembershipPeriod::create([
                        'center_id' => $lien->center_id,
                        'user_id' => $lien->user_id,
                        'role' => $lien->role,
                        'started_at' => $lien->joined_at ?? $lien->created_at ?? now(),
                    ]);
                    $ouvertes++;
                }
            }

            foreach ($periodes as $cle => $groupe) {
                if (! $liens->has($cle)) {
                    CenterMembershipPeriod::whereKey($groupe->pluck('id'))->update(['ended_at' => now()]);
                    $fermees += $groupe->count();
                }
            }
        });

        return ['ouvertes' => $ouvertes, 'fermees' => $fermees];
    }
}
