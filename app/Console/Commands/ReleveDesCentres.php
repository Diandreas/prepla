<?php

namespace App\Console\Commands;

use App\Services\Center\ReleveRevente;
use Illuminate\Console\Command;

/**
 * Releves mensuels des centres, le 1er du mois (routes/console.php), sur le
 * mois civil ecoule a Douala.
 *
 * Nature « revente » (lot C) : 30 % des acces ouverts dans le mois par chaque
 * centre en formule revente. Idempotente : relancer met a jour le brouillon et ne
 * cree pas de doublon ; un releve envoye ou paye n'est jamais recalcule.
 * Le lot D y ajoute la nature « commission ».
 */
class ReleveDesCentres extends Command
{
    protected $signature = 'centres:releves
        {--mois= : Mois a relever, au format YYYY-MM (par defaut : le mois ecoule a Douala)}';

    protected $description = 'Calcule les relevés mensuels des centres (part PrePla des accès vendus)';

    public function handle(ReleveRevente $revente): int
    {
        $mois = (string) ($this->option('mois') ?: ReleveRevente::moisPrecedent());

        if (! ReleveRevente::moisValide($mois)) {
            $this->error("Mois invalide : {$mois} (attendu : YYYY-MM).");

            return self::FAILURE;
        }

        if ($mois > ReleveRevente::moisCourant()) {
            $this->error("Le mois {$mois} n'a pas commencé.");

            return self::FAILURE;
        }

        $bilan = $revente->releverMois($mois);

        $this->info("Revente {$mois} : {$bilan['crees']} relevé(s) créé(s), {$bilan['mis_a_jour']} brouillon(s) mis à jour, {$bilan['figes']} relevé(s) envoyé(s) ou payé(s) laissé(s) tel(s) quel(s).");

        return self::SUCCESS;
    }
}
