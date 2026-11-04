<?php

use App\Http\Controllers\NodeStartController;
use App\Http\Controllers\PracticeController;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\LearningPathNode;
use App\Models\User;
use App\Models\UserExerciseAttempt;
use App\Models\UserProfile;
use App\Services\AI\ExerciseGeneratorService;
use App\Services\Content\ProchainExercice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

/**
 * Ce que recoit un compte gratuit, cote serveur : le meilleur contenu du vivier
 * d'abord, aucune generation par l'IA une fois le quota atteint, et des
 * ecoutes qui font entendre le passage plutot que la question.
 */
beforeEach(fn () => Http::preventStrayRequests());

function qdvMonde(string $competence = 'reading'): array
{
    $language = Language::firstOrCreate(['slug' => 'german'], [
        'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de', 'is_active' => true,
    ]);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'qdv-'.Exam::count(), 'name' => 'QDV exam']);
    $section = ExamSection::create([
        'exam_id' => $exam->id, 'slug' => $competence, 'name' => ucfirst($competence), 'skill_type' => $competence,
    ]);
    $type = ExerciseType::create([
        'section_id' => $section->id, 'slug' => 'mcq', 'name' => 'mcq',
        'skill_type' => $competence, 'component_key' => 'mcq',
    ]);

    return [$exam, $type];
}

function qdvApprenant(Exam $exam, string $niveau, bool $gratuit = false): User
{
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'target_exam_id' => $exam->id,
        'current_level' => $niveau,
        'onboarding_completed_at' => now(),
        'trial_ends_at' => $gratuit ? now()->subDay() : now()->addWeek(),
    ]);

    return $user;
}

/** Un exercice du vivier libre, d'une source donnee : verifiee, serie, manuel, ia. */
function qdvExercice(Exam $exam, ExerciseType $type, string $niveau, string $source, array $attributs = []): Exercise
{
    $contenu = ['instructions' => 'Lies.'];
    if (in_array($source, ['epreuve-verifiee', 'starter-library'], true)) {
        $contenu['source'] = $source;
    }

    return Exercise::create($attributs + [
        'exam_id' => $exam->id,
        'exercise_type_id' => $type->id,
        'exam_section_id' => $type->section_id,
        'difficulty' => $niveau,
        'is_ai_generated' => $source === 'ia',
        'content' => $contenu,
        'questions' => [[
            'id' => 'q1', 'type' => 'mcq', 'text' => 'Wo?',
            'options' => ['Bonn', 'Berlin'], 'correct_answer' => 'A', 'explanation' => 'Bonn.',
        ]],
    ]);
}

// ── 1. Le meilleur contenu d'abord ─────────────────────────────────────────

test('jusqu au A2 : epreuve verifiee, serie preparee, manuel, puis IA', function () {
    [$exam, $type] = qdvMonde();
    $ia = qdvExercice($exam, $type, 'A2', 'ia');
    $manuel = qdvExercice($exam, $type, 'A2', 'manuel');
    $serie = qdvExercice($exam, $type, 'A2', 'starter-library');
    $verifiee = qdvExercice($exam, $type, 'A2', 'epreuve-verifiee');

    foreach (range(1, 5) as $tirage) {
        expect(Exercise::where('difficulty', 'A2')->meilleurDAbord('A2')->pluck('id')->all())
            ->toBe([$verifiee->id, $serie->id, $manuel->id, $ia->id]);
    }
});

test('a partir du B1, la serie preparee passe apres l IA : ses textes y sont plus courts', function () {
    [$exam, $type] = qdvMonde();
    $serie = qdvExercice($exam, $type, 'B1', 'starter-library');
    $ia = qdvExercice($exam, $type, 'B1', 'ia');
    $manuel = qdvExercice($exam, $type, 'B1', 'manuel');
    $verifiee = qdvExercice($exam, $type, 'B1', 'epreuve-verifiee');

    expect(Exercise::where('difficulty', 'B1')->meilleurDAbord('B1')->pluck('id')->all())
        ->toBe([$verifiee->id, $manuel->id, $ia->id, $serie->id]);
});

test('le hasard joue a l interieur d un meme groupe', function () {
    [$exam, $type] = qdvMonde();
    $ids = collect(range(1, 6))->map(fn () => qdvExercice($exam, $type, 'B1', 'ia')->id);

    $premiers = collect(range(1, 40))
        ->map(fn () => Exercise::meilleurDAbord('B1')->value('id'))
        ->unique();

    expect($premiers->count())->toBeGreaterThan(1)
        ->and($ids->intersect($premiers)->count())->toBe($premiers->count());
});

test('le prochain exercice propose une epreuve verifiee avant un exercice genere', function () {
    [$exam, $type] = qdvMonde();
    qdvExercice($exam, $type, 'B1', 'ia');
    qdvExercice($exam, $type, 'B1', 'ia');
    $verifiee = qdvExercice($exam, $type, 'B1', 'epreuve-verifiee');
    $user = qdvApprenant($exam, 'B1');

    expect(app(ProchainExercice::class)->pour($user, $exam, 'B1')['exercice']?->id)->toBe($verifiee->id);
});

test('pratiquer par type sert la serie preparee avant l IA jusqu au A2', function () {
    [$exam, $type] = qdvMonde();
    qdvExercice($exam, $type, 'A2', 'ia');
    $this->mock(ExerciseGeneratorService::class, fn ($mock) => $mock->shouldNotReceive('generate'));

    $reponse = $this->actingAs(qdvApprenant($exam, 'A2'))->get(route('practice.drill.type', [$exam, $type]));

    $serie = Exercise::whereNotNull('catalog_key')->sole();
    expect($serie->content['source'])->toBe('starter-library');
    $reponse->assertRedirect(route('exercise.show', $serie));
});

test('pratiquer par type sert l IA avant la serie preparee a partir du B1', function () {
    [$exam, $type] = qdvMonde();
    $ia = qdvExercice($exam, $type, 'B1', 'ia');
    qdvExercice($exam, $type, 'B1', 'starter-library');
    $this->mock(ExerciseGeneratorService::class, fn ($mock) => $mock->shouldNotReceive('generate'));

    $this->actingAs(qdvApprenant($exam, 'B1'))
        ->get(route('practice.drill.type', [$exam, $type]))
        ->assertRedirect(route('exercise.show', $ia));
});

test('le repechage d un noeud ne prend pas l exercice d un autre noeud', function () {
    [$exam, $type] = qdvMonde();
    $autreNoeud = LearningPathNode::create([
        'exam_id' => $exam->id, 'sort_order' => 1, 'title' => 'Begrüßungen', 'node_type' => 'practice', 'level' => 'A1',
    ]);
    $noeud = LearningPathNode::create([
        'exam_id' => $exam->id, 'sort_order' => 2, 'title' => 'Familie', 'node_type' => 'practice', 'level' => 'A1',
    ]);
    qdvExercice($exam, $type, 'A1', 'ia', ['node_id' => $autreNoeud->id, 'order_in_node' => 1]);
    $libre = qdvExercice($exam, $type, 'A1', 'ia');

    $methode = new ReflectionMethod(NodeStartController::class, 'genericExercises');
    $methode->setAccessible(true);

    expect($methode->invoke(app(NodeStartController::class), $noeud, 'A1')->pluck('id')->all())->toBe([$libre->id]);
});

// ── 2. Le quota avant la generation ────────────────────────────────────────

function qdvQuotaEpuise(User $user, Exercise $exercice): void
{
    foreach (range(1, 3) as $i) {
        UserExerciseAttempt::create([
            'user_id' => $user->id, 'exercise_id' => $exercice->id, 'answers' => [],
            'score' => 1, 'accuracy_percent' => 100, 'time_spent' => 10, 'xp_earned' => 5, 'feedback' => [],
        ]);
    }
}

test('un compte gratuit a court de quota ne fait plus generer d exercice', function (string $route) {
    [$exam, $type] = qdvMonde();
    $user = qdvApprenant($exam, 'B1', gratuit: true);
    qdvQuotaEpuise($user, qdvExercice($exam, $type, 'B1', 'ia'));
    $this->mock(ExerciseGeneratorService::class, fn ($mock) => $mock->shouldNotReceive('generate'));

    $url = $route === 'practice.drill.type' ? route($route, [$exam, $type]) : route($route, $exam);

    $this->actingAs($user)->get($url)
        ->assertRedirect(route('subscription.index'))
        ->assertSessionHas('error', fn (string $message) => str_contains($message, 'exercices gratuits'));
})->with(['practice.continuer', 'practice.drill.type']);

test('la generation d une section, appelee par aucun ecran, n est plus joignable', function () {
    expect(Route::has('practice.section.generate'))->toBeFalse()
        ->and(method_exists(PracticeController::class, 'generateSection'))->toBeFalse()
        // Code mort : aucune route ne menait a cette seance de noeud.
        ->and(method_exists(PracticeController::class, 'startNodeSession'))->toBeFalse();
});

// ── 3. Les ecoutes font entendre le passage ────────────────────────────────

function qdvEcoute(array $question, string $composant = 'mcq'): Exercise
{
    [$exam, $type] = qdvMonde('listening');
    $type->update(['component_key' => $composant]);

    $passage = 'Anna geht jeden Morgen mit ihrem Hund in den Park. Dort trifft sie ihren Nachbarn, '
        .'der die Zeitung liest. Sie sprechen über das Wetter und über den Markt am Samstag.';

    return Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'exam_section_id' => $type->section_id,
        'difficulty' => 'A1', 'is_ai_generated' => true,
        'content' => ['passage' => $passage, 'instructions' => 'Hör zu.'],
        'questions' => [$question + [
            'id' => 'q1', 'type' => $composant, 'text' => 'Was macht Anna im Park?',
            'options' => ['Sie liest.', 'Sie geht mit dem Hund.'], 'correct_answer' => 'B', 'explanation' => 'Hund.',
        ]],
    ])->load('exerciseType');
}

test('une ecoute dont le son recopie la question fait entendre le passage, sans l ancienne piste (#464)', function () {
    $exercice = qdvEcoute(['audio_text' => 'Was macht Anna im Park?', 'audio_url' => '/storage/tts/question.mp3']);

    $question = $exercice->answerableQuestions()[0];

    expect($question['audio_text'])->toBe($exercice->content['passage'])
        ->and($question)->not->toHaveKey('audio_url');
});

test('un son de moins du tiers du passage est remplace par le passage', function () {
    $exercice = qdvEcoute(['audio_text' => 'Anna ist im Park.', 'audio_url' => '/storage/tts/court.mp3']);

    $question = $exercice->answerableQuestions()[0];

    expect($question['audio_text'])->toBe($exercice->content['passage'])
        ->and($question)->not->toHaveKey('audio_url');
});

test('un son vide recoit le passage et garde la piste fabriquee depuis le passage', function () {
    // prepla:fabriquer-medias fabrique la piste d'une question sans texte parle
    // a partir du passage : elle est bonne, la retirer rendrait l'ecoute muette.
    $exercice = qdvEcoute(['audio_url' => '/storage/tts/passage.mp3']);

    $question = $exercice->answerableQuestions()[0];

    expect($question['audio_text'])->toBe($exercice->content['passage'])
        ->and($question['audio_url'])->toBe('/storage/tts/passage.mp3');
});

test('un vrai enregistrement n est pas touche', function () {
    $son = 'Anna geht jeden Morgen mit ihrem Hund in den Park und trifft dort ihren Nachbarn.';
    $exercice = qdvEcoute(['audio_text' => $son, 'audio_url' => '/storage/tts/bon.mp3']);

    $question = $exercice->answerableQuestions()[0];

    expect($question['audio_text'])->toBe($son)
        ->and($question['audio_url'])->toBe('/storage/tts/bon.mp3');
});

test('une dictee garde sa phrase courte', function () {
    $exercice = qdvEcoute(['audio_text' => 'Ich gehe.', 'audio_url' => '/storage/tts/dictee.mp3'], 'dictation');

    expect($exercice->answerableQuestions()[0]['audio_text'])->toBe('Ich gehe.');
});

// ── 4. La page d'un exercice sert ce que les seances servent ───────────────

test('la page d un exercice ecarte les questions impossibles et corrige le son', function () {
    $exercice = qdvEcoute(['audio_text' => 'Was macht Anna im Park?', 'audio_url' => '/storage/tts/question.mp3']);
    $questions = $exercice->questions;
    $questions[] = ['id' => 'q2', 'type' => 'mcq', 'text' => 'Wer?', 'options' => ['Anna', 'Paul'],
        'correct_answer' => 'D', 'audio_text' => 'Was macht Anna im Park?'];
    $exercice->update(['questions' => $questions]);

    $this->actingAs(qdvApprenant($exercice->exam, 'A1'))
        ->get(route('exercise.show', $exercice))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('exercise.questions', 1)
            ->where('exercise.questions.0.audio_text', $exercice->content['passage'])
            ->missing('exercise.questions.0.audio_url'));

    // Rien n'est enregistre : le contenu d'origine reste reparable.
    expect($exercice->fresh()->questions)->toHaveCount(2);
});

test('un exercice sans aucune question jouable renvoie vers la pratique', function () {
    [$exam, $type] = qdvMonde();
    $casse = qdvExercice($exam, $type, 'A1', 'ia', ['questions' => [[
        'id' => 'q1', 'type' => 'mcq', 'text' => 'Wo?', 'options' => ['Bonn'], 'correct_answer' => 'D',
    ]]]);

    $this->actingAs(qdvApprenant($exam, 'A1'))
        ->get(route('exercise.show', $casse))
        ->assertRedirect(route('practice.index'));
});
