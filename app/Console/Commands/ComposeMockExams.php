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
        {--ia : autoriser la génération de ce qui manque}';

    protected $description = 'Compose une épreuve blanche par examen et par niveau';

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
                $avant = MockExam::whereHas('blueprint', fn ($q) => $q->where('exam_id', $exam->id)->where('level', $niveau))
                    ->where('is_published', true)->whereHas('exercises')->exists();

                $mock = $composeur->pour($exam, $niveau, $avecIa);

                if (! $mock) {
                    $manquantes[] = $exam->slug.' '.$niveau;

                    continue;
                }

                if ($avant) {
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
