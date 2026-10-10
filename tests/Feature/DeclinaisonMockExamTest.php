<?php

use App\Models\Exam;
use App\Models\ExamBlueprint;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\MockExam;
use App\Services\Content\MockExamComposer;

/**
 * Le TCF et le TEF ne s'inscrivent pas à un palier : ils existent en
 * DÉCLINAISONS — TCF Canada, TCF Québec, TEF Naturalisation — qui sont des
 * épreuves réelles, avec leurs propres durées. Leurs plans étaient décrits
 * depuis le début, mais la composition ne travaillait que par niveau : trois
 * versions du TCF sur quatre n'avaient donc aucun sujet blanc.
 */
function examenADeclinaisons(): Exam
{
    $langue = Language::create(['slug' => 'french', 'name' => 'Français', 'native_name' => 'Français', 'flag' => 'fr']);
    $exam = Exam::create(['language_id' => $langue->id, 'slug' => 'tcf-test', 'name' => 'TCF']);

    $modules = [];
    foreach ([['comprehension-orale', 'listening', 'mcq'], ['comprehension-ecrite', 'reading', 'mcq']] as [$slug, $skill, $composant]) {
        $section = ExamSection::create([
            'exam_id' => $exam->id, 'slug' => $slug, 'name' => $slug, 'skill_type' => $skill, 'time_limit' => 30,
        ]);
        $type = ExerciseType::create([
            'section_id' => $section->id, 'slug' => $slug.'-'.$composant, 'name' => $slug,
            'skill_type' => $skill, 'component_key' => $composant,
        ]);

        // De quoi monter plusieurs tâches, à plusieurs niveaux : une déclinaison
        // se compose à difficulté progressive, pas six fois au même palier.
        foreach (['A2', 'B1', 'B2', 'C1'] as $niveau) {
            Exercise::create([
                'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'exam_section_id' => $section->id,
                'difficulty' => $niveau,
                'content' => $skill === 'listening' ? ['audio_text' => "Un court enregistrement de niveau {$niveau}."] : [],
                'questions' => [[
                    'id' => 'q1', 'type' => 'mcq', 'text' => "Question {$slug} {$niveau}",
                    'options' => ['Oui', 'Non'], 'correct_answer' => 'A',
                ]],
            ]);
        }

        $modules[] = ['slug' => $slug, 'task_count' => 2];
    }

    foreach (['tcf-canada', 'tcf-quebec'] as $variante) {
        ExamBlueprint::create([
            'exam_id' => $exam->id, 'level' => null, 'variant' => $variante,
            'name' => 'TCF '.$variante, 'total_duration_minutes' => 120,
            'scoring_config' => [], 'sections_config' => $modules,
        ]);
    }

    return $exam;
}

test('chaque declinaison recoit sa propre epreuve blanche', function () {
    $exam = examenADeclinaisons();

    $this->artisan('prepla:compose-mock-exams', ['--exam' => 'tcf-test'])->assertSuccessful();

    foreach (['tcf-canada', 'tcf-quebec'] as $variante) {
        $plan = ExamBlueprint::where('exam_id', $exam->id)->where('variant', $variante)->sole();
        $sujet = MockExam::where('blueprint_id', $plan->id)->where('is_published', true)->first();

        expect($sujet)->not->toBeNull("la déclinaison {$variante} n'a pas de sujet")
            ->and($sujet->exercises()->count())->toBe(4)   // deux modules, deux tâches chacun
            ->and($sujet->title)->toContain('TCF '.$variante);
    }
});

test('une declinaison monte ses taches a difficulte progressive', function () {
    $exam = examenADeclinaisons();

    app(MockExamComposer::class)->pour($exam, 'B1', false, false, 'tcf-canada');

    $plan = ExamBlueprint::where('exam_id', $exam->id)->where('variant', 'tcf-canada')->sole();
    $sujet = MockExam::where('blueprint_id', $plan->id)->sole();
    $niveaux = $sujet->exercises()->pluck('difficulty')->unique()->sort()->values();

    // Deux tâches par module : la première en A2, la seconde en B1 — pas deux
    // fois le même palier.
    expect($niveaux->all())->toBe(['A2', 'B1']);
});

test('un examen sans declinaison n est pas touche', function () {
    $exam = examenADeclinaisons();
    ExamBlueprint::where('exam_id', $exam->id)->whereNotNull('variant')->delete();

    $this->artisan('prepla:compose-mock-exams', ['--exam' => 'tcf-test'])->assertSuccessful();

    expect(MockExam::whereHas('blueprint', fn ($q) => $q->whereNotNull('variant'))->count())->toBe(0);
});
