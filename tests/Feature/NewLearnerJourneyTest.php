<?php

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

/**
 * Un nouveau compte n'a aucun contenu à lui : tout est écrit à la demande par l'IA.
 * Quand ce service est saturé (429) ou en panne, le parcours doit rester praticable —
 * sinon l'inscription débouche sur une impasse, ce qui est arrivé en production.
 */
afterEach(fn () => File::deleteDirectory(storage_path('app/lessons/testlingua_via_franais')));

function prepareExam(): Exam
{
    // Langue fictive : le générateur met ses leçons en cache sur disque, sous un chemin
    // dérivé du nom de la langue. Une langue à part garde la bibliothèque réelle intacte.
    $language = Language::create(['slug' => 'testlingua', 'name' => 'Testlingua', 'native_name' => 'Testlingua', 'flag' => 'xx', 'is_active' => true]);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'testlingua-a1', 'name' => 'Testlingua A1']);
    $section = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'reading', 'name' => 'Reading', 'skill_type' => 'reading']);

    $types = [];
    foreach ([['mcq', 'reading'], ['gap-fill', 'grammar']] as [$key, $skill]) {
        $types[] = ExerciseType::create([
            'section_id' => $section->id, 'slug' => $key, 'name' => $key,
            'skill_type' => $skill, 'component_key' => $key,
        ]);
    }

    // Bibliothèque statique de l'examen : le filet de sécurité quand l'IA ne répond pas.
    foreach (range(1, 3) as $i) {
        Exercise::create([
            'exam_id' => $exam->id,
            'exercise_type_id' => $types[$i % 2]->id,
            'exam_section_id' => $section->id,
            'difficulty' => 'A1',
            'content' => [],
            'questions' => [[
                'id' => 'q1', 'type' => 'mcq', 'text' => "Frage {$i}",
                'options' => ['Ja', 'Nein'], 'correct_answer' => 'A', 'explanation' => 'Oui.',
            ]],
        ]);
    }

    return $exam;
}

function onboard(Exam $exam): User
{
    $user = User::factory()->create();
    test()->actingAs($user);

    test()->post(route('onboarding.native-language.store'), ['native_language' => 'Français'])->assertRedirect();
    test()->post(route('onboarding.exam.store'), ['exam_id' => $exam->id])->assertRedirect();
    test()->post(route('onboarding.goal.store'), ['current_level' => 'A1'])->assertRedirect();
    test()->post(route('onboarding.complete'))->assertRedirect(route('dashboard'));

    return $user;
}

/** @return array{lesson: ?array, practice: ?array} les deux nœuds du premier objectif */
function firstObjectiveNodes($response): array
{
    $nodes = collect($response->viewData('page')['props']['chapters'] ?? [])
        ->flatMap(fn ($chapter) => $chapter['nodes'] ?? []);

    return ['lesson' => $nodes->firstWhere('id', 'l_0'), 'practice' => $nodes->firstWhere('id', 'p_0')];
}

test('un nouveau compte garde lecon et pratique accessibles quand la generation est saturee', function () {
    config(['services.mistral.api_key' => 'test-key']);
    Http::fake(['*' => Http::response(['message' => 'rate limit exceeded'], 429)]);

    $exam = prepareExam();
    $user = onboard($exam);

    $nodes = firstObjectiveNodes($this->get('/dashboard')->assertOk());

    // 1. La leçon s'ouvre, même sans contenu rédigé.
    $redirect = $this->get($nodes['lesson']['action_url']);
    $redirect->assertRedirect();
    $this->get($redirect->headers->get('Location'))->assertOk();

    $lesson = Lesson::where('user_id', $user->id)->sole();
    expect($lesson->status)->toBe('draft'); // brouillon : régénérable, jamais figé

    // 2. Le parcours n'est pas verrouillé derrière cette leçon vide.
    $nodes = firstObjectiveNodes($this->get('/dashboard')->assertOk());
    expect($nodes['practice']['status'])->toBe('available');

    // 3. La pratique sert la bibliothèque statique au lieu d'une impasse.
    $this->get($nodes['practice']['action_url'])
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('exercises/player')->has('exercises', 3));
});

test('la lecon de secours est reecrite des que la generation repond', function () {
    config(['services.mistral.api_key' => 'test-key']);

    $aiDown = true;
    $written = ['choices' => [['message' => ['content' => json_encode([
        'title' => 'Se présenter',
        'concept' => 'grammar.basic',
        'theory_markdown' => "# Se présenter\n\nIch heiße Anna.",
        'key_takeaways' => ['Ich heiße + prénom'],
        'common_mistakes' => [],
        'comprehension_quiz' => [[
            'question' => 'Comment dire « je m’appelle » ?',
            'options' => ['Ich heiße', 'Ich bin alt'],
            'correct_answer' => 'A',
        ]],
    ])]]]];

    // Closure classique : la fonction flechee capturerait $aiDown par valeur.
    Http::fake(function () use (&$aiDown, $written) {
        return $aiDown
            ? Http::response(['message' => 'rate limit exceeded'], 429)
            : Http::response($written);
    });

    $exam = prepareExam();
    $user = onboard($exam);

    $this->get(route('lessons.next'))->assertRedirect();
    expect(Lesson::where('user_id', $user->id)->sole()->status)->toBe('draft');

    // Le quota repart : la même leçon est complétée sur place, sans doublon.
    $aiDown = false;
    $this->get(route('lessons.next'))->assertRedirect();

    $lesson = Lesson::where('user_id', $user->id)->sole();
    expect($lesson->status)->toBe('published')
        ->and($lesson->title)->toBe('Se présenter')
        ->and($lesson->comprehension_quiz)->toHaveCount(1);
});
