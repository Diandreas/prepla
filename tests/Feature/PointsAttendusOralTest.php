<?php

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Services\AI\ExerciseGeneratorService;
use App\Services\AI\MistralEvaluationService;
use Illuminate\Support\Facades\Http;

/**
 * Une épreuve orale ne se corrige pas « au feeling » : l'examinateur telc coche.
 * À la Vorstellung, il vérifie que le candidat a dit son nom, d'où il vient, ce
 * qu'il fait.
 *
 * L'évaluation savait déjà faire exactement cela — `expectedPoints` en entrée,
 * `covered_points` / `missing_points` en sortie — et le service de notation les
 * transmettait. Mais le générateur n'en produisait aucun : zéro question orale
 * en base n'en portait. L'IA jugeait donc librement une épreuve qui se corrige
 * par une liste, et l'apprenant ne savait jamais ce qu'il avait OUBLIÉ de dire.
 */
function promptOralPour(string $composant): string
{
    $langue = Language::firstOrCreate(['slug' => 'german'],
        ['name' => 'Allemand', 'native_name' => 'Deutsch', 'flag' => 'de', 'is_active' => true]);
    $exam = Exam::firstOrCreate(['slug' => 'oral-points'], ['language_id' => $langue->id, 'name' => 'telc Deutsch']);
    $section = ExamSection::firstOrCreate(['exam_id' => $exam->id, 'slug' => 'sprechen-'.$composant],
        ['name' => 'Sprechen', 'skill_type' => 'speaking', 'time_limit' => 15]);
    $type = ExerciseType::firstOrCreate(['section_id' => $section->id, 'slug' => $composant],
        ['name' => $composant, 'skill_type' => 'speaking', 'component_key' => $composant]);

    $methode = new ReflectionMethod(ExerciseGeneratorService::class, 'buildPrompt');
    $methode->setAccessible(true);

    return $methode->invoke(app(ExerciseGeneratorService::class), $type, $exam, 'B1', 'Allemand', 'Deutsch', null);
}

test('une tache orale reclame des points a cocher', function () {
    $prompt = promptOralPour('speaking-recorder');

    expect($prompt)->toContain('expected_points')
        ->and($prompt)->toContain('EXPECTED POINTS')
        // Ils doivent etre verifiables a l'ecoute, sinon on ne coche rien.
        ->and($prompt)->toContain('verifiable from the recording');
});

test('la presentation de soi nomme ce qu il faut couvrir', function () {
    // La Vorstellung a des attendus connus : sans eux, le modele invente.
    expect(promptOralPour('speaking-recorder'))->toContain('self-introduction');
});

test('le jeu de role aussi, puisque la consigne impose des points', function () {
    $prompt = promptOralPour('role-play');

    expect($prompt)->toContain('expected_points')
        ->and($prompt)->toContain('EXPECTED POINTS');
});

test('un exercice ecrit ne recoit pas cette consigne', function () {
    $langue = Language::firstOrCreate(['slug' => 'german'],
        ['name' => 'Allemand', 'native_name' => 'Deutsch', 'flag' => 'de', 'is_active' => true]);
    $exam = Exam::firstOrCreate(['slug' => 'oral-points'], ['language_id' => $langue->id, 'name' => 'telc Deutsch']);
    $section = ExamSection::firstOrCreate(['exam_id' => $exam->id, 'slug' => 'lesen'],
        ['name' => 'Lesen', 'skill_type' => 'reading', 'time_limit' => 60]);
    $type = ExerciseType::firstOrCreate(['section_id' => $section->id, 'slug' => 'mcq'],
        ['name' => 'QCM', 'skill_type' => 'reading', 'component_key' => 'mcq']);

    $methode = new ReflectionMethod(ExerciseGeneratorService::class, 'buildPrompt');
    $methode->setAccessible(true);
    $prompt = $methode->invoke(app(ExerciseGeneratorService::class), $type, $exam, 'B1', 'Allemand', 'Deutsch', null);

    expect($prompt)->not->toContain('EXPECTED POINTS');
});

test('l evaluation dit ce que l apprenant a oublie de dire', function () {
    Http::fake(['api.mistral.ai/*' => Http::response(['choices' => [['message' => ['content' => json_encode([
        'isCorrect' => false,
        'accuracy' => 55,
        'covered_points' => ['dit son nom'],
        'missing_points' => ['dit d’où il vient', 'dit ce qu’il fait'],
        'explanation' => ['concept' => 'Présente-toi en trois informations.', 'evidence' => '', 'hint' => 'Nom, origine, métier.'],
    ])]]]], 200)]);

    $resultat = app(MistralEvaluationService::class)->evaluateSpeaking(
        'Stell dich vor.', 'Ich heiße Awa.', 'German',
        ['dit son nom', 'dit d’où il vient', 'dit ce qu’il fait'], 'B1'
    );

    // C'est ce qui manque qui fait perdre des points le jour de l'examen.
    expect($resultat['missing_points'])->toHaveCount(2)
        ->and($resultat['covered_points'])->toBe(['dit son nom']);
});

test('les points attendus sont bien transmis a l IA', function () {
    Http::fake(['api.mistral.ai/*' => Http::response(['choices' => [['message' => ['content' =>
        json_encode(['isCorrect' => true, 'accuracy' => 90])]]]], 200)]);

    app(MistralEvaluationService::class)->evaluateSpeaking(
        'Stell dich vor.', 'Ich heiße Awa.', 'German', ['dit son nom', 'dit son métier'], 'B1'
    );

    Http::assertSent(function ($requete) {
        $corps = json_encode($requete->data(), JSON_UNESCAPED_UNICODE);

        return str_contains($corps, 'dit son nom') && str_contains($corps, 'dit son métier');
    });
});
