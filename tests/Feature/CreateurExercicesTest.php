<?php

use App\Http\Controllers\Center\ExerciseBuilderController;
use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Models\UserProfile;
use App\Notifications\AssignmentPublishedNotification;
use App\Services\AI\ExerciseGeneratorService;
use App\Services\AI\MistralService;
use App\Services\Content\ExerciseSchemaRegistry;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Le createur d'exercices des centres : la demande faite a l'IA (theme,
 * consignes, nombre de questions), la limite d'appels, l'examen du centre a
 * l'enregistrement, la liste « Contenu », et les devoirs qui piochent dans la
 * banque PrePla sans jamais toucher aux exercices prives d'un autre centre.
 */
beforeEach(function () {
    $this->withoutVite();
    Notification::fake();
});

/** Un centre (Goethe propose, TCF non), son responsable, une classe B1 et deux types. */
function createurCentre(): array
{
    $de = Language::create(['slug' => 'german', 'name' => 'Allemand', 'native_name' => 'Deutsch', 'flag' => 'de', 'is_active' => true]);
    $fr = Language::create(['slug' => 'french', 'name' => 'Français', 'native_name' => 'Français', 'flag' => 'fr', 'is_active' => true]);
    $goethe = Exam::create(['language_id' => $de->id, 'slug' => 'goethe', 'name' => 'Goethe']);
    $tcf = Exam::create(['language_id' => $fr->id, 'slug' => 'tcf', 'name' => 'TCF']);

    $lesen = ExamSection::create(['exam_id' => $goethe->id, 'slug' => 'lesen', 'name' => 'Lesen', 'skill_type' => 'reading']);
    $hoeren = ExamSection::create(['exam_id' => $goethe->id, 'slug' => 'hoeren', 'name' => 'Hören', 'skill_type' => 'listening']);
    $qcm = ExerciseType::create(['section_id' => $lesen->id, 'slug' => 'mcq', 'name' => 'Multiple Choice', 'skill_type' => 'reading', 'component_key' => 'mcq']);
    $vf = ExerciseType::create(['section_id' => $hoeren->id, 'slug' => 'true-false', 'name' => 'True/False', 'skill_type' => 'listening', 'component_key' => 'true-false-ng']);

    $centre = LanguageCenter::create([
        'name' => 'Centre Bastos', 'slug' => 'centre-bastos', 'owner_email' => 'bastos@exemple.test', 'is_active' => true,
    ]);
    $centre->examensProposes()->sync([$goethe->id]);

    $resp = User::factory()->create();
    UserProfile::factory()->for($resp)->create(['onboarding_completed_at' => now()]);
    $centre->members()->attach($resp->id, ['role' => 'center_admin', 'joined_at' => now()]);

    $classe = $centre->classrooms()->create([
        'name' => 'B1 du soir', 'level' => 'B1', 'exam_id' => $goethe->id,
        'invite_code' => Classroom::generateInviteCode(),
    ]);
    $classe->members()->attach($resp->id, ['role_in_class' => 'teacher']);

    return compact('goethe', 'tcf', 'qcm', 'vf', 'centre', 'resp', 'classe');
}

function createurQcm(): array
{
    return [[
        'id' => '', 'text' => 'Wo arbeitet Anna?', 'options' => ['Im Krankenhaus', 'In der Schule', 'Im Büro'],
        'correct_answer' => 'A', 'explanation' => 'Elle est infirmière.',
    ]];
}

function createurExercice(array $c, array $attributs = []): Exercise
{
    return Exercise::create(array_merge([
        'exam_id' => $c['goethe']->id, 'exercise_type_id' => $c['qcm']->id, 'center_id' => $c['centre']->id,
        'difficulty' => 'B1', 'content' => ['title' => 'Chez le médecin'], 'questions' => createurQcm(),
    ], $attributs));
}

// ───────────────────────────── IA ─────────────────────────────

test('le theme, les consignes et le nombre de questions vont au generateur', function () {
    $c = createurCentre();

    $this->mock(ExerciseGeneratorService::class, function ($mock) use ($c) {
        $mock->shouldReceive('generate')->once()->withArgs(function ($type, $exam, $niveau, $lecon, $partie, $faiblesse, $demande) use ($c) {
            return $type->is($c['qcm']) && $exam->is($c['goethe']) && $niveau === 'B1'
                && $lecon === null && $partie === null && $faiblesse === null
                && $demande === ['theme' => 'la santé', 'consignes' => 'des prénoms camerounais', 'nombre_questions' => 4];
        })->andReturn(new Exercise(['content' => ['passage' => 'Anna...'], 'questions' => createurQcm()]));
    });

    $this->actingAs($c['resp'])->postJson(route('center.exercises.ai-draft'), [
        'exercise_type_id' => $c['qcm']->id, 'exam_id' => $c['goethe']->id, 'difficulty' => 'B1',
        'theme' => 'la santé', 'consignes' => 'des prénoms camerounais', 'nombre_questions' => 4,
    ])->assertOk()->assertJsonPath('content.passage', 'Anna...')->assertJsonCount(1, 'questions');
});

test('sans parametre, la demande ne change rien, et un nombre absurde est refuse', function () {
    $c = createurCentre();

    $this->mock(ExerciseGeneratorService::class, function ($mock) {
        $mock->shouldReceive('generate')->once()->withArgs(fn (...$a) => $a[6] === ['theme' => null, 'consignes' => null, 'nombre_questions' => null])
            ->andReturn(new Exercise(['content' => [], 'questions' => createurQcm()]));
    });

    $base = ['exercise_type_id' => $c['qcm']->id, 'exam_id' => $c['goethe']->id, 'difficulty' => 'B1'];
    $this->actingAs($c['resp'])->postJson(route('center.exercises.ai-draft'), $base)->assertOk();
    $this->postJson(route('center.exercises.ai-draft'), $base + ['nombre_questions' => 50])->assertJsonValidationErrors('nombre_questions');
});

test('le prompt porte le theme et les consignes, et le nombre demande remplace celui de la partie', function () {
    $c = createurCentre();

    // Un faux Mistral qui garde le prompt et ne repond rien : la generation
    // echoue, mais on a lu ce qui aurait ete demande.
    $faux = new class extends MistralService
    {
        public array $prompts = [];

        public function __construct() {}

        public function chat(array $messages, string $model = 'mistral-small-latest'): ?string
        {
            $this->prompts[] = $messages[1]['content'] ?? '';

            return null;
        }
    };
    app()->instance(MistralService::class, $faux);

    try {
        app(ExerciseGeneratorService::class)->generate($c['qcm'], $c['goethe'], 'B1', null, null, null, [
            'theme' => "  le travail\n en Allemagne ", 'consignes' => 'phrases courtes', 'nombre_questions' => 4,
        ]);
    } catch (\RuntimeException) {
        // attendu : le faux Mistral ne repond rien
    }

    expect($faux->prompts)->not->toBeEmpty();
    expect($faux->prompts[0])
        ->toContain('TOPIC REQUESTED BY THE TEACHER: «le travail en Allemagne»')
        ->toContain('phrases courtes')
        ->toContain('EXACTLY 4');

    // Sans demande du centre, aucune de ces directives.
    $faux->prompts = [];
    try {
        app(ExerciseGeneratorService::class)->generate($c['qcm'], $c['goethe'], 'B1');
    } catch (\RuntimeException) {
    }
    expect($faux->prompts[0])->not->toContain('TOPIC REQUESTED')->not->toContain("TEACHER'S NOTES");

    expect(ExerciseGeneratorService::demandeDuCentre(['nombre_questions' => 99, 'theme' => '   ']))
        ->toBe(['theme' => null, 'consignes' => null, 'nombre_questions' => 20]);
});

test('les generations sont limitees par personne, avec un message clair', function () {
    $c = createurCentre();
    $collegue = User::factory()->create();
    UserProfile::factory()->for($collegue)->create(['onboarding_completed_at' => now()]);
    $c['centre']->members()->attach($collegue->id, ['role' => 'center_admin', 'joined_at' => now()]);

    $this->mock(ExerciseGeneratorService::class, function ($mock) {
        $mock->shouldReceive('generate')->andReturn(new Exercise(['content' => [], 'questions' => createurQcm()]));
    });

    $base = ['exercise_type_id' => $c['qcm']->id, 'exam_id' => $c['goethe']->id, 'difficulty' => 'B1'];
    for ($i = 0; $i < ExerciseBuilderController::GENERATIONS_PAR_FENETRE; $i++) {
        $this->actingAs($c['resp'])->postJson(route('center.exercises.ai-draft'), $base)->assertOk();
    }

    $this->actingAs($c['resp'])->postJson(route('center.exercises.ai-draft'), $base)
        ->assertStatus(429)
        ->assertJsonPath('error', fn (string $m) => str_contains($m, 'Réessayez dans'));

    // La limite est personnelle : un collegue du meme centre n'est pas bloque.
    $this->actingAs($collegue)->postJson(route('center.exercises.ai-draft'), $base)->assertOk();

    RateLimiter::clear('createur-ia:'.$c['resp']->id);
});

test('un echec de l IA renvoie un message clair, sans rien creer', function () {
    $c = createurCentre();
    $this->mock(ExerciseGeneratorService::class, function ($mock) {
        $mock->shouldReceive('generate')->andThrow(new \RuntimeException('quota'));
    });

    $this->actingAs($c['resp'])->postJson(route('center.exercises.ai-draft'), [
        'exercise_type_id' => $c['qcm']->id, 'exam_id' => $c['goethe']->id, 'difficulty' => 'B1',
    ])->assertStatus(422)->assertJsonPath('error', fn (string $m) => str_contains($m, 'Réessayez'));

    expect(Exercise::count())->toBe(0);
});

// ───────────────────────────── enregistrement ─────────────────────────────

test('un examen que le centre ne propose pas est refuse a la creation, et a la modification', function () {
    $c = createurCentre();
    $payload = [
        'exercise_type_id' => $c['qcm']->id, 'exam_id' => $c['tcf']->id, 'difficulty' => 'B1',
        'content' => [], 'questions' => createurQcm(),
    ];

    $this->actingAs($c['resp'])->post(route('center.exercises.store'), $payload)->assertSessionHasErrors('exam_id');
    expect(Exercise::count())->toBe(0);

    $exercice = createurExercice($c);
    $this->patch(route('center.exercises.update', $exercice), $payload)->assertSessionHasErrors('exam_id');
    expect($exercice->fresh()->exam_id)->toBe($c['goethe']->id);

    // L'examen deja choisi reste accepte, meme retire depuis de l'offre du centre.
    $c['centre']->examensProposes()->sync([$c['tcf']->id]);
    $this->patch(route('center.exercises.update', $exercice), array_merge($payload, ['exam_id' => $c['goethe']->id]))
        ->assertSessionHasNoErrors()->assertRedirect(route('center.exercises.index'));
});

test('enregistrer garde l origine IA, renumerote les questions et peut revenir au devoir', function () {
    $c = createurCentre();
    $questions = array_merge(createurQcm(), createurQcm());

    $this->actingAs($c['resp'])->post(route('center.exercises.store'), [
        'exercise_type_id' => $c['qcm']->id, 'exam_id' => $c['goethe']->id, 'difficulty' => 'B1',
        'content' => ['title' => 'Chez le médecin'], 'questions' => $questions, 'is_ai_generated' => true,
        'retour' => 'devoir',
    ])->assertSessionHasNoErrors();

    $exercice = Exercise::firstOrFail();
    expect($exercice->is_ai_generated)->toBeTrue()
        // Deux questions dupliquees sans identifiant n'en partagent plus un.
        ->and(array_column($exercice->questions, 'id'))->toBe(['q1', 'q2']);

    $this->get(route('center.assignments.create', ['exercice' => $exercice->id]))
        ->assertInertia(fn (Assert $page) => $page->where('preselection', [$exercice->id]));
});

test('enregistrer et donner en devoir ramene a la creation du devoir, l exercice coche', function () {
    $c = createurCentre();

    $reponse = $this->actingAs($c['resp'])->post(route('center.exercises.store'), [
        'exercise_type_id' => $c['qcm']->id, 'exam_id' => $c['goethe']->id, 'difficulty' => 'B1',
        'content' => [], 'questions' => createurQcm(), 'retour' => 'devoir',
    ]);

    $reponse->assertRedirect(route('center.assignments.create', ['exercice' => Exercise::firstOrFail()->id]));
});

test('une question sans bonne reponse est une erreur de formulaire, pas une page d erreur', function () {
    $c = createurCentre();
    $q = createurQcm();
    $q[0]['correct_answer'] = '';

    $this->actingAs($c['resp'])->post(route('center.exercises.store'), [
        'exercise_type_id' => $c['qcm']->id, 'exam_id' => $c['goethe']->id, 'difficulty' => 'B1',
        'content' => [], 'questions' => $q,
    ])->assertSessionHasErrors(['questions' => 'Question 1 : la bonne réponse est requise.']);
});

test('les identifiants vides ou en double sont renumerotes par le registre', function () {
    [$ok, , $questions] = ExerciseSchemaRegistry::validateQuestions('mcq', [
        ['id' => 'q2', 'text' => 'a', 'correct_answer' => 'A'],
        ['id' => '', 'text' => 'b', 'correct_answer' => 'A'],
        ['id' => 'q2', 'text' => 'c', 'correct_answer' => 'A'],
    ]);

    expect($ok)->toBeTrue()->and(array_column($questions, 'id'))->toBe(['q2', 'q3', 'q4']);
});

test('creer depuis la page d un devoir ajoute l exercice a ce devoir', function () {
    $c = createurCentre();
    $devoir = $c['classe']->assignments()->create(['created_by' => $c['resp']->id, 'title' => 'Révisions', 'published_at' => now()]);

    $this->actingAs($c['resp'])->get(route('center.exercises.create', ['assignment_id' => $devoir->id]))
        ->assertInertia(fn (Assert $page) => $page->where('devoir.id', $devoir->id));

    $this->post(route('center.exercises.store'), [
        'exercise_type_id' => $c['qcm']->id, 'exam_id' => $c['goethe']->id, 'difficulty' => 'B1',
        'content' => [], 'questions' => createurQcm(), 'assignment_id' => $devoir->id,
    ])->assertRedirect(route('center.assignments.show', $devoir->id));

    expect($devoir->items()->pluck('itemable_id')->all())->toBe([Exercise::firstOrFail()->id]);
});

// ───────────────────────────── page « Contenu » ─────────────────────────────

test('la page Contenu donne titre, examen, competence, origine et nombre de questions', function () {
    $c = createurCentre();
    createurExercice($c, ['is_ai_generated' => true]);
    createurExercice($c, [
        'exercise_type_id' => $c['vf']->id, 'content' => ['passage' => str_repeat('Hallo Welt. ', 20)],
        'questions' => [['id' => 'q1', 'text' => 'x', 'correct_answer' => 'True']],
    ]);
    // Un exercice d'un autre centre n'apparait pas.
    $autre = LanguageCenter::create(['name' => 'Autre', 'slug' => 'autre', 'owner_email' => 'a@exemple.test', 'is_active' => true]);
    createurExercice($c, ['center_id' => $autre->id, 'content' => ['title' => 'Secret']]);

    $this->actingAs($c['resp'])->get(route('center.exercises.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('center/exercises/index')
            ->has('exercises', 2)
            ->has('exams', 1)
            ->where('exercises', fn ($lignes) => collect($lignes)->pluck('titre')->contains('Chez le médecin')
                && collect($lignes)->every(fn ($l) => $l['exam'] === 'Goethe' && $l['questions_count'] === 1)
                && collect($lignes)->pluck('skill_type')->sort()->values()->all() === ['listening', 'reading']
                && collect($lignes)->firstWhere('titre', 'Chez le médecin')['is_ai_generated'] === true
                && str_ends_with(collect($lignes)->firstWhere('skill_type', 'listening')['titre'], '…')
                && ! collect($lignes)->pluck('titre')->contains('Secret')));
});

test('le createur ne propose que les examens du centre, et la classe visee', function () {
    $c = createurCentre();

    $this->actingAs($c['resp'])->get(route('center.exercises.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('exams', 1)
            ->where('exams.0.language', 'german')
            ->has('classrooms', 1)
            ->where('classrooms.0.level', 'B1')
            ->where('exerciseTypes', fn ($types) => collect($types)->firstWhere('id', $c['qcm']->id)['slug'] === 'mcq'));

    $this->getJson(route('center.exercises.parties', ['exam_id' => $c['tcf']->id]))->assertJsonValidationErrors('exam_id');
    $this->getJson(route('center.exercises.parties', ['exam_id' => $c['goethe']->id, 'difficulty' => 'B1']))->assertOk()->assertJsonStructure(['parties']);
});

// ───────────────────────────── devoirs et banque PrePla ─────────────────────────────

test('un devoir accepte un exercice de la banque PrePla de l examen de la classe', function () {
    $c = createurCentre();
    $duCentre = createurExercice($c);
    $vivier = createurExercice($c, ['center_id' => null, 'content' => ['title' => 'Banque']]);

    $this->actingAs($c['resp'])->getJson(route('center.assignments.vivier', ['classroom_id' => $c['classe']->id]))
        ->assertOk()
        ->assertJsonPath('niveau', 'B1')
        ->assertJsonPath('exercises.0.id', $vivier->id)
        ->assertJsonPath('exercises.0.source', 'prepla');

    $this->post(route('center.assignments.store'), [
        'classroom_id' => $c['classe']->id, 'title' => 'Mixte', 'exercise_ids' => [$duCentre->id, $vivier->id],
    ])->assertSessionHasNoErrors()->assertRedirect();

    $devoir = Assignment::firstOrFail();
    expect($devoir->items()->orderBy('sort_order')->pluck('itemable_id')->all())->toBe([$duCentre->id, $vivier->id]);
});

test('un exercice prive d un autre centre est refuse, et rien n est cree', function () {
    $c = createurCentre();
    $autre = LanguageCenter::create(['name' => 'Concurrent', 'slug' => 'concurrent', 'owner_email' => 'c@exemple.test', 'is_active' => true]);
    $prive = createurExercice($c, ['center_id' => $autre->id]);
    $duCentre = createurExercice($c);
    // Le vivier d'un autre examen non plus.
    $horsExamen = createurExercice($c, ['center_id' => null, 'exam_id' => $c['tcf']->id]);

    foreach ([[$duCentre->id, $prive->id], [$prive->id], [$horsExamen->id]] as $ids) {
        $this->actingAs($c['resp'])->post(route('center.assignments.store'), [
            'classroom_id' => $c['classe']->id, 'title' => 'Piège', 'exercise_ids' => $ids,
        ])->assertSessionHasErrors('exercise_ids');
    }

    expect(Assignment::count())->toBe(0);

    // Ni par la page du devoir.
    $devoir = $c['classe']->assignments()->create(['created_by' => $c['resp']->id, 'title' => 'Existant', 'published_at' => now()]);
    $this->post(route('center.assignments.exercises.add', $devoir), ['exercise_ids' => [$prive->id]])->assertSessionHasErrors('exercise_ids');
    expect($devoir->items()->count())->toBe(0);
});

test('la page du devoir liste ses exercices lisiblement et permet d en ajouter un', function () {
    $c = createurCentre();
    $premier = createurExercice($c);
    $second = createurExercice($c, ['content' => ['title' => 'Au travail']]);
    $devoir = $c['classe']->assignments()->create(['created_by' => $c['resp']->id, 'title' => 'Révisions', 'published_at' => now()]);
    $devoir->items()->create(['itemable_type' => Exercise::class, 'itemable_id' => $premier->id, 'sort_order' => 0]);

    $this->actingAs($c['resp'])->post(route('center.assignments.exercises.add', $devoir), ['exercise_ids' => [$second->id, $premier->id]])
        ->assertRedirect(route('center.assignments.show', $devoir));

    // Le doublon n'est pas ajoute deux fois.
    expect($devoir->items()->orderBy('sort_order')->pluck('itemable_id')->all())->toBe([$premier->id, $second->id]);

    $this->get(route('center.assignments.show', $devoir))
        ->assertInertia(fn (Assert $page) => $page
            ->where('canEdit', true)
            ->has('exercises', 2)
            ->where('exercises.1.titre', 'Au travail'));
});

test('un enseignant d une autre classe ne peut pas ajouter d exercice au devoir', function () {
    $c = createurCentre();
    $prof = User::factory()->create();
    UserProfile::factory()->for($prof)->create(['onboarding_completed_at' => now()]);
    $c['centre']->members()->attach($prof->id, ['role' => 'teacher', 'joined_at' => now()]);
    $devoir = $c['classe']->assignments()->create(['created_by' => $c['resp']->id, 'title' => 'Révisions', 'published_at' => now()]);

    $this->actingAs($prof)->post(route('center.assignments.exercises.add', $devoir), ['exercise_ids' => [createurExercice($c)->id]])
        ->assertForbidden();
    $this->getJson(route('center.assignments.vivier', ['classroom_id' => $c['classe']->id]))->assertForbidden();
});
