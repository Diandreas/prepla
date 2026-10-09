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
     */
    public function pour(Exam $exam, string $niveau, bool $avecGeneration = false): ?MockExam
    {
        $existante = $this->existante($exam, $niveau);
        if ($existante) {
            return $existante;
        }

        $blueprint = $this->blueprintPour($exam, $niveau);
        $sections = $exam->sections()->where('slug', '!=', 'level-assessment')->with('exerciseTypes')->get();

        if ($sections->isEmpty()) {
            return null;
        }

        $choisis = [];
        foreach ($sections as $section) {
            $exercice = $this->exercicePour($exam, $section, $niveau, $avecGeneration);
            if ($exercice) {
                $choisis[] = [$section, $exercice];
            }
        }

        if (count($choisis) < self::MODULES_MINIMUM) {
            Log::info('Epreuve blanche non composable', [
                'exam' => $exam->slug, 'niveau' => $niveau, 'modules' => count($choisis),
            ]);

            return null;
        }

        return DB::transaction(function () use ($blueprint, $exam, $niveau, $sections, $choisis) {
            $mock = MockExam::create([
                'blueprint_id' => $blueprint->id,
                'title' => $exam->name.' — épreuve blanche '.$niveau,
                'description' => count($choisis) === $sections->count()
                    ? 'Toutes les épreuves, au niveau '.$niveau.'.'
                    : count($choisis).' épreuves sur '.$sections->count().', au niveau '.$niveau.'.',
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
    private function exercicePour(Exam $exam, ExamSection $section, string $niveau, bool $avecGeneration): ?Exercise
    {
        $types = $section->exerciseTypes
            ->filter(fn (ExerciseType $type) => $this->pertinence->convient($type, $niveau));

        if ($types->isEmpty()) {
            return null;
        }

        // 1. Le vivier déjà en base.
        $duVivier = Exercise::where('exam_id', $exam->id)
            ->whereIn('exercise_type_id', $types->pluck('id'))
            ->where('difficulty', $niveau)
            ->whereNull('center_id')->whereNull('lesson_id')
            ->whereNull('node_id')->whereNull('mock_exam_id')
            ->inRandomOrder()
            ->first();

        if ($duVivier && $duVivier->answerableQuestions() !== []) {
            return $duVivier;
        }

        // 2. La série préparée : gratuite, immédiate, et disponible même sans IA.
        foreach ($types as $type) {
            $starter = $this->starters->ensure($exam, $type, $niveau);
            if ($starter) {
                return $starter;
            }
        }

        if (! $avecGeneration) {
            return null;
        }

        // 3. En dernier, écrire ce qui manque.
        foreach ($types as $type) {
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
        }

        return null;
    }
}
