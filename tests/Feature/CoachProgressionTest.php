<?php

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\User;
use App\Models\UserError;
use App\Models\UserProfile;
use App\Services\Content\ProgressionApprenant;
use Illuminate\Support\Facades\DB;

/**
 * Le tuteur ne savait que le niveau declare et l'examen vise. Il repondait donc
 * aux questions de langue, et a rien d'autre : « ou j'en suis ? » ou « je fais
 * quoi maintenant ? » ne trouvaient personne en face.
 *
 * Tout ce que le portrait renvoie est MESURE. Quand une donnee manque, le champ
 * est nul — pour que le coach le dise, au lieu de combler le vide.
 */
function apprenantAvec(array $tentatives = [], array $options = []): User
{
    $langue = Language::firstOrCreate(['slug' => 'german'],
        ['name' => 'Allemand', 'native_name' => 'Deutsch', 'flag' => 'de', 'is_active' => true]);
    $exam = Exam::firstOrCreate(['slug' => 'coach-exam'], ['language_id' => $langue->id, 'name' => 'telc Deutsch']);

    $u = User::create(['name' => $options['nom'] ?? 'Amadou Diallo',
        'email' => 'coach'.uniqid().'@exemple.test',
        'password' => 'mot-de-passe-de-test', 'email_verified_at' => now()]);

    UserProfile::create(array_merge([
        'user_id' => $u->id, 'current_level' => 'B1', 'target_exam_id' => $exam->id,
        'onboarding_completed_at' => now(), 'streak_current' => $options['serie'] ?? 0,
        'xp_total' => $options['xp'] ?? 0,
    ], $options['profil'] ?? []));

    foreach ($tentatives as [$competence, $reussite]) {
        $section = ExamSection::firstOrCreate(['exam_id' => $exam->id, 'slug' => $competence],
            ['name' => $competence, 'skill_type' => $competence, 'time_limit' => 30]);
        $type = ExerciseType::firstOrCreate(['section_id' => $section->id, 'slug' => $competence.'-mcq'],
            ['name' => $competence, 'skill_type' => $competence, 'component_key' => 'mcq']);
        $ex = Exercise::create(['exam_id' => $exam->id, 'exercise_type_id' => $type->id,
            'difficulty' => 'B1', 'content' => ['title' => 'x'],
            'questions' => [['id' => 'q1', 'type' => 'mcq', 'text' => 'x', 'options' => ['a', 'b'], 'correct_answer' => 'A']]]);
        DB::table('user_exercise_attempts')->insert([
            'user_id' => $u->id, 'exercise_id' => $ex->id, 'answers' => json_encode([]),
            'score' => $reussite, 'accuracy_percent' => $reussite,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    return $u;
}

test('le portrait ne qualifie rien sans assez d exercices', function () {
    // Deux exercices rates ne font pas une faiblesse : qualifier sur un coup de
    // malchance decourage pour rien.
    $u = apprenantAvec([['reading', 20], ['reading', 30]]);

    $p = app(ProgressionApprenant::class)->portrait($u);

    expect($p['competences']['a_retravailler'])->toBe([])
        ->and($p['competences']['detail']['reading']['exercices'])->toBe(2);
});

test('une competence durablement basse est signalee', function () {
    $u = apprenantAvec([['listening', 30], ['listening', 40], ['listening', 25]]);

    $p = app(ProgressionApprenant::class)->portrait($u);

    expect($p['competences']['a_retravailler'])->toContain('listening')
        ->and($p['competences']['bien_maitrisees'])->toBe([]);
});

test('les competences jamais travaillees sont nommees', function () {
    $u = apprenantAvec([['reading', 90], ['reading', 85], ['reading', 95]]);

    $p = app(ProgressionApprenant::class)->portrait($u);

    expect($p['competences']['bien_maitrisees'])->toContain('reading')
        ->and($p['competences']['jamais_travaillees'])
        ->toContain('listening')->toContain('writing')->toContain('speaking');
});

test('ce qui n est pas mesure reste nul, au lieu d etre invente', function () {
    $u = apprenantAvec();

    $p = app(ProgressionApprenant::class)->portrait($u);

    expect($p['activite']['reussite_moyenne_7j'])->toBeNull()
        ->and($p['activite']['dernier_exercice'])->toBeNull()
        ->and($p['jours_avant_examen'])->toBeNull()
        ->and($p['activite']['exercices_en_tout'])->toBe(0);
});

test('une date d examen passee n est pas annoncee comme une echeance', function () {
    $u = apprenantAvec([], ['profil' => ['exam_date' => now()->subDays(12)->toDateString()]]);

    // Mieux vaut ne rien dire que d'annoncer « dans -12 jours ».
    expect(app(ProgressionApprenant::class)->portrait($u)['jours_avant_examen'])->toBeNull();
});

test('une date d examen a venir est comptee en jours', function () {
    $u = apprenantAvec([], ['profil' => ['exam_date' => now()->addDays(30)->toDateString()]]);

    expect(app(ProgressionApprenant::class)->portrait($u)['jours_avant_examen'])->toBe(30);
});

test('les erreurs non maitrisees passent avant le reste', function () {
    $u = apprenantAvec([['reading', 90], ['reading', 95], ['reading', 92]]);
    for ($i = 0; $i < 6; $i++) {
        UserError::create(['user_id' => $u->id, 'question_id' => 'q'.$i,
            'question_text' => 'x', 'user_answer' => 'a', 'correct_answer' => 'b',
            'skill_type' => 'reading', 'exercise_type_slug' => 'mcq',
            'error_category' => 'accord', 'mastered' => false]);
    }

    $p = app(ProgressionApprenant::class)->portrait($u);

    expect($p['prochaine_etape']['quoi'])->toContain('erreurs')
        ->and($p['prochaine_etape']['pourquoi'])->toContain('6')
        ->and($p['erreurs_qui_resistent'][0]['categorie'])->toBe('accord');
});

test('sans erreur qui resiste, la competence la plus basse passe devant', function () {
    $u = apprenantAvec([['listening', 30], ['listening', 35], ['listening', 40]]);

    $p = app(ProgressionApprenant::class)->portrait($u);

    expect($p['prochaine_etape']['quoi'])->toContain('compréhension orale')
        ->and($p['prochaine_etape']['ou'])->toBe('Pratiquer');
});

test('le prenom seul est retenu, pas le nom complet', function () {
    $p = app(ProgressionApprenant::class)->portrait(apprenantAvec([], ['nom' => 'Amadou Diallo']));

    expect($p['prenom'])->toBe('Amadou');
});
