<?php

namespace App\Services\Billing;

use App\Models\ActivationManuelle;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Ouvre ou prolonge l'acces PrePla Plus d'un compte paye par Mobile Money.
 *
 * Un acces encore en cours est PROLONGE, jamais ecrase : celui qui paie un
 * deuxieme mois avant la fin du premier ne perd pas les jours qui restent.
 */
class ActivationMobileMoney
{
    public const FORMULES = ['mensuel' => 30, 'trimestriel' => 90, 'annuel' => 365];

    public function activer(User $compte, User $auteur, string $formule, ?int $jours, string $moyen, ?int $montant, ?string $reference): ActivationManuelle
    {
        $duree = self::FORMULES[$formule] ?? max(1, (int) $jours);

        return DB::transaction(function () use ($compte, $auteur, $formule, $duree, $moyen, $montant, $reference) {
            $compte->refresh();
            $debut = $compte->aUnAccesMobileMoney() ? $compte->premium_until->copy() : now();
            $fin = $debut->copy()->addDays($duree);

            $compte->forceFill(['premium_until' => $fin])->save();

            return ActivationManuelle::create([
                'user_id' => $compte->id,
                'activated_by' => $auteur->id,
                'email' => $compte->email,
                'formule' => $formule,
                'jours' => $duree,
                'moyen' => $moyen,
                'montant' => $montant,
                'reference' => $reference,
                'debut' => $debut,
                'fin' => $fin,
            ]);
        });
    }

    /**
     * Annule une activation (erreur de compte, paiement non recu) : l'acces
     * recule d'autant, sans jamais revenir avant aujourd'hui.
     */
    public function annuler(ActivationManuelle $activation): void
    {
        if ($activation->annulee_le !== null) {
            return;
        }

        DB::transaction(function () use ($activation) {
            $compte = $activation->user;
            if ($compte && $compte->premium_until) {
                $recule = $compte->premium_until->copy()->subDays($activation->jours);
                $compte->forceFill(['premium_until' => $recule->lte(now()) ? null : $recule])->save();
            }
            $activation->update(['annulee_le' => now()]);
        });
    }
}
