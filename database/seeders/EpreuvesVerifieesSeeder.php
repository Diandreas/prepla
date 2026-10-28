<?php

namespace Database\Seeders;

use App\Services\Content\BibliothequeEpreuvesVerifiees;
use Illuminate\Database\Seeder;

/**
 * Met en base les epreuves ecrites a la main.
 *
 * Ne supprime rien : chaque partie est identifiee par son `catalog_key`, donc
 * relancer le seeder met a jour les memes lignes au lieu d'en creer d'autres.
 * Peut tourner en production.
 *
 * Les parties de comprehension orale arrivent sans son. Enchainer ensuite :
 *   php artisan prepla:fabriquer-medias --son
 */
class EpreuvesVerifieesSeeder extends Seeder
{
    public function run(): void
    {
        $bibliotheque = app(BibliothequeEpreuvesVerifiees::class);
        $modules = $bibliotheque->modules();

        if ($modules === []) {
            $this->command?->warn('Aucune epreuve ecrite a la main trouvee.');

            return;
        }

        $total = 0;
        $refuses = [];

        foreach ($modules as $cle => $module) {
            ['ecrits' => $ecrits, 'refus' => $refus] = $bibliotheque->installe($module);
            $total += $ecrits;
            $refuses = array_merge($refuses, $refus);
            $this->command?->line(sprintf('  %-28s %2d partie(s)', $cle, $ecrits));
        }

        foreach ($refuses as $refus) {
            $this->command?->error('  refuse : '.$refus);
        }

        $this->command?->info(sprintf('%d partie(s) en base, %d refusee(s).', $total, count($refuses)));
    }
}
