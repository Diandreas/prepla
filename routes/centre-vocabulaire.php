<?php

/*
 * Le vocabulaire des eleves, vu par leur centre.
 *
 * Charge DANS le groupe center.* de routes/web.php : middleware auth +
 * EnsureCenterStaff, prefixe « center/ », noms « center. ». Lecture seule.
 */

use App\Http\Controllers\Center\VocabulaireController;
use Illuminate\Support\Facades\Route;

Route::get('classes/{classroom}/vocabulaire', [VocabulaireController::class, 'classe'])
    ->name('classes.vocabulaire');
Route::get('students/{user}/vocabulaire', [VocabulaireController::class, 'eleve'])
    ->name('students.vocabulaire');
