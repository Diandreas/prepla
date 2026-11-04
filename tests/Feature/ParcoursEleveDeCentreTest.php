<?php

use App\Models\Classroom;
use App\Models\CurriculumSkeleton;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Models\UserExerciseAttempt;
use App\Models\UserProfile;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * L'eleve d'un centre voyait « Choisir mon objectif — Créer mon parcours » sur
 * son accueil. C'est faux pour lui : son centre construit son parcours par ses
 * devoirs, et sa classe lui donne son examen et son niveau.
 */
function centreAvecClasse(array $settings = []): array
{
    $langue = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $examen = Exam::create(['language_id' => $langue->id, 'slug' => 'goethe-b1', 'name' => 'Goethe B1']);
    $autreExamen = Exam::create(['language_id' => $langue->id, 'slug' => 'telc-b1', 'name' => 'telc B1']);
    $section = ExamSection::create(['exam_id' => $examen->id, 'slug' => 'grammar', 'name' => 'Grammar', 'skill_type' => 'grammar']);
    $type = ExerciseType::create([
        'section_id' => $section->id, 'slug' => 'mcq', 'name' => 'QCM', 'skill_type' => 'grammar', 'component_key' => 'mcq',
    ]);

    $centre = LanguageCenter::create([
        'name' => 'Ecole Goethe', 'slug' => 'ecole-'.uniqid(), 'owner_email' => 'direction@exemple.test',
        'is_active' => true, 'seats_limit' => 50, 'settings' => $settings ?: null,
    ]);

    $membre = function (string $role) use ($centre) {
        $user = User::factory()->create();
        UserProfile::factory()->for($user)->create(['onboarding_completed_at' => now()]);
        $centre->members()->attach($user->id, ['role' => $role, 'joined_at' => now()]);

        return $user;
    };
    $responsable = $membre('center_admin');
    $prof = $membre('teacher');

    $classe = $centre->classrooms()->create([
        'name' => 'Allemand B1 soir', 'level' => 'B1', 'exam_id' => $examen->id, 'invite_code' => Classroom::generateInviteCode(),
    ]);
    $classe->members()->attach($prof->id, ['role_in_class' => 'teacher']);

    return compact('centre', 'responsable', 'prof', 'classe', 'examen', 'autreExamen', 'type');
}

/** Un eleve inscrit sans objectif ni onboarding termine : le cas signale. */
function eleveSansObjectif(array $e): User
{
    $eleve = User::factory()->create();
    UserProfile::factory()->for($eleve)->create([
        'target_exam_id' => null, 'current_level' => null, 'onboarding_completed_at' => null,
    ]);
    $e['centre']->members()->attach($eleve->id, ['role' => 'student', 'joined_at' => now()]);
    $e['classe']->members()->attach($eleve->id, ['role_in_class' => 'student']);

    return $eleve;
}

function devoir(array $e, string $titre, ?\Carbon\CarbonInterface $echeance, int $exercices = 2): \App\Models\Assignment
{
    $devoir = $e['classe']->assignments()->create([
        'created_by' => $e['prof']->id, 'title' => $titre, 'due_at' => $echeance, 'published_at' => now()->subDays(5),
    ]);
    for ($i = 0; $i < $exercices; $i++) {
        $ex = Exercise::create([
            'exam_id' => $e['examen']->id, 'exercise_type_id' => $e['type']->id, 'center_id' => $e['centre']->id,
            'difficulty' => 'B1', 'content' => [], 'questions' => [],
        ]);
        $devoir->items()->create(['itemable_type' => Exercise::class, 'itemable_id' => $ex->id, 'sort_order' => $i]);
    }

    return $devoir;
}

function faireExercice(User $eleve, int $exerciceId): void
{
    UserExerciseAttempt::create([
        'user_id' => $eleve->id, 'exercise_id' => $exerciceId, 'answers' => [],
        'score' => 1, 'accuracy_percent' => 80, 'time_spent' => 30, 'xp_earned' => 5, 'feedback' => [],
    ]);
}

test('un eleve de centre sans objectif n est pas envoye a l onboarding : sa mission est son devoir', function () {
    $e = centreAvecClasse();
    $eleve = eleveSansObjectif($e);
    $devoir = devoir($e, 'Les prépositions', now()->addDays(3));

    $this->actingAs($eleve)->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('dashboard')
            ->where('nextAction.kind', 'devoir')
            ->where('nextAction.title', 'Les prépositions')
            ->where('nextAction.url', route('devoirs.show', $devoir->id))
            ->where('nextAction.devoir.classe', 'Allemand B1 soir')
            ->where('nextAction.devoir.fait', 0)
            ->where('nextAction.devoir.total', 2)
            ->where('nextAction.devoir.en_retard', false)
            ->where('nextAction.devoir.echeance', fn ($t) => str_starts_with($t, 'pour ')));

    // Et la page du devoir existe vraiment.
    $this->actingAs($eleve)->get(route('devoirs.show', $devoir->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('devoirs/show')->where('devoir.id', $devoir->id)->has('exercices', 2));
});

test('le devoir en retard passe en premier, puis l echeance la plus proche', function () {
    $e = centreAvecClasse();
    $eleve = eleveSansObjectif($e);
    devoir($e, 'Sans échéance', null);
    devoir($e, 'Dans une semaine', now()->addWeek());
    devoir($e, 'Demain', now()->addDay()->setTime(18, 0));
    devoir($e, 'En retard', now()->subDays(2));
    $fini = devoir($e, 'Déjà fait, en retard', now()->subDays(4), 1);
    faireExercice($eleve, $fini->items->first()->itemable_id);

    $this->actingAs($eleve)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('nextAction.title', 'En retard')
            ->where('nextAction.devoir.en_retard', true)
            ->where('nextAction.devoir.echeance', 'en retard de 2 jours'));

    $this->actingAs($eleve)->get(route('devoirs.index'))
        ->assertInertia(fn (Assert $page) => $page->component('devoirs/index')
            ->where('aFaire.0.title', 'En retard')
            ->where('aFaire.1.title', 'Demain')
            ->where('aFaire.1.echeance', 'pour demain')
            ->where('aFaire.2.title', 'Dans une semaine')
            ->where('aFaire.3.title', 'Sans échéance')
            ->where('termines.0.title', 'Déjà fait, en retard'));
});

test('sans devoir, en mode libre : l entrainement PrePla sur l examen et le niveau de la classe', function () {
    $e = centreAvecClasse();
    $eleve = eleveSansObjectif($e);

    $this->actingAs($eleve)->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('nextAction.kind', 'entrainement')
            ->where('nextAction.url', route('practice.exam', $e['examen']->id))
            ->where('nextAction.title', fn ($t) => str_contains($t, 'Goethe B1'))
            ->where('nextAction.description', fn ($t) => str_contains($t, 'niveau B1'))
            ->where('eleveDeCentre.contenu', 'libre'));

    // Le menu libre reste ouvert.
    $this->actingAs($eleve)->get(route('practice.index'))->assertOk();
});

test('en mode centre : message quand tout est fait, et l entrainement libre redirige vers les devoirs', function () {
    $e = centreAvecClasse([LanguageCenter::CONTENU_ELEVES => LanguageCenter::CONTENU_CENTRE]);
    $eleve = eleveSansObjectif($e);
    $fait = devoir($e, 'Rendu', now()->subDay(), 1);
    faireExercice($eleve, $fait->items->first()->itemable_id);

    $this->actingAs($eleve)->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('nextAction.kind', 'devoirs_finis')
            ->where('nextAction.title', 'Tu as fait tous tes devoirs.')
            // Sans parcours de classe encore prepare (ParcoursDeClasseTest pour la suite).
            ->where('nextAction.description', 'Ton centre n’a pas encore préparé le parcours de ta classe. Ton enseignant te donnera bientôt du travail.')
            ->where('nextAction.url', route('results.index'))
            ->where('contenuCentre', true)
            ->where('eleveDeCentre.contenu', 'centre'));

    // La pratique mene a « Mon parcours » ; le reste, aux devoirs.
    foreach ([route('practice.index'), route('practice.exam', $e['examen']->id)] as $url) {
        $this->actingAs($eleve)->get($url)
            ->assertRedirect(route('parcours.index'))
            ->assertSessionHas('success');
    }
    foreach ([
        route('lessons.next'),
        route('ai-tools.index'),
        route('guide'),
        route('onboarding.exam'),
    ] as $url) {
        $this->actingAs($eleve)->get($url)
            ->assertRedirect(route('devoirs.index'))
            ->assertSessionHas('success');
    }

    // Ce qui reste : ses resultats, ses mots, rejoindre une classe, ses devoirs.
    $this->actingAs($eleve)->get(route('results.index'))->assertOk();
    $this->actingAs($eleve)->get(route('center.join'))->assertOk();
    $this->actingAs($eleve)->get(route('devoirs.index'))->assertOk();

    // Un exercice hors de ses devoirs ne s'ouvre pas ; celui d'un devoir, si.
    $horsDevoir = Exercise::create([
        'exam_id' => $e['examen']->id, 'exercise_type_id' => $e['type']->id, 'difficulty' => 'B1', 'content' => [], 'questions' => [],
    ]);
    $this->actingAs($eleve)->get(route('exercise.show', $horsDevoir->id))->assertRedirect(route('devoirs.index'));
    $ouvert = $this->actingAs($eleve)->get(route('exercise.show', $fait->items->first()->itemable_id));
    expect($ouvert->headers->get('Location'))->not->toBe(route('devoirs.index'));
});

test('l objectif vient de la classe sans jamais ecraser le choix de l eleve', function () {
    $e = centreAvecClasse();

    // Sans objectif : rempli au premier affichage (eleve deja inscrit).
    $sans = eleveSansObjectif($e);
    $this->actingAs($sans)->get(route('dashboard'))->assertOk();
    expect($sans->profile->fresh())
        ->target_exam_id->toBe($e['examen']->id)
        ->current_level->toBe('B1');

    // Avec son propre choix : rien ne bouge.
    $avec = User::factory()->create();
    UserProfile::factory()->for($avec)->create([
        'target_exam_id' => $e['autreExamen']->id, 'current_level' => 'A2', 'onboarding_completed_at' => now(),
    ]);
    $e['centre']->members()->attach($avec->id, ['role' => 'student', 'joined_at' => now()]);
    $e['classe']->members()->attach($avec->id, ['role_in_class' => 'student']);
    $this->actingAs($avec)->get(route('dashboard'))->assertOk();
    expect($avec->profile->fresh())
        ->target_exam_id->toBe($e['autreExamen']->id)
        ->current_level->toBe('A2');

    // En rejoignant une classe par son code : meme regle.
    $nouveau = User::factory()->create();
    UserProfile::factory()->for($nouveau)->create(['target_exam_id' => null, 'current_level' => null]);
    $this->actingAs($nouveau)->post(route('center.join.store'), ['code' => $e['classe']->invite_code])
        ->assertRedirect(route('dashboard'));
    expect($nouveau->profile->fresh())
        ->target_exam_id->toBe($e['examen']->id)
        ->current_level->toBe('B1');

    $choisi = User::factory()->create();
    UserProfile::factory()->for($choisi)->create(['target_exam_id' => $e['autreExamen']->id, 'current_level' => 'A2']);
    $this->actingAs($choisi)->post(route('center.join.store'), ['code' => $e['classe']->invite_code]);
    expect($choisi->profile->fresh())
        ->target_exam_id->toBe($e['autreExamen']->id)
        ->current_level->toBe('A2');
});

test('un eleve de plusieurs classes prend l objectif de la plus recente', function () {
    $e = centreAvecClasse();
    $eleve = eleveSansObjectif($e);
    $recente = $e['centre']->classrooms()->create([
        'name' => 'telc A2', 'level' => 'A2', 'exam_id' => $e['autreExamen']->id, 'invite_code' => Classroom::generateInviteCode(),
    ]);
    $this->travel(1)->minutes();
    $recente->members()->attach($eleve->id, ['role_in_class' => 'student']);

    $this->actingAs($eleve)->get(route('dashboard'))->assertOk();
    expect($eleve->profile->fresh())
        ->target_exam_id->toBe($e['autreExamen']->id)
        ->current_level->toBe('A2');
});

test('l apprenant libre garde l onboarding et « Choisir mon objectif »', function () {
    $e = centreAvecClasse();

    $libre = User::factory()->create();
    UserProfile::factory()->for($libre)->create(['target_exam_id' => null, 'onboarding_completed_at' => null]);
    $this->actingAs($libre)->get(route('dashboard'))->assertRedirect(route('onboarding.exam'));

    $libre->profile->update(['onboarding_completed_at' => now()]);
    $this->actingAs($libre)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('nextAction.kind', 'onboarding')
            ->where('nextAction.title', 'Choisir mon objectif')
            ->missing('eleveDeCentre'));

    // Pas d'eleve de centre, pas de page de devoirs.
    $this->actingAs($libre)->get(route('devoirs.index'))->assertRedirect(route('dashboard'));

    // Le personnel du centre n'est pas touche non plus.
    $this->actingAs($e['prof'])->get(route('practice.index'))->assertOk();
});

test('un eleve avec un parcours en cours le continue en mode libre une fois ses devoirs faits', function () {
    $e = centreAvecClasse();
    $eleve = eleveSansObjectif($e);
    $eleve->profile->update(['target_exam_id' => $e['examen']->id, 'current_level' => 'B1']);
    CurriculumSkeleton::create([
        'user_id' => $eleve->id, 'exam_id' => $e['examen']->id,
        'objectives' => [[
            'order' => 0, 'title' => 'Le passé composé', 'concept' => 'grammar.basic',
            'level' => 'B1', 'status' => 'current', 'priority' => 'normal',
        ]],
        'current_objective_index' => 0, 'consecutive_successes' => 0, 'consecutive_failures' => 0,
    ]);

    // Son parcours PrePla reprend la main : ni devoir, ni « Choisir mon objectif ».
    $this->actingAs($eleve)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('nextAction.kind',
            fn ($kind) => ! in_array($kind, ['devoir', 'devoirs_finis', 'entrainement', 'onboarding'], true)));
});

test('seul le responsable change le reglage ; un centre existant est en libre par defaut', function () {
    $e = centreAvecClasse();
    expect($e['centre']->contenuEleves())->toBe(LanguageCenter::CONTENU_LIBRE);

    $this->actingAs($e['responsable'])->get(route('center.personnalisation.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('contenuEleves', 'libre'));

    $this->actingAs($e['prof'])
        ->patch(route('center.personnalisation.contenu-eleves'), ['contenu_eleves' => 'centre'])
        ->assertForbidden();
    expect($e['centre']->fresh()->contenuEleves())->toBe('libre');

    $this->actingAs($e['responsable'])
        ->patch(route('center.personnalisation.contenu-eleves'), ['contenu_eleves' => 'n-importe-quoi'])
        ->assertSessionHasErrors('contenu_eleves');

    $e['centre']->update(['settings' => [LanguageCenter::LIBRE_SERVICE => true]]);
    $this->actingAs($e['responsable'])
        ->patch(route('center.personnalisation.contenu-eleves'), ['contenu_eleves' => 'centre'])
        ->assertRedirect();
    $centre = $e['centre']->fresh();
    expect($centre->contenuEleves())->toBe('centre')
        ->and($centre->estLibreService())->toBeTrue();
});

test('une echeance saisie au jour pres vaut pour toute la journee', function () {
    // L'enseignant choisit une date : elle arrive a minuit pile. Le devoir etait
    // dit « en retard depuis aujourd'hui » des le matin du jour de remise.
    $this->travelTo(\Carbon\Carbon::parse('2026-11-10 09:00'));
    $e = centreAvecClasse();
    $eleve = eleveSansObjectif($e);
    $devoir = devoir($e, 'Pour ce soir', \Carbon\Carbon::parse('2026-11-10'));

    expect($devoir->fresh()->isOverdue())->toBeFalse();
    $this->actingAs($eleve)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('nextAction.devoir.echeance', 'pour aujourd’hui')
            ->where('nextAction.devoir.en_retard', false));

    // Le lendemain, il est bien en retard.
    $this->travelTo(\Carbon\Carbon::parse('2026-11-11 08:00'));
    expect($devoir->fresh()->isOverdue())->toBeTrue();
    $this->actingAs($eleve)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('nextAction.devoir.echeance', 'en retard d’un jour')
            ->where('nextAction.devoir.en_retard', true));

    // Une heure precise reste precise.
    expect(\App\Models\Assignment::depassee(\Carbon\Carbon::parse('2026-11-11 07:00')))->toBeTrue();
});

test('un devoir rendu n est plus dit en retard', function () {
    $e = centreAvecClasse();
    $eleve = eleveSansObjectif($e);
    $fini = devoir($e, 'Rendu après coup', now()->subDays(4), 1);
    faireExercice($eleve, $fini->items->first()->itemable_id);

    $this->actingAs($eleve)->get(route('devoirs.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('termines.0.title', 'Rendu après coup')
            ->where('termines.0.overdue', false)
            ->where('termines.0.echeance', fn ($t) => str_starts_with($t, 'à rendre le ') && ! str_contains($t, 'retard')));
});

test('sans aucun devoir recu, l eleve n est pas felicite d avoir tout fait', function () {
    // Mode libre, classe sans devoir.
    $e = centreAvecClasse();
    $eleve = eleveSansObjectif($e);
    $this->actingAs($eleve)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('nextAction.kind', 'entrainement')
            ->where('nextAction.description', fn ($t) => str_starts_with($t, 'Ton enseignant ne t’a pas encore donné de devoir')));
    $this->actingAs($eleve)->get(route('devoirs.index'))
        ->assertInertia(fn (Assert $page) => $page->where('sansClasse', false)->has('termines', 0));

    // Mode centre, classe sans devoir ni parcours.
    $e['centre']->update(['settings' => [LanguageCenter::CONTENU_ELEVES => LanguageCenter::CONTENU_CENTRE]]);
    $c = $e;
    $this->actingAs($eleve)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('nextAction.kind', 'devoirs_finis')
            ->where('nextAction.title', 'Pas encore de devoir.'));

    // Inscrit au centre mais dans aucune classe : il lui faut son code.
    $seul = User::factory()->create();
    UserProfile::factory()->for($seul)->create(['onboarding_completed_at' => null]);
    $c['centre']->members()->attach($seul->id, ['role' => 'student', 'joined_at' => now()]);
    $this->actingAs($seul)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('nextAction.kind', 'sans_classe')
            ->where('nextAction.url', route('center.join')));
    $this->actingAs($seul)->get(route('devoirs.index'))
        ->assertInertia(fn (Assert $page) => $page->where('sansClasse', true));
});

test('en mode centre, un exercice hors devoir ne peut pas non plus etre envoye', function () {
    $e = centreAvecClasse([LanguageCenter::CONTENU_ELEVES => LanguageCenter::CONTENU_CENTRE]);
    $eleve = eleveSansObjectif($e);
    $horsDevoir = Exercise::create([
        'exam_id' => $e['examen']->id, 'exercise_type_id' => $e['type']->id, 'difficulty' => 'B1', 'content' => [], 'questions' => [],
    ]);

    $this->actingAs($eleve)->post(route('exercise.submit', $horsDevoir->id), ['answers' => []])
        ->assertRedirect(route('devoirs.index'));
    $this->actingAs($eleve)->postJson(route('exercise.submit', $horsDevoir->id), ['answers' => []])
        ->assertForbidden();
    expect(UserExerciseAttempt::where('user_id', $eleve->id)->count())->toBe(0);
});

test('la liste des exercices d un devoir donne de quoi nommer le type en francais', function () {
    $e = centreAvecClasse();
    $eleve = eleveSansObjectif($e);
    $devoir = devoir($e, 'Les prépositions', now()->addDays(3), 1);

    $this->actingAs($eleve)->get(route('devoirs.show', $devoir->id))
        ->assertInertia(fn (Assert $page) => $page
            ->where('exercices.0.type_slug', 'mcq')
            ->where('exercices.0.component_key', 'mcq'));
});
