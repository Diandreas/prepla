<?php
require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
Illuminate\Support\Facades\DB::transaction(function () {
    $lesson = App\Models\Lesson::lockForUpdate()->findOrFail(57);
    if ($lesson->concept !== 'grammar.tense.present_simple' || $lesson->title !== 'Das Präsens der Verben im Deutschen') throw new RuntimeException('Unexpected lesson.');
    $backup = 'qa-backups/lesson-57-'.now()->format('Ymd-His').'.json';
    if (! Illuminate\Support\Facades\Storage::disk('local')->put($backup, $lesson->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) throw new RuntimeException('Backup failed.');
    $quiz = $lesson->comprehension_quiz;
    $quiz[0]['question'] = 'Quelle forme de arbeiten (travailler) convient avec du au présent ?';
    $quiz[0]['options'] = ['du arbeitest', 'du arbeitst', 'du arbeiten', 'du arbeitet'];
    $quality = app(App\Services\Content\LessonQuizQuality::class);
    $quiz = $quality->normalize($quiz);
    if (! $quality->valid($quiz)) throw new RuntimeException('Invalid repair.');
    $lesson->update(['comprehension_quiz' => $quiz]);
    echo "Lesson 57 repaired; backup {$backup}.\n";
});
