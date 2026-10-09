<?php

namespace App\Services\Content;

use App\Models\Exam;
use App\Models\ExamBlueprint;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\MockExam;
use App\Services\AI\ExerciseGeneratorService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Compose une épreuve blanche pour un examen À UN NIVEAU donné.
 *
 * Avant, les épreuves blanches étaient écrites à la main : seize sujets pour
 * seize examens, presque tous sans niveau. Un apprenant A1 ou A2 était donc
 * renvoyé avec « à ton niveau, commence par une compétence » — et un examen
 * récemment ajouté n'avait rien du tout, quel que soit le niveau.
 *
 * Ici, l'épreuve est assemblée à partir de la structure officielle de l'examen
 * (le blueprint : ses modules, leurs durées, leurs formats), avec un exercice par
 * module pris dans cet ordre :
 *   1. le vivier déjà en base, au bon niveau ;
 *   2. la série préparée (gratuite, immédiate, sans IA) ;
 *   3. la génération, seulement si on l'autorise.
 *
 * Les exercices du vivier sont RECOPIÉS, jamais déplacés : rattacher l'original
 * l'aurait retiré de la pratique libre, qui exclut ce qui appartient à une épreuve.
 *
 * Une épreuve partielle vaut mieux qu'un refus : on le dit au lieu de prétendre
 * servir l'examen complet.
 */
class MockExamComposer
{
    /** Au moins deux modules, sinon ce n'est pas une épreuve. */
    public const MODULES_MINIMUM = 2;

    public function __construct(
        private StarterPracticeLibrary $starters,
        private ExerciseTypeSuitability $pertinence,
    ) {}

    /**
     * L'épreuve blanche de cet examen à ce niveau, composée au besoin.
     *
     * @param  bool  $avecGeneration  autoriser l'IA à écrire ce qui manque
     * @param  bool  $forcer  en composer une nouvelle même s'il en existe déjà une
     */
    public function pour(Exam $exam, string $niveau, bool $avecGeneration = false, bool $forcer = false): ?MockExam
    {
        $existante = $forcer ? null : $this->existante($exam, $niveau);
        if ($existante) {
            return $existante;
        }

        $officiel = ExamBlueprint::where('exam_id', $exam->id)->where('level', $niveau)->exists();
        $blueprint = $this->blueprintPour($exam, $niveau);
        $sections = $exam->sections()->where('slug', '!=', 'level-assessment')->with('exerciseTypes')->get();

        if ($sections->isEmpty()) {
            return null;
        }

        $choisis = [];
        $modulesServis = 0;
        foreach ($sections as $section) {
            // Un vrai sujet compte plusieurs taches par module — quatre textes a lire,
            // deux redactions. En n'en posant qu'une, on servait le bon format au bon
            // niveau, mais pas l'epreuve. On en monte autant que la structure en
            // annonce, et on s'arrete a ce qu'on sait vraiment servir.
            $voulues = $this->nombreDeTaches($blueprint, $section);
            $dejaPris = [];
            $typesUtilises = [];
            $pourCeModule = 0;

            for ($i = 0; $i < $voulues; $i++) {
                $exercice = $this->exercicePour($exam, $section, $niveau, $avecGeneration, $dejaPris, $typesUtilises);
                if (! $exercice) {
                    break;
                }
                $dejaPris[] = $exercice->id;
                $typesUtilises[] = $exercice->exercise_type_id;
                $choisis[] = [$section, $exercice];
                $pourCeModule++;
            }

            if ($pourCeModule > 0) {
                $modulesServis++;
            }
        }

        if ($modulesServis < self::MODULES_MINIMUM) {
            Log::info('Epreuve blanche non composable', [
                'exam' => $exam->slug, 'niveau' => $niveau, 'modules' => $modulesServis,
            ]);

            return null;
        }

        return DB::transaction(function () use ($blueprint, $officiel, $exam, $niveau, $sections, $choisis, $modulesServis) {
            // On n'appelle « épreuve blanche » que ce qui suit une structure officielle
            // à ce niveau. Ailleurs c'est un entraînement au format de l'examen, servi
            // à la difficulté de l'apprenant — et c'est ce qu'on écrit.
            $complet = $modulesServis === $sections->count();
            $taches = count($choisis);

            $mock = MockExam::create([
                'blueprint_id' => $blueprint->id,
                'title' => $officiel
                    ? $exam->name.' — épreuve blanche '.$niveau
                    : $exam->name.' — entraînement au format, niveau '.$niveau,
                'description' => ($officiel
                    ? ($complet
                        ? 'Toutes les épreuves, au niveau '.$niveau.' — '.$taches.' tâches.'
                        : $modulesServis.' épreuves sur '.$sections->count().', au niveau '.$niveau.'.')
                    : "Cet examen ne propose pas d'épreuve officielle au niveau {$niveau} : tu t'entraînes à son format, avec des exercices de ton niveau."),
                'is_published' => true,
            ]);

            foreach ($choisis as [$section, $exercice]) {
                $copie = $exercice->replicate(['created_at', 'updated_at']);
                // La cle de catalogue identifie un contenu PREPARE, une seule fois :
                // la recopier violait sa contrainte d'unicite et faisait echouer
                // toute la composition.
                $copie->catalog_key = null;
                $copie->mock_exam_id = $mock->id;
                $copie->exam_section_id = $section->id;
                $copie->node_id = null;
                $copie->lesson_id = null;
                $copie->center_id = null;
                $copie->difficulty = $niveau;
                $copie->save();
            }

            return $mock->fresh();
        });
    }

    /**
     * Le nombre de tâches d'un module.
     *
     * Il vient de la structure officielle quand elle le dit (`task_count`). Sinon on
     * se rabat sur le nombre de parties décrites, puis sur une tâche : on ne gonfle
     * pas une épreuve avec des exercices que la source ne réclame pas.
     */
    private function nombreDeTaches(ExamBlueprint $blueprint, ExamSection $section): int
    {
        foreach (($blueprint->sections_config ?? []) as $config) {
            if (($config['slug'] ?? null) !== $section->slug) {
                continue;
            }

            if (is_int($config['task_count'] ?? null) && $config['task_count'] > 0) {
                return min($config['task_count'], 8);
            }
        }

        $parties = is_array($section->parts_config) ? count($section->parts_config) : 0;

        return $parties > 0 ? min($parties, 8) : 1;
    }

    /** Une épreuve déjà publiée pour ce niveau, et qui porte vraiment des exercices. */
    private function existante(Exam $exam, string $niveau): ?MockExam
    {
        return MockExam::where('is_published', true)
            ->whereHas('exercises')
            ->whereHas('blueprint', fn ($q) => $q->where('exam_id', $exam->id)->where('level', $niveau))
            ->first();
    }

    /**
     * Le plan de l'examen à ce niveau.
     *
     * Tous les examens n'ont pas de structure par niveau : l'IELTS n'a pas de
     * « sujet A1 » officiel. On ne l'invente pas — on reprend sa structure réelle
     * et on la sert à la difficulté de l'apprenant, ce que la plateforme fait déjà
     * pour un exercice isolé.
     */
    private function blueprintPour(Exam $exam, string $niveau): ExamBlueprint
    {
        $officiel = ExamBlueprint::where('exam_id', $exam->id)->where('level', $niveau)->first();
        if ($officiel) {
            return $officiel;
        }

        $reference = ExamBlueprint::where('exam_id', $exam->id)->first();

        return ExamBlueprint::firstOrCreate(
            ['exam_id' => $exam->id, 'level' => $niveau, 'variant' => null],
            [
                'name' => $exam->name.' — entraînement niveau '.$niveau,
                'total_duration_minutes' => $reference?->total_duration_minutes
                    ?? $exam->sections->sum(fn ($s) => $s->time_limit ?? 30),
                'scoring_config' => $reference?->scoring_config ?? [],
                'sections_config' => $reference?->sections_config ?? [],
            ]
        );
    }

    /** Un exercice utilisable pour ce module, à ce niveau. */
    /**
     * Un exercice utilisable pour ce module, a ce niveau.
     *
     * On parcourt les FORMATS d'abord, les sources ensuite : sinon le format qui a
     * deja du contenu en base gagnait toujours, et un module rendait trois fois la
     * meme tache alors que l'examen en demande trois differentes.
     *
     * @param  list<int>  $dejaPris  les exercices deja retenus pour ce module
     * @param  list<int>  $typesUtilises  les formats deja poses dans ce module
     */
    private function exercicePour(Exam $exam, ExamSection $section, string $niveau, bool $avecGeneration, array $dejaPris = [], array $typesUtilises = []): ?Exercise
    {
        $types = $section->exerciseTypes
            ->filter(fn (ExerciseType $type) => $this->pertinence->convient($type, $niveau));

        if ($types->isEmpty()) {
            return null;
        }

        // Les formats pas encore poses dans ce module passent en premier ; on n'en
        // repete un que s'il n'y a pas assez de formats pour le nombre de taches.
        $ordonnes = $types
            ->sortBy(fn (ExerciseType $type) => in_array($type->id, $typesUtilises, true) ? 1 : 0)
            ->values();

        foreach ($ordonnes as $type) {
            $exercice = $this->pourCeFormat($exam, $type, $niveau, $avecGeneration, $dejaPris);
            if ($exercice) {
                return $exercice;
            }
        }

        return null;
    }

    /**
     * Ce qu'on sait servir pour UN format, de la source la plus sure a la plus
     * couteuse : le vivier deja en base, la serie preparee, puis la generation.
     *
     * @param  list<int>  $dejaPris
     */
    private function pourCeFormat(Exam $exam, ExerciseType $type, string $niveau, bool $avecGeneration, array $dejaPris): ?Exercise
    {
        $duVivier = Exercise::where('exam_id', $exam->id)
            ->where('exercise_type_id', $type->id)
            ->where('difficulty', $niveau)
            ->whereNull('center_id')->whereNull('lesson_id')
            ->whereNull('node_id')->whereNull('mock_exam_id')
            ->whereNotIn('id', $dejaPris)
            ->inRandomOrder()
            ->first();

        if ($duVivier && $duVivier->answerableQuestions() !== []) {
            return $duVivier;
        }

        $starter = $this->starters->ensure($exam, $type, $niveau);
        if ($starter && ! in_array($starter->id, $dejaPris, true)) {
            return $starter;
        }

        if (! $avecGeneration) {
            return null;
        }

        try {
            $exercice = app(ExerciseGeneratorService::class)->generate($type, $exam, $niveau);
            if ($exercice && $exercice->answerableQuestions() !== []) {
                return $exercice;
            }
        } catch (\Throwable $e) {
            Log::warning('Epreuve blanche : generation impossible', [
                'exam' => $exam->slug, 'type' => $type->slug, 'niveau' => $niveau,
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }

}
