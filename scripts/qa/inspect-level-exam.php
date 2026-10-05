<?php

// Read-only production audit. Run from the Laravel project root over SSH stdin.
require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = App\Models\User::findOrFail(84);
$path = App\Models\CurriculumSkeleton::where('user_id', $user->id)->firstOrFail();
$node = App\Models\LearningPathNode::where('exam_id', $path->exam_id)
    ->where('node_type', 'level_exam')->where('level', 'A1')->first();
$lessons = App\Models\Lesson::where('user_id', $user->id)->get();
$mismatches = $lessons->filter(fn ($lesson) =>
    ($path->objectives[$lesson->skeleton_objective_index]['concept'] ?? null) !== $lesson->concept);
echo json_encode([
    'user_id' => $user->id,
    'level' => $user->profile?->current_level,
    'exam_id' => $path->exam_id,
    'current_index' => $path->current_objective_index,
    'current_objective' => $path->currentObjective(),
    'available_exam' => $path->pendingLevelExam(),
    'lesson_index_mismatches' => $mismatches->map(fn ($lesson) => [
        'id' => $lesson->id, 'index' => $lesson->skeleton_objective_index, 'concept' => $lesson->concept,
    ])->values(),
    'node_id' => $node?->id,
    'parts' => $node ? App\Models\Exercise::where('node_id', $node->id)->get()->map(fn ($exercise) => [
        'id' => $exercise->id, 'component' => $exercise->exerciseType?->component_key,
        'order' => $exercise->order_in_node, 'questions' => count($exercise->questions ?? []),
        'categories' => collect($exercise->questions)->pluck('error_category')->unique()->values(),
    ]) : [],
    'paths' => App\Models\CurriculumSkeleton::count(),
    'paths_without_exam' => App\Models\CurriculumSkeleton::all()->filter(fn ($p) =>
        !collect($p->objectives)->contains(fn ($o) => $o['is_level_exam'] ?? false))->count(),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
