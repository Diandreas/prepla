<?php

use App\Services\AI\GrilleOfficielleEcrit;
use App\Services\AI\MistralService;
use App\Services\AI\PlacementTestService;
use App\Services\AI\WritingCorrectorService;

/**
 * Le test de placement mesure aussi la comprehension ecrite et orale, du A0
 * au C2 ; la redaction est notee dans l'echelle de l'examen vise.
 */
function secoursDuPlacement(string $langue): array
{
    $service = app(PlacementTestService::class);
    $methode = new ReflectionMethod($service, 'getFallbackFullTest');
    $methode->setAccessible(true);

    return $methode->invoke($service, $langue, 'Examen');
}

/** Les bonnes reponses de toutes les questions jusqu'a un palier inclus. */
function reponsesJusqua(array $questions, string $palier): array
{
    $rang = array_search($palier, PlacementTestService::PALIERS, true);
    $reponses = [];
    foreach ($questions as $q) {
        $reponses[$q['id']] = array_search($q['level'], PlacementTestService::PALIERS, true) <= $rang
            ? $q['correct_answer']
            : 'Z';
    }

    return $reponses;
}

test('le test de secours contient de la lecture et de l ecoute, valides et dans la langue visee', function () {
    foreach (['Anglais' => 'Sarah', 'Français' => 'Désolée', 'Allemand' => 'Kino'] as $langue => $indice) {
        $test = secoursDuPlacement($langue);
        $questions = $test['section_b']['questions'];

        $lecture = array_values(array_filter($questions, fn ($q) => ($q['skill'] ?? null) === 'reading'));
        $ecoute = array_values(array_filter($questions, fn ($q) => ($q['skill'] ?? null) === 'listening'));

        expect($lecture)->toHaveCount(3, $langue)
            ->and($ecoute)->toHaveCount(3, $langue)
            ->and($ecoute[0]['audio_text'])->toContain($indice);

        // Chaque item est un QCM sain : options distinctes, bonne reponse dedans.
        foreach (array_merge($test['section_a'], $questions) as $q) {
            expect(count(array_unique($q['options'])))->toBe(count($q['options']), $q['id'])
                ->and(ord($q['correct_answer']) - 65)->toBeLessThan(count($q['options']))
                ->and(PlacementTestService::PALIERS)->toContain($q['level']);
        }

        // Les identifiants ne se chevauchent pas : les reponses sont rangees par id.
        $ids = array_column(array_merge($test['section_a'], $questions), 'id');
        expect(count(array_unique($ids)))->toBe(count($ids));
    }
});

test('le placement va du A0 au C2, palier par palier', function () {
    $test = secoursDuPlacement('Anglais');
    $questions = array_merge($test['section_a'], $test['section_b']['questions']);
    $service = app(PlacementTestService::class);
    $copieLongue = str_repeat('word ', 120);

    expect($service->evaluateFullLevel($questions, [], ''))->toBe('A0');
    expect($service->evaluateFullLevel($questions, reponsesJusqua($questions, 'A1'), $copieLongue))->toBe('A1');
    expect($service->evaluateFullLevel($questions, reponsesJusqua($questions, 'B2'), $copieLongue))->toBe('B2');
    expect($service->evaluateFullLevel($questions, reponsesJusqua($questions, 'C2'), $copieLongue))->toBe('C2');
});

test('un palier rate arrete la montee, meme si la suite est juste', function () {
    $test = secoursDuPlacement('Allemand');
    $questions = array_merge($test['section_a'], $test['section_b']['questions']);
    $reponses = reponsesJusqua($questions, 'C2');
    foreach ($questions as $q) {
        if ($q['level'] === 'B1') {
            $reponses[$q['id']] = 'Z';
        }
    }

    expect(app(PlacementTestService::class)->evaluateFullLevel($questions, $reponses, str_repeat('Wort ', 120)))->toBe('A2');
});

test('une copie trop courte plafonne le niveau', function () {
    $test = secoursDuPlacement('Français');
    $questions = array_merge($test['section_a'], $test['section_b']['questions']);
    $service = app(PlacementTestService::class);
    $toutJuste = reponsesJusqua($questions, 'C2');

    expect($service->evaluateFullLevel($questions, $toutJuste, ''))->toBe('A2')
        ->and($service->evaluateFullLevel($questions, $toutJuste, str_repeat('mot ', 50)))->toBe('B1');
});

test('une ecoute de l IA mal formee est remplacee par celle de secours', function () {
    $ia = [
        'section_a' => array_map(fn ($i) => [
            'id' => "a{$i}", 'level' => $i === 1 ? 'pre-A1' : 'B1', 'text' => 'Q',
            'sentence' => 'x', 'options' => ['un', 'deux', 'trois', 'quatre'], 'correct_answer' => 'A',
        ], range(1, 8)),
        'section_b' => ['passage' => 'Texte.', 'questions' => [
            ['id' => 'b1', 'level' => 'A2', 'text' => 'Q', 'options' => ['a', 'b'], 'correct_answer' => 'A'],
            ['id' => 'b2', 'level' => 'B1', 'text' => 'Q', 'options' => ['c', 'd'], 'correct_answer' => 'B'],
        ]],
        'listening' => ['audio_text' => 'Trop court.', 'questions' => []],
        'section_c' => ['prompt' => 'Écris.'],
    ];
    $mistral = Mockery::mock(MistralService::class);
    $mistral->shouldReceive('chat')->andReturn(json_encode($ia));

    $test = (new PlacementTestService($mistral))->generateFullTest('Français', 'Français', 'DELF', 'Anglais');

    $ecoute = array_values(array_filter($test['section_b']['questions'], fn ($q) => ($q['skill'] ?? null) === 'listening'));
    expect($ecoute)->toHaveCount(3)
        ->and($ecoute[0]['audio_text'])->toContain('Sarah')
        ->and($test['section_a'][0]['level'])->toBe('A0');
});

test('une ecoute correcte de l IA est gardee avec son audio_text', function () {
    $son = 'Bonjour, ici la gare de Lyon. Le train pour Paris partira avec vingt minutes de retard à cause de la neige. Merci de votre patience et bon voyage à tous.';
    $ia = [
        'section_a' => array_map(fn ($i) => [
            'id' => "a{$i}", 'level' => 'A1', 'text' => 'Q', 'sentence' => 'x',
            'options' => ['un', 'deux', 'trois', 'quatre'], 'correct_answer' => 'A',
        ], range(1, 8)),
        'section_b' => ['passage' => 'Texte.', 'questions' => [
            ['id' => 'b1', 'level' => 'A2', 'text' => 'Q', 'options' => ['a', 'b'], 'correct_answer' => 'A'],
            ['id' => 'b2', 'level' => 'B1', 'text' => 'Q', 'options' => ['c', 'd'], 'correct_answer' => 'B'],
        ]],
        'listening' => ['audio_text' => $son, 'questions' => [
            ['id' => 'x', 'level' => 'A1', 'text' => 'Où ?', 'options' => ['Gare', 'Aéroport'], 'correct_answer' => 'A'],
            ['id' => 'y', 'level' => 'A2', 'text' => 'Retard ?', 'options' => ['10 min', '20 min'], 'correct_answer' => 'B'],
        ]],
        'section_c' => ['prompt' => 'Écris.'],
    ];
    $mistral = Mockery::mock(MistralService::class);
    $mistral->shouldReceive('chat')->andReturn(json_encode($ia));

    $test = (new PlacementTestService($mistral))->generateFullTest('Français', 'Français', 'DELF', 'Anglais');
    $ecoute = array_values(array_filter($test['section_b']['questions'], fn ($q) => ($q['skill'] ?? null) === 'listening'));

    expect($ecoute)->toHaveCount(2)
        ->and($ecoute[0]['audio_text'])->toBe($son)
        ->and(array_column($ecoute, 'id'))->toBe(['l1', 'l2']);
});

// ─── Grilles officielles de l'ecrit ─────────────────────────────────────────

$bandes = ['task_achievement' => 9, 'coherence_cohesion' => 4.5, 'lexical_resource' => 6, 'grammar_accuracy' => 3];

test('DELF et DALF : une note sur 25 et quatre criteres', function () use ($bandes) {
    $grille = GrilleOfficielleEcrit::calculer('DELF B1', 5.6, $bandes);

    // 9 + 2 + 4 + 2 = 17 sur 25
    expect($grille['note'])->toBe(17.0)
        ->and($grille['note_max'])->toBe(25)
        ->and($grille['affichage'])->toBe('17 / 25')
        ->and($grille['criteres'])->toHaveCount(4)
        ->and($grille['mention'])->toBe('Estimation, pas une note officielle.');
    expect(GrilleOfficielleEcrit::calculer('DALF C1', 5, $bandes)['examen'])->toBe('DELF / DALF');
});

test('TCF et TEF : une echelle par niveaux', function () {
    $tcf = GrilleOfficielleEcrit::calculer('TCF Canada', 4.5, []);
    expect($tcf['note'])->toBe(10)->and($tcf['niveau'])->toBe('B2')->and($tcf['affichage'])->toBe('10 / 20 · niveau B2');

    $tef = GrilleOfficielleEcrit::calculer('TEF Canada', 6.3, []);
    expect($tef['note'])->toBe(315)->and($tef['niveau'])->toBe('B1')->and($tef['note_max'])->toBe(450);
});

test('Goethe et telc : Erfuellung, Kohaerenz, Wortschatz, Strukturen', function () use ($bandes) {
    $grille = GrilleOfficielleEcrit::calculer('telc Deutsch B1', 5.6, $bandes);

    expect($grille['examen'])->toBe('telc')
        ->and(array_column($grille['criteres'], 'nom'))->toBe(['Erfüllung (Aufgabenerfüllung)', 'Kohärenz', 'Wortschatz', 'Strukturen'])
        ->and(array_column($grille['criteres'], 'valeur'))->toBe(['A', 'D', 'C', 'D'])
        ->and($grille['note'])->toBe(63)
        ->and(GrilleOfficielleEcrit::calculer('Goethe-Zertifikat', 5, $bandes)['examen'])->toBe('Goethe');
});

test('IELTS : des bandes de 0 a 9 sur les quatre criteres officiels', function () use ($bandes) {
    $grille = GrilleOfficielleEcrit::calculer('IELTS Academic', 5.6, $bandes);

    expect(array_column($grille['criteres'], 'nom'))->toBe(['Task Achievement', 'Coherence and Cohesion', 'Lexical Resource', 'Grammatical Range and Accuracy'])
        ->and($grille['criteres'][1]['valeur'])->toBe('Band 4,5')
        ->and($grille['note'])->toBe(5.5)
        ->and($grille['affichage'])->toBe('Band 5,5');
});

test('un examen sans grille connue n a pas de note officielle estimee', function () {
    expect(GrilleOfficielleEcrit::calculer('language proficiency', 6, []))->toBeNull();
});

test('le correcteur ajoute la grille et garde la note 0-9 pour les statistiques', function () use ($bandes) {
    $mistral = Mockery::mock(MistralService::class);
    $mistral->shouldReceive('chat')->once()->andReturn(json_encode([
        'score' => 5.6, 'band_scores' => $bandes, 'corrections' => [], 'feedback' => 'Bien.',
    ]));

    $resultat = (new WritingCorrectorService($mistral))->correct('Je suis content.', 'Raconte.', 'DELF B1');

    expect($resultat['score'])->toBe(5.6)
        ->and($resultat['grille_officielle']['affichage'])->toBe('17 / 25')
        ->and($resultat['grille_officielle']['mention'])->toContain('pas une note officielle');
});
