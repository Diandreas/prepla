<?php

/*
 * Import en masse cote centre : eleves (CSV) et QCM (CSV ou JSON).
 *
 * Charge DANS le groupe center.* de routes/web.php : middleware auth +
 * EnsureCenterStaff, prefixe « center/ », noms « center. ». Les droits fins
 * (classes encadrees, creation d'exercices) sont verifies par ImportController.
 */

use App\Http\Controllers\Center\ImportController;
use Illuminate\Support\Facades\Route;

Route::get('import', [ImportController::class, 'page'])->name('import.page');

Route::get('import/eleves/modele.csv', [ImportController::class, 'modeleEleves'])->name('import.eleves.modele');
Route::post('import/eleves/apercu', [ImportController::class, 'apercuEleves'])->name('import.eleves.apercu');
Route::post('import/eleves', [ImportController::class, 'importerEleves'])->name('import.eleves');

Route::get('import/exercices/modele.csv', [ImportController::class, 'modeleExercices'])->name('import.exercices.modele');
Route::post('import/exercices/apercu', [ImportController::class, 'apercuExercices'])->name('import.exercices.apercu');
Route::post('import/exercices', [ImportController::class, 'importerExercices'])->name('import.exercices');
