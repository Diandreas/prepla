<?php

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\User;
use App\Models\UserError;
use App\Models\UserProfile;
use App\Services\AI\ExerciseGeneratorService;

/**
 * La révision reposait la question mot pour mot : l'apprenant réapprenait une phrase,
 * pas une règle. Elle propose maintenant un exercice neuf sur le même concept.
 */
function errorToReview(): array
{
    $language = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'goethe-a1', 'name' => 'Goethe A1']);
    $section = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'grammar', 'name' => 'Grammar', 'skill_type' => 'grammar']);
    $type = ExerciseType::create([
        'section_id' => $section->id, 'slug' => 'gap-fill', 'name' => 'Texte à trou',
        'skill_type' => 'grammar', 'component_key' => 'gap-fill',
    ]);

    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'target_exam_id' => $exam->id, 'current_level' => 'A1',
        'native_language' => 'Français', 'onboarding_completed_at' => now(),
    ]);

    $error = UserError::create([
        'user_id' => $user->id, 'exercise_id' => null, 'question_id' => 'q1',
        'question_text' => "Gestern _______ ich mit meinen Freunden im Park Fußball.",
        'user_answer' => 'spiele', 'correct_answer' => 'spielte',
        'skill_type' => 'grammar', 'exercise_type_slug' => 'gap-fill',
        'error_category' => 'grammar.tense.preterite', 'ease_factor' => 2.5,
        'interval_days' => 1, 'reviewed_count' => 0,
    ]);

    return [$user, $error, $exam, $type];
}

test('la revision propose un exercice neuf sur le meme concept', function () {
    [$user, $error, $exam, $type] = errorToReview();

    // Le générateur est simulé : le test vérifie le chemin, pas l'IA.
    $this->mock(ExerciseGeneratorService::class, function ($mock) use ($exam, $type) {
        $mock->shouldReceive('generate')
            ->once()
            ->withArgs(function ($exerciseType, $usedExam, $level, $context) {
                // Le concept raté doit être transmis au générateur, sinon l'exercice
                // neuf porterait sur autre chose que la difficulté à retravailler.
                return $context['concept'] === 'grammar.tense.preterite' && $level === 'A1';
            })
            ->andReturn(Exercise::create([
                'exam_id' => $exam->id, 'exercise_type_id' => $type->id,
                'difficulty' => 'A1', 'content' => [],
                'questions' => [[
                    'id' => 'q1', 'type' => 'gap-fill',
                    'text' => "Letztes Jahr _______ wir nach Berlin. (Präteritum von 'fahren')",
                    'correct_answer' => 'fuhren',
                    'explanation' => "Au prétérit, 'fahren' donne 'fuhren'.",
                ]],
            ]));
    });

    $response = $this->actingAs($user)->post(route('errors.similar', $error))->assertOk();

    $question = $response->json('question');

    expect($question['prompt'])->toContain('Berlin')
        // Surtout pas la phrase d'origine : c'est tout l'objet du correctif.
        ->and($question['prompt'])->not->toContain('Fußball')
        ->and($question['correct_answer'])->toBe('fuhren')
        ->and($question['explanation'])->toContain('prétérit');
});

test('l erreur d un autre apprenant reste inaccessible', function () {
    [$user, $error] = errorToReview();

    // L'intrus a lui aussi termine son inscription : sans cela le middleware le
    // renverrait vers l'accueil et le test ne prouverait rien sur l'autorisation.
    $intrus = User::factory()->create();
    UserProfile::factory()->for($intrus)->create(['onboarding_completed_at' => now()]);

    $this->actingAs($intrus)
        ->post(route('errors.similar', $error))
        ->assertForbidden();
});

test('une generation impossible le dit au lieu de rester muette', function () {
    [$user, $error] = errorToReview();

    $this->mock(ExerciseGeneratorService::class, function ($mock) {
        $mock->shouldReceive('generate')->andThrow(new \RuntimeException('IA indisponible'));
    });

    $this->actingAs($user)->post(route('errors.similar', $error))
        ->assertStatus(503)
        ->assertJsonPath('message', "Aucun exercice n'a pu être écrit pour l'instant. Réessaie dans quelques minutes.");
});
