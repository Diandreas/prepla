<?php

use App\Models\CurriculumSkeleton;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\LearningPathNode;
use App\Models\LevelAssessment;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Http;

/**
 * Un niveau s'achevait sans rien pour le consolider : on enchaînait sur le palier
 * suivant sans vérifier que le précédent tenait. La promotion existait dans le code
 * mais rien ne l'appelait — la table des évaluations est restée vide depuis
 * l'ouverture, et un apprenant a bouclé trente objectifs en restant marqué A1.
 */
function pathWithTwoLevels(array $statuses): array
{
    Http::preventStrayRequests();

    $language = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'goethe', 'name' => 'Goethe']);
    $section = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'grammar', 'name' => 'Grammar', 'skill_type' => 'grammar']);
    ExerciseType::create([
        'section_id' => $section->id, 'slug' => 'mcq', 'name' => 'MCQ', 'skill_type' => 'grammar', 'component_key' => 'mcq',
    ]);

    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'target_exam_id' => $exam->id, 'current_level' => 'A1', 'onboarding_completed_at' => now(),
    ]);

    // Deux objectifs A1, deux A2 : deux paliers, donc deux examens attendus.
    $levels = ['A1', 'A1', 'A2', 'A2'];
    $objectives = [];
    foreach ($levels as $index => $level) {
        $objectives[] = [
            'order' => $index, 'title' => "Objectif {$index}", 'concept' => 'grammar.basic',
            'level' => $level, 'status' => $statuses[$index], 'priority' => 'normal',
        ];
    }

    $skeleton = CurriculumSkeleton::create([
        'user_id' => $user->id, 'exam_id' => $exam->id, 'objectives' => $objectives,
        'current_objective_index' => 2, 'consecutive_successes' => 0, 'consecutive_failures' => 0,
    ]);

    return [$user, $skeleton, $exam];
}

test('un examen est pose a la fin de chaque palier sans deplacer l objectif courant', function () {
    [$user, $skeleton] = pathWithTwoLevels(['done', 'done', 'current', 'pending']);

    $skeleton->ensureLevelExams();
    $skeleton = $skeleton->fresh();
    $objectives = collect($skeleton->objectives);

    expect($objectives)->toHaveCount(6) // 4 objectifs + 2 examens
        ->and($objectives[2]['is_level_exam'])->toBeTrue()
        ->and($objectives[2]['level'])->toBe('A1')
        ->and($objectives[5]['is_level_exam'])->toBeTrue()
        ->and($objectives[5]['level'])->toBe('A2')
        // L'apprenant avait l'objectif 2 sous les yeux : il doit l'y retrouver.
        ->and($skeleton->current_objective_index)->toBe(3)
        ->and($skeleton->currentObjective()['title'])->toBe('Objectif 2');

    // Le palier A1 est fini : son examen s'ouvre. Celui d'A2 attend son tour.
    expect($skeleton->pendingLevelExam()['level'])->toBe('A1');

    // Rejouer la pose ne cree pas de doublons.
    $skeleton->ensureLevelExams();
    expect($skeleton->fresh()->objectives)->toHaveCount(6);
});

test('l examen reste ferme tant que le palier n est pas termine', function () {
    [$user, $skeleton] = pathWithTwoLevels(['done', 'pending', 'pending', 'pending']);
    $skeleton->ensureLevelExams();

    expect($skeleton->fresh()->pendingLevelExam())->toBeNull();

    $this->actingAs($user)->get(route('level.exam', 'A1'))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('error', fn (string $message) => str_contains($message, "Termine d'abord"));
});

test('reussir l examen de palier fait monter de niveau', function () {
    [$user, $skeleton, $exam] = pathWithTwoLevels(['done', 'done', 'pending', 'pending']);
    $skeleton->ensureLevelExams();

    $node = LearningPathNode::create([
        'exam_id' => $exam->id, 'title' => 'Examen de niveau A1', 'node_type' => 'level_exam',
        'level' => 'A1', 'sort_order' => 0, 'chapter_order' => 99, 'xp_reward' => 60,
    ]);
    $exercise = Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => ExerciseType::first()->id,
        'node_id' => $node->id, 'order_in_node' => 1, 'difficulty' => 'A1', 'content' => [],
        'questions' => collect(range(1, 3))->map(fn ($i) => [
            'id' => "q{$i}", 'type' => 'mcq', 'text' => "Frage {$i}",
            'options' => ['Ja', 'Nein'], 'correct_answer' => 'A', 'explanation' => 'Oui.',
        ])->all(),
    ]);

    $this->actingAs($user)->post(route('exercise.submit_session', $node), [
        'exercise_ids' => [$exercise->id],
        'answers_by_exercise' => [$exercise->id => ['q1' => 'A', 'q2' => 'A', 'q3' => 'A']],
        'time_spent' => 120,
    ])->assertRedirect();

    expect($user->profile->fresh()->current_level)->toBe('A2')
        ->and(LevelAssessment::where('user_id', $user->id)->value('assessment_type'))->toBe('boss_test')
        // L'examen passe ne doit plus barrer la route.
        ->and($skeleton->fresh()->pendingLevelExam())->toBeNull();
});

test('echouer l examen laisse le niveau en place', function () {
    [$user, $skeleton, $exam] = pathWithTwoLevels(['done', 'done', 'pending', 'pending']);
    $skeleton->ensureLevelExams();

    $node = LearningPathNode::create([
        'exam_id' => $exam->id, 'title' => 'Examen de niveau A1', 'node_type' => 'level_exam',
        'level' => 'A1', 'sort_order' => 0, 'chapter_order' => 99, 'xp_reward' => 60,
    ]);
    $exercise = Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => ExerciseType::first()->id,
        'node_id' => $node->id, 'order_in_node' => 1, 'difficulty' => 'A1', 'content' => [],
        'questions' => collect(range(1, 3))->map(fn ($i) => [
            'id' => "q{$i}", 'type' => 'mcq', 'text' => "Frage {$i}",
            'options' => ['Ja', 'Nein'], 'correct_answer' => 'A', 'explanation' => 'Oui.',
        ])->all(),
    ]);

    // Une seule bonne réponse sur trois : 33 %, loin des 70 % exigés.
    $this->actingAs($user)->post(route('exercise.submit_session', $node), [
        'exercise_ids' => [$exercise->id],
        'answers_by_exercise' => [$exercise->id => ['q1' => 'A', 'q2' => 'B', 'q3' => 'B']],
        'time_spent' => 120,
    ])->assertRedirect();

    $skeleton = $skeleton->fresh();
    $reprises = collect($skeleton->objectives)->where('is_remedial', true);

    expect($user->profile->fresh()->current_level)->toBe('A1')
        ->and(LevelAssessment::count())->toBe(0)
        // Une reprise est posee sur ce qui n'a pas ete compris, et c'est la que
        // l'apprenant reprend — pas sur l'epreuve qu'il vient de manquer.
        ->and($reprises)->not->toBeEmpty()
        ->and($reprises->first()['level'])->toBe('A1')
        ->and($reprises->first()['title'])->toStartWith('Reprise :')
        ->and($skeleton->currentObjective()['is_remedial'])->toBeTrue()
        // L'examen se referme le temps de la remediation : il n'a plus a etre repasse
        // tout de suite, il reviendra quand les reprises seront faites.
        ->and($skeleton->pendingLevelExam())->toBeNull()
        ->and(collect($skeleton->objectives)->firstWhere('is_level_exam', true)['status'])->toBe('pending');
});
