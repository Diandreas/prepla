<?php

/*
 * « Mes cours » : les parcours publies par le centre pour les classes de
 * l'eleve (MesCoursController).
 */

use App\Http\Controllers\MesCoursController;
use App\Http\Middleware\EnsureOnboardingComplete;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', EnsureOnboardingComplete::class])->group(function () {
    Route::get('mes-cours', [MesCoursController::class, 'index'])->name('cours.index');
    Route::get('mes-cours/{parcours}', [MesCoursController::class, 'show'])->whereNumber('parcours')->name('cours.show');
    Route::get('mes-cours/seances/{seance}', [MesCoursController::class, 'seance'])->name('cours.seance');
    Route::post('mes-cours/blocs/{bloc}', [MesCoursController::class, 'progression'])->name('cours.progression');
});
