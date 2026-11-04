<?php

namespace App\Console\Commands;

use App\Services\Center\ElevesActifs;
use App\Services\Center\PeriodesAppartenance;
use Illuminate\Console\Command;
use InvalidArgumentException;

/**
 * Releve mensuel des eleves actifs de chaque centre et de leur palier
 * (A1 jusqu'a 50, A2 jusqu'a 150, A3 jusqu'a 300, reseau au-dela).
 *
 * Planifiee le 1er du mois (routes/console.php) sur le mois ecoule, fuseau
 * Africa/Douala. Un centre deja releve pour ce mois n'est recalcule qu'avec
 * --forcer : le chiffre sert a facturer, il ne doit pas bouger tout seul.
 */
class ReleveActifs extends Command
{
    protected $signature = 'centres:actifs
        {--mois= : Le mois a relever, au format YYYY-MM (par defaut : le mois ecoule)}
        {--forcer : Recalculer meme les centres deja releves pour ce mois}';

    protected $description = 'Compte les eleves actifs de chaque centre sur un mois termine et en deduit le palier';

    public function handle(ElevesActifs $actifs, PeriodesAppartenance $periodes): int
    {
        $mois = $this->option('mois') ?: ElevesActifs::moisPrecedent();

        if (! ElevesActifs::moisValide($mois)) {
            $this->error("Mois invalide : {$mois}. Format attendu : YYYY-MM.");

            return self::FAILURE;
        }
        if (! ElevesActifs::estTermine($mois)) {
            $this->error("Le mois {$mois} n'est pas terminé à Douala : son relevé serait partiel.");

            return self::FAILURE;
        }

        // Rattrape les entrees et sorties que l'observer n'a pas vues.
        $ecarts = $periodes->reconcilier();
        if ($ecarts['ouvertes'] + $ecarts['fermees'] > 0) {
            $this->warn("Périodes d'appartenance réalignées : {$ecarts['ouvertes']} ouverte(s), {$ecarts['fermees']} fermée(s).");
        }

        try {
            $resultat = $actifs->releverMois($mois, (bool) $this->option('forcer'));
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Relevé {$mois} : {$resultat['calcules']} centre(s) calculé(s), {$resultat['ignores']} déjà relevé(s).");

        return self::SUCCESS;
    }
}
