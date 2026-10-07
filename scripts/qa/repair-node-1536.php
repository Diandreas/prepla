<?php
// Scoped, backed-up content repair. Does not alter learner attempts or progression.
require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
Illuminate\Support\Facades\DB::transaction(function () {
    $exercises = App\Models\Exercise::where('node_id', 1536)->whereIn('id', [710, 712])->lockForUpdate()->get();
    if ($exercises->count() !== 2) throw new RuntimeException('Expected exercises not found.');
    $backup = 'qa-backups/node-1536-'.now()->format('Ymd-His').'.json';
    if (! Illuminate\Support\Facades\Storage::disk('local')->put($backup, $exercises->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        throw new RuntimeException('Backup failed.');
    }
    foreach ($exercises as $exercise) {
        $questions = $exercise->questions;
        foreach ($questions as &$question) {
            if ($exercise->id === 710 && $question['id'] === 'q2') {
                if ($question['correct_answer'] !== 'I am going to finish early today.') throw new RuntimeException('Unexpected sentence.');
                $question['words'] = ['I', 'am', 'going', 'to', 'finish', 'early', 'today'];
            }
            if ($exercise->id === 712 && $question['id'] === 'q1') {
                if (! str_contains($question['audio_text'] ?? '', 'Sarah has a very regular morning routine')) throw new RuntimeException('Unexpected recording.');
                $question['title'] = 'Écoute puis complète les actions de Sarah.';
                $question['notes'] = [
                    ['label' => 'Every morning at 7 AM, Sarah…', 'value' => ''],
                    ['label' => 'Right now, she… her coffee.', 'value' => ''],
                    ['label' => 'Normally, she… toast with butter.', 'value' => ''],
                    ['label' => 'Today, she… the newspaper.', 'value' => ''],
                ];
                $question['correct_answers'] = ['0' => 'wakes up', '1' => 'is drinking', '2' => 'eats', '3' => 'is reading'];
            }
        }
        unset($question);
        $exercise->update(['questions' => $questions]);
    }
    echo "Repaired exercises 710 and 712. Backup: {$backup}\n";
});
