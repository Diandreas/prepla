<?php
// Read-only inspection of the exercise content reported on node 1536.
require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
foreach (App\Models\Exercise::where('node_id', 1536)->with('exerciseType')->get() as $exercise) {
    echo json_encode(['id' => $exercise->id, 'component' => $exercise->exerciseType?->component_key,
        'questions' => $exercise->questions], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
}
