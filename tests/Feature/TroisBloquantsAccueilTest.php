<?php

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\DB;

/**
 * Trois defauts bloquants de l'accueil, trouves en simulant un debutant.
 *
 * 1. La serie n'etait jamais remise a zero : quelqu'un parti depuis trois
 *    semaines lisait toujours « 7 jours d'affilee ». Le chiffre cense dire
 *    « tu avances » affirmait quelque chose de faux.
 * 2. Trois raccourcis, dont « Revision », etaient conditionnes par
 *    `!nextAction` — toujours faux, puisque le service renvoie toujours un
 *    tableau. Ils n'ont jamais ete affiches.
 * 3. Rien ne disait ce que l'apprenant avait fait AUJOURD'HUI.
 */
function apprenantAccueil(array $profil = []): User
{
    $langue = Language::firstOrCreate(['slug' => 'german'],
        ['name' => 'Allemand', 'native_name' => 'Deutsch', 'flag' => 'de', 'is_active' => true]);
    $exam = Exam::firstOrCreate(['slug' => 'accueil-exam'], ['language_id' => $langue->id, 'name' => 'telc Deutsch']);

    $u = User::create(['name' => 'Binta Camara', 'email' => 'acc'.uniqid().'@exemple.test',
        'password' => 'mot-de-passe-de-test', 'email_verified_at' => now()]);
    UserProfile::create(array_merge([
        'user_id' => $u->id, 'current_level' => 'B1', 'target_exam_id' => $exam->id,
        'onboarding_completed_at' => now(),
    ], $profil));

    return $u;
}

function tentativeLe(User $u, $quand, int $reussite = 80, int $secondes = 300): void
{
    $langue = Language::firstOrCreate(['slug' => 'german'],
        ['name' => 'Allemand', 'native_name' => 'Deutsch', 'flag' => 'de', 'is_active' => true]);
    $exam = Exam::firstOrCreate(['slug' => 'accueil-exam'], ['language_id' => $langue->id, 'name' => 'telc Deutsch']);
    $section = ExamSection::firstOrCreate(['exam_id' => $exam->id, 'slug' => 'lesen'],
        ['name' => 'Lesen', 'skill_type' => 'reading', 'time_limit' => 30]);
    $type = ExerciseType::firstOrCreate(['section_id' => $section->id, 'slug' => 'mcq'],
        ['name' => 'QCM', 'skill_type' => 'reading', 'component_key' => 'mcq']);
    $ex = Exercise::create(['exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'difficulty' => 'B1',
        'content' => ['title' => 'x'],
        'questions' => [['id' => 'q1', 'type' => 'mcq', 'text' => 'x', 'options' => ['a', 'b'], 'correct_answer' => 'A']]]);

    DB::table('user_exercise_attempts')->insert([
        'user_id' => $u->id, 'exercise_id' => $ex->id, 'answers' => json_encode([]),
        'score' => $reussite, 'accuracy_percent' => $reussite, 'time_spent' => $secondes,
        'created_at' => $quand, 'updated_at' => $quand,
    ]);
}

// ─────────────────────────── 1. La serie ───────────────────────────

test('une serie abandonnee depuis trois semaines vaut zero', function () {
    $u = apprenantAccueil(['streak_current' => 7, 'streak_last_date' => now()->subDays(21)]);

    // La base garde 7 — c'est la derniere valeur atteinte, pas la serie en cours.
    expect($u->profile->streak_current)->toBe(7)
        ->and($u->profile->serieEffective())->toBe(0);
});

test('une serie travaillee hier tient toujours', function () {
    // Revenir le lendemain ne casse rien : la journee n'est pas finie.
    $u = apprenantAccueil(['streak_current' => 4, 'streak_last_date' => now()->subDay()]);

    expect($u->profile->serieEffective())->toBe(4);
});

test('une serie travaillee aujourd hui tient', function () {
    $u = apprenantAccueil(['streak_current' => 2, 'streak_last_date' => now()]);

    expect($u->profile->serieEffective())->toBe(2);
});

test('une serie travaillee avant-hier est cassee', function () {
    $u = apprenantAccueil(['streak_current' => 9, 'streak_last_date' => now()->subDays(2)]);

    expect($u->profile->serieEffective())->toBe(0);
});

test('un profil sans aucun jour travaille ne revendique rien', function () {
    expect(apprenantAccueil()->profile->serieEffective())->toBe(0);
});

test('l interface recoit la serie effective, pas la derniere atteinte', function () {
    $u = apprenantAccueil(['streak_current' => 7, 'streak_last_date' => now()->subDays(21)]);

    // Le bandeau, les statistiques et les reglages lisent tous ce champ.
    expect($u->profile->toArray()['serie_effective'])->toBe(0);
});

test('le coach ne felicite pas pour une serie morte', function () {
    $u = apprenantAccueil(['streak_current' => 7, 'streak_last_date' => now()->subDays(21)]);

    $portrait = app(\App\Services\Content\ProgressionApprenant::class)->portrait($u);

    expect($portrait['serie_en_cours'])->toBe(0);
});

// ──────────────────── 2. et 3. L'accueil ────────────────────

test('l accueil dit ce qui a ete fait aujourd hui', function () {
    $u = apprenantAccueil();
    tentativeLe($u, now(), 80, 300);
    tentativeLe($u, now(), 60, 420);
    // Hier ne compte pas dans la journee.
    tentativeLe($u, now()->subDay(), 100, 600);

    $props = $this->actingAs($u)->get('/dashboard')->viewData('page')['props'];

    expect($props['aujourdhui']['exercices'])->toBe(2)
        ->and($props['aujourdhui']['minutes'])->toBe(12)
        ->and($props['aujourdhui']['reussite'])->toBe(70);
});

test('une journee sans rien ne montre pas une reussite de zero', function () {
    // « 0 % » a quelqu'un qui n'a pas commence serait un reproche, pas une mesure.
    $props = $this->actingAs(apprenantAccueil())->get('/dashboard')->viewData('page')['props'];

    expect($props['aujourdhui']['exercices'])->toBe(0)
        ->and($props['aujourdhui']['reussite'])->toBeNull();
});

test('le raccourci vers les erreurs est atteignable depuis l accueil', function () {
    // Il etait dans un bloc dont la condition etait toujours fausse.
    $u = apprenantAccueil();

    $rendu = $this->actingAs($u)->get('/dashboard')->getContent();

    expect($rendu)->toContain('dueErrorsCount');
});
