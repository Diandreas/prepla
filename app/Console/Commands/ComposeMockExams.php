<?php

namespace App\Console\Commands;

use App\Models\Exam;
use App\Models\MockExam;
use App\Services\Content\MockExamComposer;
use Illuminate\Console\Command;

/**
 * Prépare une épreuve blanche par examen et par niveau.
 *
 * Sans IA par défaut : on assemble ce qui existe déjà (vivier + série préparée),
 * ce qui est gratuit et immédiat. Avec --ia, on laisse écrire ce qui manque — à
 * lancer quand le quota du fournisseur le permet.
 */
class ComposeMockExams extends Command
{
    protected $signature = 'prepla:compose-mock-exams
        {--exam= : ne traiter que cet examen (slug)}
        {--level= : ne traiter que ce niveau}
        {--ia : autoriser la génération de ce qui manque}
        {--refaire : recomposer les épreuves existantes, sauf celles déjà travaillées}';

    protected $description = 'Compose une épreuve blanche par examen et par niveau';

    /**
     * Les épreuves de ce niveau que personne n'a encore passées.
     *
     * Celles qui ont déjà été travaillées ne sont jamais touchées : supprimer un
     * exercice déjà tenté emporterait le travail de l'apprenant avec lui.
     *
     * @return \Illuminate\Support\Collection<int, MockExam>
     */
    private function anciennesIntactes(int $examId, string $niveau)
    {
        return MockExam::whereHas('blueprint', fn ($q) => $q->where('exam_id', $examId)->where('level', $niveau))
            ->get()
            ->reject(function (MockExam $epreuve) {
                $ids = $epreuve->exercises()->pluck('id');
                $travaillee = \App\Models\UserExerciseAttempt::whereIn('exercise_id', $ids)->exists();

                if ($travaillee) {
                    $this->line("  = déjà travaillée, on n'y touche pas : {$epreuve->title}");
                }

                return $travaillee;
            });
    }

    /** @param  iterable<MockExam>  $epreuves */
    private function effacer(iterable $epreuves): void
    {
        foreach ($epreuves as $epreuve) {
            \App\Models\Exercise::whereIn('id', $epreuve->exercises()->pluck('id'))->delete();
            $epreuve->delete();
        }
    }

    public function handle(MockExamComposer $composeur): int
    {
        $niveaux = $this->option('level') ? [$this->option('level')] : ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];
        $examens = Exam::with('sections.exerciseTypes', 'language')
            ->when($this->option('exam'), fn ($q) => $q->where('slug', $this->option('exam')))
            ->get();

        $avecIa = (bool) $this->option('ia');
        $composees = 0;
        $dejaLa = 0;
        $manquantes = [];

        foreach ($examens as $exam) {
            foreach ($niveaux as $niveau) {
                $refaire = (bool) $this->option('refaire');

                $anciennes = $refaire ? $this->anciennesIntactes($exam->id, $niveau) : collect();

                $avant = MockExam::whereHas('blueprint', fn ($q) => $q->where('exam_id', $exam->id)->where('level', $niveau))
                    ->where('is_published', true)->whereHas('exercises')->exists();

                // On compose D'ABORD, on efface ENSUITE. Dans l'autre sens, une
                // generation qui echoue en route — un quota epuise, par exemple —
                // laissait l'examen SANS aucune epreuve a ce niveau.
                $mock = $composeur->pour($exam, $niveau, $avecIa, $refaire);

                if (! $mock) {
                    $manquantes[] = $exam->slug.' '.$niveau;

                    continue;
                }

                if ($refaire && $anciennes->isNotEmpty()) {
                    $this->effacer($anciennes->reject(fn ($ancienne) => $ancienne->id === $mock->id));
                }

                if ($avant && ! $refaire) {
                    $dejaLa++;

                    continue;
                }

                $composees++;
                $this->line("  + {$exam->slug} {$niveau} : ".$mock->exercises()->count().' épreuves');
            }
        }

        $this->newLine();
        $this->info("Composées : {$composees} | déjà en place : {$dejaLa} | impossibles : ".count($manquantes));

        if ($manquantes !== []) {
            $this->warn('Rien à assembler pour : '.implode(', ', array_slice($manquantes, 0, 40))
                .(count($manquantes) > 40 ? ' …' : ''));
            $this->line($avecIa
                ? "Ces combinaisons n'ont ni contenu existant ni génération possible (quota ?)."
                : 'Relancer avec --ia pour écrire ce qui manque.');
        }

        return self::SUCCESS;
    }
}
