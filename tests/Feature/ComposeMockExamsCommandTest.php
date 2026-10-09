<?php

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\MockExam;
use App\Models\User;
use App\Models\UserExerciseAttempt;
use App\Models\UserProfile;

/**
 * L'option --refaire effaçait AVANT de recomposer. Une génération qui échoue en
 * route — un quota épuisé, par exemple — laissait donc l'examen SANS aucune
 * épreuve à ce niveau : on détruisait du contenu servi pour ne rien mettre à la
 * place.
 */
function examenRecomposable(): Exam
{
    $language = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'osd', 'name' => 'ÖSD Zertifikat']);

    foreach ([['lesen', 'reading'], ['hoeren', 'listening']] as [$slug, $skill]) {
        $section = ExamSection::create([
            'exam_id' => $exam->id, 'slug' => $slug, 'name' => $slug, 'skill_type' => $skill, 'time_limit' => 30,
        ]);
        $type = ExerciseType::create([
            'section_id' => $section->id, 'slug' => $slug.'-mcq', 'name' => $slug,
            'skill_type' => $skill, 'component_key' => 'mcq',
        ]);
        Exercise::create([
            'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'exam_section_id' => $section->id,
            'difficulty' => 'B2', 'content' => [],
            'questions' => [['id' => 'q1', 'type' => 'mcq', 'text' => 'Wo?', 'options' => ['A', 'B'], 'correct_answer' => 'A']],
        ]);
    }

    return $exam;
}

test('refaire ne laisse jamais un niveau sans epreuve', function () {
    $exam = examenRecomposable();

    $this->artisan('prepla:compose-mock-exams', ['--exam' => 'osd', '--level' => 'B2'])->assertSuccessful();
    $premiere = MockExam::whereHas('exercises')->sole();

    // On recompose : l'ancienne part, mais seulement une fois la nouvelle en place.
    $this->artisan('prepla:compose-mock-exams', ['--exam' => 'osd', '--level' => 'B2', '--refaire' => true])
        ->assertSuccessful();

    $epreuves = MockExam::whereHas('exercises')->get();

    expect($epreuves)->toHaveCount(1)
        ->and($epreuves->first()->id)->not->toBe($premiere->id)
        ->and($epreuves->first()->exercises()->count())->toBe(2);
});

test('refaire ne touche pas une epreuve deja travaillee', function () {
    $exam = examenRecomposable();
    $this->artisan('prepla:compose-mock-exams', ['--exam' => 'osd', '--level' => 'B2'])->assertSuccessful();

    $epreuve = MockExam::whereHas('exercises')->sole();
    $exercice = $epreuve->exercises()->first();

    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create(['onboarding_completed_at' => now()]);
    UserExerciseAttempt::create([
        'user_id' => $user->id, 'exercise_id' => $exercice->id, 'answers' => [],
        'score' => 1, 'accuracy_percent' => 100, 'time_spent' => 30, 'xp_earned' => 10, 'feedback' => [],
    ]);

    $this->artisan('prepla:compose-mock-exams', ['--exam' => 'osd', '--level' => 'B2', '--refaire' => true])
        ->expectsOutputToContain("déjà travaillée")
        ->assertSuccessful();

    // L'épreuve travaillée et sa tentative sont intactes.
    expect(MockExam::whereKey($epreuve->id)->exists())->toBeTrue()
        ->and(Exercise::whereKey($exercice->id)->exists())->toBeTrue()
        ->and(UserExerciseAttempt::count())->toBe(1);
});
