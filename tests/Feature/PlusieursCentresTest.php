<?php

use App\Models\CenterUser;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\Language;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\QueryException;

/**
 * Une personne peut gerer plusieurs centres.
 *
 * Tous les acces passent par center() et centerRole() : ils doivent parler du
 * MEME centre, celui que la personne a choisi. Avant, center() prenait le
 * premier centre venu et centerRole() une appartenance quelconque — avec deux
 * centres, on aurait pu agir dans l'un avec le role tenu dans l'autre.
 */
beforeEach(fn () => $this->withoutVite());

function deuxCentres(): array
{
    $langue = Language::create(['slug' => 'german', 'name' => 'Allemand', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::create(['language_id' => $langue->id, 'slug' => 'goethe', 'name' => 'Goethe']);

    $nouveauCentre = fn (string $nom) => LanguageCenter::create([
        'name' => $nom, 'slug' => \Illuminate\Support\Str::slug($nom), 'owner_email' => 'x@exemple.test', 'is_active' => true,
    ]);
    $a = $nouveauCentre('Centre Akwa');
    $b = $nouveauCentre('Centre Bonamoussadi');

    $personne = User::factory()->create();
    UserProfile::factory()->for($personne)->create(['onboarding_completed_at' => now()]);
    // Responsable de A, simple enseignante dans B.
    $a->members()->attach($personne->id, ['role' => 'center_admin', 'joined_at' => now()]);
    $b->members()->attach($personne->id, ['role' => 'teacher', 'joined_at' => now()]);

    $classe = fn (LanguageCenter $c, string $nom) => $c->classrooms()->create([
        'name' => $nom, 'level' => 'B1', 'exam_id' => $exam->id, 'invite_code' => Classroom::generateInviteCode(),
    ]);
    $classeA = $classe($a, 'Classe A');
    $classeB1 = $classe($b, 'Classe B1 — la sienne');
    $classeB2 = $classe($b, 'Classe B2 — celle d une collegue');
    $classeB1->members()->attach($personne->id, ['role_in_class' => 'teacher']);

    $eleve = function (LanguageCenter $c, Classroom $k) {
        $u = User::factory()->create();
        UserProfile::factory()->for($u)->create(['onboarding_completed_at' => now()]);
        $c->members()->attach($u->id, ['role' => 'student', 'joined_at' => now()]);
        $k->members()->attach($u->id, ['role_in_class' => 'student']);

        return $u;
    };

    return [
        'personne' => $personne, 'a' => $a, 'b' => $b, 'exam' => $exam,
        'classeA' => $classeA, 'classeB1' => $classeB1, 'classeB2' => $classeB2,
        'eleveA' => $eleve($a, $classeA), 'eleveB1' => $eleve($b, $classeB1), 'eleveB2' => $eleve($b, $classeB2),
    ];
}

test('sans choix, on entre dans le centre qu on dirige', function () {
    $d = deuxCentres();

    $this->actingAs($d['personne'])
        ->get(route('center.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('auth.center.id', $d['a']->id)
            ->where('auth.center.role', 'center_admin')
            ->has('auth.centres', 2));
});

/**
 * Le coeur du sujet : dans le centre B ou elle n'est qu'enseignante, elle ne
 * doit PAS garder les droits de responsable qu'elle tient dans A.
 */
test('dans un autre centre, on n a que le role qu on y tient', function () {
    $d = deuxCentres();

    $this->actingAs($d['personne'])->post(route('center.switch'), ['center_id' => $d['b']->id])
        ->assertRedirect(route('center.dashboard'));

    $this->actingAs($d['personne'])
        ->get(route('center.dashboard'))
        ->assertInertia(fn ($page) => $page->where('auth.center.id', $d['b']->id)->where('auth.center.role', 'teacher'));

    // Enseignante dans B : l'eleve d'une classe qu'elle n'enseigne pas lui est ferme.
    $this->actingAs($d['personne'])->get(route('center.students.show', $d['eleveB2']))->assertForbidden();
    $this->actingAs($d['personne'])->get(route('center.students.show', $d['eleveB1']))->assertOk();

    // Et l'eleve de A, son autre centre, n'est plus a portee depuis B.
    $this->actingAs($d['personne'])->get(route('center.students.show', $d['eleveA']))->assertForbidden();
});

test('revenir dans son centre rend les droits de responsable', function () {
    $d = deuxCentres();

    $this->actingAs($d['personne'])->post(route('center.switch'), ['center_id' => $d['b']->id]);
    $this->actingAs($d['personne'])->post(route('center.switch'), ['center_id' => $d['a']->id]);

    $this->actingAs($d['personne'])->get(route('center.students.show', $d['eleveA']))->assertOk();
});

test('on ne choisit pas un centre ou l on n encadre pas', function () {
    $d = deuxCentres();
    $autre = LanguageCenter::create(['name' => 'Ailleurs', 'slug' => 'ailleurs', 'owner_email' => 'y@exemple.test', 'is_active' => true]);

    $this->actingAs($d['personne'])->post(route('center.switch'), ['center_id' => $autre->id])->assertForbidden();

    // Un eleve d'un centre ne peut pas s'y faire passer pour du personnel.
    $this->actingAs($d['eleveB1'])->post(route('center.switch'), ['center_id' => $d['b']->id])->assertForbidden();
});

test('un centre ferme ne retient personne', function () {
    $d = deuxCentres();

    $this->actingAs($d['personne'])->post(route('center.switch'), ['center_id' => $d['b']->id]);
    $d['b']->update(['is_active' => false]);

    // Le choix enregistre designe un centre ferme : on retombe dans le centre actif.
    $this->actingAs($d['personne'])
        ->get(route('center.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('auth.center.id', $d['a']->id));
});

test('les classes affichees sont celles du centre courant', function () {
    $d = deuxCentres();

    $this->actingAs($d['personne'])->post(route('center.switch'), ['center_id' => $d['b']->id]);

    $this->actingAs($d['personne'])
        ->get(route('center.classes.index'))
        ->assertOk()
        ->assertDontSee('Classe A')
        ->assertSee('Classe B1');
});

/**
 * Ce qui reste interdit, une fois la contrainte « un seul centre » levee.
 */
test('on n appartient pas deux fois au meme centre', function () {
    $d = deuxCentres();

    expect(fn () => CenterUser::create(['center_id' => $d['a']->id, 'user_id' => $d['personne']->id, 'role' => 'teacher', 'joined_at' => now()]))
        ->toThrow(QueryException::class);
});

test('un eleve reste dans un seul etablissement', function () {
    $d = deuxCentres();

    $this->actingAs($d['eleveA'])
        ->post(route('center.join.store'), ['code' => $d['classeB1']->invite_code])
        ->assertSessionHasErrors('code');

    expect($d['eleveA']->fresh()->centers()->count())->toBe(1);
});

/**
 * Un responsable pouvait inviter un enseignant, mais ne voyait nulle part qui
 * faisait partie de son centre, ni quelle classe etait a qui.
 */
test('le responsable voit son equipe, ses classes et les invitations en attente', function () {
    $d = deuxCentres();
    $collegue = User::factory()->create(['name' => 'Collègue Ndjock']);
    $d['a']->members()->attach($collegue->id, ['role' => 'teacher', 'joined_at' => now()]);
    $d['classeA']->members()->attach($collegue->id, ['role_in_class' => 'teacher']);
    $orpheline = $d['a']->classrooms()->create(['name' => 'Classe orpheline', 'level' => 'A2', 'exam_id' => $d['exam']->id, 'invite_code' => Classroom::generateInviteCode()]);
    \App\Models\TeacherInvitation::issue(['name' => 'Futur Prof', 'email' => 'futur@exemple.test', 'center_id' => $d['a']->id, 'role' => 'teacher', 'classroom_name' => 'C1', 'invited_by' => $d['personne']->id]);

    $this->actingAs($d['personne'])
        ->get(route('center.equipe'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('center/equipe')
            ->has('equipe', 2)
            ->where('equipe', fn ($equipe) => collect($equipe)->firstWhere('name', 'Collègue Ndjock')['classes'][0]['name'] === 'Classe A')
            ->where('classesSansEnseignant.0.name', 'Classe orpheline')
            ->where('invitations.0.email', 'futur@exemple.test'));
});

test('un enseignant ne voit pas l equipe', function () {
    $d = deuxCentres();
    $this->actingAs($d['personne'])->post(route('center.switch'), ['center_id' => $d['b']->id]);

    $this->actingAs($d['personne'])->get(route('center.equipe'))->assertForbidden();
});

/**
 * Ouvrir un second espace obligeait a se creer un second compte.
 */
test('un responsable ouvre un second espace et y entre', function () {
    $d = deuxCentres();

    $this->actingAs($d['personne'])->post(route('teach.open.store'), [
        'name' => 'Centre de Kribi', 'classroom_name' => 'Premiers pas', 'level' => 'A1',
    ])->assertRedirect(route('center.dashboard'));

    $kribi = LanguageCenter::where('name', 'Centre de Kribi')->sole();

    expect($d['personne']->fresh()->centers()->count())->toBe(3);
    $this->actingAs($d['personne'])
        ->get(route('center.dashboard'))
        ->assertInertia(fn ($page) => $page->where('auth.center.id', $kribi->id)->where('auth.center.role', 'center_admin'));
});

test('un eleve n ouvre pas d espace', function () {
    $d = deuxCentres();

    $this->actingAs($d['eleveA'])->post(route('teach.open.store'), [
        'name' => 'Mon école', 'classroom_name' => 'Classe',
    ])->assertRedirect(route('dashboard'));

    expect(LanguageCenter::where('name', 'Mon école')->exists())->toBeFalse();
});

/**
 * Un enseignant deja inscrit butait sur « un compte existe deja » : le lien
 * d'invitation ne marchait que pour un compte neuf.
 */
test('un enseignant deja inscrit rejoint un second centre par invitation, sans recreer de compte', function () {
    $d = deuxCentres();
    $c = LanguageCenter::create(['name' => 'Centre Deido', 'slug' => 'deido', 'owner_email' => 'z@exemple.test', 'is_active' => true]);
    [, $jeton] = \App\Models\TeacherInvitation::issue([
        'name' => $d['personne']->name, 'email' => $d['personne']->email, 'center_id' => $c->id,
        'role' => 'teacher', 'classroom_name' => 'Groupe du soir', 'invited_by' => $d['personne']->id,
    ]);

    // Connectee sous la bonne adresse, elle voit « Rejoindre », pas un formulaire de compte.
    $this->actingAs($d['personne'])
        ->get(route('teach.invitation.show', $jeton))
        ->assertInertia(fn ($page) => $page->where('invitation.connecte_comme_invite', true));

    $this->actingAs($d['personne'])
        ->post(route('teach.invitation.accept', $jeton))
        ->assertRedirect(route('center.dashboard'));

    expect($d['personne']->fresh()->centers()->pluck('language_centers.id')->all())->toContain($c->id)
        ->and(User::where('email', $d['personne']->email)->count())->toBe(1);

    $this->actingAs($d['personne'])
        ->get(route('center.dashboard'))
        ->assertInertia(fn ($page) => $page->where('auth.center.id', $c->id)->where('auth.center.role', 'teacher'));
});

test('avec un compte existant mais deconnecte, l invitation renvoie a la connexion puis revient', function () {
    $d = deuxCentres();
    [, $jeton] = \App\Models\TeacherInvitation::issue([
        'name' => 'X', 'email' => $d['personne']->email, 'center_id' => $d['b']->id,
        'role' => 'teacher', 'classroom_name' => 'G', 'invited_by' => $d['personne']->id,
    ]);

    $this->get(route('teach.invitation.show', $jeton))
        ->assertInertia(fn ($page) => $page->where('invitation.compte_existant', true)->where('invitation.connecte_comme_invite', false))
        ->assertSessionHas('url.intended', route('teach.invitation.show', $jeton));
});

test('un compte d eleve ne devient pas enseignant par une invitation', function () {
    $d = deuxCentres();
    $c = LanguageCenter::create(['name' => 'Centre Deido', 'slug' => 'deido', 'owner_email' => 'z@exemple.test', 'is_active' => true]);
    [, $jeton] = \App\Models\TeacherInvitation::issue([
        'name' => 'Eleve', 'email' => $d['eleveA']->email, 'center_id' => $c->id,
        'role' => 'teacher', 'classroom_name' => 'G', 'invited_by' => $d['personne']->id,
    ]);

    $this->actingAs($d['eleveA'])->post(route('teach.invitation.accept', $jeton))->assertRedirect(route('dashboard'));

    expect($d['eleveA']->fresh()->centers()->count())->toBe(1);
});

/**
 * Le tableau de bord ne donnait que trois chiffres. Il dit maintenant si la
 * semaine a ete travaillee, quelle classe decroche, et qui relancer — et un
 * enseignant n'y voit que ses propres classes.
 */
test('le tableau de bord dit qui relancer, et montre au responsable toutes les classes', function () {
    $d = deuxCentres();
    $section = \App\Models\ExamSection::create(['exam_id' => $d['exam']->id, 'slug' => 'lesen', 'name' => 'Lesen', 'skill_type' => 'reading']);
    $type = \App\Models\ExerciseType::create(['section_id' => $section->id, 'slug' => 'mcq', 'name' => 'QCM', 'skill_type' => 'reading', 'component_key' => 'mcq']);
    $exercice = \App\Models\Exercise::create(['exam_id' => $d['exam']->id, 'exercise_type_id' => $type->id, 'exam_section_id' => $section->id, 'difficulty' => 'B1', 'content' => [], 'questions' => []]);
    \App\Models\UserExerciseAttempt::create([
        'user_id' => $d['eleveA']->id, 'exercise_id' => $exercice->id, 'answers' => [], 'score' => 1,
        'accuracy_percent' => 80, 'time_spent' => 600, 'xp_earned' => 5, 'feedback' => [],
    ]);
    $absent = User::factory()->create(['name' => 'Absent Depuis Longtemps']);
    $d['a']->members()->attach($absent->id, ['role' => 'student', 'joined_at' => now()]);
    $d['classeA']->members()->attach($absent->id, ['role_in_class' => 'student']);

    $this->actingAs($d['personne'])
        ->get(route('center.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('semaine.actifs', 1)
            ->where('semaine.eleves', 2)
            ->where('semaine.exercices', 1)
            ->where('semaine.minutes', 10)
            ->where('a_relancer.0.name', 'Absent Depuis Longtemps')
            ->has('classes', 1));
});

test('un enseignant ne voit que ses classes au tableau de bord', function () {
    $d = deuxCentres();
    $this->actingAs($d['personne'])->post(route('center.switch'), ['center_id' => $d['b']->id]);

    $this->actingAs($d['personne'])
        ->get(route('center.dashboard'))
        ->assertInertia(fn ($page) => $page
            ->has('classes', 1)
            ->where('classes.0.name', 'Classe B1 — la sienne')
            ->where('semaine.eleves', 1));
});
