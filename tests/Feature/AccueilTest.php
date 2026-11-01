<?php

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Support\VideoAccueil;

/**
 * La page d'accueil : la video du haut de page, l'essai sans compte des le
 * premier ecran, et rien d'invente.
 *
 * Elle annoncait « 8 examens » quand il y en avait 10, « des milliers
 * d'apprenants » que rien n'etablissait, un prix en euros pour un abonnement
 * facture en dollars, et montrait un exercice « en direct » qui se repondait
 * tout seul.
 */
beforeEach(fn () => $this->withoutVite());

function exerciceOuvertALEssai(): Exercise
{
    $langue = Language::firstOrCreate(['slug' => 'german'], ['name' => 'Allemand', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::firstOrCreate(['slug' => 'goethe'], ['language_id' => $langue->id, 'name' => 'Goethe-Zertifikat']);
    $section = ExamSection::firstOrCreate(['exam_id' => $exam->id, 'slug' => 'lesen'], ['name' => 'Lesen', 'skill_type' => 'reading']);
    $type = ExerciseType::firstOrCreate(['section_id' => $section->id, 'slug' => 'mcq'], ['name' => 'QCM', 'skill_type' => 'reading', 'component_key' => 'mcq']);

    return Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'exam_section_id' => $section->id,
        'difficulty' => 'B1', 'is_ai_generated' => false,
        'content' => ['source' => 'epreuve-verifiee', 'title' => 'Goethe B1 · Lesen, Teil 2', 'passage' => 'Anna wohnt in Wien.'],
        'questions' => [
            ['id' => 'q1', 'type' => 'mcq', 'text' => 'Wo wohnt Anna?', 'options' => ['In Berlin', 'In Wien', 'In Bern'], 'correct_answer' => 'B', 'explanation' => 'Le texte dit « Anna wohnt in Wien ».'],
            ['id' => 'q2', 'type' => 'mcq', 'text' => 'Wer wohnt in Wien?', 'options' => ['Anna', 'Paul', 'Lena'], 'correct_answer' => 'A', 'explanation' => '« Anna wohnt in Wien ».'],
        ],
    ]);
}

test('chaque fichier de la video existe, avec le poids annonce au visiteur', function () {
    $video = VideoAccueil::donnees();

    expect($video)->not->toBeNull()
        ->and($video['duree'])->toBe(34);

    foreach ($video['sources'] as $cle => $adresse) {
        $chemin = public_path(ltrim(strtok($adresse, '?'), '/'));
        expect(is_file($chemin))->toBeTrue("{$cle} : {$chemin} manque")
            ->and(filesize($chemin))->toBe($video['poids'][$cle]);
    }

    // L'apercu en boucle reste leger : il demarre sans que personne l'ait demande.
    expect($video['poids']['boucle_640'])->toBeLessThan(150_000);
});

test('la page d accueil donne la video, les dix examens et les vrais exercices d essai', function () {
    $exercice = exerciceOuvertALEssai();

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('welcome')
            ->where('video.duree', 34)
            ->has('languages', 3)
            ->where('languages', fn ($langues) => collect($langues)->sum(fn ($l) => count($l['exams'])) === 10)
            ->where('essai.0.examens.0.exercices.0.id', $exercice->id)
            // Les prix sont lus chez Stripe en differe : ils ne retardent pas la page.
            ->missing('tarifs'));
});

test('les prix differes sont ceux que facture Stripe, dans sa monnaie', function () {
    config(['cashier.secret' => null, 'cashier.currency' => 'usd']);

    $this->get('/')
        ->assertInertia(fn ($page) => $page
            ->loadDeferredProps(fn ($recharge) => $recharge
                ->where('tarifs.mensuel.amount', 9.99)
                ->where('tarifs.mensuel.currency', 'usd')
                ->where('tarifs.annuel.amount', 79.99)));
});

test('l en-tete de l accueil annonce le dollar et declare la video', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('"priceCurrency": "USD"')
        ->not->toContain('"priceCurrency": "EUR"')
        ->toContain('"@type": "VideoObject"')
        // L'affiche, plus grand element du haut de page, est chargee en priorite.
        ->toContain('rel="preload" as="image"');
});

test('aucun texte de la page d accueil n affirme ce que rien n etablit', function () {
    foreach (['fr', 'en', 'de'] as $langue) {
        $textes = json_decode(file_get_contents(resource_path("js/locales/{$langue}/translation.json")), true)['landing'];
        $tout = implode("\n", $textes);

        expect($tout)->not->toMatch('/milliers|thousands|tausende/i')
            ->not->toContain('∞')
            ->not->toContain('€')
            ->not->toMatch('/\b8\s+(examens|exams|prüfungen|certifications)/iu');
    }
});

test('les textes de l accueil existent dans les trois langues', function () {
    $cles = fn (string $langue) => array_keys(json_decode(file_get_contents(resource_path("js/locales/{$langue}/translation.json")), true)['landing']);

    expect($cles('en'))->toEqualCanonicalizing($cles('fr'))
        ->and($cles('de'))->toEqualCanonicalizing($cles('fr'));
});

test('depuis l accueil, un examen ouvre la page d essai sur cet examen', function () {
    exerciceOuvertALEssai();

    $this->get('/essayer?examen=goethe')
        ->assertInertia(fn ($page) => $page->where('langueChoisie', 'german')->where('examenChoisi', 'goethe'));

    // Un examen inconnu est ignore, sans erreur.
    $this->get('/essayer?examen=inconnu')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('examenChoisi', null));
});
