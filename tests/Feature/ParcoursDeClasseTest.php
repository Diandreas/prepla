<?php

use App\Models\Classroom;
use App\Models\ClassroomPathStep;
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
 * « Le centre peut toujours decider de laisser le parcours par defaut ou de
 * creer le parcours lui-meme. » En « Parcours créé par le centre », chaque
 * classe a son parcours (classroom_path_steps) ; en « Parcours PrePla par
 * défaut », /practice suit l'examen et le niveau de la classe.
 */
function pdcCentre(?string $contenu = null): array
{
    $langue = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $examen = Exam::create(['language_id' => $langue->id, 'slug' => 'goethe-b1', 'name' => 'Goethe B1']);
    $autreExamen = Exam::create(['language_id' => $langue->id, 'slug' => 'telc-b1', 'name' => 'telc B1']);
    $section = ExamSection::create(['exam_id' => $examen->id, 'slug' => 'listening', 'name' => 'Hören', 'skill_type' => 'listening']);
    $type = ExerciseType::create([
        'section_id' => $section->id, 'slug' => 'mcq', 'name' => 'QCM', 'skill_type' => 'listening', 'component_key' => 'mcq',
    ]);

    $centre = LanguageCenter::create([
        'name' => 'Ecole Goethe', 'slug' => 'ecole-'.uniqid(), 'owner_email' => 'direction@exemple.test',
        'is_active' => true, 'seats_limit' => 50,
        'settings' => $contenu ? [LanguageCenter::CONTENU_ELEVES => $contenu] : null,
    ]);

    $membre = function (LanguageCenter $c, string $role) {
        $user = User::factory()->create();
        UserProfile::factory()->for($user)->create(['onboarding_completed_at' => now()]);
        $c->members()->attach($user->id, ['role' => $role, 'joined_at' => now()]);

        return $user;
    };
    $responsable = $membre($centre, 'center_admin');
    $prof = $membre($centre, 'teacher');
    $autreProf = $membre($centre, 'teacher');

    $classe = $centre->classrooms()->create([
        'name' => 'Allemand B1 soir', 'level' => 'B1', 'exam_id' => $examen->id, 'invite_code' => Classroom::generateInviteCode(),
    ]);
    $classe->members()->attach($prof->id, ['role_in_class' => 'teacher']);
    $autreClasse = $centre->classrooms()->create([
        'name' => 'Allemand A2 matin', 'level' => 'A2', 'exam_id' => $examen->id, 'invite_code' => Classroom::generateInviteCode(),
    ]);
    $autreClasse->members()->attach($autreProf->id, ['role_in_class' => 'teacher']);

    $eleve = $membre($centre, 'student');
    $classe->members()->attach($eleve->id, ['role_in_class' => 'student']);
    $eleveAutreClasse = $membre($centre, 'student');
    $autreClasse->members()->attach($eleveAutreClasse->id, ['role_in_class' => 'student']);

    $autreCentre = LanguageCenter::create([
        'name' => 'Concurrent', 'slug' => 'autre-'.uniqid(), 'owner_email' => 'autre@exemple.test', 'is_active' => true, 'seats_limit' => 50,
    ]);
    $responsableAutreCentre = $membre($autreCentre, 'center_admin');

    $exercice = fn (array $attributs = []) => Exercise::create($attributs + [
        'exam_id' => $examen->id, 'exercise_type_id' => $type->id, 'difficulty' => 'B1', 'content' => [], 'questions' => [],
    ]);

    return compact('centre', 'responsable', 'prof', 'autreProf', 'classe', 'autreClasse', 'eleve', 'eleveAutreClasse',
        'autreCentre', 'responsableAutreCentre', 'examen', 'autreExamen', 'type', 'exercice');
}

function pdcEtape(Classroom $classe, Exercise $exercice, int $position, ?int $seuil = null, ?string $titre = null): ClassroomPathStep
{
    return $classe->pathSteps()->create([
        'position' => $position, 'exercise_id' => $exercice->id, 'seuil_reussite' => $seuil, 'titre' => $titre,
    ]);
}

function pdcTentative(User $eleve, Exercise $exercice, float $reussite = 80): void
{
    UserExerciseAttempt::create([
        'user_id' => $eleve->id, 'exercise_id' => $exercice->id, 'answers' => [],
        'score' => 1, 'accuracy_percent' => $reussite, 'time_spent' => 30, 'xp_earned' => 5, 'feedback' => [],
    ]);
}

test('le responsable construit le parcours : ajout vivier et centre, ordre, deplacement, retrait', function () {
    $e = pdcCentre(LanguageCenter::CONTENU_CENTRE);
    $vivier = ($e['exercice'])(['content' => ['title' => 'Au marché']]);
    $duCentre = ($e['exercice'])(['center_id' => $e['centre']->id, 'content' => ['title' => 'Notre dictée']]);

    $this->actingAs($e['responsable'])->post(route('center.classes.parcours.store', $e['classe']), ['exercise_id' => $vivier->id])
        ->assertRedirect()->assertSessionHasNoErrors();
    $this->actingAs($e['responsable'])->post(route('center.classes.parcours.store', $e['classe']), [
        'exercise_id' => $duCentre->id, 'titre' => 'La dictée du lundi', 'consigne' => 'Écoute deux fois.', 'seuil_reussite' => 70,
    ])->assertSessionHasNoErrors();

    $etapes = $e['classe']->pathSteps()->get();
    expect($etapes->pluck('exercise_id')->all())->toBe([$vivier->id, $duCentre->id])
        ->and($etapes->pluck('position')->all())->toBe([1, 2])
        ->and($etapes[1]->seuil_reussite)->toBe(70);

    // La page montre les etapes dans l'ordre, et la banque de l'examen de la classe.
    $this->actingAs($e['responsable'])->get(route('center.classes.parcours', $e['classe']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('center/classes/parcours')
            ->where('etapes.0.titre', 'Au marché')
            ->where('etapes.0.source', 'prepla')
            ->where('etapes.0.competence', 'Compréhension orale')
            ->where('etapes.1.titre', 'La dictée du lundi')
            ->where('etapes.1.source', 'centre')
            ->where('banque.0.id', $vivier->id)
            ->where('banque.0.deja_pris', true)
            ->where('exercicesCentre.0.id', $duCentre->id)
            ->where('avancement.0.faites', 0)
            ->where('avancement.0.total', 2));

    // Descendre la premiere : l'ordre s'inverse.
    $this->actingAs($e['responsable'])->post(route('center.classes.parcours.deplacer', [$e['classe'], $etapes[0]]), ['sens' => 'bas']);
    expect($e['classe']->pathSteps()->pluck('exercise_id')->all())->toBe([$duCentre->id, $vivier->id]);

    // Retirer : les positions se resserrent.
    $this->actingAs($e['responsable'])->delete(route('center.classes.parcours.destroy', [$e['classe'], $etapes[1]]));
    expect($e['classe']->pathSteps()->get()->map(fn ($s) => [$s->exercise_id, $s->position])->all())->toBe([[$vivier->id, 1]]);
});

test('jamais l exercice prive d un autre centre, ni le vivier d un autre examen', function () {
    $e = pdcCentre(LanguageCenter::CONTENU_CENTRE);
    $prive = ($e['exercice'])(['center_id' => $e['autreCentre']->id]);
    $autreExamen = ($e['exercice'])(['exam_id' => $e['autreExamen']->id]);
    $duVivier = ($e['exercice'])();

    foreach ([$prive, $autreExamen] as $refuse) {
        $this->actingAs($e['responsable'])->post(route('center.classes.parcours.store', $e['classe']), ['exercise_id' => $refuse->id])
            ->assertSessionHasErrors('exercise_id');
    }
    expect($e['classe']->pathSteps()->count())->toBe(0);

    // Le vivier de l'examen de la classe, lui, passe.
    $this->actingAs($e['responsable'])->post(route('center.classes.parcours.store', $e['classe']), ['exercise_id' => $duVivier->id])
        ->assertSessionHasNoErrors();
    expect($e['classe']->pathSteps()->count())->toBe(1);
});

test('l enseignant gere le parcours de ses classes seulement ; jamais un autre centre', function () {
    $e = pdcCentre(LanguageCenter::CONTENU_CENTRE);
    $vivier = ($e['exercice'])();

    $this->actingAs($e['prof'])->post(route('center.classes.parcours.store', $e['classe']), ['exercise_id' => $vivier->id])
        ->assertSessionHasNoErrors();
    $this->actingAs($e['prof'])->get(route('center.classes.parcours', $e['classe']))->assertOk();

    // La classe d'un collegue : refus.
    $this->actingAs($e['prof'])->get(route('center.classes.parcours', $e['autreClasse']))->assertForbidden();
    $this->actingAs($e['prof'])->post(route('center.classes.parcours.store', $e['autreClasse']), ['exercise_id' => $vivier->id])
        ->assertForbidden();
    $etape = $e['classe']->pathSteps()->first();
    $this->actingAs($e['autreProf'])->delete(route('center.classes.parcours.destroy', [$e['classe'], $etape]))->assertForbidden();

    // Le responsable d'un autre centre : refus.
    $this->actingAs($e['responsableAutreCentre'])->get(route('center.classes.parcours', $e['classe']))->assertForbidden();
    expect($e['classe']->pathSteps()->count())->toBe(1);

    // Une etape d'une autre classe ne se modifie pas par l'adresse de celle-ci.
    $ailleurs = pdcEtape($e['autreClasse'], $vivier, 1);
    $this->actingAs($e['prof'])->delete(route('center.classes.parcours.destroy', [$e['classe'], $ailleurs]))->assertNotFound();
});

test('copier le parcours d une autre classe, a la suite', function () {
    $e = pdcCentre(LanguageCenter::CONTENU_CENTRE);
    $un = ($e['exercice'])();
    $deux = ($e['exercice'])(['center_id' => $e['centre']->id]);
    pdcEtape($e['autreClasse'], $un, 1, 60, 'Échauffement');
    pdcEtape($e['autreClasse'], $deux, 2);
    $deja = ($e['exercice'])();
    pdcEtape($e['classe'], $deja, 1);

    $this->actingAs($e['responsable'])->post(route('center.classes.parcours.copier', $e['classe']), ['source_id' => $e['autreClasse']->id])
        ->assertSessionHas('success', '2 étapes copiées depuis « Allemand A2 matin ».');

    $etapes = $e['classe']->pathSteps()->get();
    expect($etapes->pluck('exercise_id')->all())->toBe([$deja->id, $un->id, $deux->id])
        ->and($etapes->pluck('position')->all())->toBe([1, 2, 3])
        ->and($etapes[1]->titre)->toBe('Échauffement')
        ->and($etapes[1]->seuil_reussite)->toBe(60);

    // L'enseignant ne copie pas depuis une classe qui n'est pas la sienne.
    $this->actingAs($e['prof'])->post(route('center.classes.parcours.copier', $e['classe']), ['source_id' => $e['autreClasse']->id])
        ->assertForbidden();
});

test('mission en parcours du centre : devoir en retard, puis prochain devoir, puis etape, puis fin', function () {
    $e = pdcCentre(LanguageCenter::CONTENU_CENTRE);
    $eleve = $e['eleve'];

    // Sans parcours ni devoir : un message clair.
    $this->actingAs($eleve)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('nextAction.kind', 'devoirs_finis')
            ->where('nextAction.description', fn ($t) => str_contains($t, 'pas encore préparé le parcours')));
    $this->actingAs($eleve)->get(route('parcours.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('parcours/index')->where('parcours.total', 0));

    $ecoute = ($e['exercice'])(['content' => ['title' => 'Au marché']]);
    $dictee = ($e['exercice'])(['center_id' => $e['centre']->id, 'content' => ['title' => 'Dictée']]);
    pdcEtape($e['classe'], $ecoute, 1);
    pdcEtape($e['classe'], $dictee, 2, 70);

    $devoir = function (string $titre, $echeance) use ($e) {
        $d = $e['classe']->assignments()->create([
            'created_by' => $e['prof']->id, 'title' => $titre, 'due_at' => $echeance, 'published_at' => now()->subDay(),
        ]);
        $ex = ($e['exercice'])(['center_id' => $e['centre']->id]);
        $d->items()->create(['itemable_type' => Exercise::class, 'itemable_id' => $ex->id, 'sort_order' => 0]);

        return $ex;
    };
    $plusTard = $devoir('Pour la semaine prochaine', now()->addWeek());
    $enRetard = $devoir('En retard', now()->subDays(2));

    $mission = fn () => $this->actingAs($eleve)->get(route('dashboard'));

    $mission()->assertInertia(fn (Assert $page) => $page->where('nextAction.kind', 'devoir')->where('nextAction.title', 'En retard'));
    pdcTentative($eleve, $enRetard);
    $mission()->assertInertia(fn (Assert $page) => $page->where('nextAction.kind', 'devoir')->where('nextAction.title', 'Pour la semaine prochaine'));
    pdcTentative($eleve, $plusTard);

    $mission()->assertInertia(fn (Assert $page) => $page
        ->where('nextAction.kind', 'etape')
        ->where('nextAction.title', 'Au marché')
        ->where('nextAction.url', route('exercise.show', $ecoute->id))
        ->where('nextAction.etape.libelle', 'Étape 1 sur 2 · Compréhension orale'));

    pdcTentative($eleve, $ecoute, 40);
    // Sous le seuil de 70 %, la dictee n'est pas faite.
    pdcTentative($eleve, $dictee, 50);
    $mission()->assertInertia(fn (Assert $page) => $page->where('nextAction.kind', 'etape')->where('nextAction.etape.libelle', 'Étape 2 sur 2 · Compréhension orale'));

    $this->actingAs($eleve)->get(route('parcours.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('classe', 'Allemand B1 soir')
            ->where('parcours.faites', 1)
            ->where('parcours.etapes.0.statut', 'fait')
            ->where('parcours.etapes.1.statut', 'en_cours'));

    pdcTentative($eleve, $dictee, 85);
    $mission()->assertInertia(fn (Assert $page) => $page
        ->where('nextAction.kind', 'parcours_fini')
        ->where('nextAction.title', 'Tu as terminé ton parcours.'));
});

test('une etape du vivier ne s ouvre que pour les eleves de la classe', function () {
    $e = pdcCentre(LanguageCenter::CONTENU_CENTRE);
    $vivier = ($e['exercice'])();
    pdcEtape($e['classe'], $vivier, 1);

    $ouvert = $this->actingAs($e['eleve'])->get(route('exercise.show', $vivier->id));
    expect($ouvert->headers->get('Location'))->not->toBe(route('devoirs.index'));

    $this->actingAs($e['eleveAutreClasse'])->get(route('exercise.show', $vivier->id))
        ->assertRedirect(route('devoirs.index'));
});

test('une etape d un exercice du centre est accessible a l eleve de la classe, pas aux autres', function () {
    $e = pdcCentre();
    $duCentre = ($e['exercice'])(['center_id' => $e['centre']->id]);
    pdcEtape($e['classe'], $duCentre, 1);

    expect($this->actingAs($e['eleve'])->get(route('exercise.show', $duCentre->id))->status())->not->toBe(403);
    $this->actingAs($e['eleveAutreClasse'])->get(route('exercise.show', $duCentre->id))->assertForbidden();
});

test('en parcours du centre, /practice mene a « Mon parcours »', function () {
    $e = pdcCentre(LanguageCenter::CONTENU_CENTRE);

    $this->actingAs($e['eleve'])->get(route('practice.index'))->assertRedirect(route('parcours.index'));
    $this->actingAs($e['eleve'])->get(route('practice.exam', $e['examen']->id))->assertRedirect(route('parcours.index'));
});

test('parcours PrePla par defaut : /practice prend l examen et le niveau de la classe, et suit un changement', function () {
    $e = pdcCentre();
    $eleve = $e['eleve'];
    // Son profil dit autre chose : la classe l'emporte.
    $eleve->profile->update(['target_exam_id' => $e['autreExamen']->id, 'current_level' => 'A2']);

    $this->actingAs($eleve)->get(route('practice.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('practice/exam-dashboard')
            ->where('exam.id', $e['examen']->id)
            ->where('learnerLevel', 'B1'));

    // L'enseignant passe la classe au B2 : la page suit.
    $e['classe']->update(['level' => 'B2']);
    $this->actingAs($eleve)->get(route('practice.index'))
        ->assertInertia(fn (Assert $page) => $page->where('learnerLevel', 'B2'));

    // Une classe sans examen : celui du centre par defaut.
    $e['centre']->update(['default_exam_id' => $e['autreExamen']->id]);
    $e['classe']->update(['exam_id' => null]);
    $this->actingAs($eleve)->get(route('practice.index'))
        ->assertInertia(fn (Assert $page) => $page->where('exam.id', $e['autreExamen']->id));

    // Seuls les examens retenus par le centre : l'autre renvoie vers celui de la classe.
    $e['classe']->update(['exam_id' => $e['examen']->id]);
    $e['centre']->examensProposes()->sync([$e['examen']->id]);
    $this->actingAs($eleve)->get(route('practice.exam', $e['autreExamen']->id))
        ->assertRedirect(route('practice.exam', $e['examen']->id));

    // Le profil n'est pas touche.
    expect($eleve->profile->fresh())
        ->target_exam_id->toBe($e['autreExamen']->id)
        ->current_level->toBe('A2');

    // Le parcours du centre n'est pas montre en mode par defaut.
    pdcEtape($e['classe'], ($e['exercice'])(), 1);
    $this->actingAs($eleve)->get(route('parcours.index'))->assertRedirect(route('dashboard'));
    $this->actingAs($eleve)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('nextAction.kind', fn ($k) => ! in_array($k, ['etape', 'parcours_fini'], true)));
});

test('l apprenant libre garde son examen et son niveau sur /practice', function () {
    $e = pdcCentre();
    $libre = User::factory()->create();
    UserProfile::factory()->for($libre)->create([
        'target_exam_id' => $e['autreExamen']->id, 'current_level' => 'A2', 'onboarding_completed_at' => now(),
    ]);

    $this->actingAs($libre)->get(route('practice.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('practice/exam-dashboard')
            ->where('exam.id', $e['autreExamen']->id)
            ->where('learnerLevel', 'A2'));
    $this->actingAs($libre)->get(route('parcours.index'))->assertRedirect(route('dashboard'));
});

test('parcours PrePla par defaut : la mission d accueil prend l examen et le niveau de la classe, pas du profil', function () {
    $e = pdcCentre();
    $eleve = $e['eleve'];
    // Profil rempli avant d'entrer dans le centre : un autre examen, un autre niveau.
    $eleve->profile->update(['target_exam_id' => $e['autreExamen']->id, 'current_level' => 'A2']);

    $this->actingAs($eleve)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('nextAction.kind', 'entrainement')
            ->where('nextAction.title', 'M’entraîner pour le Goethe B1')
            ->where('nextAction.url', route('practice.exam', $e['examen']->id))
            ->where('nextAction.description', fn ($d) => str_contains($d, 'niveau B1')));

    // L'enseignant change le niveau et l'examen de la classe : la mission suit.
    $e['classe']->update(['level' => 'B2', 'exam_id' => $e['autreExamen']->id]);
    $this->actingAs($eleve)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('nextAction.url', route('practice.exam', $e['autreExamen']->id))
            ->where('nextAction.description', fn ($d) => str_contains($d, 'niveau B2')));
});

test('un exercice ne peut etre qu une fois dans le parcours, meme par copie', function () {
    $e = pdcCentre(LanguageCenter::CONTENU_CENTRE);
    $commun = ($e['exercice'])();
    $propre = ($e['exercice'])();
    pdcEtape($e['classe'], $commun, 1);
    pdcEtape($e['autreClasse'], $commun, 1);
    pdcEtape($e['autreClasse'], $propre, 2);

    $this->actingAs($e['responsable'])->post(route('center.classes.parcours.store', $e['classe']), ['exercise_id' => $commun->id])
        ->assertSessionHasErrors('exercise_id');

    // Copier deux fois : l'etape commune et la deuxieme copie ne doublent rien.
    $this->actingAs($e['responsable'])->post(route('center.classes.parcours.copier', $e['classe']), ['source_id' => $e['autreClasse']->id])
        ->assertSessionHas('success', '1 étape copiée depuis « Allemand A2 matin ». 1 déjà dans le parcours.');
    $this->actingAs($e['responsable'])->post(route('center.classes.parcours.copier', $e['classe']), ['source_id' => $e['autreClasse']->id])
        ->assertSessionHas('success', 'Aucune étape copiée. 2 déjà dans le parcours.');

    expect($e['classe']->pathSteps()->pluck('exercise_id')->all())->toBe([$commun->id, $propre->id]);

    // Une seule tentative ne valide qu'une etape.
    pdcTentative($e['eleve'], $commun);
    $parcours = app(\App\Services\Center\ParcoursDeClasse::class)->pourLEleve($e['eleve'], $e['classe']);
    expect($parcours['faites'])->toBe(1)->and($parcours['total'])->toBe(2);

    // Et la base le refuse aussi.
    expect(fn () => pdcEtape($e['classe'], $propre, 3))->toThrow(\Illuminate\Database\QueryException::class);
});

test('l examen de la classe change : l etape de l ancien vivier n est plus servie, et elle est signalee', function () {
    $e = pdcCentre(LanguageCenter::CONTENU_CENTRE);
    $ancien = ($e['exercice'])(['content' => ['title' => 'Ancien examen']]);
    $nouveau = ($e['exercice'])(['exam_id' => $e['autreExamen']->id, 'content' => ['title' => 'Nouvel examen']]);
    pdcEtape($e['classe'], $ancien, 1);
    pdcEtape($e['classe'], $nouveau, 2);

    // Sous l'examen d'origine : seule la premiere est jouable.
    $parcours = app(\App\Services\Center\ParcoursDeClasse::class)->pourLEleve($e['eleve'], $e['classe']);
    expect($parcours['total'])->toBe(1)->and($parcours['etapes'][0]['exercise_id'])->toBe($ancien->id);

    $e['classe']->update(['exam_id' => $e['autreExamen']->id]);
    $classe = $e['classe']->fresh();

    $parcours = app(\App\Services\Center\ParcoursDeClasse::class)->pourLEleve($e['eleve'], $classe);
    expect($parcours['total'])->toBe(1)->and($parcours['actuelle']['exercise_id'])->toBe($nouveau->id);

    // L'ancienne etape ne s'ouvre plus pour l'eleve.
    $this->actingAs($e['eleve'])->get(route('exercise.show', $ancien->id))->assertRedirect(route('devoirs.index'));

    // Cote centre, elle reste, signalee, et l'avancement ne la compte pas.
    $this->actingAs($e['responsable'])->get(route('center.classes.parcours', $classe))
        ->assertInertia(fn (Assert $page) => $page->where('etapes.0.exercise_id', $ancien->id)
            ->where('etapes.0.jouable', false)
            ->where('etapes.1.jouable', true)
            ->where('avancement.0.total', 1));
});

test('un exercice retire n entre pas dans le parcours, meme s il est du centre', function () {
    $e = pdcCentre(LanguageCenter::CONTENU_CENTRE);
    $retire = ($e['exercice'])(['center_id' => $e['centre']->id, 'content' => [Exercise::CLE_RETRAIT => ['raison' => 'test']]]);

    $this->actingAs($e['responsable'])->post(route('center.classes.parcours.store', $e['classe']), ['exercise_id' => $retire->id])
        ->assertSessionHasErrors('exercise_id');
});
