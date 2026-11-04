<?php

use App\Models\CenterAnalysis;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Models\UserError;
use App\Models\UserExerciseAttempt;
use App\Models\UserProfile;
use App\Services\AI\MistralService;
use App\Services\Center\AnalyseIa;
use Illuminate\Support\Facades\Cache;

/**
 * L'analyse IA d'un eleve, d'une classe ou du centre : le probleme principal et
 * le levier de progression, pour le responsable et les enseignants.
 *
 * Elle ne doit jamais afficher d'erreur (repli par regles), ne pas repayer l'IA
 * a chaque ouverture (cache), ne rien inventer sous 3 exercices, respecter les
 * memes droits que la fiche, et ne rien envoyer de personnel a l'IA.
 */
beforeEach(fn () => $this->withoutVite());

function aiaCentre(): array
{
    $langue = Language::create(['slug' => 'german', 'name' => 'Allemand', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::create(['language_id' => $langue->id, 'slug' => 'goethe', 'name' => 'Goethe-Zertifikat']);

    $exercice = function (string $competence, string $format) use ($exam) {
        $section = ExamSection::create(['exam_id' => $exam->id, 'slug' => $competence, 'name' => $competence, 'skill_type' => $competence]);
        $type = ExerciseType::create([
            'section_id' => $section->id, 'slug' => $format, 'name' => $format, 'skill_type' => $competence, 'component_key' => 'mcq',
        ]);

        return Exercise::create([
            'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'exam_section_id' => $section->id,
            'difficulty' => 'B1', 'is_ai_generated' => false,
            'content' => ['title' => "Goethe B1 · {$competence}"],
            'questions' => [['id' => 'q1', 'type' => 'mcq', 'text' => 'Frage', 'options' => ['A', 'B'], 'correct_answer' => 'A']],
        ]);
    };

    $ecoute = $exercice('listening', 'Hören Teil 1');
    $lecture = $exercice('reading', 'Lesen Teil 2');

    $centre = LanguageCenter::create(['name' => 'Institut Test', 'slug' => 'institut-test', 'owner_email' => 'resp@exemple.test', 'is_active' => true]);
    $autreCentre = LanguageCenter::create(['name' => 'Autre Centre', 'slug' => 'autre-centre', 'owner_email' => 'autre@exemple.test', 'is_active' => true]);

    $membre = function (LanguageCenter $c, string $role, array $attributs = []) {
        $u = User::factory()->create($attributs);
        UserProfile::factory()->for($u)->create(['onboarding_completed_at' => now(), 'current_level' => 'B1']);
        $c->members()->attach($u->id, ['role' => $role, 'joined_at' => now()]);

        return $u;
    };

    $responsable = $membre($centre, 'center_admin');
    $prof = $membre($centre, 'teacher');
    $autreProf = $membre($centre, 'teacher');
    $eleve = $membre($centre, 'student', ['name' => 'Awa Nkoulou', 'email' => 'awa.nkoulou@exemple.test']);
    $autreEleve = $membre($centre, 'student');
    $responsableAilleurs = $membre($autreCentre, 'center_admin');

    $classe = $centre->classrooms()->create(['name' => 'Goethe B1 matin', 'level' => 'B1', 'exam_id' => $exam->id, 'invite_code' => Classroom::generateInviteCode()]);
    $classe->members()->attach($prof->id, ['role_in_class' => 'teacher']);
    $classe->members()->attach($eleve->id, ['role_in_class' => 'student']);

    $autreClasse = $centre->classrooms()->create(['name' => 'Goethe A2', 'level' => 'A2', 'exam_id' => $exam->id, 'invite_code' => Classroom::generateInviteCode()]);
    $autreClasse->members()->attach($autreProf->id, ['role_in_class' => 'teacher']);
    $autreClasse->members()->attach($autreEleve->id, ['role_in_class' => 'student']);

    return compact('centre', 'autreCentre', 'responsable', 'prof', 'autreProf', 'eleve', 'autreEleve', 'responsableAilleurs', 'classe', 'autreClasse', 'ecoute', 'lecture');
}

function aiaTentative(User $eleve, Exercise $exercice, float $precision, int $ilYAJours): void
{
    $t = UserExerciseAttempt::create([
        'user_id' => $eleve->id, 'exercise_id' => $exercice->id, 'answers' => ['q1' => 'A'],
        'score' => 1, 'accuracy_percent' => $precision, 'time_spent' => 120, 'xp_earned' => 5, 'feedback' => [],
    ]);
    $t->forceFill(['created_at' => now()->subDays($ilYAJours), 'updated_at' => now()->subDays($ilYAJours)])->save();
}

/** Un eleve qui reussit la lecture et rate l'ecoute, avec une faute de cas recurrente. */
function aiaEleveActif(array $c): void
{
    foreach ([1, 3, 5] as $jour) {
        aiaTentative($c['eleve'], $c['ecoute'], 30, $jour);
        aiaTentative($c['eleve'], $c['lecture'], 80, $jour);
    }
    foreach (['mit der Mann', 'mit die Frau', 'für dem Kind'] as $faute) {
        UserError::create([
            'user_id' => $c['eleve']->id, 'exercise_id' => $c['lecture']->id, 'question_id' => 'q1',
            'question_text' => 'Frage', 'user_answer' => $faute, 'correct_answer' => 'mit dem Mann',
            'skill_type' => 'writing', 'exercise_type_slug' => 'essay', 'error_category' => 'grammar', 'subcategory' => 'case', 'mastered' => false,
        ]);
    }
}

function aiaReponseValide(): array
{
    return [
        'probleme' => ['titre' => 'La compréhension orale freine Awa', 'explication' => 'Elle perd la moitié des points à l’écoute.', 'preuve' => '30 % en compréhension orale contre 80 % en lecture.'],
        'levier' => ['titre' => 'Deux écoutes guidées par semaine', 'actions' => ['Faire écouter deux fois le document.', 'Faire noter les mots-clés.', 'Corriger avec la transcription.', 'Une quatrième action de trop.']],
        'point_fort' => 'Lecture solide : 80 % de réussite.',
        'risque' => ['niveau' => 'Moyen', 'raison' => 'Écart important entre compétences.'],
        'prochain_devoir' => ['competence' => 'Compréhension orale', 'format' => 'Hören Teil 1', 'pourquoi' => 'Format le moins réussi.'],
    ];
}

/** Une reponse valable pour les trois sujets (eleve, classe et centre), pour la commande. */
function aiaReponseTousSujets(): array
{
    return aiaReponseValide() + [
        'constats' => [['titre' => 'L’écoute est le point faible du centre', 'explication' => '30 % de réussite.']],
        'leviers' => [['titre' => 'Écoutes guidées', 'action' => 'Deux par semaine.']],
    ];
}

/** @param  array<int, mixed>  $capture */
function aiaMistral(mixed $reponse, int $fois = 1, ?array &$capture = null): void
{
    $mistral = Mockery::mock(MistralService::class);
    $mistral->shouldReceive('chat')->times($fois)->andReturnUsing(function (array $messages) use ($reponse, &$capture) {
        $capture = $messages;

        return is_array($reponse) ? json_encode($reponse) : $reponse;
    });
    app()->instance(MistralService::class, $mistral);
}

test('une reponse valide de l IA est relue, bornee et gardee comme analyse IA', function () {
    $c = aiaCentre();
    aiaEleveActif($c);
    aiaMistral(aiaReponseValide());

    $this->actingAs($c['responsable'])
        ->postJson(route('center.analyses.eleve', $c['eleve']))
        ->assertOk()
        ->assertJsonPath('analyse.etat', 'pret')
        ->assertJsonPath('analyse.source', 'ia')
        ->assertJsonPath('analyse.contenu.probleme.titre', 'La compréhension orale freine Awa')
        // Trois actions au plus, et le niveau de risque ramene a la liste attendue.
        ->assertJsonCount(3, 'analyse.contenu.levier.actions')
        ->assertJsonPath('analyse.contenu.risque.niveau', 'moyen');

    expect(CenterAnalysis::where('sujet_type', 'eleve')->where('sujet_id', $c['eleve']->id)->sole()->source)->toBe('ia');
});

test('une reponse entouree de balises de code est quand meme lue', function () {
    $c = aiaCentre();
    aiaEleveActif($c);
    aiaMistral("Voici l'analyse :\n```json\n".json_encode(aiaReponseValide())."\n```");

    $this->actingAs($c['responsable'])
        ->postJson(route('center.analyses.eleve', $c['eleve']))
        ->assertJsonPath('analyse.source', 'ia');
});

test('si l IA echoue, l analyse est faite par regles et la fiche s ouvre sans erreur', function (mixed $reponse) {
    $c = aiaCentre();
    aiaEleveActif($c);
    aiaMistral($reponse);

    $analyse = $this->actingAs($c['responsable'])
        ->postJson(route('center.analyses.eleve', $c['eleve']))
        ->assertOk()
        ->assertJsonPath('analyse.etat', 'pret')
        ->assertJsonPath('analyse.source', 'regles')
        ->json('analyse.contenu');

    // Les regles partent des memes faits : la faute de cas revient 3 fois et
    // l'ecoute est la competence la plus faible.
    expect($analyse['probleme']['titre'])->not->toBe('')
        ->and($analyse['levier']['actions'])->not->toBeEmpty()
        ->and($analyse['risque']['niveau'])->toBeIn(['faible', 'moyen', 'eleve'])
        ->and($analyse['prochain_devoir']['competence'])->toBe('Compréhension orale')
        ->and($analyse['prochain_devoir']['format'])->toBe('Hören Teil 1');

    $this->actingAs($c['responsable'])->get(route('center.students.show', $c['eleve']))->assertOk();
})->with([
    'pas de reponse (429, cle absente)' => [null],
    'pas du JSON' => ['Désolé, je ne peux pas répondre.'],
    'JSON sans le probleme' => [['levier' => ['titre' => 'x', 'actions' => ['y']]]],
]);

test('une exception de l IA ne fait jamais tomber la page', function () {
    $c = aiaCentre();
    aiaEleveActif($c);
    $mistral = Mockery::mock(MistralService::class);
    $mistral->shouldReceive('chat')->andThrow(new RuntimeException('timeout'));
    app()->instance(MistralService::class, $mistral);

    $this->actingAs($c['responsable'])
        ->postJson(route('center.analyses.eleve', $c['eleve']))
        ->assertOk()
        ->assertJsonPath('analyse.source', 'regles');
});

test('l analyse est reutilisee tant que les faits n ont pas change : un seul appel', function () {
    $c = aiaCentre();
    aiaEleveActif($c);
    aiaMistral(aiaReponseValide(), fois: 1);

    $this->actingAs($c['responsable'])->postJson(route('center.analyses.eleve', $c['eleve']))->assertJsonPath('analyse.source', 'ia');
    $this->actingAs($c['responsable'])->postJson(route('center.analyses.eleve', $c['eleve']))->assertJsonPath('analyse.source', 'ia');
    $this->actingAs($c['prof'])->postJson(route('center.analyses.eleve', $c['eleve']))->assertJsonPath('analyse.source', 'ia');
    $this->actingAs($c['prof'])->getJson(route('center.analyses.eleve', $c['eleve']))
        ->assertJsonPath('analyse.source', 'ia')
        ->assertJsonPath('analyse.a_jour', true);
});

test('un nouvel exercice change les faits : l analyse est refaite', function () {
    $c = aiaCentre();
    aiaEleveActif($c);
    aiaMistral(aiaReponseValide(), fois: 2);

    $this->actingAs($c['responsable'])->postJson(route('center.analyses.eleve', $c['eleve']))->assertOk();
    aiaTentative($c['eleve'], $c['ecoute'], 90, 0);
    // La relance automatique d'un sujet est bornee a une toutes les 15 min.
    $this->travel(16)->minutes();
    $this->actingAs($c['responsable'])->postJson(route('center.analyses.eleve', $c['eleve']))->assertOk();
});

test('une analyse de plus de 24 h est refaite', function () {
    $c = aiaCentre();
    aiaEleveActif($c);
    aiaMistral(aiaReponseValide(), fois: 2);

    $this->actingAs($c['responsable'])->postJson(route('center.analyses.eleve', $c['eleve']))->assertOk();
    CenterAnalysis::query()->update(['generated_at' => now()->subHours(25)]);
    $this->travel(16)->minutes();
    $this->actingAs($c['responsable'])->postJson(route('center.analyses.eleve', $c['eleve']))->assertOk();
});

test('actualiser est limite a une fois par heure et par sujet', function () {
    $c = aiaCentre();
    aiaEleveActif($c);
    // Generation, puis une actualisation : deux appels. La seconde actualisation est refusee.
    aiaMistral(aiaReponseValide(), fois: 2);

    $this->actingAs($c['responsable'])->postJson(route('center.analyses.eleve', $c['eleve']))->assertOk();
    $this->actingAs($c['responsable'])->postJson(route('center.analyses.eleve', $c['eleve']), ['actualiser' => true])
        ->assertOk()
        ->assertJsonPath('analyse.source', 'ia');

    $this->actingAs($c['responsable'])->postJson(route('center.analyses.eleve', $c['eleve']), ['actualiser' => true])
        ->assertStatus(429)
        // L'analyse en place reste montree.
        ->assertJsonPath('analyse.source', 'ia')
        ->assertJsonPath('analyse.contenu.probleme.titre', 'La compréhension orale freine Awa');

    expect($this->actingAs($c['responsable'])->getJson(route('center.analyses.eleve', $c['eleve']))->json('analyse.actualisable_dans'))->toBeGreaterThan(0);
});

test('un enseignant ne voit pas l analyse d un eleve hors de ses classes', function () {
    $c = aiaCentre();
    aiaEleveActif($c);
    aiaMistral(aiaReponseValide(), fois: 1);

    foreach (['getJson', 'postJson'] as $methode) {
        $this->actingAs($c['autreProf'])->{$methode}(route('center.analyses.eleve', $c['eleve']))->assertForbidden();
        $this->actingAs($c['autreProf'])->{$methode}(route('center.analyses.classe', $c['classe']))->assertForbidden();
    }
    // Son propre enseignant, lui, la voit.
    $this->actingAs($c['prof'])->postJson(route('center.analyses.eleve', $c['eleve']))->assertOk();
});

test('un autre centre ne voit jamais l analyse', function () {
    $c = aiaCentre();
    aiaEleveActif($c);
    aiaMistral(aiaReponseValide(), fois: 0);

    foreach (['getJson', 'postJson'] as $methode) {
        $this->actingAs($c['responsableAilleurs'])->{$methode}(route('center.analyses.eleve', $c['eleve']))->assertForbidden();
        $this->actingAs($c['responsableAilleurs'])->{$methode}(route('center.analyses.classe', $c['classe']))->assertNotFound();
    }
});

test('l analyse du centre est reservee au responsable', function () {
    $c = aiaCentre();
    aiaEleveActif($c);
    aiaMistral(null, fois: 1);

    $this->actingAs($c['prof'])->getJson(route('center.analyses.centre'))->assertForbidden();
    $this->actingAs($c['prof'])->postJson(route('center.analyses.centre'))->assertForbidden();

    $analyse = $this->actingAs($c['responsable'])->postJson(route('center.analyses.centre'))
        ->assertOk()
        ->assertJsonPath('analyse.source', 'regles')
        ->json('analyse.contenu');

    expect($analyse['constats'])->not->toBeEmpty()->and(count($analyse['constats']))->toBeLessThanOrEqual(3)
        ->and($analyse['leviers'])->toHaveCount(3);
});

test('sous 3 exercices, on dit qu il n y a pas assez de donnees au lieu d inventer', function () {
    $c = aiaCentre();
    aiaTentative($c['eleve'], $c['ecoute'], 40, 1);
    aiaTentative($c['eleve'], $c['ecoute'], 50, 2);
    aiaMistral(aiaReponseValide(), fois: 0);

    $this->actingAs($c['responsable'])->getJson(route('center.analyses.eleve', $c['eleve']))->assertJsonPath('analyse.etat', 'insuffisant');
    $this->actingAs($c['responsable'])->postJson(route('center.analyses.eleve', $c['eleve']))
        ->assertOk()
        ->assertJsonPath('analyse.etat', 'insuffisant')
        ->assertJsonPath('analyse.contenu', null)
        ->assertJsonPath('analyse.message', fn (string $m) => str_contains($m, '2 exercices'));

    expect(CenterAnalysis::count())->toBe(0);
});

test('le prompt porte les faits, le prenom seulement, jamais l e-mail', function () {
    $c = aiaCentre();
    aiaEleveActif($c);
    $capture = null;
    aiaMistral(aiaReponseValide(), 1, $capture);

    $this->actingAs($c['responsable'])->postJson(route('center.analyses.eleve', $c['eleve']))->assertOk();

    $prompt = collect($capture)->pluck('content')->implode("\n");
    expect($prompt)
        ->toContain('N\'invente rien')
        ->toContain('"prenom": "Awa"')
        ->toContain('Compréhension orale')
        ->toContain('Goethe-Zertifikat')
        ->toContain('Grammaire · déclinaison (cas)')
        ->toContain('"exercices_total": 6')
        ->not->toContain('awa.nkoulou@exemple.test')
        ->not->toContain('Nkoulou');
});

test('dans une classe, l IA designe des references que l on remet en noms, sans en inventer', function () {
    $c = aiaCentre();
    aiaEleveActif($c);
    $capture = null;
    aiaMistral(aiaReponseValide() + ['eleves_a_surveiller' => [
        ['ref' => 'E1', 'raison' => 'Écoute à 30 %.'],
        ['ref' => 'E99', 'raison' => 'Inventé.'],
    ]], 1, $capture);

    $this->actingAs($c['prof'])->postJson(route('center.analyses.classe', $c['classe']))
        ->assertOk()
        ->assertJsonPath('analyse.source', 'ia')
        ->assertJsonCount(1, 'analyse.contenu.eleves_a_surveiller')
        ->assertJsonPath('analyse.contenu.eleves_a_surveiller.0.id', $c['eleve']->id)
        ->assertJsonPath('analyse.contenu.eleves_a_surveiller.0.nom', 'Awa Nkoulou');

    expect(collect($capture)->pluck('content')->implode("\n"))->not->toContain('Nkoulou')->not->toContain('@exemple.test');
});

test('la commande annonce sans appeler l IA, puis genere avec --appliquer', function () {
    $c = aiaCentre();
    aiaEleveActif($c);
    // Eleve actif + sa classe + le centre : trois appels ; l'autre eleve et
    // l'autre classe n'ont pas assez de donnees.
    aiaMistral(aiaReponseTousSujets(), fois: 0);

    $this->artisan('prepla:analyses-ia', ['--centre' => $c['centre']->id])
        ->expectsOutputToContain('appels IA nécessaires : 3')
        ->assertSuccessful();
    expect(CenterAnalysis::count())->toBe(0);

    aiaMistral(aiaReponseTousSujets(), fois: 3);
    $this->artisan('prepla:analyses-ia', ['--centre' => $c['centre']->id, '--appliquer' => true])->assertSuccessful();
    expect(CenterAnalysis::count())->toBe(3);

    // Relancee, elle ne repaie pas l'IA.
    aiaMistral(aiaReponseTousSujets(), fois: 0);
    $this->artisan('prepla:analyses-ia', ['--centre' => $c['centre']->id])
        ->expectsOutputToContain('appels IA nécessaires : 0')
        ->assertSuccessful();
});

test('un GET ne fait que lire : jamais d appel a l IA ni d ecriture', function () {
    $c = aiaCentre();
    aiaEleveActif($c);
    aiaMistral(aiaReponseTousSujets(), fois: 0);

    // Sans protection CSRF, un GET ne doit rien couter : la carte demandera la generation en POST.
    $this->actingAs($c['responsable'])->getJson(route('center.analyses.eleve', $c['eleve']))
        ->assertOk()
        ->assertJsonPath('analyse.etat', 'a_generer')
        ->assertJsonPath('analyse.contenu', null);
    $this->actingAs($c['responsable'])->getJson(route('center.analyses.centre'))->assertJsonPath('analyse.etat', 'a_generer');

    expect(CenterAnalysis::count())->toBe(0);
});

test('l empreinte ne bouge pas avec l heure : une pre-generation de la veille reste valable', function () {
    $c = aiaCentre();
    aiaEleveActif($c);
    // Une tentative d'il y a 6 jours, qui franchira la fenetre de 7 jours.
    aiaTentative($c['eleve'], $c['lecture'], 70, 6);
    aiaMistral(aiaReponseValide(), fois: 1);
    $service = app(AnalyseIa::class);

    $this->actingAs($c['responsable'])->postJson(route('center.analyses.eleve', $c['eleve']))->assertJsonPath('analyse.source', 'ia');
    $eleve = $service->empreinteDe($c['centre'], 'eleve', $c['eleve']);
    $classe = $service->empreinteDe($c['centre'], 'classe', $c['classe']);
    $centre = $service->empreinteDe($c['centre'], 'centre', $c['centre']);

    // 23 h plus tard, sans que personne ait travaille : rien n'a change.
    $this->travel(23)->hours();
    expect($service->empreinteDe($c['centre'], 'eleve', $c['eleve']))->toBe($eleve)
        ->and($service->empreinteDe($c['centre'], 'classe', $c['classe']))->toBe($classe)
        ->and($service->empreinteDe($c['centre'], 'centre', $c['centre']))->toBe($centre);
    $this->actingAs($c['responsable'])->getJson(route('center.analyses.eleve', $c['eleve']))->assertJsonPath('analyse.a_jour', true);
    $this->actingAs($c['responsable'])->postJson(route('center.analyses.eleve', $c['eleve']))->assertJsonPath('analyse.a_jour', true);

    // Au-dela de 24 h, elle n'est plus a jour (la carte la fera refaire).
    $this->travel(2)->hours();
    $this->actingAs($c['responsable'])->getJson(route('center.analyses.eleve', $c['eleve']))
        ->assertJsonPath('analyse.etat', 'pret')
        ->assertJsonPath('analyse.a_jour', false);
});

test('si l IA echoue a l actualisation, l analyse IA en place est gardee', function () {
    $c = aiaCentre();
    aiaEleveActif($c);
    aiaMistral(aiaReponseValide(), fois: 1);
    $this->actingAs($c['responsable'])->postJson(route('center.analyses.eleve', $c['eleve']))->assertJsonPath('analyse.source', 'ia');

    // Quota Mistral epuise (429) au moment de cliquer « Actualiser ».
    aiaMistral(null, fois: 1);
    $this->actingAs($c['responsable'])->postJson(route('center.analyses.eleve', $c['eleve']), ['actualiser' => true])
        ->assertOk()
        ->assertJsonPath('analyse.source', 'ia')
        ->assertJsonPath('analyse.contenu.probleme.titre', 'La compréhension orale freine Awa')
        ->assertJsonPath('analyse.message', fn (?string $m) => $m !== null);

    expect(CenterAnalysis::sole()->source)->toBe('ia');
});

test('deux ouvertures simultanees ne paient pas deux appels', function () {
    $c = aiaCentre();
    aiaEleveActif($c);
    aiaMistral(aiaReponseTousSujets(), fois: 0);

    // Une autre requete tient le verrou de ce sujet.
    $verrou = Cache::lock("analyse-ia-verrou:{$c['centre']->id}:eleve:{$c['eleve']->id}", 120);
    expect($verrou->get())->toBeTrue();

    $this->actingAs($c['responsable'])->postJson(route('center.analyses.eleve', $c['eleve']))
        ->assertOk()
        ->assertJsonPath('analyse.etat', 'en_cours');
    $verrou->release();
});

test('les textes libres de l eleve sont nettoyes avant l envoi a l IA', function () {
    $c = aiaCentre();
    foreach ([1, 2, 3] as $jour) {
        aiaTentative($c['eleve'], $c['lecture'], 50, $jour);
    }
    foreach ([1, 2, 3] as $i) {
        UserError::create([
            'user_id' => $c['eleve']->id, 'exercise_id' => $c['lecture']->id, 'question_id' => "q{$i}",
            'question_text' => 'Frage', 'user_answer' => 'Ich bin Awa Nkoulou, Tel 06 12 34 56 78, awa.privat@mail.de',
            'correct_answer' => 'Ich heiße Awa', 'skill_type' => 'writing', 'exercise_type_slug' => 'essay',
            'error_category' => 'grammar', 'subcategory' => 'case', 'mastered' => false,
        ]);
    }
    $capture = null;
    aiaMistral(aiaReponseValide(), 1, $capture);

    $this->actingAs($c['responsable'])->postJson(route('center.analyses.eleve', $c['eleve']))->assertOk();

    expect(collect($capture)->pluck('content')->implode("\n"))
        ->toContain('"prenom": "Awa"')
        ->not->toContain('Nkoulou')
        ->not->toContain('awa.privat@mail.de')
        ->not->toContain('12 34 56')
        // Le nom libre de la classe ne part pas non plus, seulement l'examen et le niveau.
        ->not->toContain('Goethe B1 matin');
});

test('la commande retente une analyse faite par regles et peut tout forcer', function () {
    $c = aiaCentre();
    aiaEleveActif($c);
    aiaMistral(null, fois: 3);
    $this->artisan('prepla:analyses-ia', ['--centre' => $c['centre']->id, '--appliquer' => true])->assertSuccessful();
    expect(CenterAnalysis::where('source', 'regles')->count())->toBe(3);

    // Des analyses par regles ne sont pas « a jour » : la commande les retente.
    aiaMistral(aiaReponseTousSujets(), fois: 3);
    $this->artisan('prepla:analyses-ia', ['--centre' => $c['centre']->id, '--appliquer' => true])->assertSuccessful();
    expect(CenterAnalysis::where('source', 'ia')->count())->toBe(3);

    // --forcer compte aussi les analyses IA a jour (rapport : aucun appel).
    aiaMistral(aiaReponseTousSujets(), fois: 0);
    $this->artisan('prepla:analyses-ia', ['--centre' => $c['centre']->id, '--forcer' => true])
        ->expectsOutputToContain('appels IA nécessaires : 3')
        ->assertSuccessful();
});
