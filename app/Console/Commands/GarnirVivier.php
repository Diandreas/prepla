<?php

namespace App\Console\Commands;

use App\Models\Exam;
use App\Models\ExamBlueprint;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Services\AI\ExerciseGeneratorService;
use App\Services\Content\ExerciseTypeSuitability;
use Illuminate\Console\Command;

/**
 * Garnit A L'AVANCE le vivier d'exercices libres de la page « Pratiquer ».
 *
 * La page cherche d'abord un exercice deja ecrit que l'apprenant n'a pas fait,
 * puis la serie preparee, et seulement en dernier recours demande a l'IA d'en
 * ecrire un. L'ordre est bon, mais le vivier etait presque vide hors du Goethe
 * A1 : un candidat telc B1 avait douze exercices pour cinq modules et une
 * vingtaine de tuiles de format. Chaque tuile tombait donc sur la generation en
 * direct — vingt secondes d'attente, et un ecran d'echec quand le quota est
 * epuise. D'ou l'impression, juste, de « presque aucun exercice type ».
 *
 * Cette commande fait ce travail hors ligne, une fois, pour que l'apprenant
 * trouve toujours quelque chose de pret.
 *
 * Elle ne fabrique pas le son : les exercices d'ecoute produits ici recoivent
 * leur enregistrement ensuite, par prepla:fabriquer-medias.
 */
class GarnirVivier extends Command
{
    protected $signature = 'prepla:garnir-vivier
        {--exam=* : limiter a ces examens (slug) ; par defaut tous ceux qui sont proposables}
        {--niveau=* : limiter a ces niveaux CECR}
        {--par-type=3 : nombre d exercices vises par type et par niveau}
        {--limite=0 : s arreter apres ce nombre d exercices ecrits}
        {--sec : annoncer ce qui manque, sans rien ecrire ni rien depenser}';

    protected $description = 'Ecrit a l avance les exercices libres de la page Pratiquer, type par type et niveau par niveau';

    public function handle(ExerciseGeneratorService $generateur, ExerciseTypeSuitability $pertinence): int
    {
        $sec = (bool) $this->option('sec');
        $cible = max(1, (int) $this->option('par-type'));
        $limite = (int) $this->option('limite');
        $niveauxVoulus = array_map('strtoupper', $this->option('niveau'));

        $examens = Exam::proposables()->with('language')
            ->when($this->option('exam'), fn ($q) => $q->whereIn('slug', $this->option('exam')))
            ->orderBy('slug')->get();

        if ($examens->isEmpty()) {
            $this->error('Aucun examen ne correspond.');

            return self::FAILURE;
        }

        $manquants = 0;
        $ecrits = 0;
        $echecs = [];

        foreach ($examens as $exam) {
            $plans = ExamBlueprint::where('exam_id', $exam->id)->whereNotNull('level')->get();
            if ($plans->isEmpty()) {
                continue;
            }

            $this->newLine();
            $this->line('<fg=cyan>'.$exam->slug.'</> — '.$exam->name);

            foreach ($plans as $plan) {
                $niveau = $plan->level;
                if ($niveauxVoulus !== [] && ! in_array(strtoupper($niveau), $niveauxVoulus, true)) {
                    continue;
                }

                // Seuls les modules que l'examen declare A CE NIVEAU : les
                // Sprachbausteine n'existent pas au telc A1, et rien ne sert de
                // garnir un module que l'apprenant n'y verra jamais.
                $slugs = collect($plan->sections_config ?? [])->pluck('slug')->filter()->all();
                $types = ExerciseType::whereHas('section', fn ($q) => $q->where('exam_id', $exam->id)
                    ->when($slugs, fn ($q) => $q->whereIn('slug', $slugs)))
                    ->with('section:id,slug')->get();

                foreach ($types as $type) {
                    // Le meme filtre que la page : un A1 n'a pas a recevoir
                    // « decrivez l'evolution du chomage ».
                    if (! $pertinence->convient($type, $niveau)) {
                        continue;
                    }

                    $dejaLa = Exercise::where('exam_id', $exam->id)
                        ->where('exercise_type_id', $type->id)
                        ->where('difficulty', $niveau)
                        ->whereNull('center_id')->whereNull('lesson_id')
                        ->whereNull('node_id')->whereNull('mock_exam_id')
                        ->count();

                    $aEcrire = max(0, $cible - $dejaLa);
                    if ($aEcrire === 0) {
                        continue;
                    }
                    $manquants += $aEcrire;

                    if ($sec) {
                        $this->line('  '.$niveau.' '.$type->section?->slug.'/'.$type->slug.' : '.$dejaLa.' → '.$cible);

                        continue;
                    }

                    for ($i = 0; $i < $aEcrire; $i++) {
                        if ($limite > 0 && $ecrits >= $limite) {
                            $this->comment('Limite atteinte.');
                            break 4;
                        }

                        try {
                            $generateur->generate($type, $exam, $niveau);
                            $ecrits++;
                            $this->line('  <fg=green>+</> '.$niveau.' '.$type->section?->slug.'/'.$type->slug);
                        } catch (\Throwable $e) {
                            $echecs[] = $exam->slug.' '.$niveau.' '.$type->slug.' : '.$e->getMessage();
                            // Un type qui refuse une fois refusera les suivantes :
                            // inutile de redepenser le quota pour le meme echec.
                            break;
                        }
                    }
                }
            }
        }

        $this->newLine();
        $this->info($sec
            ? $manquants.' exercice(s) manquent pour atteindre '.$cible.' par type et par niveau'
            : $ecrits.' exercice(s) ecrit(s) sur '.$manquants.' attendu(s)');

        if ($echecs !== []) {
            $this->warn(count($echecs).' echec(s) :');
            foreach (array_slice($echecs, 0, 10) as $e) {
                $this->line('  '.$e);
            }
        }

        return self::SUCCESS;
    }
}
