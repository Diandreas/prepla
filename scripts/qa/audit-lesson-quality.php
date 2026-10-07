<?php
// Read-only inventory; no learner data or answers are modified.
require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$quality = app(App\Services\Content\LessonQuizQuality::class);
$total = 0;
$invalid = [];
App\Models\Lesson::where('status', '!=', 'draft')->chunkById(100, function ($lessons) use ($quality, &$total, &$invalid) {
    foreach ($lessons as $lesson) {
        $total++;
        if (! $quality->valid($lesson->comprehension_quiz ?? [])) $invalid[] = $lesson->id;
    }
});
echo json_encode(['checked' => $total, 'invalid_ids' => $invalid]).PHP_EOL;
