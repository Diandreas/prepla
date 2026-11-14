<?php

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\LearningPathNode;
use App\Models\Lesson;
use App\Models\User;
use App\Models\UserError;
use App\Models\UserProfile;
use App\Services\Curriculum\NextLessonGenerator;
use Inertia\Testing\AssertableInertia;

/** Un examen, un type QCM, et un apprenant qui le prepare. */
function revisionMonde(): array
{
    $language = Language::firstOrCreate(['slug' => 'german'], ['name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'goethe-rev', 'name' => 'Goethe A1']);
    $section = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'grammar', 'name' => 'Grammar', 'skill_type' => 'grammar']);
    $type = ExerciseType::create([
        'section_id' => $section->id, 'slug' => 'mcq-rev', 'name' => 'MCQ', 'skill_type' => 'grammar', 'component_key' => 'mcq',
    ]);
    $node = LearningPathNode::create([
        'exam_id' => $exam->id, 'sort_order' => 1, 'title' => 'Articles', 'node_type' => 'general', 'level' => 'A1',
    ]);
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'target_exam_id' => $exam->id, 'current_level' => 'A1', 'onboarding_completed_at' => now(),
    ]);

    return compact('exam', 'section', 'type', 'node', 'user');
}

function revisionExercice(array $monde, int $ordre, string $texte, array $options, string $bonne): Exercise
{
    return Exercise::create([
        'exam_id' => $monde['exam']->id, 'exercise_type_id' => $monde['type']->id, 'exam_section_id' => $monde['section']->id,
        'node_id' => $monde['node']->id, 'order_in_node' => $ordre, 'difficulty' => 'A1', 'content' => [],
        'questions' => [[
            'id' => 'q1', 'type' => 'mcq', 'text' => $texte,
            'options' => $options, 'correct_answer' => $bonne, 'explanation' => "Explication {$ordre}",
        ]],
    ]);
}

function revisionErreur(User $user, ?Exercise $exercise, string $attendu = 'ging'): UserError
{
    return UserError::create([
        'user_id' => $user->id, 'exercise_id' => $exercise?->id, 'question_id' => 'q1',
        'question_text' => $exercise ? $exercise->questions[0]['text'] : 'Gestern ___ ich ins Kino.',
        'user_answer' => 'gehe', 'correct_answer' => $attendu, 'explanation' => 'Passe.',
        'skill_type' => 'grammar', 'exercise_type_slug' => $exercise ? 'mcq-rev' : 'gap-fill',
        'error_category' => 'grammar', 'mastered' => false, 'reviewed_count' => 0,
        'next_review_at' => now()->subDay(),
    ]);
}

// --- 1. La bonne reponse ne part plus avant la reponse -----------------------

test('la revision n envoie pas la bonne reponse avant la reponse', function () {
    $monde = revisionMonde();
    revisionErreur($monde['user'], null);

    $this->actingAs($monde['user'])->get(route('errors.practice'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('errors/practice')
            ->has('errors', 1)
            ->missing('errors.0.correct_answer')
            ->where('errors.0.recall', true)
            ->where('errors.0.explanation', null));

    $this->actingAs($monde['user'])->get(route('errors.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page->missing('errors.data.0.correct_answer'));
});

test('le serveur corrige la reponse et renvoie alors la correction', function () {
    $monde = revisionMonde();
    $erreur = revisionErreur($monde['user'], null);

    $this->actingAs($monde['user'])->get(route('errors.practice'));

    $this->actingAs($monde['user'])
        ->postJson(route('errors.submit-review', $erreur), ['answer' => 'gehe'])
        ->assertOk()
        ->assertJsonPath('correct', false)
        ->assertJsonPath('correct_answer', 'ging')
        ->assertJsonPath('explanation', 'Passe.');

    $this->actingAs($monde['user'])
        ->postJson(route('errors.submit-review', $erreur), ['answer' => '  GING '])
        ->assertOk()
        ->assertJsonPath('correct', true);
});

test('la page ne peut plus se declarer juste toute seule', function () {
    $monde = revisionMonde();
    $erreur = revisionErreur($monde['user'], null);

    $this->actingAs($monde['user'])
        ->postJson(route('errors.submit-review', $erreur), ['correct' => true])
        ->assertStatus(422);

    expect($erreur->fresh()->reviewed_count)->toBe(0);
});

// --- 2. La question varie ------------------------------------------------------

test('la revision pose une autre question du meme type quand le vivier en a une', function () {
    $monde = revisionMonde();
    $origine = revisionExercice($monde, 1, 'Ich ___ Lina.', ['heisse', 'bin Montag'], 'A');
    revisionExercice($monde, 2, 'Du ___ Paul.', ['heisst', 'bist Montag', 'heissen'], 'A');
    $erreur = revisionErreur($monde['user'], $origine, 'A) heisse');

    $this->actingAs($monde['user'])->get(route('errors.practice'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('errors.0.variant', true)
            ->where('errors.0.prompt', 'Du ___ Paul.')
            ->where('errors.0.options', fn ($options) => collect($options)->sort()->values()->all() === ['bist Montag', 'heissen', 'heisst']));

    // La correction se fait contre la question POSEE, en texte.
    $this->actingAs($monde['user'])
        ->postJson(route('errors.submit-review', $erreur), ['answer' => 'heisst'])
        ->assertJsonPath('correct', true)
        ->assertJsonPath('correct_answer', 'heisst')
        ->assertJsonPath('explanation', 'Explication 2');
});

test('sans autre question, la meme est reposee avec ses choix melanges', function () {
    $monde = revisionMonde();
    $options = ['eins', 'zwei', 'drei', 'vier', 'fuenf', 'sechs', 'sieben', 'acht'];
    $origine = revisionExercice($monde, 1, 'Zahl ___ ?', $options, 'C');
    $erreur = revisionErreur($monde['user'], $origine, 'C) drei');

    $ordres = [];
    foreach (range(1, 6) as $ignored) {
        $this->actingAs($monde['user'])->get(route('errors.practice'))
            ->assertInertia(function (AssertableInertia $page) use (&$ordres) {
                $page->where('errors.0.variant', false)->where('errors.0.prompt', 'Zahl ___ ?');
                $ordres[] = implode(',', $page->toArray()['props']['errors'][0]['options']);
            });
    }

    // Toujours les memes choix, mais pas toujours dans l'ordre d'origine.
    expect(collect($ordres)->unique()->count())->toBeGreaterThan(1);

    $this->actingAs($monde['user'])
        ->postJson(route('errors.submit-review', $erreur), ['answer' => 'drei'])
        ->assertJsonPath('correct', true);
});

// --- 3. Quiz de lecon : seuil explicite -----------------------------------------

test('un quiz vide n est jamais reussi', function () {
    $lesson = new Lesson(['comprehension_quiz' => []]);

    expect($lesson->isComprehensionPassed([]))->toBeFalse();
});

test('le seuil est de 3 bonnes reponses sur 4, toutes sauf une sous 4 questions', function () {
    expect(Lesson::bonnesReponsesRequises(4))->toBe(3)
        ->and(Lesson::bonnesReponsesRequises(8))->toBe(6)
        ->and(Lesson::bonnesReponsesRequises(5))->toBe(4)
        ->and(Lesson::bonnesReponsesRequises(3))->toBe(2)
        ->and(Lesson::bonnesReponsesRequises(2))->toBe(1)
        ->and(Lesson::bonnesReponsesRequises(1))->toBe(1);
});

test('2 bonnes reponses sur 4 ne suffisent plus, 3 oui', function () {
    $quiz = collect(range(1, 4))->map(fn ($i) => [
        'type' => 'mcq', 'question' => "Q{$i}", 'options' => ['oui', 'non'], 'correct_answer' => 'A',
    ])->all();
    $lesson = new Lesson(['comprehension_quiz' => $quiz]);

    expect($lesson->isComprehensionPassed(['A', 'A', 'B', 'B']))->toBeFalse()
        ->and($lesson->isComprehensionPassed(['A', 'A', 'A', 'B']))->toBeTrue();
});

// --- 4. Cache des lecons : par examen et par tranche ---------------------------

test('le cache des lecons separe les examens et les tranches du niveau', function () {
    $base = ['language' => 'French', 'native_language' => 'English', 'level' => 'B1', 'position' => 0, 'total' => 10];

    $tcf = NextLessonGenerator::dossierDuCache($base + ['exam_slug' => 'tcf-tp']);
    $delf = NextLessonGenerator::dossierDuCache($base + ['exam_slug' => 'delf-b1']);
    $finDeNiveau = NextLessonGenerator::dossierDuCache(['position' => 9] + $base + ['exam_slug' => 'tcf-tp']);

    expect($tcf)->not->toBe($delf)
        ->and($tcf)->toContain('tcf-tp')
        ->and($delf)->toContain('delf-b1')
        ->and($finDeNiveau)->not->toBe($tcf)
        ->and($finDeNiveau)->toEndWith('fin')
        ->and($tcf)->toEndWith('debut');
});
