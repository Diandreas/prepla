<?php

/*
 * La salle PrePla d'un live (LiveSalleController) : eleves de la classe et
 * equipe qui anime. Charge au niveau racine de routes/web.php.
 *
 * L'etat est relu par sondage HTTP court (GET live/{live}/etat) : les routes
 * d'ecriture sont limitees (throttle) pour proteger le petit serveur.
 */

use App\Http\Controllers\LiveSalleController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('lives/prochains', [LiveSalleController::class, 'prochains'])->name('live.prochains');

    Route::prefix('live/{live}')->whereNumber('live')->name('live.')->group(function () {
        Route::get('/', [LiveSalleController::class, 'show'])->name('show');
        Route::get('etat', [LiveSalleController::class, 'etat'])->middleware('throttle:120,1')->name('etat');
        Route::get('rejoindre', [LiveSalleController::class, 'rejoindre'])->name('rejoindre');
        Route::get('replay', [LiveSalleController::class, 'replay'])->name('replay');
        Route::get('calendrier.ics', [LiveSalleController::class, 'ics'])->name('ics');

        Route::middleware('throttle:60,1')->group(function () {
            Route::post('main', [LiveSalleController::class, 'main'])->name('main');
            Route::post('messages', [LiveSalleController::class, 'message'])->name('message');
            Route::post('questions/{message}/voter', [LiveSalleController::class, 'voter'])->name('voter');
            Route::post('questions/{message}/repondue', [LiveSalleController::class, 'questionRepondue'])->name('question.repondue');
            Route::post('sondages', [LiveSalleController::class, 'sondage'])->name('sondage');
            Route::post('sondages/{sondage}/repondre', [LiveSalleController::class, 'repondre'])->name('sondage.repondre');
            Route::post('sondages/{sondage}/fermer', [LiveSalleController::class, 'fermerSondage'])->name('sondage.fermer');
            Route::post('ressources', [LiveSalleController::class, 'ressource'])->name('ressource');
            Route::post('mains/{user}/baisser', [LiveSalleController::class, 'baisserMain'])->name('main.baisser');
            Route::put('notes', [LiveSalleController::class, 'notes'])->name('notes');
            // Salle video Daily.co integree : jeton personnel et presence reelle.
            Route::post('video/jeton', [LiveSalleController::class, 'jeton'])->name('video.jeton');
            Route::post('video/presence', [LiveSalleController::class, 'presenceVideo'])->name('video.presence');
        });
    });
});
