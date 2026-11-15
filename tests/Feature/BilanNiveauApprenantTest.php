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
use App\Models\UserExerciseAttempt;
use App\Models\UserProfile;
use App\Services\LevelAdvancementService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

function bilanApprenant(array $statuts, array $niveaux): array
{
    $language = Language::create(['slug' => 'english', 'name' => 'English', 'native_name' => 'English', 'flag' => 'gb']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'ielts', 'name' => 'IELTS']);
    $section = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'reading', 'name' => 'Reading', 'skill_type' => 'reading']);
    $type = ExerciseType::create([
        'section_id' => $section->id, 'slug' => 'mcq', 'name' => 'MCQ', 'skill_type' => 'reading', 'component_key' => 'mcq',
    ]);
    $exercice = Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'exam_section_id' => $section->id,
        'difficulty' => 'A1', 'content' => [], 'questions' => [],
    ]);

    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'target_exam_id' => $exam->id, 'current_level' => 'A1', 'onboarding_completed_at' => now(),
    ]);

    $objectifs = [];
    foreach ($statuts as $i => $statut) {
        $objectifs[] = ['order' => $i, 'title' => "Objectif {$i}", 'concept' => 'grammar.basic',
            'level' => $niveaux[$i], 'status' => $statut, 'priority' => 'normal'];
    }

    $skeleton = CurriculumSkeleton::create([
        'user_id' => $user->id, 'exam_id' => $exam->id, 'objectives' => $objectifs,
        'current_objective_index' => 0, 'consecutive_successes' => 0, 'consecutive_failures' => 0,
    ]);

    return [$user, $skeleton, $exam, $exercice->id];
}

function bilanEcrit(int $userId, Exam $exam): void
{
    $section = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'writing', 'name' => 'Writing', 'skill_type' => 'writing']);
    $type = ExerciseType::create(['section_id' => $section->id, 'slug' => 'essay', 'name' => 'Essay', 'skill_type' => 'writing', 'component_key' => 'essay']);
    $exercice = Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'exam_section_id' => $section->id,
        'difficulty' => 'A1', 'content' => [], 'questions' => [],
    ]);
    UserExerciseAttempt::create(['user_id' => $userId, 'exercise_id' => $exercice->id, 'answers' => [], 'score' => 1, 'accuracy_percent' => 75]);
}

function seancesDeLecture(int $userId, int $exerciceId, int $nombre, int $precision): void
{
    foreach (range(1, $nombre) as $ignored) {
        UserExerciseAttempt::create([
            'user_id' => $userId, 'exercise_id' => $exerciceId, 'answers' => [],
            'score' => 3, 'accuracy_percent' => $precision, 'time_spent' => 60, 'xp_earned' => 10, 'feedback' => [],
        ]);
    }
}

test('un apprenant A0 qui comprend les exercices A1 passe A1', function () {
    [$user, $skeleton, $exam, $exerciceId] = bilanApprenant(['done', 'pending'], ['A1', 'A1']);
    $user->profile->update(['current_level' => 'A0']);

    seancesDeLecture($user->id, $exerciceId, 6, 85);

    $nouveau = app(LevelAdvancementService::class)->assessAfterObjective($user->id, $skeleton);

    expect($nouveau)->toBe('A1')
        ->and($user->profile->fresh()->current_level)->toBe('A1')
        ->and(LevelAssessment::where('user_id', $user->id)->value('previous_level'))->toBe('A0');
});

test('un apprenant A0 qui n a pas assez pratique reste A0', function () {
    [$user, $skeleton, $exam, $exerciceId] = bilanApprenant(['done'], ['A1']);
    $user->profile->update(['current_level' => 'A0']);

    seancesDeLecture($user->id, $exerciceId, 2, 90);

    expect(app(LevelAdvancementService::class)->assessAfterObjective($user->id, $skeleton))->toBeNull()
        ->and($user->profile->fresh()->current_level)->toBe('A0');
});

test('le bilan detaille les quatre competences et ce qui manque', function () {
    [$user, $skeleton, $exam, $exerciceId] = bilanApprenant(['pending'], ['A1']);
    seancesDeLecture($user->id, $exerciceId, 5, 80);
    bilanEcrit($user->id, $exam);

    $bilan = app(LevelAdvancementService::class)->bilanDuNiveau($user->id, $exam->id, 'A1');

    expect($bilan['niveau_suivant'])->toBe('A2')
        ->and($bilan['pret'])->toBeFalse()
        ->and($bilan['competences']['lecture']['seances'])->toBe(5)
        ->and($bilan['competences']['ecoute']['seances'])->toBe(0)
        ->and($bilan['competences']['ecrit']['seances'])->toBe(1)
        ->and($bilan['competences']['oral']['seances'])->toBe(0)
        ->and($bilan['reception']['ok'])->toBeTrue()
        ->and($bilan['manques'])->toHaveCount(2);
});

test('le tableau de bord montre le bilan du niveau a l apprenant', function () {
    [$user, $skeleton, $exam, $exerciceId] = bilanApprenant(['pending'], ['A1']);
    seancesDeLecture($user->id, $exerciceId, 3, 80);

    $this->actingAs($user)->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('bilanNiveau.niveau', 'A1')
            ->where('bilanNiveau.niveau_suivant', 'A2')
            ->where('bilanNiveau.competences.lecture.seances', 3)
            ->where('bilanNiveau.pret', false));
});

/** Un noeud du parcours et ses exercices A1 de lecture, a trois questions chacun. */
function bilanNoeud(Exam $exam, string $type, int $nombre, string $niveau = 'A1'): array
{
    $node = LearningPathNode::create([
        'exam_id' => $exam->id, 'title' => $type === 'level_exam' ? "Examen de niveau {$niveau}" : 'Lecture A1',
        'node_type' => $type, 'level' => $niveau, 'sort_order' => 0, 'chapter_order' => 99, 'xp_reward' => 60,
    ]);
    $exercices = collect(range(1, $nombre))->map(fn ($ordre) => Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => ExerciseType::first()->id,
        'node_id' => $node->id, 'order_in_node' => $ordre, 'difficulty' => 'A1', 'content' => [],
        'questions' => collect(range(1, 3))->map(fn ($i) => [
            'id' => "q{$i}", 'type' => 'mcq', 'text' => "Question {$i}",
            'options' => ['Yes', 'No'], 'correct_answer' => 'A', 'explanation' => 'Oui.',
        ])->all(),
    ]));

    return [$node, $exercices];
}

function bilanReponses($exercices, string $reponse): array
{
    return [
        'exercise_ids' => $exercices->pluck('id')->all(),
        'answers_by_exercise' => $exercices->mapWithKeys(fn ($e) => [$e->id => ['q1' => $reponse, 'q2' => $reponse, 'q3' => $reponse]])->all(),
        'time_spent' => 120,
    ];
}

test('un apprenant A0 qui reussit l examen A1 en le soumettant monte de niveau', function () {
    Http::preventStrayRequests();
    [$user, $skeleton, $exam] = bilanApprenant(['done', 'pending'], ['A1', 'A2']);
    $user->profile->update(['current_level' => 'A0']);
    $skeleton->ensureLevelExams();
    expect($skeleton->fresh()->pendingLevelExam()['level'])->toBe('A1');

    [$node, $exercices] = bilanNoeud($exam, 'level_exam', 3);

    $this->actingAs($user)->post(route('exercise.submit_session', $node), bilanReponses($exercices, 'A'))
        ->assertRedirect();

    // L'examen A1 reussi valide le A1 : il passe A2, comme un apprenant A1.
    expect($user->profile->fresh()->current_level)->toBe('A2')
        ->and(LevelAssessment::where('user_id', $user->id)->value('previous_level'))->toBe('A0')
        ->and(LevelAssessment::where('user_id', $user->id)->value('assessment_type'))->toBe('boss_test')
        ->and($skeleton->fresh()->pendingLevelExam())->toBeNull();
});

test('un apprenant A0 qui echoue l examen A1 reste A0', function () {
    Http::preventStrayRequests();
    [$user, $skeleton, $exam] = bilanApprenant(['done', 'pending'], ['A1', 'A2']);
    $user->profile->update(['current_level' => 'A0']);
    $skeleton->ensureLevelExams();

    [$node, $exercices] = bilanNoeud($exam, 'level_exam', 3);

    $this->actingAs($user)->post(route('exercise.submit_session', $node), bilanReponses($exercices, 'B'))
        ->assertRedirect();

    expect($user->profile->fresh()->current_level)->toBe('A0')
        ->and(LevelAssessment::count())->toBe(0);
});

test('un examen A2 ne promeut pas un apprenant A0', function () {
    [$user, $skeleton, $exam] = bilanApprenant(['done'], ['A1']);
    $user->profile->update(['current_level' => 'A0']);

    expect(app(LevelAdvancementService::class)->assessAfterBossNode($user->id, $exam->id, 95, 'A2'))->toBeNull()
        ->and($user->profile->fresh()->current_level)->toBe('A0');
});

test('un apprenant A0 passe A1 en faisant ses seances de lecture A1', function () {
    Http::preventStrayRequests();
    [$user, $skeleton, $exam] = bilanApprenant(['current'], ['A1']);
    $user->profile->update(['current_level' => 'A0']);

    [$node, $exercices] = bilanNoeud($exam, 'practice', 5);

    $this->actingAs($user)->post(route('exercise.submit_session', $node), bilanReponses($exercices, 'A'))
        ->assertRedirect();

    expect($user->profile->fresh()->current_level)->toBe('A1')
        ->and(LevelAssessment::where('user_id', $user->id)->value('previous_level'))->toBe('A0');
});

test('un apprenant A0 passe A1 en ne faisant que des examens blancs', function () {
    Http::preventStrayRequests();
    [$user, $skeleton, $exam] = bilanApprenant(['current'], ['A1']);
    $user->profile->update(['current_level' => 'A0']);

    [, $exercices] = bilanNoeud($exam, 'practice', 5);
    Cache::put("practice-simulation:{$user->id}:{$exam->id}", $exercices->pluck('id')->all(), now()->addHour());

    $reponses = bilanReponses($exercices, 'A');
    unset($reponses['exercise_ids']);
    $this->actingAs($user)->post(route('practice.simulate.store', $exam), $reponses)->assertRedirect();

    expect($user->profile->fresh()->current_level)->toBe('A1')
        ->and(LevelAssessment::where('user_id', $user->id)->value('previous_level'))->toBe('A0');
});

test('un apprenant A0 qui rate ses seances de lecture A1 reste A0', function () {
    Http::preventStrayRequests();
    [$user, $skeleton, $exam] = bilanApprenant(['current'], ['A1']);
    $user->profile->update(['current_level' => 'A0']);

    [$node, $exercices] = bilanNoeud($exam, 'practice', 5);

    $this->actingAs($user)->post(route('exercise.submit_session', $node), bilanReponses($exercices, 'B'))
        ->assertRedirect();

    expect($user->profile->fresh()->current_level)->toBe('A0')
        ->and(LevelAssessment::count())->toBe(0);
});

test('le bilan annonce que l examen de niveau decide du passage hors A0', function () {
    [$user, $skeleton, $exam, $exerciceId] = bilanApprenant(['pending'], ['A1']);

    $this->actingAs($user)->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('bilanNiveau.regle', 'examen')
            ->where('bilanNiveau.examen_seuil', LevelAdvancementService::ADVANCE_THRESHOLD));

    $user->profile->update(['current_level' => 'A0']);
    $this->get('/dashboard')
        ->assertInertia(fn (Assert $page) => $page->where('bilanNiveau.regle', 'comprehension'));
});

test('un bilan complet ne fait pas monter sans l examen de niveau', function () {
    Http::preventStrayRequests();
    [$user, $skeleton, $exam, $exerciceId] = bilanApprenant(['current'], ['A1']);
    seancesDeLecture($user->id, $exerciceId, 6, 90);
    [$node, $exercices] = bilanNoeud($exam, 'practice', 1);

    $this->actingAs($user)->post(route('exercise.submit_session', $node), bilanReponses($exercices, 'A'))
        ->assertRedirect();

    expect($user->profile->fresh()->current_level)->toBe('A1');
});
