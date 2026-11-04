<?php

/*
 * « Mes devoirs » : le travail donne par le centre, cote eleve.
 *
 * Charge au niveau racine de routes/web.php. Un compte qui n'est pas eleve d'un
 * centre actif est renvoye vers son accueil (MesDevoirsController).
 *
 * GET devoirs (devoirs.index), GET devoirs/{assignment} (devoirs.show).
 */

use App\Http\Controllers\MesDevoirsController;
use App\Http\Middleware\EnsureOnboardingComplete;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', EnsureOnboardingComplete::class])->group(function () {
    Route::get('devoirs', [MesDevoirsController::class, 'index'])->name('devoirs.index');
    Route::get('devoirs/{assignment}', [MesDevoirsController::class, 'show'])->whereNumber('assignment')->name('devoirs.show');
});
