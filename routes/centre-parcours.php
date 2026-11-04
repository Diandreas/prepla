<?php

/*
 * Le parcours d'une classe, construit par le centre (ParcoursClasseController).
 *
 * Charge DANS le groupe center.* de routes/web.php : middleware auth +
 * EnsureCenterStaff, prefixe « center/ », noms « center. ».
 *
 * Droits : ClassroomPolicy::update. Le responsable pour toutes les classes du
 * centre, l'enseignant pour les siennes.
 */

use App\Http\Controllers\Center\ParcoursClasseController;
use Illuminate\Support\Facades\Route;

Route::get('classes/{classroom}/parcours', [ParcoursClasseController::class, 'show'])->name('classes.parcours');
Route::post('classes/{classroom}/parcours', [ParcoursClasseController::class, 'store'])->name('classes.parcours.store');
Route::post('classes/{classroom}/parcours/copier', [ParcoursClasseController::class, 'copier'])->name('classes.parcours.copier');
Route::patch('classes/{classroom}/parcours/{etape}', [ParcoursClasseController::class, 'update'])->whereNumber('etape')->name('classes.parcours.update');
Route::post('classes/{classroom}/parcours/{etape}/deplacer', [ParcoursClasseController::class, 'deplacer'])->whereNumber('etape')->name('classes.parcours.deplacer');
Route::delete('classes/{classroom}/parcours/{etape}', [ParcoursClasseController::class, 'destroy'])->whereNumber('etape')->name('classes.parcours.destroy');
