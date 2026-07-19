<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // B2BDemoSeeder est VOLONTAIREMENT exclu de cet appel automatique : il crée
        // un faux centre/staff/élèves et ne doit jamais s'exécuter via un
        // migrate:fresh --seed lancé par erreur sur un environnement partagé.
        // Lancer manuellement : php artisan demo:reset (voir docs/demo-b2b-guide.md).
        $this->call([
            LanguageSeeder::class,
            ExamSeeder::class,
            ExamSyllabusSeeder::class,
            AchievementSeeder::class,
        ]);
    }
}
