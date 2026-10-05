<?php

// CLI-only live QA: prepare the shared assessment, then exercise a disposable
// learner inside a transaction. The real learner's results are never submitted.
require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\ExerciseController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\LevelExamController;
use App\Http\Controllers\NodeStartController;
use App\Models\CurriculumSkeleton;
use App\Models\Exercise;
use App\Models\LearningPathNode;
use App\Models\Lesson;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

function check(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
    echo 'OK '.$message.PHP_EOL;
}
function submitPayload($exercises, bool $correct): array {
    return ['exercise_ids' => $exercises->pluck('id')->all(), 'time_spent' => 120,
        'answers_by_exercise' => $exercises->mapWithKeys(fn ($e) => [$e->id =>
            collect($e->questions)->mapWithKeys(fn ($q) => [$q['id'] => $correct ? $q['correct_answer'] : '__QA_WRONG__'])->all()
        ])->all()];
}
function qaRequest(string $url, array $data): Request {
    $request = Request::create($url, 'POST', $data);
    $request->setLaravelSession(app('session')->driver());
    return $request;
}

$source = User::findOrFail((int) ($argv[1] ?? 84));
$path = CurriculumSkeleton::where('user_id', $source->id)->firstOrFail();
$fingerprint = hash('sha256', $path->toJson().$source->profile->toJson());
Auth::setUser($source);
$response = app()->call([app(LevelExamController::class), 'start'], ['level' => 'A1']);
$node = LearningPathNode::where('exam_id', $path->exam_id)->where('node_type', 'level_exam')->where('level', 'A1')->firstOrFail();
$examExercises = Exercise::where('node_id', $node->id)->orderBy('order_in_node')->get();
check($examExercises->count() === 3, 'Examen A1 complet : trois parties générées par la vraie IA');
foreach ($examExercises as $exercise) {
    check(count($exercise->questions ?? []) >= 3, 'Questions valides pour '.$exercise->exerciseType->component_key);
}

DB::beginTransaction();
try {
    $qa = User::create(['name' => 'QA examen temporaire', 'email' => 'qa-'.bin2hex(random_bytes(8)).'@example.invalid',
        'password' => Hash::make(bin2hex(random_bytes(24))), 'email_verified_at' => now()]);
    UserProfile::create(['user_id' => $qa->id, 'target_exam_id' => $path->exam_id,
        'current_level' => 'A1', 'native_language' => $source->profile->native_language,
        'onboarding_completed_at' => now(), 'trial_ends_at' => now()->addDay()]);
    $copy = $path->replicate();
    $copy->user_id = $qa->id;
    $copy->save();
    Auth::setUser($qa);
    app(ExerciseController::class)->submitSession(qaRequest('/qa', submitPayload($examExercises, false)), $node);
    $copy->refresh();
    check($copy->pendingLevelExam() === null, 'Échec : examen refermé');
    check($copy->currentObjective()['is_remedial'] ?? false, 'Échec : reprise ciblée proposée');
    $count = collect($copy->objectives)->where('is_remedial', true)->where('status', '!=', 'done')->count();
    check($count >= 1 && $count <= 2, 'Une à deux reprises, sans empilement');
    for ($i = 0; $i < $count; $i++) {
        app(LessonController::class)->next();
        $lesson = Lesson::where('user_id', $qa->id)->where('skeleton_objective_index', $copy->fresh()->current_objective_index)->firstOrFail();
        check($lesson->status !== 'draft' && count($lesson->comprehension_quiz ?? []) === 3, 'Leçon ciblée et quiz produits par la vraie IA');
        $answers = collect($lesson->comprehension_quiz)->map(fn ($q) => Lesson::resolveCorrectAnswerText($q))->all();
        $quiz = app(LessonController::class)->submitQuiz(qaRequest('/qa', ['answers' => $answers]), $lesson);
        check($quiz->getData(true)['passed'], 'Quiz de reprise validé');
        $player = app()->call([app(NodeStartController::class), '__invoke'], ['node' => $lesson->node]);
        check($player instanceof Inertia\Response, 'Pratique ciblée ouverte');
        $practice = Exercise::where('node_id', $lesson->node_id)->get();
        check($practice->isNotEmpty(), 'Exercices de reprise réels disponibles');
        app(ExerciseController::class)->submitSession(qaRequest('/qa', submitPayload($practice, true)), $lesson->node);
        $copy->refresh();
    }
    check(($copy->pendingLevelExam()['level'] ?? '') === 'A1', 'Après les reprises : examen rouvert');
    app(ExerciseController::class)->submitSession(qaRequest('/qa', submitPayload($examExercises, true)), $node);
    check($qa->profile->fresh()->current_level === 'A2', 'Réussite : promotion A1 vers A2');
} finally {
    DB::rollBack();
    Auth::forgetUser();
}
check(hash('sha256', $path->fresh()->toJson().$source->profile->fresh()->toJson()) === $fingerprint,
    'Progression du véritable apprenant inchangée ; données QA annulées');
