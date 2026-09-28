<?php

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Services\ExerciseScoringService;
use Illuminate\Support\Facades\Http;

/**
 * Une question que l'apprenant n'a pas pu faire ne doit pas lui coûter de points.
 *
 * Les composants envoient « __skipped__ » quand l'exercice arrive sans contenu
 * utilisable ou quand le rendu plante et propose de passer. Ce repère n'était reconnu
 * que par la branche IA : ailleurs, il partait en comparaison avec la réponse attendue
 * et comptait comme une faute. La panne était de notre côté, la sanction pour eux.
 */
function skippedScoringExercise(array $questions): Exercise
{
    $language = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'skip-exam', 'name' => 'Skip exam']);
    $section = ExamSection::create([
        'exam_id' => $exam->id, 'slug' => 'reading', 'name' => 'Reading', 'skill_type' => 'reading',
    ]);
    $type = ExerciseType::create([
        'section_id' => $section->id, 'slug' => 'mcq', 'name' => 'MCQ', 'skill_type' => 'reading', 'component_key' => 'mcq',
    ]);

    return Exercise::create([
        'exam_id' => $exam->id,
        'exercise_type_id' => $type->id,
        'exam_section_id' => $section->id,
        'title' => 'Skip',
        'difficulty' => 'A2',
        'questions' => $questions,
        'content' => ['instructions' => 'Test'],
    ]);
}

test('une question sautee sort du denominateur au lieu de compter comme une faute', function () {
    Http::preventStrayRequests();

    $exercise = skippedScoringExercise([
        ['id' => 'q1', 'type' => 'mcq', 'text' => 'Wo?', 'options' => ['Bonn', 'Berlin'], 'correct_answer' => 'A', 'explanation' => 'Bonn.'],
        ['id' => 'q2', 'type' => 'build-a-sentence', 'text' => 'Casse', 'correct_answer' => 'Ich gehe', 'explanation' => 'Cassé.'],
    ]);

    $result = app(ExerciseScoringService::class)->score($exercise, [
        'q1' => 'A',
        'q2' => '__skipped__',
    ]);

    // Une bonne réponse sur une seule question réellement présentée.
    expect($result['score'])->toBe(1);
    expect($result['accuracy'])->toBe(100.0);

    $skipped = collect($result['feedback'])->firstWhere('question_id', 'q2');
    expect($skipped['technical_failure'])->toBeTrue();
    expect($skipped['explanation'])->toContain('ne compte pas dans ton score');
});

test('une reponse reellement fausse compte toujours', function () {
    Http::preventStrayRequests();

    $exercise = skippedScoringExercise([
        ['id' => 'q1', 'type' => 'mcq', 'text' => 'Wo?', 'options' => ['Bonn', 'Berlin'], 'correct_answer' => 'A', 'explanation' => 'Bonn.'],
        ['id' => 'q2', 'type' => 'mcq', 'text' => 'Wann?', 'options' => ['Heute', 'Morgen'], 'correct_answer' => 'A', 'explanation' => 'Heute.'],
    ]);

    $result = app(ExerciseScoringService::class)->score($exercise, ['q1' => 'A', 'q2' => 'B']);

    expect($result['score'])->toBe(1);
    expect($result['accuracy'])->toBe(50.0);
});
