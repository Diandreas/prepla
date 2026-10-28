<?php

namespace App\Console\Commands;

use App\Models\Exam;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Services\Content\ExerciseSchemaRegistry;
use App\Services\Content\StructureOfficielle;
use Illuminate\Console\Command;

/**
 * Les exercices d'entrainement suivent-ils vraiment l'epreuve ?
 *
 * C'est la question qui decide de la valeur de l'application. Un apprenant qui
 * prepare le Lesen Teil 2 du Goethe B1 doit rencontrer SIX questions a choix
 * multiple, parce que l'epreuve en compte six. Trois questions, c'est un bon
 * exercice de langue et un mauvais entrainement a l'examen : le jour venu, il
 * decouvre un format qu'il n'a jamais vu, et le temps ne tombe plus juste.
 *
 * Ce controle compare, partie par partie, ce que l'organisme publie et ce que
 * nous servons. Il ne corrige rien : il dit l'ecart, et c'est deja ce qui
 * manquait. Releve du premier passage, en production : 33 exercices sur 121
 * portaient le bon nombre d'items.
 */
class AuditStructure extends Command
{
    protected $signature = 'prepla:audit-structure
        {--examen= : se limiter a un examen (goethe, telc, osd, testdaf...)}
        {--detail : lister chaque exercice non conforme}';

    protected $description = 'Compare les exercices servis aux parties officielles de chaque epreuve';

    public function handle(StructureOfficielle $structure): int
    {
        $examens = Exam::query()
            ->when($this->option('examen'), fn ($q) => $q->where('slug', $this->option('examen')))
            ->orderBy('slug')->get();

        $totalExos = 0;
        $totalOk = 0;
        $ecarts = [];
        $muettes = [];

        foreach ($examens as $exam) {
            foreach ($this->niveauxDe($exam) as $niveau) {
                $parties = collect($structure->parties($exam, $niveau))
                    ->filter(fn ($p) => $p['exercise_type_id'] !== null);

                foreach ($parties->groupBy('exercise_type_id') as $typeId => $groupe) {
                    $attendus = $groupe->pluck('items')->filter()->unique()->values()->all();
                    $type = ExerciseType::find($typeId);
                    if (! $type) {
                        continue;
                    }

                    $exos = Exercise::with('exerciseType')
                        ->where('exam_id', $exam->id)
                        ->where('exercise_type_id', $typeId)
                        ->where('difficulty', $niveau)
                        // On ne juge que la pratique libre : une lecon ou une
                        // epreuve blanche compose ses propres exercices.
                        ->whereNull('lesson_id')->whereNull('node_id')->whereNull('mock_exam_id')
                        ->get();

                    if ($attendus === []) {
                        if ($exos->isNotEmpty()) {
                            $muettes[] = sprintf('%s %s %s — %d exercice(s), mais la partie n annonce aucun nombre d items',
                                $exam->slug, $niveau, $type->slug, $exos->count());
                        }

                        continue;
                    }
                    if ($exos->isEmpty()) {
                        continue;
                    }

                    $ok = 0;
                    $trouves = [];
                    foreach ($exos as $exo) {
                        $n = $this->itemsDe($exo);
                        if (in_array($n, $attendus, true)) {
                            $ok++;

                            continue;
                        }
                        $trouves[] = $n;
                        if ($this->option('detail')) {
                            $this->line(sprintf('    #%-5d %s %s %s : %d item(s) au lieu de %s',
                                $exo->id, $exam->slug, $niveau, $type->slug, $n, implode(' ou ', $attendus)));
                        }
                    }

                    $totalExos += $exos->count();
                    $totalOk += $ok;

                    if ($ok < $exos->count()) {
                        $ecarts[] = sprintf('  %-9s %-3s %-20s %2d/%-2d conformes — attendu %-10s trouve %s',
                            $exam->slug, $niveau, $type->slug, $ok, $exos->count(),
                            implode(' ou ', $attendus),
                            implode(', ', array_slice(array_unique($trouves), 0, 6)));
                    }
                }
            }
        }

        $this->newLine();
        $this->line('<fg=cyan>Exercices d entrainement, compares aux parties officielles</>');

        if ($ecarts === []) {
            $this->info(sprintf('  %d exercice(s) : tous portent le nombre d items de leur partie.', $totalExos));
        } else {
            foreach ($ecarts as $ecart) {
                $this->line($ecart);
            }
        }

        if ($muettes !== []) {
            $this->newLine();
            $this->line('<fg=yellow>Parties sans nombre d items publie — rien a comparer</>');
            foreach (array_slice($muettes, 0, 10) as $m) {
                $this->line('  '.$m);
            }
        }

        $this->newLine();
        $part = $totalExos ? round($totalOk * 100 / $totalExos) : 100;
        $this->line(sprintf('%d / %d exercice(s) conformes — %d%%', $totalOk, $totalExos, $part));

        // Un ecart n'est pas une panne : il se repare en regenerant ou en
        // ecrivant la partie a la main. La commande le dit, elle n'echoue pas.
        return self::SUCCESS;
    }

    /** Les niveaux que cet examen declare, ou l'epreuve unique. */
    private function niveauxDe(Exam $exam): array
    {
        $niveaux = \App\Models\ExamBlueprint::where('exam_id', $exam->id)
            ->whereNotNull('level')->pluck('level')->unique()->values()->all();

        return $niveaux ?: [null];
    }

    /**
     * Le nombre d'items d'un exercice.
     *
     * Un item n'est pas toujours une question : pour les formats a champs
     * multiples, ce sont les TROUS, additionnes sur toutes les questions.
     */
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
