<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DictionaryController;
use App\Http\Controllers\ErrorReviewController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\VocabularyController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');

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
        Route::post('/complete', [OnboardingController::class, 'complete'])->name('complete');
    });

    // Protected by onboarding
    Route::middleware([\App\Http\Middleware\EnsureOnboardingComplete::class])->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');

        // Practice
        Route::get('practice', [\App\Http\Controllers\PracticeController::class, 'index'])->name('practice.index');
        Route::get('practice/{exam}', [\App\Http\Controllers\PracticeController::class, 'examDashboard'])->name('practice.exam');
        Route::get('practice/{exam}/section/{section}', [\App\Http\Controllers\PracticeController::class, 'sectionDrills'])->name('practice.section');
        Route::get('practice/{exam}/simulate', [\App\Http\Controllers\PracticeController::class, 'simulate'])->name('practice.simulate');
        Route::post('practice/{exam}/simulate', [\App\Http\Controllers\PracticeController::class, 'submitSimulation'])->name('practice.simulate.store');

        // Node start (Duolingo-style: 1 click → exercise)
        Route::get('node/{node}/start', \App\Http\Controllers\NodeStartController::class)->name('node.start');

        // Exercises
        Route::get('exercise/{exercise}', [\App\Http\Controllers\ExerciseController::class, 'show'])->name('exercise.show');
        Route::post('exercise/{exercise}/submit', [\App\Http\Controllers\ExerciseController::class, 'submit'])->name('exercise.submit');
        Route::post('node/{node}/submit', [\App\Http\Controllers\ExerciseController::class, 'submitSession'])->name('exercise.submit_session');
        Route::get('exercise/result/{attempt}', [\App\Http\Controllers\ExerciseController::class, 'result'])->name('exercise.result');

        // Dictionary
        Route::prefix('dictionary')->name('dictionary.')->group(function () {
            Route::get('/', [DictionaryController::class, 'index'])->name('index');
            Route::post('/discover', [DictionaryController::class, 'discover'])->name('discover');
            Route::get('/lookup/{language}/{word}', [DictionaryController::class, 'lookup'])->name('lookup');
            Route::get('/review/{progress}', [DictionaryController::class, 'review'])->name('review');
            Route::post('/review/{progress}/submit', [DictionaryController::class, 'submitReview'])->name('submit_review');
        });

        Route::post('api/ai/explain', [\App\Http\Controllers\ExerciseController::class, 'explainMistake'])->name('api.ai.explain');

        // TTS API
        Route::post('api/tts/speak', [\App\Http\Controllers\TtsController::class, 'speak'])->name('tts.speak');

        // AI Tools
        Route::get('ai-tools', [\App\Http\Controllers\AiToolsController::class, 'index'])->name('ai-tools.index');
        Route::get('ai-tools/generator', [\App\Http\Controllers\AiToolsController::class, 'generator'])->name('ai-tools.generator');
        Route::post('ai-tools/generator', [\App\Http\Controllers\AiToolsController::class, 'generateExercise'])->name('ai-tools.generator.store');
        Route::get('ai-tools/writing-corrector', [\App\Http\Controllers\AiToolsController::class, 'writingCorrector'])->name('ai-tools.writing-corrector');
        Route::post('ai-tools/writing-corrector', [\App\Http\Controllers\AiToolsController::class, 'submitWriting'])->name('ai-tools.writing-corrector.store');
        Route::get('ai-tools/explainer', [\App\Http\Controllers\AiToolsController::class, 'explainer'])->name('ai-tools.explainer');
        Route::post('ai-tools/explainer/ask', [\App\Http\Controllers\AiToolsController::class, 'askExplainer'])->name('ai-tools.explainer.ask');
        Route::get('ai-tools/recommendations', [\App\Http\Controllers\AiToolsController::class, 'recommendations'])->name('ai-tools.recommendations');

        // Vocabulary
        Route::prefix('vocabulary')->name('vocabulary.')->group(function () {
            Route::get('/', [VocabularyController::class, 'index'])->name('index');
            Route::get('/learn', [VocabularyController::class, 'learn'])->name('learn');
            Route::get('/review', [VocabularyController::class, 'review'])->name('review');
            Route::post('/', [VocabularyController::class, 'store'])->name('store');
            Route::post('/{vocab}/review', [VocabularyController::class, 'submitReview'])->name('submit-review');
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
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
