<?php

use App\Models\Exam;
use App\Models\ExamBlueprint;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\AI\ExerciseGeneratorService;
use App\Services\AI\MistralService;

/**
 * Ce que l'audit du 2026-11-04 a trouve dans les exercices servis aux comptes
 * gratuits, et que le generateur verifie desormais APRES avoir ecrit : un
 * exercice qui ne passe pas est refuse et recommence, comme un mauvais nombre
 * d'items. Aucun de ces tests n'appelle la vraie IA : MistralService est
 * remplace par une file de reponses ecrites a la main.
 */
beforeEach(fn () => \Illuminate\Support\Facades\Http::preventStrayRequests());

/** Un examen, une section, un type : le minimum pour generer. */
function qegType(string $langue = 'german', string $competence = 'reading', string $composant = 'mcq', ?string $slug = null): array
{
    $noms = [
        'german' => ['German', 'Deutsch'],
        'english' => ['English', 'English'],
        'french' => ['French', 'Français'],
    ][$langue];
    $language = Language::firstOrCreate(['slug' => $langue], [
        'name' => $noms[0], 'native_name' => $noms[1], 'flag' => substr($langue, 0, 2),
    ]);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'qeg-'.Exam::count(), 'name' => 'QEG exam']);
    $section = ExamSection::create([
        'exam_id' => $exam->id, 'slug' => $competence, 'name' => ucfirst($competence), 'skill_type' => $competence,
    ]);
    $type = ExerciseType::create([
        'section_id' => $section->id, 'slug' => $slug ?? $composant, 'name' => $composant,
        'skill_type' => $competence, 'component_key' => $composant,
    ]);

    return [$exam, $type];
}

/** Un texte de N mots, sans aucun des mots que les trous attendent. */
function qegTexte(int $mots): string
{
    $reserve = ['Heute', 'scheint', 'die', 'Sonne', 'über', 'dem', 'kleinen', 'Dorf', 'am', 'See', 'und', 'Kinder', 'spielen', 'Kiste'];

    return implode(' ', array_map(fn ($i) => $reserve[$i % count($reserve)], range(0, $mots - 1))).'.';
}

function qegReponse(array $questions, array $contenu = []): string
{
    return json_encode([
        'content' => $contenu + ['instructions' => 'Lies und antworte.'],
        'questions' => $questions,
    ], JSON_UNESCAPED_UNICODE);
}

function qegQcm(string $id, array $options = ['Bonn', 'Berlin', 'Hamburg', 'Köln'], string $reponse = 'A', ?string $explication = 'Der Text nennt Bonn.'): array
{
    return array_filter([
        'id' => $id, 'type' => 'mcq', 'text' => "Wo wohnt Anna ({$id})?",
        'options' => $options, 'correct_answer' => $reponse, 'explanation' => $explication,
    ], fn ($v) => $v !== null);
}

/**
 * Remplace le fournisseur par une file de reponses. Chaque prompt recu est
 * garde, dans l'ordre, pour qu'un test puisse le lire.
 *
 * @param  list<string|\Closure>  $reponses  une reponse, ou une fonction du prompt
 */
function qegFournisseur($test, array $reponses, array &$prompts = []): void
{
    $mock = Mockery::mock(MistralService::class);
    $mock->shouldReceive('chat')->times(count($reponses))
        ->andReturnUsing(function (array $messages) use (&$reponses, &$prompts) {
            $prompt = $messages[1]['content'] ?? '';
            $prompts[] = $prompt;
            $suivante = array_shift($reponses);

            return $suivante instanceof \Closure ? $suivante($prompt) : $suivante;
        });
    app()->instance(MistralService::class, $mock);
}

/** Les items que la verification a l'aveugle a recus. */
function qegItemsAveugles(string $prompt): array
{
    return json_decode(substr($prompt, strrpos($prompt, 'ITEMS: ') + 7), true);
}

// ── a. Les lettres dans les options ────────────────────────────────────────

test('les lettres « A » + espace sont retirees, et la reponse suit sa lettre (#2287)', function () {
    [$exam, $type] = qegType();
    $q = qegQcm('q1', ['D Dance group', 'C Fireworks show', 'A Spring Festival', 'B Community spirit'], 'C');
    qegFournisseur($this, [qegReponse([$q, qegQcm('q2'), qegQcm('q3')], ['passage' => qegTexte(140)])]);

    $exercice = app(ExerciseGeneratorService::class)->generate($type, $exam, 'A2');
    $premiere = $exercice->questions[0];

    // Les options n'ont plus de lettre, et la lettre marquee designe, apres
    // melange, l'option qui portait « C » : « Fireworks show ».
    expect($premiere['options'])->toEqualCanonicalizing(['Dance group', 'Fireworks show', 'Spring Festival', 'Community spirit'])
        ->and($premiere['options'][ord($premiere['correct_answer']) - 65])->toBe('Fireworks show');
});

test('un mot qui commence par une lettre n est pas pris pour une lettre d option', function () {
    [$exam, $type] = qegType('english');
    $q = qegQcm('q1', ['A book', 'A pen', 'The bag', 'Some paper'], 'B');
    qegFournisseur($this, [qegReponse([$q, qegQcm('q2'), qegQcm('q3')], ['passage' => qegTexte(140)])]);

    $premiere = app(ExerciseGeneratorService::class)->generate($type, $exam, 'A2')->questions[0];

    expect($premiere['options'])->toContain('A book')
        ->and($premiere['options'][ord($premiere['correct_answer']) - 65])->toBe('A pen');
});

// ── b. L'explication pour tout format corrige ───────────────────────────────

test('une association sans explication est refusee, puis recommencee', function () {
    [$exam, $type] = qegType('german', 'reading', 'matching');
    $sans = ['id' => 'q1', 'type' => 'matching', 'text' => 'Bahnhof', 'options' => ['Ort für Züge', 'Ort für Bücher', 'Ort für Brot', 'Ort für Ärzte'], 'correct_answer' => 'A'];
    $avec = $sans + ['explanation' => 'Am Bahnhof fahren Züge ab.'];
    qegFournisseur($this, [
        qegReponse([$sans], ['passage' => qegTexte(140)]),
        qegReponse([$avec], ['passage' => qegTexte(140)]),
    ]);

    $exercice = app(ExerciseGeneratorService::class)->generate($type, $exam, 'A2');

    expect($exercice->questions[0]['explanation'])->toBe('Am Bahnhof fahren Züge ab.');
});

test('un format a champs multiples exige une explication par trou', function () {
    [$exam, $type] = qegType('german', 'reading', 'note-completion');
    $notes = [['label' => 'Name', 'value' => 'Anna'], ['label' => 'Beginn', 'value' => ''], ['label' => 'Ort', 'value' => '']];
    $uneSeule = ['id' => 'q1', 'type' => 'note-completion', 'notes' => $notes,
        'correct_answers' => ['1' => '7 Uhr', '2' => 'Bonn'], 'explanation' => 'Le texte le dit.'];
    $parTrou = ['id' => 'q1', 'type' => 'note-completion', 'notes' => $notes,
        'correct_answers' => ['1' => '7 Uhr', '2' => 'Bonn'],
        'explanations' => ['1' => 'Le cours commence à sept heures.', '2' => 'Anna habite à Bonn.']];
    qegFournisseur($this, [qegReponse([$uneSeule]), qegReponse([$parTrou])]);

    $question = app(ExerciseGeneratorService::class)->generate($type, $exam, 'A2')->questions[0];

    // L'explication composee est celle que lisent la correction et l'ecran.
    expect($question['explanation'])->toContain('« 7 Uhr » : Le cours commence à sept heures.')
        ->and($question['explanation'])->toContain('« Bonn » : Anna habite à Bonn.');
});

// ── c. La langue des explications ──────────────────────────────────────────

test('jusqu au A2 on explique dans la langue de l apprenant, ensuite dans celle de l examen', function () {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create(['native_language' => 'Español', 'onboarding_completed_at' => now()]);
    $this->actingAs($user);
    [$exam, $type] = qegType();

    $prompts = [];
    qegFournisseur($this, [
        qegReponse([qegQcm('q1'), qegQcm('q2'), qegQcm('q3')], ['passage' => qegTexte(100)]),
        qegReponse([qegQcm('q1'), qegQcm('q2'), qegQcm('q3')], ['passage' => qegTexte(250)]),
    ], $prompts);

    $a1 = app(ExerciseGeneratorService::class)->generate($type, $exam, 'A1');
    $b1 = app(ExerciseGeneratorService::class)->generate($type, $exam, 'B1');

    expect($prompts[0])->toContain('Explanations are written in Español')
        ->and($prompts[0])->toContain('explanation (string in Español)')
        ->and($a1->content['explanation_language'])->toBe('Español')
        ->and($prompts[1])->toContain('Explanations are written in German')
        ->and($b1->content['explanation_language'])->toBe('German');
});

// ── d. La longueur du texte selon le niveau ────────────────────────────────

test('la longueur du texte demande suit le niveau', function () {
    [$exam, $type] = qegType();
    $methode = new ReflectionMethod(ExerciseGeneratorService::class, 'buildPrompt');
    $methode->setAccessible(true);
    $prompt = fn (string $niveau) => $methode->invoke(app(ExerciseGeneratorService::class), $type, $exam, $niveau, 'German', 'Deutsch');

    expect($prompt('A1'))->toContain('(80-120 words)')
        ->and($prompt('A2'))->toContain('(120-180 words)')
        ->and($prompt('B1'))->toContain('(200-300 words)')
        ->and($prompt('B2'))->toContain('(300-400 words)')
        ->and($prompt('C1'))->toContain('(400-600 words)')
        ->and($prompt('C2'))->not->toContain('150-200');
});

test('un texte trop court pour le niveau est refuse, puis recommence', function () {
    [$exam, $type] = qegType();
    qegFournisseur($this, [
        qegReponse([qegQcm('q1'), qegQcm('q2'), qegQcm('q3')], ['passage' => qegTexte(120)]),
        qegReponse([qegQcm('q1'), qegQcm('q2'), qegQcm('q3')], ['passage' => qegTexte(450)]),
    ]);

    $exercice = app(ExerciseGeneratorService::class)->generate($type, $exam, 'C1');

    expect(Exercise::nombreDeMots($exercice->content['passage']))->toBe(450);
});

// ── e. Les textes a trous ──────────────────────────────────────────────────

function qegTrou(string $texte, string $reponse): array
{
    return ['id' => 'q1', 'type' => 'gap-fill', 'text' => $texte, 'correct_answer' => $reponse, 'explanation' => 'Präteritum von gehen.'];
}

test('un trou dont la reponse est ecrite dans le texte est refuse', function () {
    [$exam, $type] = qegType('german', 'reading', 'gap-fill');
    $bon = qegTrou('Gestern ___ (gehen) ich ins Kino.', 'ging');
    qegFournisseur($this, [
        qegReponse([$bon], ['passage' => qegTexte(130).' Anna ging nach Hause.']),
        // « ist » apparait dans « Kiste », pas comme mot : ce n'est pas une copie.
        qegReponse([$bon, qegTrou('Das Wetter ___ (sein) heute schön.', 'ist')], ['passage' => qegTexte(130)]),
    ]);

    $exercice = app(ExerciseGeneratorService::class)->generate($type, $exam, 'A2');

    expect($exercice->questions)->toHaveCount(2)
        ->and($exercice->content['passage'])->not->toContain('ging');
});

test('un trou dont la reponse recopie l indice entre parentheses est refuse (#476)', function () {
    [$exam, $type] = qegType('german', 'reading', 'gap-fill');
    qegFournisseur($this, [
        qegReponse([qegTrou('Ich habe eine ___ (Schwester).', 'Schwester')], ['passage' => qegTexte(130)]),
        qegReponse([qegTrou('Ich habe zwei ___ (Schwester).', 'Schwestern')], ['passage' => qegTexte(130)]),
    ]);

    $exercice = app(ExerciseGeneratorService::class)->generate($type, $exam, 'A2');

    expect($exercice->questions[0]['correct_answer'])->toBe('Schwestern');
});

// ── f. Les sections de grammaire ──────────────────────────────────────────

test('une section de grammaire n a pas de texte a lire', function () {
    [$exam, $type] = qegType('french', 'grammar', 'mcq');
    $phrase = ['id' => 'q1', 'type' => 'mcq', 'text' => 'Hier, je ___ au cinéma.',
        'options' => ['suis allé', 'vais', 'irai', 'allais'], 'correct_answer' => 'A', 'explanation' => 'Passé composé.'];
    $prompts = [];
    qegFournisseur($this, [
        qegReponse([$phrase], ['passage' => qegTexte(250)]),
        qegReponse([$phrase]),
    ], $prompts);

    $exercice = app(ExerciseGeneratorService::class)->generate($type, $exam, 'B1');

    expect($exercice->content)->not->toHaveKey('passage')
        ->and($prompts[0])->toContain('THIS IS A GRAMMAR SECTION')
        ->and($prompts[0])->not->toContain('"passage": "A B1-level text');
});

test('une section de grammaire qui renvoie au texte est refusee', function () {
    [$exam, $type] = qegType('french', 'grammar', 'mcq');
    $lecture = ['id' => 'q1', 'type' => 'mcq', 'text' => 'Selon le texte, où va Paul ?',
        'options' => ['Au cinéma', 'À la gare', 'Au marché', 'Chez lui'], 'correct_answer' => 'A', 'explanation' => 'Il le dit.'];
    qegFournisseur($this, [qegReponse([$lecture]), qegReponse([$lecture])]);

    expect(fn () => app(ExerciseGeneratorService::class)->generate($type, $exam, 'B1'))
        ->toThrow(RuntimeException::class, 'renvoie a un texte');
});

// ── g. Les ecoutes ─────────────────────────────────────────────────────────

test('une ecoute dont le son ne dit que la question est refusee (#464)', function () {
    [$exam, $type] = qegType('german', 'listening', 'mcq');
    $recopie = qegQcm('q1') + ['audio_text' => 'Wo wohnt Anna (q1)?'];
    $vraiSon = qegQcm('q1') + ['audio_text' => qegTexte(40)];
    qegFournisseur($this, [qegReponse([$recopie]), qegReponse([$vraiSon])]);

    $exercice = app(ExerciseGeneratorService::class)->generate($type, $exam, 'A2');

    expect(Exercise::nombreDeMots($exercice->questions[0]['audio_text']))->toBe(40);
});

test('une ecoute de moins de 25 mots est refusee', function () {
    [$exam, $type] = qegType('german', 'listening', 'mcq');
    $court = qegQcm('q1') + ['audio_text' => 'Anna wohnt seit zwei Jahren in Bonn und arbeitet dort.'];
    qegFournisseur($this, [qegReponse([$court]), qegReponse([$court])]);

    expect(fn () => app(ExerciseGeneratorService::class)->generate($type, $exam, 'A2'))
        ->toThrow(RuntimeException::class, 'il en faut au moins 25');
});

// ── h. Le vrai/faux de l'epreuve ───────────────────────────────────────────

function qegAffirmation(string $id, string $reponse): array
{
    return ['id' => $id, 'type' => 'true-false-ng', 'text' => "Aussage {$id}.", 'correct_answer' => $reponse, 'explanation' => 'Siehe Zeile 3.'];
}

test('le Goethe se joue en Richtig / Falsch, sans « non mentionne »', function () {
    $this->seed(\Database\Seeders\LanguageSeeder::class);
    $this->seed(\Database\Seeders\ExamSeeder::class);
    $exam = Exam::where('slug', 'goethe')->sole();
    $type = ExerciseType::whereHas('section', fn ($q) => $q->where('exam_id', $exam->id)->where('slug', 'lesen'))
        ->where('slug', 'true-false')->sole();

    $prompts = [];
    // Le modele repond en anglais : la reponse est juste, le libelle non.
    qegFournisseur($this, [qegReponse([
        qegAffirmation('q1', 'True'), qegAffirmation('q2', 'False'), qegAffirmation('q3', 'Richtig'),
        qegAffirmation('q4', 'falsch'), qegAffirmation('q5', 'True'),
    ], ['passage' => qegTexte(100)])], $prompts);

    $exercice = app(ExerciseGeneratorService::class)->generate($type, $exam, 'A1');

    expect(array_column($exercice->questions, 'correct_answer'))->toBe(['Richtig', 'Falsch', 'Richtig', 'Falsch', 'Richtig'])
        ->and($exercice->questions[0]['answer_options'])->toBe(['Richtig', 'Falsch'])
        ->and($prompts[0])->toContain('Richtig / Falsch')
        ->and($prompts[0])->toContain('There is NO "not given" answer');
});

test('un type pedagogique « non mentionne » suit l examen qui n en a pas', function () {
    $this->seed(\Database\Seeders\LanguageSeeder::class);
    $this->seed(\Database\Seeders\ExamSeeder::class);
    $exam = Exam::where('slug', 'goethe')->sole();
    $type = ExerciseType::whereHas('section', fn ($q) => $q->where('exam_id', $exam->id)->where('slug', 'lesen'))
        ->where('slug', 'true-false-not-given')->sole();

    $methode = new ReflectionMethod(ExerciseGeneratorService::class, 'libellesVraiFaux');
    $methode->setAccessible(true);

    expect($methode->invoke(app(ExerciseGeneratorService::class), $type, $exam, null))->toBe(['Richtig', 'Falsch']);
});

test('quand l epreuve offre « Not Given », au moins une affirmation l emploie', function () {
    [$exam, $type] = qegType('english', 'reading', 'true-false-ng', 'true-false-not-given');
    ExamBlueprint::create([
        'exam_id' => $exam->id, 'level' => 'B1', 'name' => 'QEG B1', 'total_duration_minutes' => 60, 'scoring_config' => [],
        'sections_config' => [['slug' => 'reading', 'name' => 'Reading', 'skill_type' => 'reading',
            'parts' => [['part' => 1, 'name' => 'Reading, Part 1', 'type' => 'true-false-not-given']]]],
    ]);
    qegFournisseur($this, [
        qegReponse([qegAffirmation('q1', 'True'), qegAffirmation('q2', 'False'), qegAffirmation('q3', 'False')], ['passage' => qegTexte(250)]),
        qegReponse([qegAffirmation('q1', 'True'), qegAffirmation('q2', 'False'), qegAffirmation('q3', 'Not Given')], ['passage' => qegTexte(250)]),
    ]);

    $exercice = app(ExerciseGeneratorService::class)->generate($type, $exam, 'B1');

    expect(array_column($exercice->questions, 'correct_answer'))->toBe(['True', 'False', 'Not Given'])
        ->and($exercice->questions[0]['answer_options'])->toBe(['True', 'False', 'Not Given']);
});

// ── i. La verification a l'aveugle ─────────────────────────────────────────

/** Le second regard : il repond « Bonn » partout, ou se trompe sur la question 2. */
function qegAveugle(bool $seTrompe = false, array $ambigues = []): \Closure
{
    return function (string $prompt) use ($seTrompe, $ambigues) {
        $reponses = [];
        foreach (qegItemsAveugles($prompt) as $item) {
            $lettre = array_search('Bonn', $item['options'], true);
            if ($seTrompe && $item['id'] === '2') {
                $lettre = $lettre === 'A' ? 'B' : 'A';
            }
            $reponses[$item['id']] = $lettre;
        }

        return json_encode(['answers' => $reponses, 'ambiguous' => $ambigues]);
    };
}

test('une cle que le second regard contredit est refusee, puis recommencee', function () {
    config(['prepla.generation.verification_aveugle' => true]);
    [$exam, $type] = qegType();
    $serie = qegReponse([qegQcm('q1'), qegQcm('q2'), qegQcm('q3')], ['passage' => qegTexte(140)]);
    $prompts = [];
    qegFournisseur($this, [$serie, qegAveugle(seTrompe: true), $serie, qegAveugle()], $prompts);

    $exercice = app(ExerciseGeneratorService::class)->generate($type, $exam, 'A2');

    // Le second regard ne voit ni la cle ni les explications.
    expect($exercice->questions)->toHaveCount(3)
        ->and($prompts[1])->toContain('WITHOUT any answer key')
        ->and($prompts[1])->not->toContain('correct_answer')
        ->and($prompts[1])->not->toContain('Der Text nennt Bonn');
});

test('deux bonnes options font refuser l exercice (#581)', function () {
    config(['prepla.generation.verification_aveugle' => true]);
    [$exam, $type] = qegType();
    $serie = qegReponse([qegQcm('q1'), qegQcm('q2'), qegQcm('q3')], ['passage' => qegTexte(140)]);
    qegFournisseur($this, [$serie, qegAveugle(ambigues: ['3']), $serie, qegAveugle(ambigues: ['3'])]);

    expect(fn () => app(ExerciseGeneratorService::class)->generate($type, $exam, 'A2'))
        ->toThrow(RuntimeException::class, 'plusieurs bonnes reponses');
});

test('une verification illisible ne prive pas l apprenant de son exercice', function () {
    config(['prepla.generation.verification_aveugle' => true]);
    [$exam, $type] = qegType();
    qegFournisseur($this, [
        qegReponse([qegQcm('q1'), qegQcm('q2'), qegQcm('q3')], ['passage' => qegTexte(140)]),
        'pas du JSON',
    ]);

    expect(app(ExerciseGeneratorService::class)->generate($type, $exam, 'A2')->questions)->toHaveCount(3);
});

test('la verification a l aveugle est activee par defaut, et se coupe par la configuration', function () {
    // Le fichier de configuration l'active hors tests ; phpunit.xml la coupe.
    $source = file_get_contents(config_path('prepla.php'));
    expect($source)->toContain("env('PREPLA_VERIFICATION_AVEUGLE', true)");

    config(['prepla.generation.verification_aveugle' => false]);
    [$exam, $type] = qegType();
    qegFournisseur($this, [qegReponse([qegQcm('q1'), qegQcm('q2'), qegQcm('q3')], ['passage' => qegTexte(140)])]);

    expect(app(ExerciseGeneratorService::class)->generate($type, $exam, 'A2')->questions)->toHaveCount(3);
});

// ── Les reprises de la relecture ───────────────────────────────────────────

test('une ecoute ne demande plus de texte a lire, seulement l enregistrement', function () {
    [$exam, $type] = qegType('german', 'listening', 'mcq');
    $methode = new ReflectionMethod(ExerciseGeneratorService::class, 'buildPrompt');
    $methode->setAccessible(true);
    $prompt = $methode->invoke(app(ExerciseGeneratorService::class), $type, $exam, 'C1', 'German', 'Deutsch');

    // Le prompt demandait un passage de 400-600 mots EN PLUS d'un audio_text
    // de 60-100 mots : le service jetait ensuite l'enregistrement, trop court.
    expect($prompt)->not->toContain('"passage": "A C1-level text')
        ->and($prompt)->toContain('audio_text (string: the SAME spoken passage of 60-100 words');
});

test('une ecoute que le lecteur remplacerait par le passage est refusee, puis recommencee', function () {
    [$exam, $type] = qegType('german', 'listening', 'mcq');
    $son = qegTexte(81);
    $avecSon = fn () => [qegQcm('q1') + ['audio_text' => $son], qegQcm('q2') + ['audio_text' => $son], qegQcm('q3') + ['audio_text' => $son]];
    qegFournisseur($this, [
        qegReponse($avecSon(), ['passage' => qegTexte(480)]),
        qegReponse($avecSon()),
    ]);

    $exercice = app(ExerciseGeneratorService::class)->generate($type, $exam, 'C1');

    // Ce qui a ete genere est ce qui est servi : le son reste celui de
    // l'enregistrement, et rien ne le remplace.
    $servie = $exercice->fresh()->answerableQuestions()[0];
    expect($exercice->content)->not->toHaveKey('passage')
        ->and($servie['audio_text'])->toBe($son);
});

test('les Sprachbausteine du telc se passent sur un texte a trous, pas sur des phrases isolees', function () {
    $this->seed(\Database\Seeders\LanguageSeeder::class);
    $this->seed(\Database\Seeders\ExamSeeder::class);
    $exam = Exam::where('slug', 'telc')->sole();
    $type = ExerciseType::whereHas('section', fn ($q) => $q->where('exam_id', $exam->id)->where('slug', 'sprachbausteine'))
        ->where('slug', 'mcq-cloze')->sole();
    expect($type->section->skill_type)->toBe('grammar');

    $trous = [];
    $lettre = 'Liebe Anna,';
    for ($i = 1; $i <= 10; $i++) {
        $lettre .= " Satz ({$i})___ hier.";
        $trous[] = ['id' => "q{$i}", 'type' => 'gap-fill', 'text' => "Satz ___ hier ({$i}).",
            'options' => ['dem', 'den', 'der'], 'correct_answer' => 'B', 'explanation' => 'Akkusativ nach für.'];
    }
    $prompts = [];
    qegFournisseur($this, [qegReponse($trous, ['passage' => $lettre])], $prompts);

    $exercice = app(ExerciseGeneratorService::class)->generate($type, $exam, 'B1');

    // La lettre a trous est gardee : c'est l'epreuve. Et le prompt ne donne
    // plus deux consignes contraires.
    expect($exercice->content['passage'])->toBe($lettre)
        ->and($prompts[0])->toContain('TEXT WITH GAPS')
        ->and($prompts[0])->not->toContain('Each item is a standalone sentence')
        ->and($prompts[0])->toContain('"passage": "The B1-level text');
});

test('une section de grammaire en QCM isole reste sans texte (TCF)', function () {
    $this->seed(\Database\Seeders\LanguageSeeder::class);
    $this->seed(\Database\Seeders\ExamSeeder::class);
    $exam = Exam::where('slug', 'tcf')->sole();
    $type = ExerciseType::whereHas('section', fn ($q) => $q->where('exam_id', $exam->id)->where('slug', 'maitrise-structures'))
        ->where('slug', 'grammar-mcq')->sole();

    $methode = new ReflectionMethod(ExerciseGeneratorService::class, 'buildPrompt');
    $methode->setAccessible(true);
    $prompt = $methode->invoke(app(ExerciseGeneratorService::class), $type, $exam, 'B1', 'French', 'Français');

    expect($prompt)->toContain('Each item is a standalone sentence')
        ->and($prompt)->not->toContain('TEXT WITH GAPS')
        ->and($prompt)->not->toContain('"passage": "');
});

test('une partie Ja / Nein se joue en Ja / Nein, les autres vrai/faux du meme examen restent en Richtig / Falsch', function () {
    $this->seed(\Database\Seeders\LanguageSeeder::class);
    $this->seed(\Database\Seeders\ExamSeeder::class);
    $structure = app(\App\Services\Content\StructureOfficielle::class);
    $libelles = new ReflectionMethod(ExerciseGeneratorService::class, 'libellesVraiFaux');
    $libelles->setAccessible(true);
    $service = app(ExerciseGeneratorService::class);

    $goethe = Exam::where('slug', 'goethe')->sole();
    $vraiFaux = ExerciseType::whereHas('section', fn ($q) => $q->where('exam_id', $goethe->id)->where('slug', 'lesen'))
        ->where('slug', 'true-false')->sole();
    $teil4 = $structure->partiePour($goethe, 'B1', $vraiFaux, 4);
    $teil1 = $structure->partiePour($goethe, 'B1', $vraiFaux, 1);

    $osd = Exam::where('slug', 'osd')->sole();
    $osdVraiFaux = ExerciseType::whereHas('section', fn ($q) => $q->where('exam_id', $osd->id)->where('slug', 'lesen'))
        ->where('slug', 'true-false')->sole();
    $aufgabe2 = $structure->partiePour($osd, 'A1', $osdVraiFaux, 2);

    expect($teil4)->not->toBeNull()
        ->and($libelles->invoke($service, $vraiFaux, $goethe, $teil4))->toBe(['Ja', 'Nein'])
        ->and($libelles->invoke($service, $vraiFaux, $goethe, $teil1))->toBe(['Richtig', 'Falsch'])
        ->and($libelles->invoke($service, $osdVraiFaux, $osd, $aufgabe2))->toBe(['Ja', 'Nein']);

    // A la generation, une reponse « Richtig » devient « Ja ».
    $prompts = [];
    qegFournisseur($this, [qegReponse(array_map(
        fn ($i) => qegAffirmation("q{$i}", $i % 2 ? 'Richtig' : 'Nein'), range(1, 7)
    ), ['passage' => qegTexte(250)])], $prompts);
    $exercice = app(ExerciseGeneratorService::class)->generate($vraiFaux, $goethe, 'B1', null, $teil4);

    expect($exercice->questions[0]['correct_answer'])->toBe('Ja')
        ->and($exercice->questions[1]['correct_answer'])->toBe('Nein')
        ->and($exercice->questions[0]['answer_options'])->toBe(['Ja', 'Nein'])
        ->and($prompts[0])->toContain('Ja / Nein')
        ->and($prompts[0])->toContain('answered YES or NO');
});

test('des explications ecrites dans la langue de l examen au lieu de celle de l apprenant sont refusees', function () {
    [$exam, $type] = qegType();
    $enAllemand = 'Der Text sagt, dass Anna in Bonn wohnt und nicht in Berlin, weil sie dort arbeitet.';
    $enFrancais = 'Le texte dit que Anna habite à Bonn et pas à Berlin.';
    $serie = fn (string $explication) => qegReponse(
        [qegQcm('q1', explication: $explication), qegQcm('q2', explication: $explication), qegQcm('q3', explication: $explication)],
        ['passage' => qegTexte(100)]
    );
    qegFournisseur($this, [$serie($enAllemand), $serie($enFrancais)]);

    $exercice = app(ExerciseGeneratorService::class)->generate($type, $exam, 'A1');

    expect($exercice->questions[0]['explanation'])->toBe($enFrancais)
        ->and($exercice->content['explanation_language'])->toBe('Français');
});

test('le vivier ne sert pas a un apprenant un exercice explique dans la langue d un autre', function () {
    [$exam, $type] = qegType();
    $creer = fn (string $niveau, ?string $langue) => Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'difficulty' => $niveau, 'is_ai_generated' => true,
        'content' => array_filter(['instructions' => 'Lies.', 'explanation_language' => $langue]),
        'questions' => [qegQcm('q1')],
    ]);
    $espagnol = $creer('A1', 'Español');
    $francais = $creer('A1', 'Français');
    $ancien = $creer('A1', null);
    $b1 = $creer('B1', 'German');

    expect(Exercise::expliqueDansLaLangueDe('Français')->pluck('id')->all())
        ->toEqualCanonicalizing([$francais->id, $ancien->id, $b1->id])
        ->and(Exercise::expliqueDansLaLangueDe('Español')->pluck('id')->all())->toContain($espagnol->id);
});

test('l entrainement par type ne sert pas a un francophone l exercice explique en espagnol', function () {
    [$exam, $type] = qegType();
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'target_exam_id' => $exam->id, 'current_level' => 'A2', 'native_language' => 'Français', 'onboarding_completed_at' => now(),
    ]);
    // La serie preparee passe d'abord jusqu'au A2 : on la suppose faite.
    $serie = app(\App\Services\Content\StarterPracticeLibrary::class)->ensure($exam, $type, 'A2');
    if ($serie) {
        \App\Models\UserExerciseAttempt::create([
            'user_id' => $user->id, 'exercise_id' => $serie->id, 'answers' => [], 'score' => 1, 'accuracy_percent' => 100,
        ]);
    }
    $creer = fn (string $langue) => Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'difficulty' => 'A2', 'is_ai_generated' => true,
        'content' => ['instructions' => 'Lies.', 'explanation_language' => $langue],
        'questions' => [qegQcm('q1')],
    ]);
    $creer('Español');
    $francais = $creer('Français');
    $this->mock(ExerciseGeneratorService::class, fn ($mock) => $mock->shouldNotReceive('generate'));

    $this->actingAs($user)
        ->get(route('practice.drill.type', [$exam, $type]))
        ->assertRedirect(route('exercise.show', $francais));
});
