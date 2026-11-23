<?php

namespace App\Console\Commands;

use App\Models\Exam;
use App\Support\MarqueInstance;
use Illuminate\Console\Command;

/**
 * Affiche la marque de l'instance (config/marque.php) et signale ce qui manque :
 * fichiers publics introuvables, examens inconnus, couleurs mal ecrites.
 */
class VerifierMarque extends Command
{
    protected $signature = 'marque:verifier';

    protected $description = "Affiche la marque active de l'instance et signale les fichiers manquants";

    public function handle(): int
    {
        $i = MarqueInstance::pourFront();
        $this->info('Marque active : '.$i['nom'].($i['personnalisee'] ? ' (marque blanche)' : ' (PrePla par defaut)'));

        $this->table(['Reglage', 'Valeur'], [
            ['Nom court', $i['nom_court']],
            ['Slogan', $i['slogan']],
            ['Couleur primaire', $i['couleurs']['primaire']],
            ['Couleur secondaire', $i['couleurs']['secondaire']],
            ['E-mail de contact', $i['email_contact']],
            ['Telephone / WhatsApp', $i['telephone']],
            ['Site vitrine', $i['site'] ?: '-'],
            ['Langues', $i['langues_interface'] ? implode(', ', $i['langues_interface']) : 'toutes'],
            ['Langue par defaut', $i['langue_defaut'] ?: '-'],
            ['Examens', $i['examens'] ? implode(', ', $i['examens']) : 'tous'],
            ['Masquer Kids', $i['masquer_kids'] ? 'oui' : 'non'],
            ['Masquer prix publics', $i['masquer_prix_publics'] ? 'oui' : 'non'],
            ['Mascotte', $i['mascotte'] ? 'oui' : 'non'],
            ['Manifeste', MarqueInstance::urlDuManifeste()],
        ]);

        $problemes = 0;

        foreach (MarqueInstance::fichiers() as $cle => $chemin) {
            $fichier = public_path(ltrim((string) strtok($chemin, '?'), '/'));
            if (! is_file($fichier)) {
                $this->warn("Fichier manquant ($cle) : $chemin");
                $problemes++;
            }
        }

        foreach (['couleur_primaire', 'couleur_secondaire'] as $cle) {
            if (! preg_match('/^#[0-9A-Fa-f]{6}$/', (string) config("marque.$cle"))) {
                $this->warn("Couleur invalide ($cle) : ".config("marque.$cle").' (format #RRGGBB attendu)');
                $problemes++;
            }
        }

        if ($i['examens'] !== []) {
            try {
                $connus = Exam::whereIn('slug', $i['examens'])->pluck('slug')->all();
                foreach (array_diff($i['examens'], $connus) as $inconnu) {
                    $this->warn("Examen inconnu dans MARQUE_EXAMENS : $inconnu");
                    $problemes++;
                }
            } catch (\Throwable) {
                $this->warn('Base indisponible : examens non verifies.');
            }
        }

        foreach ($i['langues_interface'] as $langue) {
            if (! in_array($langue, ['fr', 'en', 'de'], true)) {
                $this->warn("Langue non prise en charge : $langue (fr, en, de)");
                $problemes++;
            }
        }

        $problemes === 0 ? $this->info('Aucun probleme.') : $this->error("$problemes probleme(s).");

        return $problemes === 0 ? self::SUCCESS : self::FAILURE;
    }
}
