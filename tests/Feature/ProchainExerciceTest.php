<?php

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\User;
use App\Models\UserError;
use App\Models\UserProfile;
use App\Services\Content\ProchainExercice;
use Illuminate\Support\Facades\DB;

/**
 * La pratique libre tirait au hasard : elle ouvrait un exercice du format
 * demande sans jamais regarder ce que l'apprenant venait de rater. On pouvait
 * manquer dix fois le meme accord, le onzieme exercice n'en savait rien.
 *
 * C'est pourtant le mode de celui qui travaille SEUL — donc de celui qui n'a
 * personne pour lui dire quoi reprendre.
 */
function examenEntrainement(): Exam
{
    $langue = Language::firstOrCreate(['slug' => 'german'],
        ['name' => 'Allemand', 'native_name' => 'Deutsch', 'flag' => 'de', 'is_active' => true]);
    $exam = Exam::firstOrCreate(['slug' => 'suite-exam'], ['language_id' => $langue->id, 'name' => 'telc Deutsch']);

    foreach ([['lesen', 'reading', ['mcq']], ['sprachbausteine', 'grammar', ['grammar-mcq', 'word-formation']],
        ['hoeren', 'listening', ['true-false']]] as [$slug, $skill, $types]) {
        $section = ExamSection::firstOrCreate(['exam_id' => $exam->id, 'slug' => $slug],
            ['name' => $slug, 'skill_type' => $skill, 'time_limit' => 30]);
        foreach ($types as $t) {
            ExerciseType::firstOrCreate(['section_id' => $section->id, 'slug' => $t],
                ['name' => $t, 'skill_type' => $skill, 'component_key' => 'mcq']);
        }
    }

    return $exam;
}

function apprenantSeul(): User
{
    $exam = examenEntrainement();
    $u = User::create(['name' => 'Oumar Sy', 'email' => 'seul'.uniqid().'@exemple.test',
        'password' => 'mot-de-passe-de-test', 'email_verified_at' => now()]);
    UserProfile::create(['user_id' => $u->id, 'current_level' => 'B1',
        'target_exam_id' => $exam->id, 'onboarding_completed_at' => now()]);

    return $u;
}

function erreurDe(User $u, string $categorie, string $competence = 'grammar', $due = null): UserError
{
    $e = UserError::create([
        'user_id' => $u->id, 'question_id' => 'q'.uniqid(), 'question_text' => 'x',
        'user_answer' => 'faux', 'correct_answer' => 'juste', 'skill_type' => $competence,
        'exercise_type_slug' => 'grammar-mcq', 'error_category' => $categorie, 'mastered' => false,
    ]);
    $e->forceFill(['next_review_at' => $due ?: now()->subDay(), 'interval_days' => 1])->save();

    return $e;
}

function tentativeSur(User $u, string $slugDuType, int $reussite): void
{
    $exam = examenEntrainement();
    $type = ExerciseType::whereHas('section', fn ($q) => $q->where('exam_id', $exam->id))
        ->where('slug', $slugDuType)->first();
    $ex = Exercise::create(['exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'difficulty' => 'B1',
        'content' => ['title' => 'x'],
        'questions' => [['id' => 'q1', 'type' => 'mcq', 'text' => 'x', 'options' => ['a', 'b'], 'correct_answer' => 'A']]]);
    DB::table('user_exercise_attempts')->insert([
        'user_id' => $u->id, 'exercise_id' => $ex->id, 'answers' => json_encode([]),
        'score' => $reussite, 'accuracy_percent' => $reussite, 'time_spent' => 120,
        'created_at' => now(), 'updated_at' => now(),
    ]);
}

test('une erreur a revoir passe avant tout le reste', function () {
    $u = apprenantSeul();
    erreurDe($u, 'grammar');

    $choix = app(ProchainExercice::class)->pour($u, examenEntrainement(), 'B1');

    expect($choix['categorie'])->toBe('grammar')
        ->and($choix['pourquoi'])->toContain('grammaire')
        ->and($choix['type']->skill_type)->toBe('grammar');
});

test('la raison dit a l apprenant pourquoi on fait ca', function () {
    $u = apprenantSeul();
    erreurDe($u, 'vocabulary', 'grammar');

    $choix = app(ProchainExercice::class)->pour($u, examenEntrainement(), 'B1');

    // « Un exercice » ne donne envie a personne.
    expect($choix['pourquoi'])->toContain('vocabulaire')
        ->and($choix['pourquoi'])->not->toBe('');
});

test('sans erreur, la competence la plus faible passe devant', function () {
    $u = apprenantSeul();
    // Trois tentatives ratees en lecture, trois reussies en grammaire.
    foreach ([30, 40, 35] as $n) {
        tentativeSur($u, 'mcq', $n);
    }
    foreach ([90, 95, 92] as $n) {
        tentativeSur($u, 'grammar-mcq', $n);
    }

    $choix = app(ProchainExercice::class)->pour($u, examenEntrainement(), 'B1');

    expect($choix['type']->skill_type)->toBe('reading')
        ->and($choix['pourquoi'])->toContain('réussis le moins');
});

test('une competence a peine essayee n est pas declaree faible', function () {
    $u = apprenantSeul();
    // Deux echecs ne font pas une tendance : on ne condamne pas sur un coup de malchance.
    tentativeSur($u, 'mcq', 20);
    tentativeSur($u, 'mcq', 30);

    $choix = app(ProchainExercice::class)->pour($u, examenEntrainement(), 'B1');

    expect($choix['pourquoi'])->not->toContain('réussis le moins');
});

test('sans erreur ni faiblesse, un format jamais tente est propose', function () {
    $u = apprenantSeul();
    foreach ([90, 95, 92] as $n) {
        tentativeSur($u, 'mcq', $n);
    }

    $choix = app(ProchainExercice::class)->pour($u, examenEntrainement(), 'B1');

    expect($choix['pourquoi'])->toContain('jamais fait ce format')
        ->and($choix['type']->slug)->not->toBe('mcq');
});

test('le choix sert un exercice du vivier quand il y en a un d inedit', function () {
    $u = apprenantSeul();
    erreurDe($u, 'grammar');

    $exam = examenEntrainement();
    $type = ExerciseType::whereHas('section', fn ($q) => $q->where('exam_id', $exam->id))
        ->where('slug', 'grammar-mcq')->first();
    $pret = Exercise::create(['exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'difficulty' => 'B1',
        'content' => ['title' => 'deja ecrit'],
        'questions' => [['id' => 'q1', 'type' => 'mcq', 'text' => 'x', 'options' => ['a', 'b'], 'correct_answer' => 'A']]]);

    $choix = app(ProchainExercice::class)->pour($u, examenEntrainement(), 'B1');

    // On ne depense pas l'IA quand le vivier a deja ce qu'il faut.
    expect($choix['exercice']?->id)->toBe($pret->id);
});

test('un exercice deja fait n est pas represente comme du neuf', function () {
    $u = apprenantSeul();
    erreurDe($u, 'grammar');
    tentativeSur($u, 'grammar-mcq', 50);

    $choix = app(ProchainExercice::class)->pour($u, examenEntrainement(), 'B1');

    expect($choix['exercice'])->toBeNull();
});

test('le bouton Continuer mene a un exercice', function () {
    $u = apprenantSeul();
    erreurDe($u, 'grammar');

    $exam = examenEntrainement();
    $type = ExerciseType::whereHas('section', fn ($q) => $q->where('exam_id', $exam->id))
        ->where('slug', 'grammar-mcq')->first();
    $pret = Exercise::create(['exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'difficulty' => 'B1',
        'content' => ['title' => 'x'],
        'questions' => [['id' => 'q1', 'type' => 'mcq', 'text' => 'x', 'options' => ['a', 'b'], 'correct_answer' => 'A']]]);

    $this->actingAs($u)->get('/practice/'.$exam->id.'/continuer')
        ->assertRedirect(route('exercise.show', $pret->id))
        // La raison suit l'apprenant jusqu'a l'exercice.
        ->assertSessionHas('pourquoi');
});

test('la page Pratiquer annonce ce qu on fait maintenant', function () {
    $u = apprenantSeul();
    erreurDe($u, 'vocabulary');

    $props = $this->actingAs($u)->get('/practice/'.examenEntrainement()->id)->viewData('page')['props'];

    expect($props['prochain']['pourquoi'])->toContain('vocabulaire');
});

test('les durees relatives sont en francais', function () {
    // L'apprenant lisait « tu as buté dessus 13 seconds ago » sur une page
    // entièrement en français.
    expect(now()->subSeconds(13)->diffForHumans())->toContain('il y a')
        ->and(now()->subDays(3)->diffForHumans())->toContain('jours');
});
