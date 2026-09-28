<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Contrôle d'après-déploiement.
 *
 * Le .env n'est pas versionné et une restauration du VPS l'a déjà effacé deux fois :
 * le site répondait 200 pendant que le tuteur, la voix et les prix étaient muets. Une
 * page servie ne prouve rien. Cette commande dit ce qui manque, tout de suite, au lieu
 * de laisser un apprenant le découvrir.
 */
class CheckDeploymentCommand extends Command
{
    protected $signature = 'prepla:check';

    protected $description = "Vérifie qu'un déploiement est réellement fonctionnel (clés, base, assets, droits).";

    /** Sans ces réglages, le produit ne rend pas le service promis. */
    private const REQUIRED = [
        'services.mistral.api_key' => 'MISTRAL_API_KEY — tuteur IA, génération de leçons et d\'exercices',
        'services.deepgram.api_key' => 'DEEPGRAM_API_KEY — synthèse vocale et transcription orale',
        'services.stripe.prices.monthly' => 'STRIPE_PRICE_MONTHLY — prix mensuel affiché',
        'services.stripe.prices.annual' => 'STRIPE_PRICE_ANNUAL — prix annuel affiché',
    ];

    public function handle(): int
    {
        $problems = [];

        foreach (self::REQUIRED as $key => $why) {
            if (blank(config($key))) {
                $problems[] = "Réglage absent : {$why}";
            }
        }

        if (app()->environment('production') && config('app.debug')) {
            $problems[] = 'APP_DEBUG est actif en production : une erreur exposerait le code et les réglages.';
        }

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $problems[] = 'Base de données injoignable : ' . $e->getMessage();
        }

        $manifest = public_path('build/manifest.json');
        if (!File::exists($manifest)) {
            $problems[] = 'Aucun manifeste Vite dans public/build : le navigateur servira les anciens bundles.';
        }

        // Les audios d'exercice passent par ce lien ; nginx tourne avec
        // disable_symlinks if_not_owner, donc un lien absent ou mal possédé les rend
        // introuvables, en silence.
        if (!File::exists(public_path('storage'))) {
            $problems[] = 'Le lien public/storage est absent : les audios des exercices renverront 404.';
        }

        foreach (['storage/logs', 'storage/framework', 'bootstrap/cache'] as $path) {
            if (!is_writable(base_path($path))) {
                $problems[] = "Dossier non inscriptible : {$path}";
            }
        }

        if ($problems === []) {
            $this->info('Déploiement vérifié : clés, base, assets et droits en place.');

            return self::SUCCESS;
        }

        $this->error(count($problems) . ' problème(s) détecté(s) :');
        foreach ($problems as $problem) {
            $this->line('  - ' . $problem);
        }

        return self::FAILURE;
    }
}
