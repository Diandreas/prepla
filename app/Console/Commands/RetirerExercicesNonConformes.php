<?php

namespace App\Console\Commands;

use App\Models\Exam;
use App\Models\ExamBlueprint;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\UserExerciseAttempt;
use App\Services\Content\ExerciseSchemaRegistry;
use App\Services\Content\StructureOfficielle;
use Illuminate\Console\Command;

/**
 * Retire les exercices d'entrainement qui n'ont pas le format de leur epreuve.
 *
 * Des exercices ont ete produits avant que le nombre d'items officiel ne soit
 * impose au generateur : un QCM de trois questions la ou le Goethe B1 Lesen
 * Teil 2 en compte six. Ils ne sont plus servis qu'a defaut, mais ils occupent
 * le vivier et faussent tout comptage.
 *
 * DEUX REGLES, et elles ne se discutent pas :
 *
 * 1. On ne touche JAMAIS a un exercice qu'un apprenant a ouvert. Sa tentative y
 *    renvoie, son historique le cite, sa revision le reprend. Le supprimer
 *    effacerait du travail reel.
 * 2. Rien n'est supprime sans que la commande l'ait d'abord annonce. Le mode a
 *    blanc est le defaut : il faut `--vraiment` pour ecrire.
 *
 * Il n'y a rien a regenerer derriere : l'application fabrique a la demande, et
 * elle fabrique desormais au bon format.
 */
class RetirerExercicesNonConformes extends Command
{
    protected $signature = 'prepla:retirer-non-conformes
        {--examen= : se limiter a un examen}
        {--vraiment : supprimer pour de bon ; sans cette option, rien n est ecrit}
        {--meme-tentes : retirer AUSSI ceux qu un apprenant a ouverts, et leurs tentatives}
        {--limite=0 : s arreter apres ce nombre d exercices}';

    protected $description = 'Retire les exercices d entrainement qui n ont pas le nombre d items de leur partie';

    public function handle(StructureOfficielle $structure): int
    {
        $pourDeBon = (bool) $this->option('vraiment');
        $memeTentes = (bool) $this->option('meme-tentes');
        $limite = (int) $this->option('limite');

        $aRetirer = [];
        $proteges = 0;

        $examens = Exam::query()
            ->when($this->option('examen'), fn ($q) => $q->where('slug', $this->option('examen')))
            ->orderBy('slug')->get();

        foreach ($examens as $exam) {
            $niveaux = ExamBlueprint::where('exam_id', $exam->id)->whereNotNull('level')
                ->pluck('level')->unique()->values()->all() ?: [null];

            foreach ($niveaux as $niveau) {
                foreach ($structure->parties($exam, $niveau) as $partie) {
                    if (! $partie['exercise_type_id'] || empty($partie['items'])) {
                        continue;
                    }

                    // ON NE SUPPRIME QUE SUR LA FOI D'UNE STRUCTURE VERIFIEE.
                    // Une partie marquee « tache reconstituee » porte un nombre
                    // d'items que personne n'a lu dans le document de
                    // l'organisme — le TCF en annonce 29 pour une partie, ce
                    // qui est le total du module, pas la longueur d'un
                    // exercice. Effacer du contenu sur cette base detruirait du
                    // bon travail a cause de notre propre approximation.
                    if (($partie['source'] ?? null) !== 'officiel') {
                        continue;
                    }
                    $type = ExerciseType::find($partie['exercise_type_id']);
                    if (! $type) {
                        continue;
                    }

                    $exos = Exercise::with('exerciseType')
                        ->where('exam_id', $exam->id)
                        ->where('exercise_type_id', $type->id)
                        ->where('difficulty', $niveau)
                        ->whereNull('lesson_id')->whereNull('node_id')->whereNull('mock_exam_id')
                        ->get();

                    foreach ($exos as $exo) {
                        if (isset($aRetirer[$exo->id])) {
                            continue;
                        }
                        $items = $this->itemsDe($exo);
                        // Un exercice peut servir plusieurs parties du meme type :
                        // il est conforme s'il correspond a l'UNE d'elles.
                        if (in_array($items, $structure->itemsAttendus($exam, $niveau, $type), true)) {
                            continue;
                        }
                        if (! $memeTentes && UserExerciseAttempt::where('exercise_id', $exo->id)->exists()) {
                            $proteges++;

                            continue;
                        }

                        $aRetirer[$exo->id] = sprintf('  #%-6d %-9s %-3s %-20s %d item(s) au lieu de %s',
                            $exo->id, $exam->slug, $niveau ?? '—', $type->slug, $items,
                            implode(' ou ', $structure->itemsAttendus($exam, $niveau, $type)));
                    }
                }
            }
        }

        if ($limite > 0) {
            $aRetirer = array_slice($aRetirer, 0, $limite, true);
        }

        $this->newLine();
        foreach (array_slice($aRetirer, 0, 15) as $ligne) {
            $this->line($ligne);
        }
        if (count($aRetirer) > 15) {
            $this->line('  … et '.(count($aRetirer) - 15).' autre(s)');
        }

        $this->newLine();
        if ($proteges > 0) {
            $this->line("<fg=yellow>{$proteges} exercice(s) non conforme(s) gardes : un apprenant les a ouverts.</>");
        }

        // CE QUI PART AVEC. La cle etrangere des tentatives est en cascade :
        // supprimer un exercice efface le travail que des apprenants y ont fait.
        // On le compte et on l'annonce, toujours, avant d'ecrire.
        $ids = array_keys($aRetirer);
        $tentatives = UserExerciseAttempt::whereIn('exercise_id', $ids)->count();
        $apprenants = UserExerciseAttempt::whereIn('exercise_id', $ids)
            ->distinct('user_id')->count('user_id');

        if ($tentatives > 0) {
            $this->line("<fg=red>{$tentatives} tentative(s) de {$apprenants} apprenant(s) seront effacees avec ces exercices.</>");
        }

        if (! $pourDeBon) {
            $this->info(count($aRetirer).' exercice(s) seraient retires. Relancer avec --vraiment pour le faire.');

            return self::SUCCESS;
        }

        Exercise::whereIn('id', $ids)->delete();
        $this->info(count($aRetirer).' exercice(s) retires'
            .($tentatives > 0 ? ", et {$tentatives} tentative(s) avec eux." : '.'));

        return self::SUCCESS;
    }

    /** Un item n'est pas toujours une question : voir prepla:audit-structure. */
    private function itemsDe(Exercise $exo): int
    {
        $questions = is_array($exo->questions) ? $exo->questions : [];

        if (ExerciseSchemaRegistry::familyFor((string) $exo->exerciseType?->component_key) === ExerciseSchemaRegistry::FAMILY_MULTI) {
            $trous = 0;
            foreach ($questions as $q) {
                if (is_array($q['correct_answers'] ?? null)) {
                    $trous += count($q['correct_answers']);
                }
            }

            return $trous;
        }

        return count($questions);
    }
}
