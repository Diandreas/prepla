<?php

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Services\AI\ExerciseGeneratorService;

/**
 * Le prompt se terminait toujours par deux longs blocs « CRITICAL » sur les
 * options et sur les grilles de reponses, meme pour un expose oral qui n'a ni
 * l'un ni l'autre. Mistral les suivait : interroge sur « speaking-long-turn »,
 * il repondait « Sie hören gleich einen Text » — un QCM d'ecoute. Le validateur
 * refusait alors toute la fournee, et aucun exercice oral ne pouvait entrer
 * dans le vivier : les quatre types de Sprechen du telc B1 echouaient a chaque
 * essai.
 */
function promptPour(string $composant, string $skill = 'speaking'): string
{
    $langue = Language::firstOrCreate(['slug' => 'german'],
        ['name' => 'Allemand', 'native_name' => 'Deutsch', 'flag' => 'de', 'is_active' => true]);
    $exam = Exam::firstOrCreate(['slug' => 'telc-prompt'],
        ['language_id' => $langue->id, 'name' => 'telc Deutsch']);
    $section = ExamSection::firstOrCreate(
        ['exam_id' => $exam->id, 'slug' => 'section-'.$composant],
        ['name' => 'section', 'skill_type' => $skill, 'time_limit' => 20]
    );
    $type = ExerciseType::firstOrCreate(
        ['section_id' => $section->id, 'slug' => $composant],
        ['name' => $composant, 'skill_type' => $skill, 'component_key' => $composant]
    );

    $methode = new ReflectionMethod(ExerciseGeneratorService::class, 'buildPrompt');
    $methode->setAccessible(true);

    return $methode->invoke(app(ExerciseGeneratorService::class), $type, $exam, 'B1', 'Allemand', 'Deutsch', null);
}

test('un expose oral ne recoit aucune consigne sur les options', function () {
    $prompt = promptPour('speaking-recorder');

    expect($prompt)->not->toContain('CRITICAL FOR ANY QUESTION WITH "options"')
        ->and($prompt)->not->toContain('CRITICAL FOR MULTI-FIELD TYPES')
        // Le reste du prompt doit rester intact.
        ->and($prompt)->toContain('speaking-recorder')
        ->and($prompt)->toContain('CEFR B1');
});

test('un jeu de role non plus', function () {
    expect(promptPour('role-play'))
        ->not->toContain('CRITICAL FOR ANY QUESTION WITH "options"');
});

test('un QCM recoit bien la consigne sur les options', function () {
    $prompt = promptPour('mcq', 'reading');

    expect($prompt)->toContain('CRITICAL FOR ANY QUESTION WITH "options"')
        ->and($prompt)->not->toContain('CRITICAL FOR MULTI-FIELD TYPES');
});

test('un exercice a champs multiples recoit la consigne sur la grille', function () {
    $prompt = promptPour('summary-completion', 'reading');

    expect($prompt)->toContain('CRITICAL FOR MULTI-FIELD TYPES')
        ->and($prompt)->toContain('word_list');
});

test('une tache orale interdit explicitement le QCM', function () {
    // Rien ne l'interdisait, et la directive orale ne valait qu'aux niveaux
    // debutants : au B1, Mistral rendait cinq questions de comprehension ecrite.
    $prompt = promptPour('speaking-recorder');

    expect($prompt)->toContain('THIS IS A SPEAKING TASK')
        ->and($prompt)->toContain('EXACTLY ONE question object')
        ->and($prompt)->toContain('NEVER include an "options" array');
});

test('un jeu de role recoit la meme interdiction', function () {
    expect(promptPour('role-play'))->toContain('THIS IS A SPEAKING TASK');
});

test('un QCM de lecture ne recoit pas cette interdiction', function () {
    expect(promptPour('mcq', 'reading'))->not->toContain('THIS IS A SPEAKING TASK');
});
