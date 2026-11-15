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
