<?php

use App\Models\CurriculumSkeleton;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\User;
use App\Models\UserExerciseAttempt;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Http;

/**
 * Finir son parcours menait à une impasse : plus aucun objectif courant, donc plus
 * de leçon à ouvrir, et un niveau de profil figé depuis le test d'entrée. Un
 * apprenant est allé au bout de trente objectifs et s'est retrouvé devant un écran
 * « parcours terminé », toujours marqué A1, sans rien à faire.
 */
test('un parcours termine s ouvre sur l etape du niveau atteint', function () {
    config(['services.mistral.api_key' => 'test-key']);

    // Une seule réponse sert les deux appels : le plan d'étape lit « objectives »,
    // la leçon lit « theory_markdown ».
    Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => json_encode([
        'objectives' => collect(range(0, 11))->map(fn ($i) => [
            'order' => $i,
            'title' => "Objectif A2 numéro {$i}",
            'concept' => 'grammar.tense.past',
            'level' => 'A2',
        ])->all(),
        'title' => 'Le prétérit',
        'theory_markdown' => "# Le prétérit\n\nGestern spielte ich.",
        'key_takeaways' => ['-te pour les verbes faibles'],
        'common_mistakes' => [],
        'comprehension_quiz' => [[
            'question' => 'Quelle forme ?', 'options' => ['spielte', 'spiele'], 'correct_answer' => 'A',
        ]],
    ])]]]])]);

    $language = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'goethe-a1', 'name' => 'Goethe A1']);
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
        'target_exam_id' => $exam->id, 'current_level' => 'A1',
        'native_language' => 'Français', 'onboarding_completed_at' => now(),
    ]);

    CurriculumSkeleton::create([
        'user_id' => $user->id, 'exam_id' => $exam->id,
        'objectives' => collect(range(0, 2))->map(fn ($i) => [
            'order' => $i, 'title' => "Objectif A1 {$i}", 'concept' => 'grammar.basic',
            'level' => 'A1', 'status' => 'done', 'priority' => 'normal',
        ])->all(),
        'current_objective_index' => 2, 'consecutive_successes' => 3, 'consecutive_failures' => 0,
    ]);

    foreach (range(1, 6) as $ignored) {
        UserExerciseAttempt::create([
            'user_id' => $user->id, 'exercise_id' => $exercise->id, 'answers' => [],
            'score' => 3, 'accuracy_percent' => 88, 'time_spent' => 60, 'xp_earned' => 10, 'feedback' => [],
        ]);
    }

    // Avant : redirection vers la liste avec « Impossible de générer la prochaine leçon ».
    $this->actingAs($user)->get(route('lessons.next'))->assertRedirect();

    $skeleton = CurriculumSkeleton::where('user_id', $user->id)->sole();
    $objectives = collect($skeleton->objectives);

    expect($objectives)->toHaveCount(13) // 3 termines + 10 nouveaux, plafonnes a dix
        ->and($objectives[3]['status'])->toBe('current')
        ->and($objectives[3]['level'])->toBe('A2')
        ->and($skeleton->current_objective_index)->toBe(3)
        // Le niveau du profil suit : tous les objectifs A1 sont tenus a plus de 70 %.
        ->and($user->profile->fresh()->current_level)->toBe('A2')
        // Et une vraie leçon attend l'apprenant au bout du clic.
        ->and(Lesson::where('user_id', $user->id)->count())->toBe(1);
});

test('un parcours encore en cours n est pas prolonge', function () {
    config(['services.mistral.api_key' => '']);

    $language = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'goethe-a1', 'name' => 'Goethe A1']);

    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'target_exam_id' => $exam->id, 'current_level' => 'A1', 'onboarding_completed_at' => now(),
    ]);

    $skeleton = CurriculumSkeleton::create([
        'user_id' => $user->id, 'exam_id' => $exam->id,
        'objectives' => [
            ['order' => 0, 'title' => 'Fini', 'concept' => 'grammar.basic', 'level' => 'A1', 'status' => 'done', 'priority' => 'normal'],
            ['order' => 1, 'title' => 'En cours', 'concept' => 'grammar.basic', 'level' => 'A1', 'status' => 'current', 'priority' => 'normal'],
        ],
        'current_objective_index' => 1, 'consecutive_successes' => 0, 'consecutive_failures' => 0,
    ]);

    $extended = app(\App\Services\Curriculum\CurriculumPlannerService::class)
        ->extendForNextLevel($user, app(\App\Services\LevelAdvancementService::class));

    expect($extended)->toBeFalse()
        ->and(CurriculumSkeleton::where('user_id', $user->id)->sole()->objectives)->toHaveCount(2);
});
