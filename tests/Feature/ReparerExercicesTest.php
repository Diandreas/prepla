<?php

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\User;
use App\Models\UserExerciseAttempt;
use App\Services\AI\MistralService;
use Illuminate\Support\Facades\DB;

/**
 * L'audit a trouve en production douze exercices qu'aucun apprenant ne pouvait
 * faire : des entrees parasites dans la liste des questions — un exercice en
 * annoncait vingt-sept et n'en avait qu'une de vraie — et des « construire la
 * phrase » sans phrase correcte.
 *
 * Une tentative d'apprenant disparait avec son exercice : on ne supprime donc
 * que ce dont personne ne s'est servi.
 */
function exerciceCasse(string $composant, array $questions, string $slug = 'cassé'): Exercise
{
    $langue = Language::firstOrCreate(['slug' => 'english'],
        ['name' => 'Anglais', 'native_name' => 'English', 'flag' => 'gb', 'is_active' => true]);
    $exam = Exam::firstOrCreate(['slug' => 'reparer'], ['language_id' => $langue->id, 'name' => 'Examen']);
    $section = ExamSection::firstOrCreate(['exam_id' => $exam->id, 'slug' => 'reading'],
        ['name' => 'Reading', 'skill_type' => 'reading', 'time_limit' => 30]);
    $type = ExerciseType::firstOrCreate(['section_id' => $section->id, 'slug' => $slug],
        ['name' => $slug, 'skill_type' => 'reading', 'component_key' => $composant]);

    return Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'difficulty' => 'B1',
        'content' => ['title' => 'test'], 'questions' => $questions,
    ]);
}

/** Une ecoute dont la question n'a ni son ni texte parle. */
function exerciceEcouteSansSon(): Exercise
{
    $langue = Language::firstOrCreate(['slug' => 'english'],
        ['name' => 'Anglais', 'native_name' => 'English', 'flag' => 'gb', 'is_active' => true]);
    $exam = Exam::firstOrCreate(['slug' => 'reparer'], ['language_id' => $langue->id, 'name' => 'Examen']);
    $section = ExamSection::firstOrCreate(['exam_id' => $exam->id, 'slug' => 'listening'],
        ['name' => 'Listening', 'skill_type' => 'listening', 'time_limit' => 30]);
    $type = ExerciseType::firstOrCreate(['section_id' => $section->id, 'slug' => 'form-completion'],
        ['name' => 'Formulaire', 'skill_type' => 'listening', 'component_key' => 'form-completion']);

    return Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'difficulty' => 'B2',
        'content' => ['title' => 'test'],
        'questions' => [['id' => 'q1', 'type' => 'form-completion', 'text' => 'Complète',
            'fields' => [['label' => 'Nom', 'value' => '']], 'correct_answers' => ['Sarah']]],
    ]);
}

test('les entrees parasites sont retirees et l exercice redevient jouable', function () {
    // Une vraie question, puis vingt-six chaines : l'exercice annoncait
    // vingt-sept questions et n'en avait qu'une.
    $vraie = ['id' => 'q1', 'type' => 'mcq', 'text' => 'Quelle est la capitale ?',
        'options' => ['Paris', 'Lyon'], 'correct_answer' => 'A'];
    $exercice = exerciceCasse('mcq', array_merge([$vraie], array_fill(0, 26, 'parasite')), 'mcq');

    $this->artisan('prepla:reparer-exercices')->assertSuccessful();

    $exercice->refresh();
    expect($exercice->questions)->toHaveCount(1)
        ->and($exercice->answerableQuestions())->toHaveCount(1);
});

test('une ecoute sans passage parle n est pas supprimee', function () {
    // Il ne lui manque que son enregistrement, et c'est prepla:sonoriser-ecoutes
    // qui l'ecrit. La supprimer jetterait un exercice complet pour un son absent.
    $exercice = exerciceEcouteSansSon();

    $this->artisan('prepla:reparer-exercices')
        ->expectsOutputToContain('attendent leur passage parle')
        ->assertSuccessful();

    expect(Exercise::find($exercice->id))->not->toBeNull();
});

test('un exercice irreparable sans tentative est retire', function () {
    $exercice = exerciceCasse('build-a-sentence', [['id' => 'q1', 'type' => 'build-a-sentence',
        'text' => 'Remets les mots.', 'words' => ['seul'], 'correct_answer' => null]], 'build-a-sentence');

    $this->artisan('prepla:reparer-exercices')->assertSuccessful();

    expect(Exercise::find($exercice->id))->toBeNull();
});

test('un exercice irreparable avec une tentative est garde', function () {
    $exercice = exerciceCasse('build-a-sentence', [['id' => 'q1', 'type' => 'build-a-sentence',
        'text' => 'Remets les mots.', 'words' => ['seul'], 'correct_answer' => null]], 'build-a-sentence');

    $u = User::create(['name' => 'Ana', 'email' => 'ana@exemple.test',
        'password' => 'mot-de-passe-de-test', 'email_verified_at' => now()]);
    // Le travail de quelqu'un ne se jette pas pour faire du menage : la
    // tentative disparaitrait avec l'exercice.
    DB::table('user_exercise_attempts')->insert([
        'user_id' => $u->id, 'exercise_id' => $exercice->id, 'score' => 0, 'answers' => json_encode([]),
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $this->artisan('prepla:reparer-exercices')->assertSuccessful();

    expect(Exercise::find($exercice->id))->not->toBeNull()
        ->and(UserExerciseAttempt::where('exercise_id', $exercice->id)->count())->toBe(1);
});

test('la phrase recomposee doit employer exactement les mots fournis', function () {
    $exercice = exerciceCasse('build-a-sentence', [['id' => 'q1', 'type' => 'build-a-sentence',
        'text' => 'Remets les mots dans l\'ordre.',
        'words' => ['I', 'am', 'going', 'home'], 'correct_answer' => null]], 'build-a-sentence');

    // L'IA rend une phrase qui invente un mot : on refuse, et l'exercice part.
    $faux = new class extends MistralService
    {
        public function __construct() {}

        public function chat(array $messages, string $model = 'mistral-small-latest'): ?string
        {
            return 'I am going home today';
        }
    };
    app()->instance(MistralService::class, $faux);

    $this->artisan('prepla:reparer-exercices')->assertSuccessful();

    expect(Exercise::find($exercice->id))->toBeNull();
});

test('une phrase fidele aux mots fournis repare l exercice', function () {
    $exercice = exerciceCasse('build-a-sentence', [['id' => 'q1', 'type' => 'build-a-sentence',
        'text' => 'Remets les mots dans l\'ordre.',
        'words' => ['I', 'am', 'going', 'home'], 'correct_answer' => null]], 'build-a-sentence');

    $bon = new class extends MistralService
    {
        public function __construct() {}

        public function chat(array $messages, string $model = 'mistral-small-latest'): ?string
        {
            return 'I am going home';
        }
    };
    app()->instance(MistralService::class, $bon);

    $this->artisan('prepla:reparer-exercices')->assertSuccessful();

    $exercice->refresh();
    expect($exercice->questions[0]['correct_answer'])->toBe('I am going home')
        ->and($exercice->answerableQuestions())->toHaveCount(1);
});

test('le mode sec ne touche a rien', function () {
    $exercice = exerciceCasse('form-completion', [['id' => 'q1', 'type' => 'form-completion',
        'text' => 'Complète', 'fields' => [['label' => 'Nom', 'value' => '']]]], 'form-completion');

    $this->artisan('prepla:reparer-exercices', ['--sec' => true])->assertSuccessful();

    expect(Exercise::find($exercice->id))->not->toBeNull();
});

test('le mode sec annonce tout le travail, pas seulement le nettoyage', function () {
    // Se contenter de « il reste des questions » apres nettoyage annonçait
    // moins de travail qu'il n'y en a : une ecoute sans son restait invisible.
    exerciceEcouteSansSon();

    $this->artisan('prepla:reparer-exercices', ['--sec' => true])
        ->expectsOutputToContain('attendent leur passage parle')
        ->assertSuccessful();
});
