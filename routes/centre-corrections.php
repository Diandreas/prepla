<?php

/*
 * Correction humaine par le centre : file « A corriger », correction d'une
 * production, ecoute de l'audio d'une prise de parole.
 *
 * Charge DANS le groupe center.* de routes/web.php : middleware auth +
 * EnsureCenterStaff, prefixe « center/ », noms « center. ». Droits dans
 * CorrectionController (centre courant, classes animees).
 */

use App\Http\Controllers\Center\CorrectionController;
use Illuminate\Support\Facades\Route;

Route::get('corrections', [CorrectionController::class, 'index'])->name('corrections.index');
Route::get('corrections/{attempt}', [CorrectionController::class, 'show'])->whereNumber('attempt')->name('corrections.show');
Route::post('corrections/{attempt}', [CorrectionController::class, 'store'])->whereNumber('attempt')->name('corrections.store');
Route::get('corrections/{attempt}/audio/{question}', [CorrectionController::class, 'audio'])->whereNumber('attempt')->name('corrections.audio');
