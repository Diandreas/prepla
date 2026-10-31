<?php

use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Models\UserExerciseAttempt;
use App\Models\UserProfile;
use Illuminate\Support\Carbon;

/**
 * Ce qu'un centre sait du travail d'un apprenant : quoi, quand, a quelle heure,
 * combien de temps, avec quel resultat — et ce qu'il a repondu.
 *
 * Tout etait enregistre, mais la fiche ne montrait que les vingt dernieres
 * tentatives, par type et pourcentage : la date partait vers la page sans y
 * etre affichee. Un centre ne pouvait pas dire a un parent quand son enfant
 * avait travaille.
 */
beforeEach(fn () => $this->withoutVite());

function centreAvecEleve(): array
{
    $langue = Language::create(['slug' => 'german', 'name' => 'Allemand', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::create(['language_id' => $langue->id, 'slug' => 'goethe', 'name' => 'Goethe-Zertifikat']);
    $section = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'lesen', 'name' => 'Lesen', 'skill_type' => 'reading']);
    $type = ExerciseType::create([
        'section_id' => $section->id, 'slug' => 'mcq', 'name' => 'QCM', 'skill_type' => 'reading', 'component_key' => 'mcq',
    ]);
    $exercice = Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'exam_section_id' => $section->id,
        'difficulty' => 'B1', 'is_ai_generated' => false,
        'content' => ['title' => 'Goethe B1 · Lesen, Teil 2', 'exam_part_name' => 'Lesen, Teil 2', 'passage' => 'Anna wohnt in Wien.'],
        'questions' => [
            ['id' => 'q1', 'type' => 'mcq', 'text' => 'Wo wohnt Anna?', 'options' => ['In Berlin', 'In Wien'], 'correct_answer' => 'B', 'explanation' => 'Elle habite à Vienne.'],
            ['id' => 'q2', 'type' => 'mcq', 'text' => 'Ist sie Studentin?', 'options' => ['Ja', 'Nein'], 'correct_answer' => 'A', 'explanation' => 'Oui, elle étudie.'],
        ],
    ]);

    $centre = LanguageCenter::create([
        'name' => 'Institut Test', 'slug' => 'institut-test', 'owner_email' => 'resp@exemple.test', 'is_active' => true,
    ]);

    $membre = function (string $role) use ($centre) {
        $u = User::factory()->create();
        UserProfile::factory()->for($u)->create(['onboarding_completed_at' => now()]);
        $centre->members()->attach($u->id, ['role' => $role, 'joined_at' => now()]);

        return $u;
    };

    $responsable = $membre('center_admin');
    $prof = $membre('teacher');
    $autreProf = $membre('teacher');
    $eleve = $membre('student');
    $autreEleve = $membre('student');

    $classe = $centre->classrooms()->create([
        'name' => 'Groupe B1', 'level' => 'B1', 'exam_id' => $exam->id, 'invite_code' => Classroom::generateInviteCode(),
    ]);
    $classe->members()->attach($prof->id, ['role_in_class' => 'teacher']);
    $classe->members()->attach($eleve->id, ['role_in_class' => 'student']);

    $autreClasse = $centre->classrooms()->create([
        'name' => 'Groupe A2', 'level' => 'A2', 'exam_id' => $exam->id, 'invite_code' => Classroom::generateInviteCode(),
    ]);
    $autreClasse->members()->attach($autreProf->id, ['role_in_class' => 'teacher']);
    $autreClasse->members()->attach($autreEleve->id, ['role_in_class' => 'student']);

    return compact('centre', 'responsable', 'prof', 'autreProf', 'eleve', 'autreEleve', 'exercice', 'classe');
}

function tentative(User $eleve, Exercise $exercice, string $quandUtc, array $reponses = ['q1' => 'B', 'q2' => 'B'], int $secondes = 95): UserExerciseAttempt
{
    $t = UserExerciseAttempt::create([
        'user_id' => $eleve->id,
        'exercise_id' => $exercice->id,
        'answers' => $reponses,
        'score' => 1,
        'accuracy_percent' => 50,
        'time_spent' => $secondes,
        'xp_earned' => 5,
        'feedback' => [
            ['question_id' => 'q1', 'correct' => true, 'accuracy' => 100, 'correct_answer' => 'In Wien', 'explanation' => 'Elle habite à Vienne.'],
            ['question_id' => 'q2', 'correct' => false, 'accuracy' => 0, 'correct_answer' => 'Ja', 'explanation' => 'Oui, elle étudie.'],
        ],
    ]);
    $t->forceFill(['created_at' => Carbon::parse($quandUtc, 'UTC'), 'updated_at' => Carbon::parse($quandUtc, 'UTC')])->save();

    return $t;
}

test('la fiche dit quand l eleve a travaille, combien de temps, et sur quoi', function () {
    $c = centreAvecEleve();
    Carbon::setTestNow(Carbon::parse('2026-10-31 10:00', 'UTC'));
    tentative($c['eleve'], $c['exercice'], '2026-10-30 17:42', secondes: 300);
    tentative($c['eleve'], $c['exercice'], '2026-10-28 08:15', secondes: 120);

    $this->actingAs($c['responsable'])
        ->get(route('center.students.show', $c['eleve']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('center/students/show')
            ->where('resume.tentatives', 2)
            ->where('resume.temps_total_secondes', 420)
            ->where('resume.jours_actifs_30', 2)
            ->where('resume.derniere_activite', Carbon::parse('2026-10-30 17:42', 'UTC')->toIso8601String())
            // L'historique porte la DATE et l'HEURE de chaque exercice, et dit lequel.
            ->where('historique.data.0.date', Carbon::parse('2026-10-30 17:42', 'UTC')->toIso8601String())
            ->where('historique.data.0.exercice', 'Goethe B1 · Lesen, Teil 2')
            ->where('historique.data.0.examen', 'Goethe-Zertifikat')
            ->where('historique.data.0.temps_secondes', 300)
            ->where('historique.data.0.justes', 1)
            ->where('historique.data.0.total', 2));

    Carbon::setTestNow();
});

/**
 * Les jours se comptent a l'heure du centre : un exercice fait a 0 h 30 a
 * Douala tombe la veille en UTC, et ne doit pas etre range dans le mauvais jour.
 */
test('l activite par jour suit l heure du centre et non l UTC', function () {
    $c = centreAvecEleve();
    Carbon::setTestNow(Carbon::parse('2026-10-31 10:00', 'UTC'));
    // 23 h 30 UTC le 29 = 0 h 30 le 30 a Douala (UTC+1).
    tentative($c['eleve'], $c['exercice'], '2026-10-29 23:30');

    $this->actingAs($c['responsable'])
        ->get(route('center.students.show', $c['eleve']))
        ->assertInertia(function ($page) {
            $jours = collect($page->toArray()['props']['resume']['activite_30_jours'])->keyBy('date');
            expect($jours['2026-10-30']['exercices'])->toBe(1)
                ->and($jours['2026-10-29']['exercices'])->toBe(0);
        });

    Carbon::setTestNow();
});

test('le detail d une copie montre ce que l eleve a repondu et la correction', function () {
    $c = centreAvecEleve();
    $t = tentative($c['eleve'], $c['exercice'], '2026-10-30 17:42');

    $this->actingAs($c['prof'])
        ->get(route('center.students.attempt', ['user' => $c['eleve'], 'attempt' => $t]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('center/students/attempt')
            ->where('tentative.questions.0.texte', 'Wo wohnt Anna?')
            ->where('tentative.questions.0.reponse', 'B')
            ->where('tentative.questions.0.juste', true)
            ->where('tentative.questions.1.reponse', 'B')
            ->where('tentative.questions.1.juste', false)
            ->where('tentative.questions.1.bonne_reponse', 'Ja')
            ->where('tentative.questions.1.explication', 'Oui, elle étudie.'));
});

/**
 * L'identifiant d'une tentative ne doit pas ouvrir la copie d'un autre eleve en
 * passant par l'adresse d'un eleve qu'on a le droit de suivre.
 */
test('on ne lit pas la copie d un autre eleve par l adresse d un eleve suivi', function () {
    $c = centreAvecEleve();
    $copieDeLAutre = tentative($c['autreEleve'], $c['exercice'], '2026-10-30 17:42');

    $this->actingAs($c['prof'])
        ->get(route('center.students.attempt', ['user' => $c['eleve'], 'attempt' => $copieDeLAutre]))
        ->assertNotFound();
});

test('un enseignant ne suit que les eleves de ses classes', function () {
    $c = centreAvecEleve();
    $t = tentative($c['autreEleve'], $c['exercice'], '2026-10-30 17:42');

    $this->actingAs($c['prof'])->get(route('center.students.show', $c['autreEleve']))->assertForbidden();
    $this->actingAs($c['prof'])->get(route('center.students.export', $c['autreEleve']))->assertForbidden();
    $this->actingAs($c['prof'])
        ->get(route('center.students.attempt', ['user' => $c['autreEleve'], 'attempt' => $t]))
        ->assertForbidden();

    // Le responsable du centre, lui, suit tout le monde.
    $this->actingAs($c['responsable'])->get(route('center.students.show', $c['autreEleve']))->assertOk();
});

test('un centre ne suit pas l eleve d un autre centre', function () {
    $c = centreAvecEleve();
    $etranger = User::factory()->create();

    $this->actingAs($c['responsable'])->get(route('center.students.show', $etranger))->assertForbidden();
    $this->actingAs($c['responsable'])->get(route('center.students.export', $etranger))->assertForbidden();
});

/**
 * L'export est ecrit cote serveur : il prend le fuseau du centre, et le nomme
 * dans son en-tete pour qu'on ne se trompe pas d'heure en le lisant.
 */
test('l export donne la date, l heure du centre et le temps passe', function () {
    $c = centreAvecEleve();
    tentative($c['eleve'], $c['exercice'], '2026-10-29 23:30', secondes: 300);

    $csv = $this->actingAs($c['responsable'])
        ->get(route('center.students.export', $c['eleve']))
        ->assertOk()
        ->streamedContent();

    expect($csv)->toContain('Heure (Africa/Douala)')
        // 23 h 30 UTC le 29 = 0 h 30 le 30 a Douala.
        ->toContain('2026-10-30;00:30')
        ->toContain('Goethe B1 · Lesen, Teil 2')
        // La competence en francais : l'export se lit dans un tableur, par un
        // centre francophone. Puis 50 % de reussite et 300 s = 5 min.
        ->toContain(';Lecture;1;2;50;5');
});

test('un centre peut regler son propre fuseau', function () {
    $c = centreAvecEleve();
    $c['centre']->update(['settings' => ['fuseau' => 'Europe/Paris']]);
    tentative($c['eleve'], $c['exercice'], '2026-10-29 23:30');

    $csv = $this->actingAs($c['responsable'])
        ->get(route('center.students.export', $c['eleve']))
        ->streamedContent();

    // Le 29 octobre 2026, Paris est en heure d'hiver : UTC+1.
    expect($csv)->toContain('Heure (Europe/Paris)')->toContain('2026-10-30;00:30');
});

/**
 * L'export de classe ouvrait en une seule colonne dans l'Excel francais (il
 * attend le point-virgule), donnait la derniere activite en jour UTC sans heure,
 * et ignorait le temps de travail.
 */
test('l export de classe se lit dans un Excel francais, a l heure du centre', function () {
    $c = centreAvecEleve();
    tentative($c['eleve'], $c['exercice'], '2026-10-29 23:30', secondes: 600);

    $csv = $this->actingAs($c['responsable'])
        ->get(route('center.classes.export', $c['classe']))
        ->assertOk()
        ->streamedContent();

    expect($csv)->toContain('Élève;"Exercices faits"')
        ->toContain('Heure (Africa/Douala)')
        // 10 min de travail, derniere activite le 30 a 0 h 30, heure de Douala.
        ->toContain(';10;2026-10-30;00:30');
});

/**
 * La derniere activite partait vers la page de classe comme une chaine brute de
 * la base, sans fuseau : le navigateur la lisait comme de l'heure locale.
 */
test('la page de classe recoit la derniere activite avec son fuseau', function () {
    $c = centreAvecEleve();
    tentative($c['eleve'], $c['exercice'], '2026-10-29 23:30', secondes: 600);

    $this->actingAs($c['responsable'])
        ->get(route('center.classes.show', $c['classe']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('stats.per_student.0.last_active', Carbon::parse('2026-10-29 23:30', 'UTC')->toIso8601String())
            ->where('stats.per_student.0.temps_total_secondes', 600));
});

/**
 * Une tentative sans note (correction absente) ne doit pas se lire comme un
 * echec : sans aucune note, il n'y a pas de moyenne, et non « 0 % ».
 */
test('sans aucune note, la moyenne est absente et non nulle', function () {
    $c = centreAvecEleve();
    $t = tentative($c['eleve'], $c['exercice'], '2026-10-30 17:42');
    $t->forceFill(['accuracy_percent' => null])->save();

    $this->actingAs($c['responsable'])
        ->get(route('center.students.show', $c['eleve']))
        ->assertInertia(fn ($page) => $page->where('resume.precision_moyenne', null));

    $this->actingAs($c['responsable'])
        ->get(route('center.classes.show', $c['classe']))
        ->assertInertia(fn ($page) => $page->where('stats.per_student.0.avg_accuracy', null));
});
