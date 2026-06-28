<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DictionaryController;
use App\Http\Controllers\ErrorReviewController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\VocabularyController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');
Route::get('/offline', fn() => view('offline'))->name('offline');

Route::middleware(['auth'])->group(function () {
    // Onboarding
    Route::prefix('onboarding')->name('onboarding.')->group(function () {
        Route::get('/native-language', [OnboardingController::class, 'nativeLanguage'])->name('native-language');
        Route::post('/native-language', [OnboardingController::class, 'storeNativeLanguage'])->name('native-language.store');
        Route::get('/exam', [OnboardingController::class, 'examSelect'])->name('exam');
        Route::post('/exam', [OnboardingController::class, 'storeExam'])->name('exam.store');
        Route::get('/goal', [OnboardingController::class, 'goalSetting'])->name('goal');
        Route::post('/goal', [OnboardingController::class, 'storeGoal'])->name('goal.store');
        Route::get('/placement', [OnboardingController::class, 'placementTest'])->name('placement');
        Route::post('/placement', [OnboardingController::class, 'submitPlacement'])->name('placement.store');
        Route::get('/result', [OnboardingController::class, 'result'])->name('result');
        // Async program generation — keeps the result page instant; the heavy AI
        // call runs here and the page fetches it client-side behind a loader.
        Route::get('/program', [OnboardingController::class, 'programData'])->name('program');
        Route::post('/complete', [OnboardingController::class, 'complete'])->name('complete');
    });

    // Protected by onboarding
    Route::middleware([\App\Http\Middleware\EnsureOnboardingComplete::class])->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');

        // Practice
        Route::get('practice', [\App\Http\Controllers\PracticeController::class, 'index'])->name('practice.index');
        Route::get('practice/{exam}', [\App\Http\Controllers\PracticeController::class, 'examDashboard'])->name('practice.exam');
        Route::get('practice/{exam}/section/{section}', [\App\Http\Controllers\PracticeController::class, 'sectionDrills'])->name('practice.section');
        // Pratiquer par type : 1 clic sur un type → 1 exercice (biblio d'abord, sinon généré)
        Route::get('practice/{exam}/drill/{exerciseType}', [\App\Http\Controllers\PracticeController::class, 'drillByType'])->name('practice.drill.type');
        Route::post('practice/{exam}/section/{section}/generate', [\App\Http\Controllers\PracticeController::class, 'generateSection'])->name('practice.section.generate');
        Route::get('practice/{exam}/simulate', [\App\Http\Controllers\PracticeController::class, 'simulate'])->name('practice.simulate');
        Route::post('practice/{exam}/simulate', [\App\Http\Controllers\PracticeController::class, 'submitSimulation'])->name('practice.simulate.store');

        // Node start (Duolingo-style: 1 click → exercise)
        Route::get('node/{node}/start', \App\Http\Controllers\NodeStartController::class)->name('node.start');

        // Exercises
        Route::get('exercise/{exercise}', [\App\Http\Controllers\ExerciseController::class, 'show'])->name('exercise.show');
        Route::post('exercise/{exercise}/submit', [\App\Http\Controllers\ExerciseController::class, 'submit'])->name('exercise.submit');
        Route::post('node/{node}/submit', [\App\Http\Controllers\ExerciseController::class, 'submitSession'])->name('exercise.submit_session');
        Route::get('exercise/result/{attempt}', [\App\Http\Controllers\ExerciseController::class, 'result'])->name('exercise.result');
        Route::get('node/{node}/result', [\App\Http\Controllers\ExerciseController::class, 'sessionResult'])->name('node.session_result');

        // Boss-level chapter synthesis
        Route::get('chapter/{chapterOrder}/synthesis', [\App\Http\Controllers\ChapterSynthesisController::class, 'start'])
            ->whereNumber('chapterOrder')
            ->name('chapter.synthesis');

        // Dictionary
        Route::prefix('dictionary')->name('dictionary.')->group(function () {
            Route::get('/', [DictionaryController::class, 'index'])->name('index');
            Route::post('/discover', [DictionaryController::class, 'discover'])->name('discover');
            Route::get('/lookup/{language}/{word}', [DictionaryController::class, 'lookup'])->name('lookup');
            Route::get('/review', [DictionaryController::class, 'reviewPage'])->name('review_page');
            Route::get('/review-session', [DictionaryController::class, 'reviewSession'])->name('review_session');
            Route::post('/review-batch/submit', [DictionaryController::class, 'submitReviewBatch'])->name('submit_review_batch');
            Route::get('/audio/{word}', [DictionaryController::class, 'audio'])->name('audio');
        });

        Route::post('api/ai/explain', [\App\Http\Controllers\ExerciseController::class, 'explainMistake'])->name('api.ai.explain');
        Route::post('api/ai/chat', [\App\Http\Controllers\ExerciseController::class, 'chatMistake'])->name('api.ai.chat');
        Route::post('api/exercise/verify-single', [\App\Http\Controllers\ExerciseController::class, 'verifySingle'])->name('api.exercise.verify-single');
        // Évaluation live d'un tour de role-play (audio → transcription + correction)
        Route::post('api/exercise/evaluate-turn', [\App\Http\Controllers\ExerciseController::class, 'evaluateTurn'])->name('api.exercise.evaluate-turn');

        // TTS API
        Route::post('api/tts/speak', [\App\Http\Controllers\TtsController::class, 'speak'])->name('tts.speak');

        // AI Tools
        Route::get('ai-tools', [\App\Http\Controllers\AiToolsController::class, 'index'])->name('ai-tools.index');
        Route::get('ai-tools/generator', [\App\Http\Controllers\AiToolsController::class, 'generator'])->name('ai-tools.generator');
        Route::post('ai-tools/generator', [\App\Http\Controllers\AiToolsController::class, 'generateExercise'])->name('ai-tools.generator.store');
        Route::get('ai-tools/writing-corrector', [\App\Http\Controllers\AiToolsController::class, 'writingCorrector'])->name('ai-tools.writing-corrector');
        Route::post('ai-tools/writing-corrector', [\App\Http\Controllers\AiToolsController::class, 'submitWriting'])->name('ai-tools.writing-corrector.store');
        Route::post('ai-tools/writing-corrector/extract', [\App\Http\Controllers\AiToolsController::class, 'extractWritingImage'])->name('ai-tools.writing-corrector.extract');
        Route::get('ai-tools/explainer', [\App\Http\Controllers\AiToolsController::class, 'explainer'])->name('ai-tools.explainer');
        Route::post('ai-tools/explainer/ask', [\App\Http\Controllers\AiToolsController::class, 'askExplainer'])->name('ai-tools.explainer.ask');
        Route::get('ai-tools/recommendations', [\App\Http\Controllers\AiToolsController::class, 'recommendations'])->name('ai-tools.recommendations');

        // Vocabulary — fusionné avec le Dictionnaire : les pages "Mon Lexique" /
        // "Session de Révision" redirigent vers le Dictionnaire (point d'entrée
        // unique). On conserve les endpoints POST utilisés par les composants.
        Route::prefix('vocabulary')->name('vocabulary.')->group(function () {
            Route::get('/', fn() => redirect()->route('dictionary.index'))->name('index');
            Route::get('/learn', fn() => redirect()->route('dictionary.index'))->name('learn');
            Route::get('/random/{languageSlug}', [VocabularyController::class, 'random'])->name('random');
            Route::get('/review', fn() => redirect()->route('dictionary.review_session'))->name('review');
            Route::post('/', [VocabularyController::class, 'store'])->name('store');
            Route::post('/{vocab}/review', [VocabularyController::class, 'submitReview'])->name('submit-review');
        });

        // Lessons (Pilier 1 + 9: Progressive adaptive lessons)
        Route::prefix('lessons')->name('lessons.')->group(function () {
            Route::get('/', [\App\Http\Controllers\LessonController::class, 'index'])->name('index');
            Route::get('/next', [\App\Http\Controllers\LessonController::class, 'next'])->name('next');
            Route::get('/{lesson}', [\App\Http\Controllers\LessonController::class, 'show'])->name('show');
            Route::post('/{lesson}/quiz', [\App\Http\Controllers\LessonController::class, 'submitQuiz'])->name('quiz');
        });

        // Error Review
        Route::prefix('errors')->name('errors.')->group(function () {
            Route::get('/', [ErrorReviewController::class, 'index'])->name('index');
            Route::get('/practice', [ErrorReviewController::class, 'practice'])->name('practice');
            Route::post('/{error}/review', [ErrorReviewController::class, 'submitReview'])->name('submit-review');
        });

        // Leaderboard
        Route::get('leaderboard', [\App\Http\Controllers\HomeController::class, 'leaderboard'])->name('leaderboard');

        // Results
        Route::get('results', [\App\Http\Controllers\ResultsController::class, 'index'])->name('results.index');
        Route::get('results/attempts', [\App\Http\Controllers\ResultsController::class, 'attempts'])->name('results.attempts');
        Route::get('results/attempts/{attempt}', [\App\Http\Controllers\ResultsController::class, 'attemptDetail'])->name('results.attempt');

        // Profile extras
        Route::get('profile/achievements', [\App\Http\Controllers\ProfileController::class, 'achievements'])->name('profile.achievements');
        Route::get('profile/stats', [\App\Http\Controllers\ProfileController::class, 'stats'])->name('profile.stats');

        // Test Sandbox
        Route::get('test/sandbox', function() {
            return \Inertia\Inertia::render('test/sandbox');
        })->name('test.sandbox');

        Route::get('test/exercises', function() {
            return \Inertia\Inertia::render('test/test-exercises');
        })->name('test.exercises');

        Route::get('test/exercises/audit', function() {
            return \Inertia\Inertia::render('test/audit');
        })->name('test.exercises.audit');
    });
});

// ───────────────────────── B2B « Centre de langue » ─────────────────────────
// Super-admin : création/gestion manuelle des centres (réservé role super_admin).
Route::middleware(['auth', \App\Http\Middleware\EnsureSuperAdmin::class])
    ->prefix('admin')->name('admin.')->group(function () {
        Route::get('centers', [\App\Http\Controllers\SuperAdmin\CenterController::class, 'index'])->name('centers.index');
        Route::get('centers/create', [\App\Http\Controllers\SuperAdmin\CenterController::class, 'create'])->name('centers.create');
        Route::post('centers', [\App\Http\Controllers\SuperAdmin\CenterController::class, 'store'])->name('centers.store');
        Route::get('centers/{center}', [\App\Http\Controllers\SuperAdmin\CenterController::class, 'show'])->name('centers.show');
        Route::patch('centers/{center}', [\App\Http\Controllers\SuperAdmin\CenterController::class, 'update'])->name('centers.update');
        Route::delete('centers/{center}', [\App\Http\Controllers\SuperAdmin\CenterController::class, 'destroy'])->name('centers.destroy');
    });

// Staff du centre (center_admin + teacher). Le middleware injecte le centre courant.
Route::middleware(['auth', \App\Http\Middleware\EnsureCenterStaff::class])
    ->prefix('center')->name('center.')->group(function () {
        Route::get('/', \App\Http\Controllers\Center\DashboardController::class)->name('dashboard');

        // Classes
        Route::get('classes', [\App\Http\Controllers\Center\ClassroomController::class, 'index'])->name('classes.index');
        Route::post('classes', [\App\Http\Controllers\Center\ClassroomController::class, 'store'])->name('classes.store');
        Route::get('classes/{classroom}', [\App\Http\Controllers\Center\ClassroomController::class, 'show'])->name('classes.show');
        Route::patch('classes/{classroom}', [\App\Http\Controllers\Center\ClassroomController::class, 'update'])->name('classes.update');
        Route::post('classes/{classroom}/regenerate-code', [\App\Http\Controllers\Center\ClassroomController::class, 'regenerateCode'])->name('classes.regenerate-code');
        Route::get('classes/{classroom}/export.csv', [\App\Http\Controllers\Center\ProgressController::class, 'exportCsv'])->name('classes.export');
        Route::delete('classes/{classroom}/students/{user}', [\App\Http\Controllers\Center\StudentController::class, 'remove'])->name('classes.students.remove');

        // Élèves
        Route::get('students', [\App\Http\Controllers\Center\StudentController::class, 'index'])->name('students.index');
        Route::get('students/{user}', [\App\Http\Controllers\Center\StudentController::class, 'show'])->name('students.show');

        // Médiathèque (upload images / audio en local)
        Route::get('media', [\App\Http\Controllers\Center\MediaController::class, 'index'])->name('media.index');
        Route::post('media', [\App\Http\Controllers\Center\MediaController::class, 'store'])->name('media.store');
        Route::delete('media/{medium}', [\App\Http\Controllers\Center\MediaController::class, 'destroy'])->name('media.destroy');

        // Builder de contenu (manuel + IA assistée)
        Route::get('exercises', [\App\Http\Controllers\Center\ExerciseBuilderController::class, 'index'])->name('exercises.index');
        Route::get('exercises/create', [\App\Http\Controllers\Center\ExerciseBuilderController::class, 'create'])->name('exercises.create');
        Route::post('exercises/ai-draft', [\App\Http\Controllers\Center\ExerciseBuilderController::class, 'aiDraft'])->name('exercises.ai-draft');
        Route::post('exercises', [\App\Http\Controllers\Center\ExerciseBuilderController::class, 'store'])->name('exercises.store');
        Route::get('exercises/{exercise}/edit', [\App\Http\Controllers\Center\ExerciseBuilderController::class, 'edit'])->name('exercises.edit');
        Route::patch('exercises/{exercise}', [\App\Http\Controllers\Center\ExerciseBuilderController::class, 'update'])->name('exercises.update');

        // Devoirs / assignations
        Route::get('assignments', [\App\Http\Controllers\Center\AssignmentController::class, 'index'])->name('assignments.index');
        Route::get('assignments/create', [\App\Http\Controllers\Center\AssignmentController::class, 'create'])->name('assignments.create');
        Route::post('assignments', [\App\Http\Controllers\Center\AssignmentController::class, 'store'])->name('assignments.store');
        Route::get('assignments/{assignment}', [\App\Http\Controllers\Center\AssignmentController::class, 'show'])->name('assignments.show');
        Route::delete('assignments/{assignment}', [\App\Http\Controllers\Center\AssignmentController::class, 'destroy'])->name('assignments.destroy');
    });

// Élève — rejoindre un centre via code d'invitation.
Route::middleware('auth')->group(function () {
    Route::get('join', [\App\Http\Controllers\JoinCenterController::class, 'show'])->name('center.join');
    Route::post('join', [\App\Http\Controllers\JoinCenterController::class, 'store'])->name('center.join.store');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

// Web Push subscription management
Route::middleware('auth')->group(function () {
    Route::post('push/subscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'store'])->name('push.subscribe');
    Route::delete('push/unsubscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'destroy'])->name('push.unsubscribe');
});
