<?php

use App\Models\CenterUser;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Models\UserError;
use App\Models\UserExerciseAttempt;

/**
 * La demo fabriquait chaque exercice comme un QCM a une question, toute reponse
 * valant « A », sans correction — meme a l'ecrit. Un prospect qui ouvrait une
 * copie dans le suivi du centre voyait une redaction reduite a « A ».
 *
 * La reparation se fait EN PLACE : le generateur de la demo commence par tout
 * supprimer, on ne le relance pas en production.
 */
function demoFactice(): array
{
    $langue = Language::create(['slug' => 'french', 'name' => 'Français', 'native_name' => 'Français', 'flag' => 'fr']);
    $exam = Exam::create(['language_id' => $langue->id, 'slug' => 'delf-dalf', 'name' => 'DELF']);
    $sections = [];
    $type = function (string $slug, string $competence, string $composant) use ($exam, &$sections) {
        $sections[$competence] ??= ExamSection::create(['exam_id' => $exam->id, 'slug' => $competence, 'name' => $competence, 'skill_type' => $competence]);

        return ExerciseType::create([
            'section_id' => $sections[$competence]->id, 'slug' => $slug, 'name' => $slug, 'skill_type' => $competence, 'component_key' => $composant,
        ]);
    };
    // L'ancienne demo rangeait ses lectures sous l'appariement.
    $lecture = $type('matching', 'reading', 'matching');
    $qcm = $type('mcq', 'reading', 'mcq');
    $ecrit = $type('article-writing', 'writing', 'essay-editor');

    $centre = LanguageCenter::create(['name' => 'Institut Linguae', 'slug' => 'institut-linguae-demo', 'owner_email' => 'demo@demo.prepla.local', 'is_active' => true]);
    $autre = LanguageCenter::create(['name' => 'Vrai centre', 'slug' => 'vrai-centre', 'owner_email' => 'x@exemple.test', 'is_active' => true]);

    // Tel que l'ancien generateur les fabriquait, titre compris : « Reading — B1 ».
    $faux = fn (ExerciseType $t, LanguageCenter $c) => Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => $t->id, 'exam_section_id' => $t->section_id, 'center_id' => $c->id,
        'difficulty' => 'B1', 'is_ai_generated' => false, 'content' => ['title' => ucfirst($t->skill_type).' — B1'],
        'questions' => [['id' => 'q1', 'text' => 'Rédigez un court message pour annuler un rendez-vous.', 'options' => ['A', 'B', 'C', 'D'], 'correct_answer' => 'A']],
    ]);
    $exLecture = $faux($lecture, $centre);
    $exEcrit = $faux($ecrit, $centre);
    $exAutreCentre = $faux($ecrit, $autre);

    $eleve = User::factory()->create(['email' => 'eleve1@demo.prepla.local']);
    CenterUser::create(['center_id' => $centre->id, 'user_id' => $eleve->id, 'role' => 'student', 'joined_at' => now()]);
    $vrai = User::factory()->create(['email' => 'vrai@exemple.test']);
    CenterUser::create(['center_id' => $autre->id, 'user_id' => $vrai->id, 'role' => 'student', 'joined_at' => now()]);

    $tentative = fn (User $u, Exercise $e, float $p) => UserExerciseAttempt::create([
        'user_id' => $u->id, 'exercise_id' => $e->id, 'answers' => ['q1' => 'A'], 'score' => (int) $p,
        'accuracy_percent' => $p, 'time_spent' => 120, 'xp_earned' => 10, 'feedback' => [],
    ]);

    $erreurFactice = UserError::create([
        'user_id' => $eleve->id, 'exercise_id' => $exEcrit->id, 'question_id' => 'q1',
        'question_text' => 'Rédigez un court message pour annuler un rendez-vous.', 'user_answer' => 'B', 'correct_answer' => 'A',
        'explanation' => 'Révisez la règle correspondante et réessayez.', 'skill_type' => 'writing',
        'exercise_type_slug' => 'article-writing', 'error_category' => 'grammar', 'subcategory' => 'tense', 'mastered' => false,
    ]);

    return [
        'centre' => $centre, 'qcm' => $qcm,
        'exLecture' => $exLecture, 'exEcrit' => $exEcrit, 'exAutreCentre' => $exAutreCentre,
        'tLecture' => $tentative($eleve, $exLecture, 50), 'tEcrit' => $tentative($eleve, $exEcrit, 35),
        'tVrai' => $tentative($vrai, $exAutreCentre, 50), 'eleve' => $eleve, 'erreurFactice' => $erreurFactice,
    ];
}

test('la reparation donne a l ecrit un vrai sujet, une vraie copie et sa correction', function () {
    $d = demoFactice();

    $this->artisan('prepla:demo-reparer')->assertSuccessful();

    $exercice = $d['exEcrit']->fresh();
    expect($exercice->questions[0]['type'])->toBe('essay')
        ->and($exercice->questions[0])->not->toHaveKey('options');

    $t = $d['tEcrit']->fresh();
    // Une vraie redaction d'apprenant, et non « A ».
    expect(mb_strlen($t->answers['q1']))->toBeGreaterThan(100)
        ->and($t->feedback[0]['corrections'])->not->toBeEmpty()
        ->and($t->feedback[0]['diagnostic'])->not->toBeEmpty()
        ->and($t->feedback[0]['correct'])->toBeFalse();

    // Ses fautes sont retenues comme celles d'un vrai apprenant, avec sous-categorie.
    expect(UserError::where('user_id', $d['eleve']->id)->whereNull('exercise_id')->whereNotNull('subcategory')->exists())->toBeTrue();
});

test('une tentative fermee recoit des reponses coherentes avec son score', function () {
    $d = demoFactice();

    $this->artisan('prepla:demo-reparer')->assertSuccessful();

    $t = $d['tLecture']->fresh();
    $justes = collect($t->feedback)->where('correct', true)->count();

    // 50 % sur quatre questions : deux justes, deux fausses — et la precision suit.
    expect($t->feedback)->toHaveCount(4)
        ->and($justes)->toBe(2)
        ->and((float) $t->accuracy_percent)->toBe(50.0)
        ->and(UserError::where('user_id', $d['eleve']->id)->where('exercise_id', $d['exLecture']->id)->count())->toBe(2);
});

test('la reparation ne touche ni un autre centre ni un vrai compte, et ne supprime rien', function () {
    $d = demoFactice();
    $avant = [UserExerciseAttempt::count(), UserError::count(), Exercise::count()];

    $this->artisan('prepla:demo-reparer')->assertSuccessful();

    expect($d['exAutreCentre']->fresh()->questions[0]['options'])->toBe(['A', 'B', 'C', 'D'])
        ->and($d['tVrai']->fresh()->answers)->toBe(['q1' => 'A'])
        // Rien de supprime : les comptes ne peuvent que monter (erreurs retenues).
        ->and(UserExerciseAttempt::count())->toBe($avant[0])
        ->and(Exercise::count())->toBe($avant[2])
        ->and(UserError::count())->toBeGreaterThanOrEqual($avant[1]);
});

test('l erreur factice est reecrite en vraie faute de redaction', function () {
    $d = demoFactice();

    $this->artisan('prepla:demo-reparer')->assertSuccessful();

    $e = $d['erreurFactice']->fresh();
    expect($e->explanation)->not->toBe('Révisez la règle correspondante et réessayez.')
        ->and($e->user_answer)->not->toBe('B')
        ->and($e->skill_type)->toBe('writing');
});

test('une lecture de la demo prend le type QCM de sa competence', function () {
    $d = demoFactice();

    $this->artisan('prepla:demo-reparer')->assertSuccessful();

    // Rangee sous l'appariement, elle s'affichait « Associez ce terme », et le
    // centre lisait « matching » sur une copie de QCM.
    expect($d['exLecture']->fresh()->exercise_type_id)->toBe($d['qcm']->id)
        ->and(UserError::where('exercise_id', $d['exLecture']->id)->pluck('exercise_type_slug')->unique()->all())->toBe(['mcq']);
});

test('un exercice cree a la main dans le centre de demo n est pas touche', function () {
    $d = demoFactice();
    $aLaMain = Exercise::create([
        'exam_id' => $d['exLecture']->exam_id, 'exercise_type_id' => $d['exLecture']->exercise_type_id, 'center_id' => $d['centre']->id,
        'difficulty' => 'B1', 'is_ai_generated' => false, 'content' => ['title' => 'Le subjonctif après « bien que »'],
        'questions' => [['id' => 'q1', 'text' => 'Bien qu’il ___ tard…', 'options' => ['est', 'soit'], 'correct_answer' => 'B']],
    ]);

    $this->artisan('prepla:demo-reparer')->assertSuccessful();

    expect($aLaMain->fresh()->content)->toBe(['title' => 'Le subjonctif après « bien que »'])
        ->and($aLaMain->fresh()->questions)->toBe($aLaMain->questions);
});

test('un exercice de la demo qu un autre compte a fait garde son contenu', function () {
    $d = demoFactice();
    $prospect = User::factory()->create(['email' => 'prospect@exemple.test']);
    CenterUser::create(['center_id' => $d['centre']->id, 'user_id' => $prospect->id, 'role' => 'student', 'joined_at' => now()]);
    UserExerciseAttempt::create([
        'user_id' => $prospect->id, 'exercise_id' => $d['exLecture']->id, 'answers' => ['q1' => 'B'], 'score' => 0,
        'accuracy_percent' => 0, 'time_spent' => 60, 'xp_earned' => 0, 'feedback' => [],
    ]);

    $this->artisan('prepla:demo-reparer')->assertSuccessful();

    // Sa copie repondrait sinon a des questions qui n'existent plus.
    expect($d['exLecture']->fresh()->questions)->toHaveCount(1)
        ->and($d['tLecture']->fresh()->answers)->toBe(['q1' => 'A'])
        // Le reste de la demo est bien repare.
        ->and($d['exEcrit']->fresh()->questions[0]['type'])->toBe('essay');
});

test('a sec, la reparation n ecrit rien', function () {
    $d = demoFactice();

    $this->artisan('prepla:demo-reparer', ['--sec' => true])->assertSuccessful();

    expect($d['tEcrit']->fresh()->answers)->toBe(['q1' => 'A']);
});

test('la meme reparation relancee donne le meme resultat', function () {
    $d = demoFactice();

    $this->artisan('prepla:demo-reparer');
    $premiere = $d['tEcrit']->fresh()->answers;
    $erreurs = UserError::count();

    $this->artisan('prepla:demo-reparer');

    expect($d['tEcrit']->fresh()->answers)->toBe($premiere)
        ->and(UserError::count())->toBe($erreurs);
});

/**
 * Le generateur de la demo produit desormais la meme qualite que la reparation :
 * de vrais exercices et des copies coherentes. Lance ici dans la base de test,
 * vide : son nettoyage initial n'y trouve rien a supprimer.
 */
test('le generateur de la demo produit de vraies copies', function () {
    $this->seed(\Database\Seeders\LanguageSeeder::class);
    $this->seed(\Database\Seeders\ExamSeeder::class);
    $this->seed(\Database\Seeders\B2BDemoSeeder::class);

    $centre = LanguageCenter::where('slug', 'institut-linguae-demo')->sole();
    $ecrits = Exercise::where('center_id', $centre->id)->whereHas('exerciseType', fn ($q) => $q->where('skill_type', 'writing'))->get();

    expect($ecrits)->not->toBeEmpty();
    foreach ($ecrits as $e) {
        expect($e->questions[0]['type'])->toBe('essay')->and($e->questions[0])->not->toHaveKey('options');
    }

    $copie = UserExerciseAttempt::whereIn('exercise_id', $ecrits->pluck('id'))->first();
    expect($copie)->not->toBeNull()
        ->and(mb_strlen($copie->answers['q1']))->toBeGreaterThan(80)
        ->and($copie->feedback[0])->toHaveKey('diagnostic');

    // Plus aucune reponse factice « A » sur une redaction.
    expect(UserExerciseAttempt::whereIn('exercise_id', $ecrits->pluck('id'))->get()
        ->contains(fn ($t) => ($t->answers['q1'] ?? null) === 'A'))->toBeFalse();

    // Les lectures et les ecoutes sont des QCM, et s'affichent comme tels.
    $fermes = Exercise::where('center_id', $centre->id)->with('exerciseType')
        ->whereHas('exerciseType', fn ($q) => $q->whereIn('skill_type', ['reading', 'listening']))->get();
    expect($fermes)->not->toBeEmpty()
        ->and($fermes->pluck('exerciseType.component_key')->unique()->values()->all())->toBe(['mcq']);
});
