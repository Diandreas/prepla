<?php

use App\Http\Controllers\LandingController;
use App\Http\Controllers\OnboardingController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', LandingController::class)->name('home');

Route::middleware(['auth'])->group(function () {
    // Onboarding
    Route::prefix('onboarding')->name('onboarding.')->group(function () {
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
        Route::get('dashboard', function () {
            return Inertia::render('dashboard');
        })->name('dashboard');

        // Practice
        Route::get('practice', [\App\Http\Controllers\PracticeController::class, 'index'])->name('practice.index');
        Route::get('practice/{exam}', [\App\Http\Controllers\PracticeController::class, 'examDashboard'])->name('practice.exam');
        Route::get('practice/{exam}/{section}', [\App\Http\Controllers\PracticeController::class, 'sectionDrills'])->name('practice.section');

        // Exercises
        Route::get('exercise/{exercise}', [\App\Http\Controllers\ExerciseController::class, 'show'])->name('exercise.show');
        Route::post('exercise/{exercise}/submit', [\App\Http\Controllers\ExerciseController::class, 'submit'])->name('exercise.submit');
        Route::get('exercise/result/{attempt}', [\App\Http\Controllers\ExerciseController::class, 'result'])->name('exercise.result');

        // AI Tools
        Route::get('ai-tools', [\App\Http\Controllers\AiToolsController::class, 'index'])->name('ai-tools.index');
        Route::get('ai-tools/generator', [\App\Http\Controllers\AiToolsController::class, 'generator'])->name('ai-tools.generator');
        Route::post('ai-tools/generator', [\App\Http\Controllers\AiToolsController::class, 'generateExercise'])->name('ai-tools.generator.store');
        Route::get('ai-tools/writing-corrector', [\App\Http\Controllers\AiToolsController::class, 'writingCorrector'])->name('ai-tools.writing-corrector');
        Route::post('ai-tools/writing-corrector', [\App\Http\Controllers\AiToolsController::class, 'submitWriting'])->name('ai-tools.writing-corrector.store');
        Route::get('ai-tools/explainer', [\App\Http\Controllers\AiToolsController::class, 'explainer'])->name('ai-tools.explainer');
        Route::get('ai-tools/recommendations', [\App\Http\Controllers\AiToolsController::class, 'recommendations'])->name('ai-tools.recommendations');

        // Leaderboard
        Route::get('leaderboard', [\App\Http\Controllers\HomeController::class, 'leaderboard'])->name('leaderboard');

        // Results
        Route::get('results', [\App\Http\Controllers\ResultsController::class, 'index'])->name('results.index');
        Route::get('results/attempts', [\App\Http\Controllers\ResultsController::class, 'attempts'])->name('results.attempts');
        Route::get('results/attempts/{attempt}', [\App\Http\Controllers\ResultsController::class, 'attemptDetail'])->name('results.attempt');

        // Profile extras
        Route::get('profile/achievements', [\App\Http\Controllers\ProfileController::class, 'achievements'])->name('profile.achievements');
        Route::get('profile/stats', [\App\Http\Controllers\ProfileController::class, 'stats'])->name('profile.stats');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
