<?php

/**
 * Test exhaustif de la logique de correction (ExerciseScoringService) pour
 * chaque type d'exercice non-IA. Construit un Exercise en mémoire avec une
 * réponse correcte connue, puis vérifie que score() le marque correct.
 *
 * Usage : php artisan tinker --execute="require 'scripts/test_scoring.php';"
 */

use App\Models\Exercise;
use App\Models\Language;
use App\Models\Exam;
use App\Models\ExerciseType;
use App\Services\ExerciseScoringService;

/** @var \App\Services\ExerciseScoringService $svc */
$svc = app(ExerciseScoringService::class);

// Cas de test : [type, question(s), réponse correcte attendue]
$cases = [
    // --- Exact match scalaire ---
    ['mcq', [['id' => 'q1', 'type' => 'mcq', 'text' => 'Capitale ?', 'options' => ['Munich', 'Berlin', 'Hambourg', 'Cologne'], 'correct_answer' => 'B']], ['q1' => 'B']],
    ['true-false-ng', [['id' => 'q1', 'type' => 'true-false-ng', 'text' => 'Vrai ?', 'correct_answer' => 'True']], ['q1' => 'True']],
    ['gap-fill', [['id' => 'q1', 'type' => 'gap-fill', 'text' => 'Ich ___ Hause.', 'correct_answer' => 'gehe']], ['q1' => 'gehe']],
    ['short-answer', [['id' => 'q1', 'type' => 'short-answer', 'text' => 'Qui ?', 'correct_answer' => 'Olaf Scholz']], ['q1' => 'olaf scholz']], // casse insensible
    ['sentence-completion', [['id' => 'q1', 'type' => 'sentence-completion', 'text' => '...', 'options' => ['A1', 'B2', 'C3', 'D4'], 'correct_answer' => 'C']], ['q1' => 'C']],
    ['word-formation', [['id' => 'q1', 'type' => 'word-formation', 'text' => '___', 'correct_answer' => 'Schönheit']], ['q1' => 'Schönheit']],
    ['key-word-transformation', [['id' => 'q1', 'type' => 'key-word-transformation', 'correct_answer' => 'long time since I last']], ['q1' => 'long time since I last']],
    ['dictation', [['id' => 'q1', 'type' => 'dictation', 'correct_answer' => 'Das Wetter ist schön.']], ['q1' => 'Das Wetter ist schön.']],
    ['insert-text', [['id' => 'q1', 'type' => 'insert-text', 'correct_answer' => 'B']], ['q1' => 'B']],

    // --- MCQ via lettre/index ---
    ['matching', [['id' => 'q1', 'type' => 'matching', 'text' => 'Terme', 'options' => ['Def1', 'Def2', 'Def3', 'Def4'], 'correct_answer' => 'A']], ['q1' => 'A']],

    // --- Multi-champs (correct_answers objet) ---
    ['note-completion', [['id' => 'q1', 'type' => 'note-completion', 'correct_answers' => ['0' => 'Berlin', '1' => '1990']]], ['q1' => ['0' => 'Berlin', '1' => '1990']]],
    ['open-cloze', [['id' => 'q1', 'type' => 'open-cloze', 'correct_answers' => ['1' => 'the', '2' => 'of']]], ['q1' => ['1' => 'the', '2' => 'of']]],
    ['form-completion', [['id' => 'q1', 'type' => 'form-completion', 'correct_answers' => ['0' => 'Dupont', '1' => 'Paris']]], ['q1' => ['0' => 'Dupont', '1' => 'Paris']]],
    ['summary-completion', [['id' => 'q1', 'type' => 'summary-completion', 'correct_answers' => ['0' => 'climate', '1' => 'rising']]], ['q1' => ['0' => 'climate', '1' => 'rising']]],
    ['table-completion', [['id' => 'q1', 'type' => 'table-completion', 'correct_answers' => ['0-1' => 'Paris', '1-1' => 'Rome']]], ['q1' => ['0-1' => 'Paris', '1-1' => 'Rome']]],
    ['flow-chart-completion', [['id' => 'q1', 'type' => 'flow-chart-completion', 'correct_answers' => ['0' => 'start', '2' => 'end']]], ['q1' => ['0' => 'start', '2' => 'end']]],
    ['multiple-matching', [['id' => 'q1', 'type' => 'multiple-matching', 'correct_answers' => ['s1' => 'B', 's2' => 'A']]], ['q1' => ['s1' => 'B', 's2' => 'A']]],
    ['diagram-labeling', [['id' => 'q1', 'type' => 'diagram-labeling', 'correct_answers' => ['l1' => 'engine', 'l2' => 'wheel']]], ['q1' => ['l1' => 'engine', 'l2' => 'wheel']]],

    // --- correct_answer tableau (ordering) ---
    ['ordering', [['id' => 'q1', 'type' => 'ordering', 'correct_answer' => ['A', 'B', 'C']]], ['q1' => ['A', 'B', 'C']]],
];

// Setup minimal en mémoire (pas de save en base)
$lang = new Language(['slug' => 'german', 'name' => 'Allemand']);
$exam = new Exam(['name' => 'Goethe']);
$exam->setRelation('language', $lang);

$pass = 0; $fail = 0; $lines = [];
foreach ($cases as [$type, $questions, $answer]) {
    $ex = new Exercise(['questions' => $questions, 'xp_reward' => 30]);
    $etype = new ExerciseType(['slug' => $type, 'skill_type' => 'reading', 'component_key' => $type]);
    $ex->setRelation('exerciseType', $etype);
    $ex->setRelation('exam', $exam);

    try {
        $result = $svc->score($ex, $answer);
        $ok = ($result['score'] ?? 0) >= 1 && ($result['accuracy'] ?? 0) >= 70;
        if ($ok) { $pass++; $lines[] = sprintf("  ✅ %-24s acc=%s%%", $type, $result['accuracy']); }
        else     { $fail++; $lines[] = sprintf("  ❌ %-24s acc=%s%% score=%s", $type, $result['accuracy'] ?? '?', $result['score'] ?? '?'); }
    } catch (\Throwable $e) {
        $fail++; $lines[] = sprintf("  💥 %-24s EXCEPTION: %s", $type, $e->getMessage());
    }
}

echo "\n=== TEST SCORING (réponse correcte attendue → doit être correct) ===\n";
echo implode("\n", $lines) . "\n";
echo "\n--- Test bonus : mauvaise réponse → doit être incorrect ---\n";
$exBad = new Exercise(['questions' => [['id' => 'q1', 'type' => 'mcq', 'options' => ['X', 'Y', 'Z', 'W'], 'correct_answer' => 'B']], 'xp_reward' => 10]);
$exBad->setRelation('exerciseType', new ExerciseType(['slug' => 'mcq', 'skill_type' => 'reading']));
$exBad->setRelation('exam', $exam);
$rBad = $svc->score($exBad, ['q1' => 'A']);
echo (($rBad['score'] === 0) ? "  ✅ MCQ mauvaise réponse → score 0\n" : "  ❌ MCQ mauvaise réponse mal notée: " . json_encode($rBad) . "\n");

echo "\n=== RÉSULTAT : $pass OK / $fail KO sur " . count($cases) . " types ===\n";
