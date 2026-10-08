<?php

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\LanguageCenter;
use App\Models\LearningPathNode;
use App\Models\User;
use App\Models\UserExerciseAttempt;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Http;

/**
 * Un élève inscrit dans la classe d'un enseignant n'est pas un apprenant libre :
 * son travail lui est donné par son professeur. Le quota de 3 exercices gratuits le
 * renvoyait vers la page d'abonnement au quatrième exercice de son devoir.
 */
test('un eleve de centre n est pas arrete par le quota gratuit', function () {
    Http::preventStrayRequests();

    $language = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'goethe-a1', 'name' => 'Goethe A1']);
    $section = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'reading', 'name' => 'Reading', 'skill_type' => 'reading']);
    $type = ExerciseType::create([
        'section_id' => $section->id, 'slug' => 'mcq', 'name' => 'MCQ', 'skill_type' => 'reading', 'component_key' => 'mcq',
    ]);
    $node = LearningPathNode::create([
        'exam_id' => $exam->id, 'sort_order' => 1, 'title' => 'Begrüßungen', 'node_type' => 'general', 'level' => 'A1',
    ]);

    $exercises = collect(range(1, 3))->map(fn ($i) => Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'exam_section_id' => $section->id,
        'node_id' => $node->id, 'order_in_node' => $i, 'difficulty' => 'A1', 'content' => [],
        'questions' => [[
            'id' => 'q1', 'type' => 'mcq', 'text' => "Frage {$i}",
            'options' => ['Ja', 'Nein'], 'correct_answer' => 'A', 'explanation' => 'Oui.',
        ]],
    ]));

    $center = LanguageCenter::create([
        'name' => 'Cours de Zidane', 'slug' => 'cours-zidane',
        'owner_email' => 'zidane@exemple.test', 'is_active' => true,
    ]);

    $student = User::factory()->create();
    UserProfile::factory()->for($student)->create([
        'target_exam_id' => $exam->id, 'current_level' => 'A1',
        'onboarding_completed_at' => now(), 'trial_ends_at' => now()->subDay(), // essai termine
    ]);
    $center->members()->attach($student->id, ['role' => 'student', 'joined_at' => now()]);

    // Son quota du jour est deja consomme.
    foreach ($exercises as $exercise) {
        UserExerciseAttempt::create([
            'user_id' => $student->id, 'exercise_id' => $exercise->id, 'answers' => [],
            'score' => 1, 'accuracy_percent' => 100, 'time_spent' => 10, 'xp_earned' => 5, 'feedback' => [],
        ]);
    }

    expect($student->hasPremiumAccess())->toBeFalse()
        ->and($student->isCenterStudent())->toBeTrue();

    // Il continue de travailler : le devoir vient de son etablissement.
    $this->actingAs($student)->get(route('node.start', $node))->assertOk();
});

test('un apprenant libre reste arrete par le quota', function () {
    Http::preventStrayRequests();

    $language = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'goethe-a1', 'name' => 'Goethe A1']);
    $section = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'reading', 'name' => 'Reading', 'skill_type' => 'reading']);
    $type = ExerciseType::create([
        'section_id' => $section->id, 'slug' => 'mcq', 'name' => 'MCQ', 'skill_type' => 'reading', 'component_key' => 'mcq',
    ]);
    $node = LearningPathNode::create([
        'exam_id' => $exam->id, 'sort_order' => 1, 'title' => 'Begrüßungen', 'node_type' => 'general', 'level' => 'A1',
    ]);
    $exercise = Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'exam_section_id' => $section->id,
        'node_id' => $node->id, 'order_in_node' => 1, 'difficulty' => 'A1', 'content' => [],
        'questions' => [['id' => 'q1', 'type' => 'mcq', 'text' => 'Frage', 'options' => ['Ja', 'Nein'], 'correct_answer' => 'A', 'explanation' => 'Oui.']],
    ]);

    $learner = User::factory()->create();
    UserProfile::factory()->for($learner)->create([
        'target_exam_id' => $exam->id, 'current_level' => 'A1',
        'onboarding_completed_at' => now(), 'trial_ends_at' => now()->subDay(),
    ]);

    foreach (range(1, 3) as $ignored) {
        UserExerciseAttempt::create([
            'user_id' => $learner->id, 'exercise_id' => $exercise->id, 'answers' => [],
            'score' => 1, 'accuracy_percent' => 100, 'time_spent' => 10, 'xp_earned' => 5, 'feedback' => [],
        ]);
    }

    $this->actingAs($learner)->get(route('node.start', $node))
        ->assertRedirect(route('subscription.index'));
});
