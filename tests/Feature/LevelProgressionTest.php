<?php

use App\Models\CurriculumSkeleton;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\LevelAssessment;
use App\Models\User;
use App\Models\UserExerciseAttempt;
use App\Models\UserProfile;
use App\Services\LevelAdvancementService;

/**
 * Un apprenant traversait tout son parcours — des pronoms de base à la fluidité
 * avancée — en ne recevant que des exercices A1, et restait marqué A1 à vie : le
 * service de montée de niveau n'était appelé de nulle part.
 */
function learnerWithPath(array $statuses, ?array $levels = null): array
{
    $language = Language::create(['slug' => 'english', 'name' => 'English', 'native_name' => 'English', 'flag' => 'gb']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'ielts', 'name' => 'IELTS']);
    $section = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'reading', 'name' => 'Reading', 'skill_type' => 'reading']);
    $type = ExerciseType::create([
        'section_id' => $section->id, 'slug' => 'mcq', 'name' => 'MCQ', 'skill_type' => 'reading', 'component_key' => 'mcq',
    ]);
    $exercise = Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'exam_section_id' => $section->id,
        'difficulty' => 'A1', 'content' => [], 'questions' => [],
    ]);

    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'target_exam_id' => $exam->id, 'current_level' => 'A1', 'onboarding_completed_at' => now(),
    ]);

    $objectives = [];
    foreach ($statuses as $index => $status) {
        $objectives[] = array_filter([
            'order' => $index,
            'title' => "Objectif {$index}",
            'concept' => 'grammar.basic',
            'level' => $levels[$index] ?? null,
            'status' => $status,
            'priority' => 'normal',
        ], fn ($value) => $value !== null);
    }

    $skeleton = CurriculumSkeleton::create([
        'user_id' => $user->id, 'exam_id' => $exam->id, 'objectives' => $objectives,
        'current_objective_index' => 0, 'consecutive_successes' => 0, 'consecutive_failures' => 0,
    ]);

    return [$user, $skeleton, $exam, $exercise->id];
}

test('un parcours sans niveaux se voit attribuer une progression, pas du A1 partout', function () {
    [$user, $skeleton] = learnerWithPath(array_fill(0, 30, 'pending'));

    $skeleton->ensureObjectiveLevels('A1');

    $levels = collect($skeleton->fresh()->objectives)->pluck('level');

    // Un palier par tiers de parcours, deux crans au maximum au-dessus du départ.
    expect($levels[0])->toBe('A1')
        ->and($levels[9])->toBe('A1')
        ->and($levels[10])->toBe('A2')
        ->and($levels[20])->toBe('B1')
        ->and($levels[29])->toBe('B1')
        ->and($levels->unique()->values()->all())->toBe(['A1', 'A2', 'B1']);

    // Figés une fois pour toutes : un apprenant qui monte ne fait pas glisser son parcours.
    $skeleton->ensureObjectiveLevels('B2');
    expect(collect($skeleton->fresh()->objectives)->pluck('level')->first())->toBe('A1');
});

test('finir tous les objectifs de son niveau fait monter d un cran', function () {
    // Trois objectifs A1 terminés, la suite en A2 encore à faire.
    [$user, $skeleton, $exam, $exerciseId] = learnerWithPath(
        ['done', 'done', 'done', 'pending', 'pending'],
        ['A1', 'A1', 'A1', 'A2', 'A2'],
    );

    foreach (range(1, 6) as $ignored) {
        UserExerciseAttempt::create([
            'user_id' => $user->id, 'exercise_id' => $exerciseId, 'answers' => [],
            'score' => 3, 'accuracy_percent' => 85, 'time_spent' => 60, 'xp_earned' => 10, 'feedback' => [],
        ]);
    }

    $promoted = app(LevelAdvancementService::class)->assessAfterObjective($user->id, $skeleton);

    expect($promoted)->toBe('A2')
        ->and($user->profile->fresh()->current_level)->toBe('A2')
        ->and(LevelAssessment::where('user_id', $user->id)->value('assessment_type'))->toBe('curriculum');
});

test('on ne monte pas tant qu il reste du travail au niveau courant', function () {
    [$user, $skeleton, $exam, $exerciseId] = learnerWithPath(['done', 'pending', 'done'], ['A1', 'A1', 'A2']);

    foreach (range(1, 6) as $ignored) {
        UserExerciseAttempt::create([
            'user_id' => $user->id, 'exercise_id' => $exerciseId, 'answers' => [],
            'score' => 3, 'accuracy_percent' => 95, 'time_spent' => 60, 'xp_earned' => 10, 'feedback' => [],
        ]);
    }

    expect(app(LevelAdvancementService::class)->assessAfterObjective($user->id, $skeleton))->toBeNull()
        ->and($user->profile->fresh()->current_level)->toBe('A1');
});

test('on ne monte pas avec une precision insuffisante', function () {
    [$user, $skeleton, $exam, $exerciseId] = learnerWithPath(['done', 'done'], ['A1', 'A1']);

    foreach (range(1, 6) as $ignored) {
        UserExerciseAttempt::create([
            'user_id' => $user->id, 'exercise_id' => $exerciseId, 'answers' => [],
            'score' => 1, 'accuracy_percent' => 61, 'time_spent' => 60, 'xp_earned' => 3, 'feedback' => [],
        ]);
    }

    expect(app(LevelAdvancementService::class)->assessAfterObjective($user->id, $skeleton))->toBeNull()
        ->and(LevelAssessment::count())->toBe(0);
});

test('des exercices plus faciles ne font pas monter de niveau', function () {
    // La moyenne portait sur les vingt dernières séances, toutes difficultés
    // confondues. Le repêchage du parcours sert des exercices d'un autre niveau
    // quand il n'en trouve aucun au bon, et l'espace hors ligne en propose aussi :
    // une série de succès sur du plus facile suffisait à promouvoir.
    [$user, $skeleton, $exam, $exerciseId] = learnerWithPath(array_fill(0, 3, 'done'));
    $user->profile->update(['current_level' => 'B1']);

    $easier = Exercise::find($exerciseId);           // difficulté A1
    $atLevel = Exercise::create([
        'exam_id' => $exam->id,
        'exercise_type_id' => $easier->exercise_type_id,
        'exam_section_id' => $easier->exam_section_id,
        'difficulty' => 'B1', 'content' => [], 'questions' => [],
    ]);

    // Cinq réussites parfaites, mais sur des exercices d'un niveau inférieur.
    foreach (range(1, 5) as $i) {
        UserExerciseAttempt::create([
            'user_id' => $user->id, 'exercise_id' => $easier->id,
            'answers' => [], 'score' => 1, 'accuracy_percent' => 100,
        ]);
    }

    expect(app(LevelAdvancementService::class)->assessAfterObjective($user->id, $skeleton))->toBeNull()
        ->and($user->profile->fresh()->current_level)->toBe('B1');

    // Les mêmes réussites au bon niveau font bien monter.
    foreach (range(1, 5) as $i) {
        UserExerciseAttempt::create([
            'user_id' => $user->id, 'exercise_id' => $atLevel->id,
            'answers' => [], 'score' => 1, 'accuracy_percent' => 100,
        ]);
    }

    expect(app(LevelAdvancementService::class)->assessAfterObjective($user->id, $skeleton->fresh()))->toBe('B2');
});
