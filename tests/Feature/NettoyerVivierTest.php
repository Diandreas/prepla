<?php

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\ExamBlueprint;
use App\Models\Language;
use App\Models\MockExam;
use App\Models\User;
use App\Models\UserExerciseAttempt;
use App\Services\AI\ExplainerService;
use Illuminate\Support\Facades\File;

/**
 * prepla:nettoyer-vivier corrige le contenu DEJA en base, releve par l'audit de
 * qualite : gabarits du TEF, lettres contradictoires dans les choix, ecoutes qui
 * ne disent que la question, explications manquantes, doublons. Elle doit tout
 * annoncer avant d'ecrire, sauvegarder avant de modifier, savoir revenir en
 * arriere, et ne rien refaire quand on la relance.
 */
function nvExamen(string $slug, string $langue): Exam
{
    $lang = Language::firstOrCreate(['slug' => $langue], ['name' => ucfirst($langue), 'native_name' => $langue, 'flag' => substr($langue, 0, 2)]);

    return Exam::firstOrCreate(['slug' => $slug], ['language_id' => $lang->id, 'name' => strtoupper($slug)]);
}

function nvType(Exam $exam, string $competence, string $format): ExerciseType
{
    $section = ExamSection::firstOrCreate(
        ['exam_id' => $exam->id, 'slug' => $competence],
        ['name' => ucfirst($competence), 'skill_type' => $competence]
    );

    return ExerciseType::firstOrCreate(
        ['section_id' => $section->id, 'slug' => $competence.'-'.$format],
        ['name' => $format, 'skill_type' => $competence, 'component_key' => $format]
    );
}

function nvExercice(Exam $exam, ExerciseType $type, string $niveau, array $content, array $questions, array $autres = []): Exercise
{
    return Exercise::create(array_merge([
        'exam_id' => $exam->id,
        'exercise_type_id' => $type->id,
        'difficulty' => $niveau,
        'content' => $content,
        'questions' => $questions,
        'is_ai_generated' => true,
    ], $autres));
}

/** Un gabarit TEF tel que releve en production : « Phrase d'entraînement », Bon / Mauvais / Super. */
function nvGabaritTef(string $niveau = 'A1'): Exercise
{
    $tef = nvExamen('tef', 'french');

    return nvExercice($tef, nvType($tef, 'listening', 'mcq'), $niveau,
        ['passage' => "Phrase d'entraînement {$niveau} - 0"],
        [['id' => 'q1', 'type' => 'mcq', 'text' => 'Complétez la phrase.', 'options' => ['Super', 'Bon', 'Mauvais'], 'correct_answer' => 'B']],
        ['is_ai_generated' => false]);
}

/** Un QCM d'association dont les choix portent des lettres melangees. */
function nvExerciceALettres(array $autresQuestion = []): Exercise
{
    $ielts = nvExamen('ielts', 'english');

    return nvExercice($ielts, nvType($ielts, 'reading', 'matching'), 'B1',
        ['passage' => 'The town organises a spring festival every year.'],
        [array_merge([
            'id' => 'q1', 'type' => 'matching', 'text' => 'What is the main event?',
            'options' => ['D Dance group', 'C Fireworks show', 'A Spring Festival', 'B Community spirit'],
            'correct_answer' => 'C',
        ], $autresQuestion)]);
}

/** Une ecoute dont le son ne dit que la question. */
function nvEcoutePauvre(): Exercise
{
    $goethe = nvExamen('goethe', 'german');
    $passage = 'Hallo, ich bin Anna. Heute gehe ich in den Park. Dort lese ich ein Buch und trinke einen Kaffee. '
        .'Ein Mann geht mit seinem Hund spazieren. Am Abend koche ich mit meiner Schwester Nudeln mit Tomaten.';

    return nvExercice($goethe, nvType($goethe, 'listening', 'mcq'), 'A1',
        ['passage' => $passage],
        [
            ['id' => 'q1', 'type' => 'mcq', 'text' => 'Was macht Anna im Park?', 'audio_text' => 'Was macht Anna im Park?',
                'audio_url' => '/storage/tts/ancien.mp3', 'options' => ['Sie liest', 'Sie schläft'], 'correct_answer' => 'A', 'explanation' => 'Sie liest ein Buch.'],
            ['id' => 'q2', 'type' => 'mcq', 'text' => 'Wer geht mit dem Hund?', 'audio_text' => 'Wer geht mit dem Hund?',
                'audio_url' => '/storage/tts/ancien2.mp3', 'options' => ['Ein Mann', 'Eine Frau'], 'correct_answer' => 'A', 'explanation' => 'Ein Mann.'],
        ]);
}

function nvEtat(): array
{
    // Retires compris : le scope global les cache a toute autre requete.
    return Exercise::avecLesRetires()->orderBy('id')->get()
        ->mapWithKeys(fn ($e) => [$e->id => [$e->content, $e->questions]])
        ->all();
}

function nvSauvegardes(): array
{
    $dossier = storage_path('app/nettoyage-vivier');

    return File::isDirectory($dossier) ? File::files($dossier) : [];
}

beforeEach(function () {
    // La sauvegarde s'ecrit sous storage_path() : un dossier jetable, pour ne
    // rien laisser dans le storage du projet.
    $this->stockage = sys_get_temp_dir().DIRECTORY_SEPARATOR.'nettoyage-vivier-'.uniqid();
    File::ensureDirectoryExists($this->stockage);
    $this->app->useStoragePath($this->stockage);

    // Aucun appel a l'IA tant qu'un test ne l'a pas explicitement prevu.
    $this->mock(ExplainerService::class, fn ($mock) => $mock->shouldNotReceive('explain'));
});

afterEach(function () {
    File::deleteDirectory($this->stockage);
});

test('sans --appliquer, la commande annonce et n ecrit rien', function () {
    nvGabaritTef();
    nvExerciceALettres();
    nvEcoutePauvre();
    $avant = nvEtat();

    $this->artisan('prepla:nettoyer-vivier')
        ->expectsOutputToContain('Rapport seulement')
        ->expectsOutputToContain('a retirer du service')
        ->expectsOutputToContain('seraient modifies')
        ->assertSuccessful();

    expect(nvEtat())->toBe($avant)
        ->and(nvSauvegardes())->toBe([]);
});

test('une etape inconnue est refusee', function () {
    $this->artisan('prepla:nettoyer-vivier', ['--etape' => ['nimporte']])->assertFailed();
});

test('gabarits-tef retire les gabarits et les copies a l identique, sans rien supprimer', function () {
    $gabaritA1 = nvGabaritTef('A1');
    $gabaritC2 = nvGabaritTef('C2');

    $tef = nvExamen('tef', 'french');
    $type = nvType($tef, 'listening', 'mcq');
    $grammaire = ['passage' => 'Hier, nous ___ au cinéma.'];
    $question = [['id' => 'q1', 'type' => 'mcq', 'text' => 'Choisissez le bon verbe.', 'options' => ['sommes allés', 'allons'], 'correct_answer' => 'A']];
    $copie1 = nvExercice($tef, $type, 'B1', $grammaire, $question, ['is_ai_generated' => false]);
    $copie2 = nvExercice($tef, $type, 'C1', $grammaire, $question, ['is_ai_generated' => false]);

    // Un exercice manuel unique et un exercice de l'IA ne sont pas des gabarits.
    $unique = nvExercice($tef, $type, 'B1', ['passage' => 'Un texte bien à lui.'],
        [['id' => 'q1', 'type' => 'mcq', 'text' => 'Question propre ?', 'options' => ['Oui', 'Non'], 'correct_answer' => 'A']],
        ['is_ai_generated' => false]);
    $parIa = nvExercice($tef, $type, 'B1', $grammaire, $question);

    $this->artisan('prepla:nettoyer-vivier', ['--etape' => ['gabarits-tef'], '--appliquer' => true])->assertSuccessful();

    foreach ([$gabaritA1, $gabaritC2, $copie1, $copie2] as $retire) {
        expect($retire->refresh()->content['retire']['etape'] ?? null)->toBe('gabarits-tef');
    }
    expect($unique->refresh()->content)->not->toHaveKey('retire')
        ->and($parIa->refresh()->content)->not->toHaveKey('retire')
        // Retirer n'est pas supprimer, et le vivier ne les sert plus.
        ->and(Exercise::avecLesRetires()->count())->toBe(6)
        ->and(Exercise::where('exam_id', $tef->id)->pluck('id')->sort()->values()->all())->toBe([$unique->id, $parIa->id]);

    // La sauvegarde garde l'etat d'avant.
    $sauvegardes = nvSauvegardes();
    expect($sauvegardes)->toHaveCount(1);
    $donnees = json_decode(File::get($sauvegardes[0]->getPathname()), true);
    expect($donnees['exercices'][(string) $gabaritA1->id]['content'])->toBe(['passage' => "Phrase d'entraînement A1 - 0"]);
});

test('lettres remet les choix dans l ordre de leurs lettres et pointe la bonne reponse', function () {
    $exercice = nvExerciceALettres();

    $this->artisan('prepla:nettoyer-vivier', ['--etape' => ['lettres'], '--appliquer' => true])->assertSuccessful();

    $q = $exercice->refresh()->questions[0];
    // La position C designait « A Spring Festival » : c'est lui la bonne reponse.
    expect($q['options'])->toBe(['Spring Festival', 'Community spirit', 'Fireworks show', 'Dance group'])
        ->and($q['correct_answer'])->toBe('A');
});

test('lettres signale sans corriger une reponse que l explication contredit', function () {
    $contredit = nvExerciceALettres(['explanation' => 'The text says the Fireworks show closes the day.']);
    $horsChoix = nvExerciceALettres(['correct_answer' => 'F']);
    // Des lettres qui ne vont pas de A sans trou : ce sont peut-etre des mots.
    $ielts = nvExamen('ielts', 'english');
    $mots = nvExercice($ielts, nvType($ielts, 'reading', 'mcq'), 'A1', [],
        [['id' => 'q1', 'type' => 'mcq', 'text' => 'Pick one.', 'options' => ['A cat', 'I am'], 'correct_answer' => 'A']]);
    $avant = nvEtat();

    $this->artisan('prepla:nettoyer-vivier', ['--etape' => ['lettres'], '--appliquer' => true])
        ->expectsOutputToContain('l explication parle de « Fireworks show »')
        ->expectsOutputToContain('hors des choix')
        ->assertSuccessful();

    expect(nvEtat())->toBe($avant);
});

test('lettres ne touche ni des lettres deja a leur place, ni une epreuve verifiee', function () {
    $goethe = nvExamen('goethe', 'german');
    $type = nvType($goethe, 'reading', 'matching');
    // Releve en base : les lettres renvoient aux annonces A a D du passage.
    $enOrdre = nvExercice($goethe, $type, 'A2', ['passage' => 'A — Deutschkurs. B — Zimmer.'],
        [['id' => 'q1', 'type' => 'matching', 'text' => 'Frau K. will Deutsch lernen.', 'options' => ['A — Deutschkurs', 'B — Zimmer'], 'correct_answer' => 'A']]);
    $verifiee = nvExercice($goethe, $type, 'A2', [],
        [['id' => 'q1', 'type' => 'matching', 'text' => 'Wer?', 'options' => ['B Zimmer', 'A Deutschkurs'], 'correct_answer' => 'B']],
        ['catalog_key' => 'verifie-v1:test', 'is_ai_generated' => false]);
    $avant = nvEtat();

    $this->artisan('prepla:nettoyer-vivier', ['--etape' => ['lettres'], '--appliquer' => true])->assertSuccessful();

    expect(nvEtat())->toBe($avant);
});

test('ecoutes laisse un son deja fabrique a partir du passage', function () {
    $goethe = nvExamen('goethe', 'german');
    $goethe->language->update(['name' => 'Allemand']);
    $passage = str_repeat('Anna geht heute in den Park und liest ein Buch. ', 6);
    $exercice = nvExercice($goethe, nvType($goethe, 'listening', 'mcq'), 'A1', ['passage' => $passage],
        [['id' => 'q1', 'type' => 'mcq', 'text' => 'Was macht Anna?', 'options' => ['Lesen', 'Schlafen'], 'correct_answer' => 'A', 'explanation' => 'x',
            'audio_url' => '/storage/tts/'.md5(trim($passage).'de').'.mp3']]);
    $avant = $exercice->questions;

    $this->artisan('prepla:nettoyer-vivier', ['--etape' => ['ecoutes'], '--appliquer' => true])->assertSuccessful();

    expect($exercice->refresh()->questions)->toBe($avant);
});

test('ecoutes met le passage dans la piste et efface l ancienne adresse', function () {
    $ecoute = nvEcoutePauvre();

    // Des petites pistes DIFFERENTES par question peuvent etre voulues.
    $goethe = nvExamen('goethe', 'german');
    $parItem = nvExercice($goethe, nvType($goethe, 'listening', 'mcq'), 'A1',
        ['passage' => str_repeat('Ein langer Text über den Alltag in der Stadt. ', 10)],
        [
            ['id' => 'q1', 'type' => 'mcq', 'text' => 'Wo?', 'audio_text' => 'Ich bin im Supermarkt.', 'audio_url' => '/a.mp3', 'options' => ['X', 'Y'], 'correct_answer' => 'A', 'explanation' => 'x'],
            ['id' => 'q2', 'type' => 'mcq', 'text' => 'Wann?', 'audio_text' => 'Um acht Uhr morgens.', 'audio_url' => '/b.mp3', 'options' => ['X', 'Y'], 'correct_answer' => 'A', 'explanation' => 'x'],
        ]);
    $parItemAvant = $parItem->questions;

    $this->artisan('prepla:nettoyer-vivier', ['--etape' => ['ecoutes'], '--appliquer' => true])
        ->expectsOutputToContain('prepla:fabriquer-medias')
        ->assertSuccessful();

    foreach ($ecoute->refresh()->questions as $q) {
        expect($q['audio_text'])->toBe($ecoute->content['passage'])
            ->and($q)->not->toHaveKey('audio_url');
    }
    expect($parItem->refresh()->questions)->toBe($parItemAvant);
});

test('explications compte sans appeler l IA, meme avec --appliquer, tant que l etape n est pas nommee', function () {
    $ielts = nvExamen('ielts', 'english');
    nvExercice($ielts, nvType($ielts, 'reading', 'matching'), 'B1', ['passage' => 'Texte.'],
        [['id' => 'q1', 'type' => 'matching', 'text' => 'Which?', 'options' => ['One', 'Two'], 'correct_answer' => 'B']]);
    $avant = nvEtat();

    // shouldNotReceive('explain') est pose dans beforeEach.
    $this->artisan('prepla:nettoyer-vivier', ['--appliquer' => true])
        ->expectsOutputToContain('1 question(s) corrigee(s) automatiquement sans explication')
        ->assertSuccessful();

    expect(nvEtat())->toBe($avant);
});

test('explications complete par l IA, en francais pour un debutant, sur demande explicite', function () {
    $goethe = nvExamen('goethe', 'german');
    $exercice = nvExercice($goethe, nvType($goethe, 'reading', 'matching'), 'A1', ['passage' => 'Anna wohnt in Berlin.'],
        [
            ['id' => 'q1', 'type' => 'matching', 'text' => 'Wo wohnt Anna?', 'options' => ['In Berlin', 'In Wien'], 'correct_answer' => 'A'],
            // Une question ouverte est jugee par l'IA a la correction : rien a expliquer d'avance.
            ['id' => 'q2', 'type' => 'essay', 'text' => 'Schreib über dich.'],
        ]);

    $this->mock(ExplainerService::class, function ($mock) {
        $mock->shouldReceive('explain')->once()
            ->withArgs(fn ($demande, $contexte, $langue) => $langue === 'French'
                && str_contains($demande, 'in French') && str_contains($contexte, 'In Berlin'))
            ->andReturn('Le texte dit « Anna wohnt in Berlin ».');
    });

    $this->artisan('prepla:nettoyer-vivier', ['--etape' => ['explications'], '--appliquer' => true])->assertSuccessful();

    $questions = $exercice->refresh()->questions;
    expect($questions[0]['explanation'])->toBe('Le texte dit « Anna wohnt in Berlin ».')
        ->and($questions[1])->not->toHaveKey('explanation')
        ->and(nvSauvegardes())->toHaveCount(1);
});

test('une reponse d excuse de l IA n est pas ecrite comme explication', function () {
    $ielts = nvExamen('ielts', 'english');
    $exercice = nvExercice($ielts, nvType($ielts, 'reading', 'matching'), 'B2', [],
        [['id' => 'q1', 'type' => 'matching', 'text' => 'Which?', 'options' => ['One', 'Two'], 'correct_answer' => 'B']]);

    $this->mock(ExplainerService::class, fn ($mock) => $mock->shouldReceive('explain')->once()
        ->andReturn("Désolé, je n'ai pas pu me connecter à l'API Mistral pour le moment."));

    $this->artisan('prepla:nettoyer-vivier', ['--etape' => ['explications'], '--appliquer' => true])
        ->expectsOutputToContain('1 echec(s)')
        ->assertSuccessful();

    expect($exercice->refresh()->questions[0])->not->toHaveKey('explanation');
});

test('doublons garde le plus ancien et retire les autres, choix melanges compris', function () {
    $goethe = nvExamen('goethe', 'german');
    $type = nvType($goethe, 'reading', 'mcq');
    $q = fn (array $choix) => [['id' => 'q1', 'type' => 'mcq', 'text' => 'Wie begrüßt du jemanden am Morgen?', 'options' => $choix, 'correct_answer' => 'A']];

    $recent = nvExercice($goethe, $type, 'A1', [], $q(['Guten Abend', 'Guten Morgen']));
    $ancien = nvExercice($goethe, $type, 'A1', [], $q(['Guten Morgen', 'Guten Abend']));
    $ancien->forceFill(['created_at' => now()->subMonth()])->saveQuietly();
    $autreNiveau = nvExercice($goethe, $type, 'A2', [], $q(['Guten Morgen', 'Guten Abend']));
    // Meme texte, autres choix : peut-etre une consigne generique. A revoir, pas a retirer.
    $autresChoix = nvExercice($goethe, $type, 'A1', [], $q(['Hallo', 'Tschüss']));

    $this->artisan('prepla:nettoyer-vivier', ['--etape' => ['doublons'], '--appliquer' => true])
        ->expectsOutputToContain('a revoir')
        ->assertSuccessful();

    expect($recent->refresh()->content['retire']['etape'] ?? null)->toBe('doublons')
        ->and($ancien->refresh()->content)->not->toHaveKey('retire')
        ->and($autreNiveau->refresh()->content)->not->toHaveKey('retire')
        ->and($autresChoix->refresh()->content)->not->toHaveKey('retire');
});

test('--restaurer remet les lignes sauvegardees, et seulement avec --appliquer', function () {
    nvGabaritTef();
    nvExerciceALettres();
    nvEcoutePauvre();
    $avant = nvEtat();

    $this->artisan('prepla:nettoyer-vivier', ['--appliquer' => true])->assertSuccessful();
    expect(nvEtat())->not->toBe($avant);
    $fichier = nvSauvegardes()[0]->getFilename();
    $apres = nvEtat();

    $this->artisan('prepla:nettoyer-vivier', ['--restaurer' => $fichier])
        ->expectsOutputToContain('seraient remis')
        ->assertSuccessful();
    expect(nvEtat())->toBe($apres);

    $this->artisan('prepla:nettoyer-vivier', ['--restaurer' => $fichier, '--appliquer' => true])->assertSuccessful();
    expect(nvEtat())->toBe($avant);
});

test('relancer --appliquer ne change rien de plus', function () {
    nvGabaritTef();
    nvExerciceALettres();
    nvEcoutePauvre();
    $goethe = nvExamen('goethe', 'german');
    $type = nvType($goethe, 'reading', 'mcq');
    $q = [['id' => 'q1', 'type' => 'mcq', 'text' => 'Wie heißt du?', 'options' => ['Anna', 'Berlin'], 'correct_answer' => 'A', 'explanation' => 'x']];
    nvExercice($goethe, $type, 'A1', [], $q);
    nvExercice($goethe, $type, 'A1', [], $q);

    $this->artisan('prepla:nettoyer-vivier', ['--appliquer' => true])->assertSuccessful();
    $premier = nvEtat();
    expect(nvSauvegardes())->toHaveCount(1);

    $this->artisan('prepla:nettoyer-vivier', ['--appliquer' => true])
        ->expectsOutputToContain('Rien a modifier')
        ->assertSuccessful();

    expect(nvEtat())->toBe($premier)
        ->and(nvSauvegardes())->toHaveCount(1);
});

test('un exercice retire n est plus tire du vivier, mais reste lie au travail deja fait', function () {
    $gabarit = nvGabaritTef('B1');
    $tef = nvExamen('tef', 'french');
    $type = nvType($tef, 'listening', 'mcq');
    $bon = nvExercice($tef, $type, 'B1', ['passage' => 'Un vrai texte.'],
        [['id' => 'q1', 'type' => 'mcq', 'text' => 'Question ?', 'options' => ['Oui', 'Non'], 'correct_answer' => 'A']]);
    $user = User::factory()->create();
    $tentative = UserExerciseAttempt::create(['user_id' => $user->id, 'exercise_id' => $gabarit->id, 'answers' => [], 'accuracy_percent' => 80]);

    $this->artisan('prepla:nettoyer-vivier', ['--etape' => ['gabarits-tef'], '--appliquer' => true])->assertSuccessful();

    // Le tirage du vivier, tel que l'ecrivent PracticeController et ProchainExercice.
    $tirage = Exercise::where('exam_id', $tef->id)->where('exercise_type_id', $type->id)->where('difficulty', 'B1')
        ->whereNull('center_id')->whereNull('lesson_id')->whereNull('node_id')->whereNull('mock_exam_id')
        ->meilleurDAbord('B1')->pluck('id')->all();
    expect($tirage)->toBe([$bon->id])
        ->and(Exercise::where('exam_id', $tef->id)->whereNotIn('id', [$bon->id])->exists())->toBeFalse()
        // Designe par son numero : l'adresse, la seance en cours, la tentative.
        ->and(Exercise::find($gabarit->id)?->id)->toBe($gabarit->id)
        ->and(Exercise::whereIn('id', [$gabarit->id, $bon->id])->count())->toBe(2)
        ->and($tentative->fresh()->exercise?->id)->toBe($gabarit->id)
        ->and(UserExerciseAttempt::with('exercise')->find($tentative->id)->exercise?->id)->toBe($gabarit->id)
        // Le calcul du niveau part des tentatives : il compte toujours celle-ci.
        ->and(UserExerciseAttempt::whereHas('exercise', fn ($q) => $q->where('difficulty', 'B1'))->count())->toBe(1);
});

test('explications : une seule question payee par contenu, recopiee dans les copies des epreuves blanches', function () {
    $ielts = nvExamen('ielts', 'english');
    $type = nvType($ielts, 'reading', 'matching');
    $question = [['id' => 'q1', 'type' => 'matching', 'text' => 'Which one?', 'options' => ['One', 'Two'], 'correct_answer' => 'B']];
    $original = nvExercice($ielts, $type, 'B2', ['passage' => 'A text.'], $question);

    $blanche = MockExam::create([
        'blueprint_id' => ExamBlueprint::create([
            'exam_id' => $ielts->id, 'name' => 'Blanc', 'total_duration_minutes' => 30, 'scoring_config' => [], 'sections_config' => [],
        ])->id,
        'title' => 'Epreuve blanche',
    ]);
    // Ce que fait MockExamComposer : une copie a l'identique, sans catalog_key.
    $copie = $original->replicate();
    $copie->mock_exam_id = $blanche->id;
    $copie->save();
    // Une question qui n'existe QUE dans l'epreuve blanche : hors du perimetre.
    $seule = nvExercice($ielts, $type, 'B2', ['passage' => 'Other.'],
        [['id' => 'q1', 'type' => 'matching', 'text' => 'Only here?', 'options' => ['Yes', 'No'], 'correct_answer' => 'A']],
        ['mock_exam_id' => $blanche->id]);

    $this->artisan('prepla:nettoyer-vivier', ['--etape' => ['explications']])
        ->expectsOutputToContain('1 question(s) corrigee(s) automatiquement sans explication dans le vivier libre, 1 distincte(s)')
        ->expectsOutputToContain('1 copie(s) dans les epreuves blanches')
        ->assertSuccessful();

    $this->mock(ExplainerService::class, fn ($mock) => $mock->shouldReceive('explain')->once()->andReturn('Because the text says two.'));

    $this->artisan('prepla:nettoyer-vivier', ['--etape' => ['explications'], '--appliquer' => true])
        ->expectsOutputToContain('1 recopiee(s)')
        ->assertSuccessful();

    expect($original->refresh()->questions[0]['explanation'] ?? null)->toBe('Because the text says two.')
        ->and($copie->refresh()->questions[0]['explanation'] ?? null)->toBe('Because the text says two.')
        ->and($seule->refresh()->questions[0])->not->toHaveKey('explanation');
});
