<?php

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\LearningPathNode;
use App\Models\Lesson;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Http;

/**
 * Le bilan de fin de séance ne servait à rien : « Question 1, Question 2 », sans
 * l'énoncé, sans la réponse donnée, sans la bonne réponse. Et son bouton « Revoir
 * la leçon » prenait l'identifiant du nœud pour celui d'une leçon → 404.
 */
test('le bilan de seance dit ce qui etait demande, ce qui a ete repondu et ce qu il fallait repondre', function () {
    Http::preventStrayRequests(); // chaque question porte son explication : aucun appel IA

    $language = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'goethe-a1', 'name' => 'Goethe A1']);
    $section = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'reading', 'name' => 'Reading', 'skill_type' => 'reading']);
    $type = ExerciseType::create([
        'section_id' => $section->id, 'slug' => 'mcq', 'name' => 'Multiple Choice',
        'skill_type' => 'reading', 'component_key' => 'mcq',
    ]);
    $node = LearningPathNode::create([
        'exam_id' => $exam->id, 'sort_order' => 1, 'title' => 'Grundlegende Begrüßungen', 'node_type' => 'lesson', 'level' => 'A1',
    ]);

    $exercise = Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'exam_section_id' => $section->id,
        'node_id' => $node->id, 'order_in_node' => 1, 'difficulty' => 'A1', 'content' => [],
        'questions' => [
            [
                'id' => 'q1', 'type' => 'mcq',
                'text' => 'Wie verabschiedet sich Anna?',
                'options' => ['Hallo!', 'Gut, danke!', 'Ich lerne Mathe.', 'Tschüss!'],
                'correct_answer' => 'D', 'explanation' => 'Im Text steht: Tschüss!',
            ],
            [
                'id' => 'q2', 'type' => 'note-completion',
                'text' => 'Ergänze die Notizen.',
                'notes' => [['label' => 'Gruß am Morgen', 'value' => '']],
                'correct_answers' => ['0' => 'Guten Morgen'], 'explanation' => 'Am Morgen sagt man Guten Morgen.',
            ],
        ],
    ]);

    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'target_exam_id' => $exam->id, 'current_level' => 'A1', 'onboarding_completed_at' => now(),
    ]);
    $lesson = Lesson::create([
        'user_id' => $user->id, 'node_id' => $node->id, 'skeleton_objective_index' => 0,
        'title' => 'Begrüßungen', 'theory_markdown' => '# Begrüßungen', 'status' => 'published',
    ]);

    $this->actingAs($user)->post(route('exercise.submit_session', $node), [
        'exercise_ids' => [$exercise->id],
        'answers_by_exercise' => [$exercise->id => ['q1' => 'A', 'q2' => ['0' => 'Guten Morgen']]],
        'time_spent' => 90,
    ])->assertRedirect(route('node.session_result', $node));

    $this->get(route('node.session_result', $node))
        ->assertOk()
        ->assertInertia(function ($page) use ($lesson) {
            $feedback = collect($page->toArray()['props']['report']['details'][0]['feedback']);
            $wrong = $feedback->firstWhere('question_id', 'q1');
            $right = $feedback->firstWhere('question_id', 'q2');

            expect($wrong['correct'])->toBeFalse()
                ->and($wrong['question_text'])->toBe('Wie verabschiedet sich Anna?')
                ->and($wrong['given_answer'])->toBe('A')
                // Une lettre seule n'apprend rien : on rend l'option en toutes lettres.
                ->and($wrong['expected_answer'])->toBe('D) Tschüss!')
                ->and($right['correct'])->toBeTrue()
                ->and($right['expected_answer'])->toBe('Guten Morgen')
                // Le bouton « Revoir la leçon » vise la leçon, pas le nœud.
                ->and($page->toArray()['props']['lessonId'])->toBe($lesson->id);
        });
});
