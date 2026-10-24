<?php

namespace App\Console\Commands;

use App\Models\Exam;
use App\Models\ExamBlueprint;
use App\Models\Exercise;
use App\Models\MockExam;
use App\Services\Content\StructureOfficielle;
use Illuminate\Console\Command;

/**
 * Passe en revue TOUT le contenu servi aux apprenants, et sort en erreur des
 * qu'une chose ne tient pas.
 *
 * `prepla:check` verifie le deploiement — cles, base, assets, droits. Il ne dit
 * rien du contenu : un examen peut etre en ligne, servi en 200, et proposer des
 * ecoutes muettes, des parties sans exercice ou une epreuve blanche vide.
 * Chaque fois qu'on a voulu s'en assurer, il a fallu improviser des requetes.
 *
 * Cette commande repond a une seule question : « est-ce qu'un apprenant qui
 * ouvre la plateforme maintenant tombe sur quelque chose de casse ? »
 */
class AuditContenu extends Command
{
    protected $signature = 'prepla:audit-contenu
        {--exam= : se limiter a cet examen (slug)}
        {--detail : lister chaque anomalie plutot que de les compter}';

    protected $description = 'Verifie que tout le contenu servi aux apprenants est jouable : examens, parties, exercices, epreuves blanches';

    /** @var list<array{quoi: string, ou: string}> */
    private array $anomalies = [];

    /** Parties qui ne s'ouvrent pas encore, mais qui disent pourquoi. */
    private int $expliquees = 0;

    public function handle(StructureOfficielle $structure): int
    {
        $examens = Exam::proposables()->with('language')
            ->when($this->option('exam'), fn ($q) => $q->where('slug', $this->option('exam')))
            ->orderBy('slug')->get();

        if ($examens->isEmpty()) {
            $this->error('Aucun examen proposable.');

            return self::FAILURE;
        }

        $this->verifieLesExamens($examens, $structure);
        $this->verifieLesExercices($examens);
        $this->verifieLesEpreuvesBlanches($examens);

        return $this->rapport();
    }

    /** Chaque examen a-t-il ses modules, ses formats et ses parties jouables ? */
    private function verifieLesExamens($examens, StructureOfficielle $structure): void
    {
        $this->line('<fg=cyan>Examens, modules et parties</>');

        foreach ($examens as $exam) {
            $sections = $exam->sections()->where('slug', '!=', 'level-assessment')->with('exerciseTypes')->get();

            if ($sections->isEmpty()) {
                $this->anomalie('examen sans aucun module', $exam->slug);

                continue;
            }
            foreach ($sections as $section) {
                if ($section->exerciseTypes->isEmpty()) {
                    $this->anomalie('module sans aucun format', $exam->slug.'/'.$section->slug);
                }
            }

            $niveaux = ExamBlueprint::where('exam_id', $exam->id)->whereNotNull('level')
                ->pluck('level')->unique()->values();
            $niveaux = $niveaux->isEmpty() ? collect(['B1']) : $niveaux;

            $parties = 0;
            foreach ($niveaux as $niveau) {
                foreach ($structure->parties($exam, $niveau) as $partie) {
                    $parties++;
                    if (! $partie['exercise_type_id']) {
                        // Une partie qui DIT pourquoi elle ne s'ouvre pas encore
                        // — « se prepare a partir du B2 » — n'est pas cassee :
                        // elle est expliquee. Seul le silence est un defaut.
                        if (empty($partie['raison'])) {
                            $this->anomalie('partie sans format ouvrable et sans explication',
                                $exam->slug.' '.$niveau.' '.($partie['name'] ?? '?'));
                        } else {
                            $this->expliquees++;
                        }
                    }
                }
            }

            if ($parties === 0) {
                $this->anomalie('examen sans aucune partie decrite', $exam->slug);
            }

            $this->line(sprintf('  %-12s %2d module(s), %3d format(s), %3d partie(s) sur %d niveau(x)',
                $exam->slug, $sections->count(), $sections->sum(fn ($s) => $s->exerciseTypes->count()),
                $parties, $niveaux->count()));
        }
    }

    /** Chaque exercice en base est-il reellement jouable ? */
    private function verifieLesExercices($examens): void
    {
        $this->newLine();
        $this->line('<fg=cyan>Exercices</>');

        $ids = $examens->pluck('id');
        $total = 0;
        $muets = 0;
        $sansQuestion = 0;
        $ecoutesSansSon = 0;

        Exercise::whereIn('exam_id', $ids)
            ->with('exerciseType:id,slug,skill_type,component_key')
            ->chunkById(300, function ($lot) use (&$total, &$muets, &$sansQuestion, &$ecoutesSansSon) {
                foreach ($lot as $exercice) {
                    $total++;
                    $questions = is_array($exercice->questions) ? $exercice->questions : [];

                    if ($questions === []) {
                        $sansQuestion++;
                        $this->anomalie('exercice sans aucune question', '#'.$exercice->id);

                        continue;
                    }

                    $jouables = $exercice->answerableQuestions();
                    if ($jouables === []) {
                        $muets++;
                        $this->anomalie('exercice dont aucune question n est jouable', '#'.$exercice->id
                            .' '.($exercice->exerciseType?->slug ?? '?'));

                        continue;
                    }

                    // Une ecoute dont une question n'a ni son ni texte parle ne
                    // peut pas etre repondue : la garde l'ecarte a l'affichage,
                    // mais l'exercice arrive ampute sans que personne le sache.
                    if ($exercice->estUneEcoute() && count($jouables) < count($questions)) {
                        $ecoutesSansSon++;
                        $this->anomalie('ecoute amputee de questions sans son', '#'.$exercice->id);
                    }
                }
            });

        $this->line(sprintf('  %d exercice(s) — %d sans question, %d injouable(s), %d ecoute(s) amputee(s)',
            $total, $sansQuestion, $muets, $ecoutesSansSon));
    }

    /** Les epreuves blanches publiees tiennent-elles debout ? */
    private function verifieLesEpreuvesBlanches($examens): void
    {
        $this->newLine();
        $this->line('<fg=cyan>Epreuves blanches</>');

        $sujets = MockExam::where('is_published', true)
            ->whereHas('blueprint', fn ($q) => $q->whereIn('exam_id', $examens->pluck('id')))
            ->with('exercises.exerciseType', 'blueprint')
            ->get();

        $vides = 0;
        $casses = 0;

        foreach ($sujets as $sujet) {
            if ($sujet->exercises->isEmpty()) {
                $vides++;
                $this->anomalie('epreuve blanche publiee sans exercice', 'sujet #'.$sujet->id);

                continue;
            }
            foreach ($sujet->exercises as $exercice) {
                if ($exercice->answerableQuestions() === []) {
                    $casses++;
                    $this->anomalie('epreuve blanche contenant un exercice injouable',
                        'sujet #'.$sujet->id.' exercice #'.$exercice->id);
                }
            }
        }

        $this->line(sprintf('  %d epreuve(s) publiee(s) — %d vide(s), %d contenant un exercice injouable',
            $sujets->count(), $vides, $casses));
    }

    private function anomalie(string $quoi, string $ou): void
    {
        $this->anomalies[] = ['quoi' => $quoi, 'ou' => $ou];
    }

    private function rapport(): int
    {
        $this->newLine();

        if ($this->expliquees > 0) {
            $this->line($this->expliquees.' partie(s) ne s ouvrent pas encore a leur niveau, et le disent.');
        }

        if ($this->anomalies === []) {
            $this->info('Contenu verifie : tout ce qui est propose aux apprenants est jouable.');

            return self::SUCCESS;
        }

        $parType = collect($this->anomalies)->groupBy('quoi');
        $this->error(count($this->anomalies).' anomalie(s) :');

        foreach ($parType as $quoi => $cas) {
            $this->line(sprintf('  %3d  %s', $cas->count(), $quoi));
            if ($this->option('detail')) {
                foreach ($cas as $cas1) {
                    $this->line('         '.$cas1['ou']);
                }
            } else {
                $this->line('         ex. '.$cas->take(3)->pluck('ou')->implode(', ')
                    .($cas->count() > 3 ? ', …' : ''));
            }
        }

        return self::FAILURE;
    }
}
