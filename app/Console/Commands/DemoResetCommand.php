<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Wrapper convivial autour de `db:seed --class=B2BDemoSeeder` — plus simple à
 * documenter/donner au commercial qu'une syntaxe db:seed à retenir, et laisse
 * la porte ouverte à des étapes de nettoyage futures (médias, cache) sans
 * toucher à la commande db:seed standard.
 */
class DemoResetCommand extends Command
{
    protected $signature = 'demo:reset {--force : Ne pas demander de confirmation}';

    protected $description = 'Réinitialise la démo B2B "Institut Linguae" (centre, classes, élèves, devoirs) avec des données propres.';

    public function handle(): int
    {
        if (
            ! $this->option('force')
            && app()->environment('production')
            && ! $this->confirm('Vous êtes en environnement de PRODUCTION. Réinitialiser quand même la démo B2B ?', false)
        ) {
            $this->info('Annulé.');
            return self::SUCCESS;
        }

        // --force propagé : SeedCommand a son propre garde-fou de confirmation
        // en prod (ConfirmableTrait), distinct de celui ci-dessus, qui bloque
        // sinon silencieusement en environnement non-interactif (SSH batch).
        $this->call('db:seed', ['--class' => 'Database\\Seeders\\B2BDemoSeeder', '--force' => true]);

        return self::SUCCESS;
    }
}
