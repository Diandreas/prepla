<?php

use App\Models\CurriculumSkeleton;
use App\Models\Exam;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Http;

/**
 * Une leçon sans questions ne mesure rien.
 *
 * Elle comptait pourtant pour 100 % : la série de réussites augmentait, et trois
 * leçons de ce genre d'affilée déclenchaient un saut de leçon — l'apprenant était
 * poussé en avant sur un savoir jamais vérifié.
 *
 * Le chemin est étroit, l'interface n'envoyant pas de quiz quand il n'y en a pas,
 * mais il est réel : une leçon restée sans quiz pendant une panne du service est
 * réécrite au passage suivant, et peut donc perdre son quiz entre l'affichage et
 * l'envoi des réponses.
 */
function quizlessWorld(): array
{
    $language = Language::firstOrCreate(['slug' => 'german'], [
        'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de',
    ]);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'quizless', 'name' => 'Quizless exam']);

    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'target_exam_id' => $exam->id,
        'onboarding_completed_at' => now(),
    ]);

    $skeleton = CurriculumSkeleton::create([
        'user_id' => $user->id,
        'exam_id' => $exam->id,
        'objectives' => [
            ['order' => 1, 'title' => 'Objectif 1', 'status' => 'current'],
            ['order' => 2, 'title' => 'Objectif 2', 'status' => 'pending'],
            ['order' => 3, 'title' => 'Objectif 3', 'status' => 'pending'],
        ],
        'consecutive_successes' => 2,
        'consecutive_failures' => 0,
    ]);

    return [$user, $skeleton];
}

function quizlessLesson(User $user, array $quiz = []): Lesson
{
    return Lesson::create([
        'user_id' => $user->id,
        'title' => 'Le prétérit',
        'theory_markdown' => 'Le prétérit sert à raconter au passé à l’écrit.',
        'comprehension_quiz' => $quiz,
    ]);
}

beforeEach(fn () => Http::preventStrayRequests());

test('une lecon sans questions ne compte pas comme une reussite', function () {
    [$user, $skeleton] = quizlessWorld();
    $lesson = quizlessLesson($user);

    // Le quiz a disparu entre l'affichage de la leçon et l'envoi des réponses.
    $response = $this->actingAs($user)
        ->postJson(route('lessons.quiz', $lesson), ['answers' => ['ging']])
        ->assertOk();

    // Rien n'est noté, et la série de réussites ne bouge pas : sans elle, trois
    // leçons de ce genre faisaient sauter la suivante.
    expect($response->json('accuracy'))->toBeNull()
        ->and($response->json('outcome'))->toBe('not_assessed')
        ->and($skeleton->fresh()->consecutive_successes)->toBe(2);
});

test('une lecon avec questions reussies compte normalement', function () {
    [$user, $skeleton] = quizlessWorld();
    $lesson = quizlessLesson($user, [
        ['question' => 'Wie lautet das Präteritum von gehen?', 'options' => ['ging', 'gegangen', 'geht', 'gehe'], 'correct_answer' => 'A'],
        ['question' => 'Und von sein?', 'options' => ['war', 'gewesen', 'ist', 'bin'], 'correct_answer' => 'A'],
        ['question' => 'Und von haben?', 'options' => ['hatte', 'gehabt', 'hat', 'habe'], 'correct_answer' => 'A'],
    ]);

    $response = $this->actingAs($user)
        ->postJson(route('lessons.quiz', $lesson), ['answers' => ['ging', 'war', 'hatte']])
        ->assertOk();

    // A streak opens practice, never certifies the following unattempted lessons.
    expect($response->json('accuracy'))->toBe(100)
        ->and($response->json('passed'))->toBeTrue()
        ->and($response->json('outcome'))->toBe('advance')
        ->and($skeleton->fresh()->current_objective_index)->toBe(1)
        ->and($skeleton->fresh()->objectives[0]['status'])->toBe('current_practice')
        ->and($skeleton->fresh()->objectives[2]['status'])->toBe('pending');
});

test('le quiz d une lecon d un autre apprenant est refuse', function () {
    [$owner] = quizlessWorld();
    $intruder = User::factory()->create();
    UserProfile::factory()->for($intruder)->create(['onboarding_completed_at' => now()]);
    $lesson = quizlessLesson($owner);

    $this->actingAs($intruder)
        ->postJson(route('lessons.quiz', $lesson), ['answers' => ['ging']])
        ->assertForbidden();
});
