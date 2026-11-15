<?php

use App\Models\CenterMedia;
use App\Models\Classroom;
use App\Models\CourseBlock;
use App\Models\CourseBlockProgress;
use App\Models\CoursePath;
use App\Models\CourseSession;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Models\UserExerciseAttempt;
use App\Models\UserProfile;
use App\Services\AI\WritingCorrectorService;
use App\Services\Center\ParcoursEleveDeCentre;
use App\Services\Cours\HtmlAssaini;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Createur de parcours du centre : parcours -> modules -> seances -> blocs,
 * regles de deroulement, lecteur eleve, suivi enseignant, isolation entre centres.
 */
function cdpMonde(): array
{
    $langue = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $examen = Exam::create(['language_id' => $langue->id, 'slug' => 'goethe-b1', 'name' => 'Goethe B1']);
    $section = ExamSection::create(['exam_id' => $examen->id, 'slug' => 'listening', 'name' => 'Hören', 'skill_type' => 'listening']);
    $type = ExerciseType::create(['section_id' => $section->id, 'slug' => 'mcq', 'name' => 'QCM', 'skill_type' => 'listening', 'component_key' => 'mcq']);

    $nouveauCentre = fn (string $nom) => LanguageCenter::create([
        'name' => $nom, 'slug' => 'c-'.uniqid(), 'owner_email' => 'x@exemple.test', 'is_active' => true, 'seats_limit' => 50,
    ]);
    $centre = $nouveauCentre('Ecole');
    $autreCentre = $nouveauCentre('Concurrent');
    $membre = function (LanguageCenter $c, string $role) {
        $u = User::factory()->create();
        UserProfile::factory()->for($u)->create(['onboarding_completed_at' => now()]);
        $c->members()->attach($u->id, ['role' => $role, 'joined_at' => now()]);

        return $u;
    };
    $responsable = $membre($centre, 'center_admin');
    $prof = $membre($centre, 'teacher');
    $autreProf = $membre($centre, 'teacher');
    $classe = $centre->classrooms()->create(['name' => 'B1 soir', 'level' => 'B1', 'exam_id' => $examen->id, 'invite_code' => Classroom::generateInviteCode()]);
    $classe->members()->attach($prof->id, ['role_in_class' => 'teacher']);
    $autreClasse = $centre->classrooms()->create(['name' => 'A2 matin', 'level' => 'A2', 'exam_id' => $examen->id, 'invite_code' => Classroom::generateInviteCode()]);
    $autreClasse->members()->attach($autreProf->id, ['role_in_class' => 'teacher']);
    $eleve = $membre($centre, 'student');
    $classe->members()->attach($eleve->id, ['role_in_class' => 'student']);
    $eleveAutreClasse = $membre($centre, 'student');
    $autreClasse->members()->attach($eleveAutreClasse->id, ['role_in_class' => 'student']);
    $responsableAutreCentre = $membre($autreCentre, 'center_admin');
    $eleveAutreCentre = $membre($autreCentre, 'student');

    $exercice = fn (array $a = []) => Exercise::create($a + [
        'exam_id' => $examen->id, 'exercise_type_id' => $type->id, 'difficulty' => 'B1', 'content' => [], 'questions' => [],
    ]);

    return compact('centre', 'autreCentre', 'responsable', 'prof', 'autreProf', 'classe', 'autreClasse', 'eleve',
        'eleveAutreClasse', 'responsableAutreCentre', 'eleveAutreCentre', 'exercice', 'examen');
}

/** Un parcours publie : un module, des seances publiees ; renvoie [parcours, seances]. */
function cdpParcours(array $m, array $seances, string $statut = 'publie'): array
{
    $p = CoursePath::create(['center_id' => $m['centre']->id, 'classroom_id' => $m['classe']->id, 'titre' => 'Allemand B1', 'statut' => $statut]);
    $module = $p->modules()->create(['position' => 1, 'titre' => 'Module 1']);
    $faites = [];
    foreach ($seances as $i => $s) {
        $blocs = $s['blocs'] ?? [];
        unset($s['blocs']);
        $seance = $module->sessions()->create($s + ['position' => $i + 1, 'titre' => 'Séance '.($i + 1), 'statut' => 'publie']);
        foreach ($blocs as $j => $b) {
            $seance->blocks()->create($b + ['position' => $j + 1, 'obligatoire' => true]);
        }
        $faites[] = $seance;
    }

    return [$p, $faites];
}

// ── Editeur centre ──────────────────────────────────────────────────────

it('cree un parcours avec un module et une seance, puis ajoute chaque type de bloc', function () {
    $m = cdpMonde();
    $this->actingAs($m['prof'])->post('/center/cours', ['titre' => 'Mon parcours', 'classroom_id' => $m['classe']->id])
        ->assertRedirect();
    $p = CoursePath::firstWhere('titre', 'Mon parcours');
    expect($p->statut)->toBe('brouillon')->and($p->classroom_id)->toBe($m['classe']->id);
    $seance = $p->sessions()->first();

    foreach (['texte', 'video', 'audio', 'document', 'exercice', 'quiz', 'ecrit', 'oral', 'vocabulaire', 'lien', 'consigne', 'live'] as $type) {
        $this->actingAs($m['prof'])->postJson("/center/cours/seances/{$seance->id}/blocs", ['type' => $type])->assertOk();
    }
    expect($seance->blocks()->count())->toBe(12);

    $this->actingAs($m['prof'])->get("/center/cours/{$p->id}")
        ->assertInertia(fn (Assert $page) => $page->component('center/cours/editeur')->has('parcours.modules.0.seances.0.blocs', 12));
});

it('assainit le texte riche : pas de script, pas d’evenement, pas de javascript:', function () {
    $sale = '<h2 onclick="x()">Titre</h2><p><strong>gras</strong> <a href="javascript:alert(1)">x</a> <a href="https://ok.test">ok</a></p>'
        .'<script>alert(1)</script><img src="x" onerror="alert(1)"><img src="https://img.test/a.png" alt="a"><iframe src="https://evil"></iframe><custom>garde</custom>';
    $propre = HtmlAssaini::nettoyer($sale);

    expect($propre)->not->toContain('script')->not->toContain('onclick')->not->toContain('onerror')
        ->not->toContain('javascript')->not->toContain('iframe')->not->toContain('<custom')
        ->toContain('<h2>Titre</h2>')->toContain('<strong>gras</strong>')
        ->toContain('href="https://ok.test"')->toContain('rel="noopener noreferrer nofollow"')
        ->toContain('src="https://img.test/a.png"')->toContain('garde');

    $m = cdpMonde();
    [, [$s]] = cdpParcours($m, [['blocs' => [['type' => 'texte', 'contenu' => ['html' => '']]]]]);
    $bloc = $s->blocks()->first();
    $this->actingAs($m['prof'])->patchJson("/center/cours/blocs/{$bloc->id}", ['contenu' => ['html' => $sale]])->assertOk();
    expect($bloc->fresh()->contenu['html'])->not->toContain('script')->not->toContain('onerror');
});

it('refuse un exercice brouillon ou d’un autre centre, accepte la banque PrePla et un exercice publie du centre', function () {
    $m = cdpMonde();
    [, [$s]] = cdpParcours($m, [['blocs' => [['type' => 'exercice', 'contenu' => ['exercise_id' => null]]]]]);
    $bloc = $s->blocks()->first();
    $brouillon = $m['exercice'](['center_id' => $m['centre']->id, 'center_status' => Exercise::STATUT_BROUILLON]);
    $etranger = $m['exercice'](['center_id' => $m['autreCentre']->id]);
    $publie = $m['exercice'](['center_id' => $m['centre']->id]);
    $banque = $m['exercice']();

    foreach ([$brouillon, $etranger] as $e) {
        $this->actingAs($m['prof'])->patchJson("/center/cours/blocs/{$bloc->id}", ['contenu' => ['exercise_id' => $e->id]])->assertStatus(422);
    }
    foreach ([$publie, $banque] as $e) {
        $this->actingAs($m['prof'])->patchJson("/center/cours/blocs/{$bloc->id}", ['contenu' => ['exercise_id' => $e->id, 'seuil' => 70]])->assertOk();
        expect($bloc->fresh()->contenu['exercise_id'])->toBe($e->id);
    }
});

it('valide la video (YouTube, Vimeo, fichier du centre) et refuse le media d’un autre centre', function () {
    $m = cdpMonde();
    [, [$s]] = cdpParcours($m, [['blocs' => [['type' => 'video', 'contenu' => []]]]]);
    $bloc = $s->blocks()->first();

    $this->actingAs($m['prof'])->patchJson("/center/cours/blocs/{$bloc->id}", ['contenu' => ['source' => 'youtube', 'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'seuil_visionnage' => 80]])->assertOk();
    expect($bloc->fresh()->contenu)->toMatchArray(['video_id' => 'dQw4w9WgXcQ', 'seuil_visionnage' => 80]);
    $this->actingAs($m['prof'])->patchJson("/center/cours/blocs/{$bloc->id}", ['contenu' => ['source' => 'vimeo', 'url' => 'https://vimeo.com/123456789']])->assertOk();
    expect($bloc->fresh()->contenu['video_id'])->toBe('123456789');
    $this->actingAs($m['prof'])->patchJson("/center/cours/blocs/{$bloc->id}", ['contenu' => ['source' => 'youtube', 'url' => 'https://evil.test/x']])->assertStatus(422);

    $etranger = CenterMedia::create(['center_id' => $m['autreCentre']->id, 'type' => 'video', 'path' => 'x.mp4']);
    $this->actingAs($m['prof'])->patchJson("/center/cours/blocs/{$bloc->id}", ['contenu' => ['source' => 'media', 'media_id' => $etranger->id]])->assertStatus(422);

    Storage::fake('public');
    $fiche = $this->actingAs($m['prof'])->post("/center/cours/{$s->module->course_path_id}/media", [
        'type' => 'video', 'fichier' => UploadedFile::fake()->create('cours.mp4', 500, 'video/mp4'), 'droits_certifies' => '1',
    ])->assertOk()->json();
    $this->actingAs($m['prof'])->patchJson("/center/cours/blocs/{$bloc->id}", ['contenu' => ['source' => 'media', 'media_id' => $fiche['id']]])->assertOk();
});

it('reordonne modules, seances et blocs, y compris d’une seance a l’autre, et refuse un identifiant etranger', function () {
    $m = cdpMonde();
    [$p, [$s1, $s2]] = cdpParcours($m, [
        ['blocs' => [['type' => 'texte', 'contenu' => ['html' => 'a']], ['type' => 'texte', 'contenu' => ['html' => 'b']]]],
        ['blocs' => [['type' => 'consigne', 'contenu' => ['texte' => 'c']]]],
    ]);
    $module = $p->modules()->first();
    $m2 = $p->modules()->create(['position' => 2, 'titre' => 'Module 2']);
    [$a, $b] = $s1->blocks()->get();
    $c = $s2->blocks()->first();

    $this->actingAs($m['prof'])->postJson("/center/cours/{$p->id}/ordre", ['modules' => [
        ['id' => $m2->id, 'seances' => [['id' => $s2->id, 'blocs' => [$c->id, $b->id]]]],
        ['id' => $module->id, 'seances' => [['id' => $s1->id, 'blocs' => [$a->id]]]],
    ]])->assertOk();

    expect($m2->fresh()->position)->toBe(1)
        ->and($s2->fresh()->course_module_id)->toBe($m2->id)
        ->and($b->fresh()->course_session_id)->toBe($s2->id)
        ->and($b->fresh()->position)->toBe(2);

    // Un bloc d'un autre parcours glisse dans la liste : refuse.
    [, [$autre]] = cdpParcours($m, [['blocs' => [['type' => 'texte', 'contenu' => []]]]]);
    $this->actingAs($m['prof'])->postJson("/center/cours/{$p->id}/ordre", ['modules' => [
        ['id' => $m2->id, 'seances' => [['id' => $s2->id, 'blocs' => [$c->id, $b->id, $autre->blocks()->first()->id]]]],
        ['id' => $module->id, 'seances' => [['id' => $s1->id, 'blocs' => [$a->id]]]],
    ]])->assertStatus(422);
});

it('duplique une seance (en brouillon) et un parcours vers une autre classe', function () {
    $m = cdpMonde();
    [$p, [$s]] = cdpParcours($m, [['blocs' => [['type' => 'texte', 'contenu' => ['html' => '<p>x</p>']], ['type' => 'quiz', 'contenu' => ['questions' => [], 'seuil' => 50]]]]]);

    $this->actingAs($m['responsable'])->postJson("/center/cours/seances/{$s->id}/dupliquer")->assertOk();
    $copie = CourseSession::where('titre', 'Séance 1 (copie)')->first();
    expect($copie->statut)->toBe('brouillon')->and($copie->blocks()->count())->toBe(2)->and($copie->position)->toBe(2);

    $this->actingAs($m['responsable'])->post("/center/cours/{$p->id}/dupliquer", ['classroom_id' => $m['autreClasse']->id])->assertRedirect();
    $double = CoursePath::where('classroom_id', $m['autreClasse']->id)->first();
    expect($double->statut)->toBe('brouillon')->and($double->sessions()->count())->toBe(2)->and($double->source_path_id)->toBe($p->id);
});

it('isole les centres et les classes : ni autre centre, ni classe d’un autre enseignant', function () {
    $m = cdpMonde();
    [$p, [$s]] = cdpParcours($m, [['blocs' => [['type' => 'texte', 'contenu' => []]]]]);
    $bloc = $s->blocks()->first();

    $this->actingAs($m['responsableAutreCentre'])->get("/center/cours/{$p->id}")->assertNotFound();
    $this->actingAs($m['responsableAutreCentre'])->patchJson("/center/cours/blocs/{$bloc->id}", ['titre' => 'pirate'])->assertNotFound();
    $this->actingAs($m['responsableAutreCentre'])->getJson("/center/cours/{$p->id}/suivi")->assertNotFound();
    $this->actingAs($m['autreProf'])->get("/center/cours/{$p->id}")->assertForbidden();
    $this->actingAs($m['autreProf'])->post("/center/cours/{$p->id}/dupliquer", ['classroom_id' => $m['autreClasse']->id])->assertForbidden();
    $this->actingAs($m['prof'])->post('/center/cours', ['titre' => 'x', 'classroom_id' => $m['autreClasse']->id])->assertForbidden();
    $this->actingAs($m['eleve'])->get("/center/cours/{$p->id}")->assertStatus(403);

    // La liste du centre : rien de l'autre centre.
    $this->actingAs($m['responsableAutreCentre'])->get('/center/cours')
        ->assertInertia(fn (Assert $page) => $page->has('parcours', 0));
    expect($bloc->fresh()->titre)->toBeNull();
});

// ── Cote eleve ──────────────────────────────────────────────────────────

it('ne montre jamais un parcours ou une seance brouillon, ni le parcours d’une autre classe', function () {
    $m = cdpMonde();
    [$brouillon] = cdpParcours($m, [[]], 'brouillon');
    [$p, [$publiee, $cachee]] = cdpParcours($m, [[], ['statut' => 'brouillon']]);

    $this->actingAs($m['eleve'])->get('/mes-cours')->assertInertia(fn (Assert $page) => $page->component('cours/index')
        ->has('parcours', 1)->where('parcours.0.id', $p->id)->where('parcours.0.total', 1));
    $this->actingAs($m['eleve'])->get("/mes-cours/{$brouillon->id}")->assertNotFound();
    $this->actingAs($m['eleve'])->get("/mes-cours/seances/{$cachee->id}")->assertNotFound();
    $this->actingAs($m['eleveAutreClasse'])->get("/mes-cours/{$p->id}")->assertNotFound();
    $this->actingAs($m['eleveAutreCentre'])->get("/mes-cours/seances/{$publiee->id}")->assertNotFound();
});

it('debloque la seance suivante apres completion, puis apres reussite du quiz, puis a une date', function () {
    $m = cdpMonde();
    $quiz = ['questions' => [
        ['type' => 'qcm', 'enonce' => 'Hund ?', 'choix' => ['chat', 'chien'], 'bonne' => 1],
        ['type' => 'vrai_faux', 'enonce' => 'Ich bin = je suis', 'reponse' => true],
        ['type' => 'trous', 'texte' => 'Ich [bin|war] müde'],
    ], 'seuil' => 100];
    [$p, [$s1, $s2, $s3, $s4]] = cdpParcours($m, [
        ['blocs' => [['type' => 'texte', 'contenu' => ['html' => '<p>Bonjour</p>']]]],
        ['deblocage' => 'completion', 'duree_minutes' => 12, 'blocs' => [['type' => 'quiz', 'contenu' => $quiz]]],
        ['deblocage' => 'quiz', 'seuil_quiz' => 80, 'blocs' => [['type' => 'consigne', 'contenu' => ['texte' => 'x']]]],
        ['deblocage' => 'date', 'deblocage_at' => now()->addDay(), 'blocs' => [['type' => 'lien', 'contenu' => ['url' => 'https://a.test']]]],
    ]);
    $eleve = $m['eleve'];

    $this->actingAs($eleve)->get("/mes-cours/{$p->id}")->assertInertia(fn (Assert $page) => $page
        ->where('parcours.prochaine.id', $s1->id)
        ->where('parcours.modules.0.seances.1.ouverte', false)
        ->where('parcours.modules.0.seances.1.raison.code', 'completion'));
    $this->actingAs($eleve)->get("/mes-cours/seances/{$s2->id}")->assertRedirect("/mes-cours/{$p->id}");
    $this->actingAs($eleve)->postJson('/mes-cours/blocs/'.$s2->blocks()->first()->id, ['action' => 'quiz', 'reponses' => [1, true, ['bin']]])->assertForbidden();

    $this->actingAs($eleve)->postJson('/mes-cours/blocs/'.$s1->blocks()->first()->id, ['action' => 'terminer'])->assertOk()->assertJson(['fait' => true]);

    // Le quiz : les bonnes reponses ne partent jamais chez l'eleve.
    $this->actingAs($eleve)->get("/mes-cours/seances/{$s2->id}")->assertInertia(fn (Assert $page) => $page->component('cours/seance')
        ->missing('blocs.0.contenu.questions.0.bonne')->missing('blocs.0.contenu.questions.1.reponse')
        ->where('blocs.0.contenu.questions.2.segments', ['Ich ', null, ' müde']));

    $qb = $s2->blocks()->first();
    $this->actingAs($eleve)->postJson("/mes-cours/blocs/{$qb->id}", ['action' => 'quiz', 'reponses' => [0, true, ['bin']]])
        ->assertOk()->assertJson(['score' => 67, 'reussi' => false, 'fait' => false]);
    $this->actingAs($eleve)->get("/mes-cours/seances/{$s3->id}")->assertRedirect();
    $this->actingAs($eleve)->postJson("/mes-cours/blocs/{$qb->id}", ['action' => 'quiz', 'reponses' => [1, 'true', ['War ']]])
        ->assertOk()->assertJson(['score' => 100, 'reussi' => true, 'fait' => true]);
    $this->actingAs($eleve)->get("/mes-cours/seances/{$s3->id}")->assertOk();

    // A une date : fermee avant, meme les precedentes finies.
    $this->actingAs($eleve)->postJson('/mes-cours/blocs/'.$s3->blocks()->first()->id, ['action' => 'terminer'])->assertOk();
    $this->actingAs($eleve)->get("/mes-cours/{$p->id}")->assertInertia(fn (Assert $page) => $page
        ->where('parcours.modules.0.seances.3.ouverte', false)->where('parcours.modules.0.seances.3.raison.code', 'date')
        ->where('parcours.finies', 3));
    $this->travel(2)->days();
    $this->actingAs($eleve)->get("/mes-cours/seances/{$s4->id}")->assertOk();
});

it('met en avant la prochaine etape avec ses types et sa duree, et respecte la date de debut', function () {
    $m = cdpMonde();
    [$p, [$s1, $s2]] = cdpParcours($m, [
        ['debut_at' => now()->addWeek(), 'blocs' => [['type' => 'texte', 'contenu' => []]]],
        ['duree_minutes' => 12, 'blocs' => [['type' => 'audio', 'contenu' => ['mode_texte' => 'transcription', 'texte_html' => '<p>Text</p>']]]],
    ]);

    $this->actingAs($m['eleve'])->get("/mes-cours/{$p->id}")->assertInertia(fn (Assert $page) => $page
        ->where('parcours.modules.0.seances.0.raison.code', 'debut')
        ->where('parcours.prochaine.id', $s2->id)
        ->where('parcours.prochaine.types', ['audio_texte'])
        ->where('parcours.prochaine.duree_minutes', 12));
});

it('limite les ecoutes d’un audio et exige le seuil de visionnage d’une video du centre', function () {
    $m = cdpMonde();
    $video = CenterMedia::create(['center_id' => $m['centre']->id, 'type' => 'video', 'path' => 'v.mp4']);
    [, [$s]] = cdpParcours($m, [['blocs' => [
        ['type' => 'audio', 'contenu' => ['ecoutes_max' => 2, 'mode_texte' => 'aucun']],
        ['type' => 'video', 'contenu' => ['source' => 'media', 'media_id' => $video->id, 'seuil_visionnage' => 80]],
    ]]]);
    [$audio, $vid] = $s->blocks()->get();

    $this->actingAs($m['eleve'])->postJson("/mes-cours/blocs/{$audio->id}", ['action' => 'ecoute'])->assertOk();
    $this->actingAs($m['eleve'])->postJson("/mes-cours/blocs/{$audio->id}", ['action' => 'ecoute'])->assertOk()->assertJson(['ecoutes' => 2]);
    $this->actingAs($m['eleve'])->postJson("/mes-cours/blocs/{$audio->id}", ['action' => 'ecoute'])->assertStatus(422);

    $this->actingAs($m['eleve'])->postJson("/mes-cours/blocs/{$vid->id}", ['action' => 'terminer'])->assertStatus(422);
    $this->actingAs($m['eleve'])->postJson("/mes-cours/blocs/{$vid->id}", ['action' => 'video', 'pourcentage' => 50])->assertJson(['fait' => false]);
    $this->actingAs($m['eleve'])->postJson("/mes-cours/blocs/{$vid->id}", ['action' => 'video', 'pourcentage' => 85])->assertJson(['fait' => true]);
});

it('impose l’ordre des blocs quand la seance le demande', function () {
    $m = cdpMonde();
    [, [$s]] = cdpParcours($m, [['blocs_ordre_impose' => true, 'blocs' => [
        ['type' => 'texte', 'contenu' => []], ['type' => 'consigne', 'contenu' => ['texte' => 'x']],
    ]]]);
    [$a, $b] = $s->blocks()->get();

    $this->actingAs($m['eleve'])->postJson("/mes-cours/blocs/{$b->id}", ['action' => 'terminer'])->assertForbidden();
    $this->actingAs($m['eleve'])->postJson("/mes-cours/blocs/{$a->id}", ['action' => 'terminer'])->assertOk();
    $this->actingAs($m['eleve'])->postJson("/mes-cours/blocs/{$b->id}", ['action' => 'terminer'])->assertOk();
});

it('envoie les productions ecrite et orale a la correction, que l’enseignant corrige dans le suivi', function () {
    Storage::fake('public');
    $m = cdpMonde();
    app()->instance(WritingCorrectorService::class, Mockery::mock(WritingCorrectorService::class, fn ($x) => $x->shouldReceive('correct')->andReturn(['score' => 14, 'feedback' => 'Bien'])));
    [$p, [$s]] = cdpParcours($m, [['blocs' => [
        ['type' => 'ecrit', 'contenu' => ['consigne' => 'Décrivez', 'mots_min' => 5, 'mots_max' => null]],
        ['type' => 'oral', 'contenu' => ['consigne' => 'Parlez', 'duree_max_secondes' => 60]],
    ]]]);
    [$ecrit, $oral] = $s->blocks()->get();

    $this->actingAs($m['eleve'])->postJson("/mes-cours/blocs/{$ecrit->id}", ['action' => 'ecrit', 'texte' => 'trop court'])->assertStatus(422);
    $this->actingAs($m['eleve'])->postJson("/mes-cours/blocs/{$ecrit->id}", ['action' => 'ecrit', 'texte' => 'Ich wohne in Berlin seit zwei Jahren.'])->assertOk()->assertJson(['fait' => true]);
    $this->actingAs($m['eleve'])->post("/mes-cours/blocs/{$oral->id}", ['action' => 'oral', 'audio' => UploadedFile::fake()->create('rec.webm', 50, 'audio/webm')], ['Accept' => 'application/json'])->assertOk();

    $prod = CourseBlockProgress::where('course_block_id', $ecrit->id)->first();
    expect($prod->correction_statut)->toBe('a_corriger')->and($prod->correction_ia['score'])->toBe(14);

    $this->actingAs($m['prof'])->get("/center/cours/{$p->id}/suivi")->assertOk()->assertInertia(fn (Assert $page) => $page->component('center/cours/suivi')
        ->has('productions', 2)->where('eleves.0.finies', 1)->where('eleves.0.id', $m['eleve']->id));
    $this->actingAs($m['prof'])->patch("/center/cours/productions/{$prod->id}", ['commentaire' => 'Très bien', 'note' => 16])->assertRedirect();
    expect($prod->fresh())->correction_statut->toBe('corrige')->note_enseignant->toBe(16);

    $this->actingAs($m['responsableAutreCentre'])->patch("/center/cours/productions/{$prod->id}", ['note' => 0])->assertNotFound();
});

it('suit les eleves par seance avec les scores de quiz', function () {
    $m = cdpMonde();
    [$p, [$s]] = cdpParcours($m, [['blocs' => [['type' => 'quiz', 'contenu' => ['questions' => [['type' => 'vrai_faux', 'enonce' => 'x', 'reponse' => false]], 'seuil' => 50]]]]]);
    $this->actingAs($m['eleve'])->postJson('/mes-cours/blocs/'.$s->blocks()->first()->id, ['action' => 'quiz', 'reponses' => [false]])->assertOk();

    $this->actingAs($m['prof'])->get("/center/cours/{$p->id}/suivi")->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where("eleves.0.seances.{$s->id}.finie", true)->where("eleves.0.seances.{$s->id}.score", 100));
});

it('reprend les anciens parcours d’etapes dans le nouveau modele sans perdre la progression', function () {
    $m = cdpMonde();
    $e1 = $m['exercice']();
    $e2 = $m['exercice']();
    $m['classe']->pathSteps()->create(['position' => 1, 'exercise_id' => $e1->id, 'titre' => 'Écoute 1', 'consigne' => 'Écoutez bien']);
    $m['classe']->pathSteps()->create(['position' => 2, 'exercise_id' => $e2->id, 'seuil_reussite' => 70]);
    UserExerciseAttempt::create(['user_id' => $m['eleve']->id, 'exercise_id' => $e1->id, 'answers' => [], 'accuracy_percent' => 40]);

    $migration = require database_path('migrations/2026_11_15_200001_create_course_builder_tables.php');
    $migration->down();
    $migration->up();

    $p = CoursePath::where('classroom_id', $m['classe']->id)->firstOrFail();
    expect($p->statut)->toBe('publie')->and($p->sessions()->count())->toBe(2);
    $s1 = $p->sessions()->orderBy('position')->first();
    expect($s1->titre)->toBe('Écoute 1')->and($s1->blocks()->pluck('type')->all())->toBe(['consigne', 'exercice']);
    $blocExo2 = CourseBlock::where('type', 'exercice')->get()->first(fn ($b) => $b->contenu['exercise_id'] === $e2->id);
    expect($blocExo2->contenu['seuil'])->toBe(70);

    // La tentative existante compte : la seance 1 est finie, la 2 ouverte.
    $this->actingAs($m['eleve'])->get("/mes-cours/{$p->id}")->assertInertia(fn (Assert $page) => $page
        ->where('parcours.finies', 1)->where('parcours.modules.0.seances.1.ouverte', true));
});

it('montre l’apercu « comme l’eleve » au centre sans rien enregistrer', function () {
    $m = cdpMonde();
    [, [$s]] = cdpParcours($m, [['statut' => 'brouillon', 'blocs' => [['type' => 'texte', 'contenu' => ['html' => '<p>x</p>']]]]]);

    $this->actingAs($m['prof'])->get("/center/cours/seances/{$s->id}/apercu")
        ->assertInertia(fn (Assert $page) => $page->component('cours/seance')->where('apercu', true)->has('blocs', 1));
    $this->actingAs($m['responsableAutreCentre'])->get("/center/cours/seances/{$s->id}/apercu")->assertNotFound();
    expect(CourseBlockProgress::count())->toBe(0);
});

it('ouvre a l’eleve l’exercice d’un bloc publie de son parcours, jamais celui d’un brouillon', function () {
    $m = cdpMonde();
    $publie = $m['exercice']();
    $cache = $m['exercice']();
    cdpParcours($m, [['blocs' => [['type' => 'exercice', 'contenu' => ['exercise_id' => $publie->id]]]]]);
    cdpParcours($m, [['blocs' => [['type' => 'exercice', 'contenu' => ['exercise_id' => $cache->id]]]]], 'brouillon');
    $service = app(ParcoursEleveDeCentre::class);

    expect($service->exerciceDansSesDevoirs($m['eleve'], $publie->id))->toBeTrue()
        ->and($service->exerciceDansSesDevoirs($m['eleve'], $cache->id))->toBeFalse()
        ->and($service->exerciceDansSesDevoirs($m['eleveAutreClasse'], $publie->id))->toBeFalse();
});
