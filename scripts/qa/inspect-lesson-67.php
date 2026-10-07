<?php
require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$lesson = App\Models\Lesson::findOrFail(67);
echo json_encode($lesson->only(['title', 'concept', 'theory_markdown', 'comprehension_quiz']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
