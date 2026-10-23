<?php

use App\Models\Exam;
use App\Models\ExamBlueprint;
use App\Models\ExamSection;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Services\AI\ExerciseGeneratorService;

/**
 * Plusieurs parties d'un meme module partagent souvent le meme format de
 * reponse : au telc, les trois parties de Hörverstehen sont toutes en
 * vrai/faux. Elles different par leur support et leur nombre d'items — un
 * journal radio de cinq phrases, un entretien de dix, cinq annonces — mais le
 * type d'exercice, lui, est le meme.
 *
 * L'apprenant cliquait donc sur « Teil 2 » et recevait un exercice
 * indistinguable du « Teil 1 » : 128 parties sur 713 partageaient leur lien.
 */
function promptAvecPartie(?array $partie): string
{
    $langue = Language::firstOrCreate(['slug' => 'german'],
        ['name' => 'Allemand', 'native_name' => 'Deutsch', 'flag' => 'de', 'is_active' => true]);
    $exam = Exam::firstOrCreate(['slug' => 'telc-partie'], ['language_id' => $langue->id, 'name' => 'telc Deutsch']);
    $section = ExamSection::firstOrCreate(['exam_id' => $exam->id, 'slug' => 'hoeren'],
        ['name' => 'Hörverstehen', 'skill_type' => 'listening', 'time_limit' => 30]);
    $type = ExerciseType::firstOrCreate(['section_id' => $section->id, 'slug' => 'true-false'],
        ['name' => 'Vrai/Faux', 'skill_type' => 'listening', 'component_key' => 'true-false-ng']);

    $methode = new ReflectionMethod(ExerciseGeneratorService::class, 'buildPrompt');
    $methode->setAccessible(true);

    return $methode->invoke(app(ExerciseGeneratorService::class), $type, $exam, 'B1', 'Allemand', 'Deutsch', null, $partie);
}

test('la partie demandee est decrite a l IA', function () {
    $prompt = promptAvecPartie([
        'name' => 'Hörverstehen, Teil 2',
        'task' => 'Une interview ou un entretien radio : juger 10 affirmations vraies ou fausses.',
        'items' => 10,
        'part' => 2,
    ]);

    expect($prompt)->toContain('EXAM PART')
        ->and($prompt)->toContain('Hörverstehen, Teil 2')
        ->and($prompt)->toContain('Une interview ou un entretien radio')
        ->and($prompt)->toContain('Produce exactly 10 items')
        // La mise en garde qui empeche de rendre la partie voisine.
        ->and($prompt)->toContain('DIFFERENT');
});

test('sans partie demandee le prompt reste celui d un exercice libre', function () {
    expect(promptAvecPartie(null))->not->toContain('EXAM PART');
});

test('une partie sans nombre d items n en impose aucun', function () {
    $prompt = promptAvecPartie(['name' => 'Sprechen, Teil 1', 'task' => 'Faire connaissance.', 'items' => 1, 'part' => 1]);

    // Une tache unique ne doit pas se voir imposer « exactly 1 items ».
    expect($prompt)->toContain('Sprechen, Teil 1')
        ->and($prompt)->not->toContain('Produce exactly');
});

test('le lien d une partie ne peut pas faire passer une partie pour une autre', function () {
    $langue = Language::create(['slug' => 'german', 'name' => 'Allemand', 'native_name' => 'Deutsch', 'flag' => 'de', 'is_active' => true]);
    $exam = Exam::create(['language_id' => $langue->id, 'slug' => 'telc-lien', 'name' => 'telc Deutsch']);
    $section = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'hoeren', 'name' => 'Hörverstehen', 'skill_type' => 'listening', 'time_limit' => 30]);
    $type = ExerciseType::create(['section_id' => $section->id, 'slug' => 'true-false', 'name' => 'Vrai/Faux', 'skill_type' => 'listening', 'component_key' => 'true-false-ng']);
    $autre = ExerciseType::create(['section_id' => $section->id, 'slug' => 'mcq', 'name' => 'QCM', 'skill_type' => 'listening', 'component_key' => 'mcq']);

    ExamBlueprint::create([
        'exam_id' => $exam->id, 'level' => 'B1', 'variant' => null,
        'name' => 'telc B1', 'total_duration_minutes' => 150, 'scoring_config' => [],
        'sections_config' => [['slug' => 'hoeren', 'name' => 'Hörverstehen', 'skill_type' => 'listening',
            'parts' => [
                ['part' => 1, 'name' => 'Hörverstehen, Teil 1', 'type' => 'true-false', 'source' => 'tache'],
                ['part' => 2, 'name' => 'Hörverstehen, Teil 2', 'type' => 'true-false', 'source' => 'tache'],
            ]]],
    ]);

    $controleur = new ReflectionMethod(\App\Http\Controllers\PracticeController::class, 'partieDemandee');
    $controleur->setAccessible(true);
    $instance = app(\App\Http\Controllers\PracticeController::class);

    request()->merge(['partie' => 2]);
    expect($controleur->invoke($instance, $exam, $type, 'B1')['name'])->toBe('Hörverstehen, Teil 2');

    // Le numero ne correspond a aucune partie de CE type : on ne devine pas.
    expect($controleur->invoke($instance, $exam, $autre, 'B1'))->toBeNull();

    request()->merge(['partie' => 99]);
    expect($controleur->invoke($instance, $exam, $type, 'B1'))->toBeNull();

    request()->merge(['partie' => null]);
    expect($controleur->invoke($instance, $exam, $type, 'B1'))->toBeNull();
});
