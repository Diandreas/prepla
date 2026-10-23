<?php

use App\Models\Exam;
use App\Models\ExamBlueprint;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Services\Content\MockExamComposer;

/**
 * Le module de l'epreuve blanche etait monte en prenant n'importe quel format
 * convenable, dans un ordre arbitraire. Au telc B2, « Leseverstehen » pouvait
 * donc tomber en trois QCM, alors que l'epreuve enchaine une association de
 * titres, un QCM, puis une association d'annonces. Le niveau etait bon, la
 * nature et l'ordre des taches, non — et c'est precisement ce qu'un candidat
 * vient repeter.
 */
function examenAvecParties(): Exam
{
    $langue = Language::create(['slug' => 'german', 'name' => 'Allemand', 'native_name' => 'Deutsch', 'flag' => 'de', 'is_active' => true]);
    $exam = Exam::create(['language_id' => $langue->id, 'slug' => 'telc-sujet', 'name' => 'telc Deutsch']);

    $section = ExamSection::create([
        'exam_id' => $exam->id, 'slug' => 'lesen', 'name' => 'Leseverstehen',
        'skill_type' => 'reading', 'time_limit' => 90,
    ]);

    foreach (['matching-headings', 'mcq', 'matching'] as $slug) {
        $type = ExerciseType::create([
            'section_id' => $section->id, 'slug' => $slug, 'name' => $slug,
            'skill_type' => 'reading', 'component_key' => 'mcq',
        ]);
        // Deux exercices par format dans le vivier, pour que le compositeur ait
        // vraiment le choix et ne soit pas contraint par la penurie.
        foreach ([1, 2] as $n) {
            Exercise::create([
                'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'difficulty' => 'B2',
                'content' => ['title' => $slug.' '.$n],
                'questions' => [['id' => 'q1', 'type' => 'mcq', 'text' => 'x', 'options' => ['a', 'b'], 'correct_answer' => 'A']],
            ]);
        }
    }

    // Le compositeur refuse un sujet d'un seul module : un vrai examen en a
    // plusieurs. On en ajoute donc un second, sans parties declarees.
    $second = ExamSection::create([
        'exam_id' => $exam->id, 'slug' => 'hoeren', 'name' => 'Hörverstehen',
        'skill_type' => 'listening', 'time_limit' => 20,
    ]);
    $typeEcoute = ExerciseType::create([
        'section_id' => $second->id, 'slug' => 'true-false', 'name' => 'Vrai/Faux',
        'skill_type' => 'listening', 'component_key' => 'true-false-ng',
    ]);
    foreach (['B1', 'B2'] as $palier) {
        Exercise::create([
            'exam_id' => $exam->id, 'exercise_type_id' => $typeEcoute->id, 'difficulty' => $palier,
            'content' => ['title' => 'ecoute '.$palier, 'audio_text' => 'Guten Tag.'],
            'questions' => [['id' => 'q1', 'type' => 'true-false-ng', 'text' => 'x',
                'audio_url' => '/storage/tts/essai.mp3', 'options' => ['Richtig', 'Falsch'], 'correct_answer' => 'A']],
        ]);
    }

    ExamBlueprint::create([
        'exam_id' => $exam->id, 'level' => 'B2', 'variant' => null,
        'name' => 'telc Deutsch B2', 'total_duration_minutes' => 155, 'scoring_config' => [],
        'sections_config' => [[
            'slug' => 'lesen', 'name' => 'Leseverstehen', 'skill_type' => 'reading',
            'time_limit' => 90, 'task_count' => 3,
            'parts' => [
                ['part' => 1, 'name' => 'Leseverstehen, Teil 1', 'type' => 'matching-headings', 'items' => 5, 'points' => 25],
                ['part' => 2, 'name' => 'Leseverstehen, Teil 2', 'type' => 'mcq', 'items' => 5, 'points' => 25],
                ['part' => 3, 'name' => 'Leseverstehen, Teil 3', 'type' => 'matching', 'items' => 10, 'points' => 25],
            ],
        ], [
            'slug' => 'hoeren', 'name' => 'Hörverstehen', 'skill_type' => 'listening',
            'time_limit' => 20, 'task_count' => 1,
        ]],
    ]);

    return $exam;
}

test('le sujet enchaine les parties dans l ordre que l examen declare', function () {
    $exam = examenAvecParties();

    $sujet = app(MockExamComposer::class)->pour($exam, 'B2', false, true);

    expect($sujet)->not->toBeNull();

    $slugs = $sujet->exercises()->with('exerciseType.section')->get()
        ->sortBy('pivot.sort_order')
        ->filter(fn ($e) => $e->exerciseType?->section?->slug === 'lesen')
        ->pluck('exerciseType.slug')->values()->all();

    expect($slugs)->toBe(['matching-headings', 'mcq', 'matching']);
});

test('une partie sans exercice ne troue pas le sujet', function () {
    $exam = examenAvecParties();

    // On vide le vivier du format de la deuxieme partie : elle ne peut plus etre
    // servie telle quelle. Le sujet doit rester complet plutot que de perdre une
    // tache — un candidat prefere trois taches dont une approchee a deux taches.
    $mcq = ExerciseType::where('slug', 'mcq')->first();
    Exercise::where('exercise_type_id', $mcq->id)->delete();

    $sujet = app(MockExamComposer::class)->pour($exam, 'B2', false, true);

    expect($sujet)->not->toBeNull();

    $lesen = $sujet->exercises()->with('exerciseType.section')->get()
        ->filter(fn ($e) => $e->exerciseType?->section?->slug === 'lesen');

    expect($lesen)->toHaveCount(3);
});

test('un niveau sans parties declarees compose comme avant', function () {
    $exam = examenAvecParties();

    // Le plan du B1 ne decrit aucune partie : le compositeur garde son choix
    // libre, et ne doit pas se bloquer faute de structure.
    ExamBlueprint::create([
        'exam_id' => $exam->id, 'level' => 'B1', 'variant' => null,
        'name' => 'telc Deutsch B1', 'total_duration_minutes' => 150, 'scoring_config' => [],
        'sections_config' => [
            ['slug' => 'lesen', 'name' => 'Leseverstehen', 'skill_type' => 'reading', 'time_limit' => 90, 'task_count' => 2],
            ['slug' => 'hoeren', 'name' => 'Hörverstehen', 'skill_type' => 'listening', 'time_limit' => 20, 'task_count' => 1],
        ],
    ]);
    foreach (ExerciseType::where('skill_type', 'reading')->get() as $type) {
        Exercise::create([
            'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'difficulty' => 'B1',
            'content' => ['title' => 'b1'],
            'questions' => [['id' => 'q1', 'type' => 'mcq', 'text' => 'x', 'options' => ['a', 'b'], 'correct_answer' => 'A']],
        ]);
    }

    $sujet = app(MockExamComposer::class)->pour($exam, 'B1', false, true);

    expect($sujet)->not->toBeNull();

    $lesen = $sujet->exercises()->with('exerciseType.section')->get()
        ->filter(fn ($e) => $e->exerciseType?->section?->slug === 'lesen');

    expect($lesen)->toHaveCount(2);
});
