<?php

/*
 * Module « Live class » (Google Meet), cote equipe du centre (Center\LiveController).
 *
 * Charge DANS le groupe center.* de routes/web.php : middleware auth +
 * EnsureCenterStaff, prefixe « center/ », noms « center. ».
 * Droits : ClassroomPolicy::update sur la classe du live. Sans identifiants
 * OAuth Google (services.google_meet), tout repond 404.
 */

use App\Http\Controllers\Center\LiveController;
use Illuminate\Support\Facades\Route;

Route::get('lives', [LiveController::class, 'index'])->name('lives.index');
Route::post('classes/{classroom}/lives', [LiveController::class, 'store'])->name('lives.store');
Route::get('lives/{live}', [LiveController::class, 'show'])->whereNumber('live')->name('lives.show');
Route::patch('lives/{live}', [LiveController::class, 'update'])->whereNumber('live')->name('lives.update');
Route::post('lives/{live}/demarrer', [LiveController::class, 'demarrer'])->whereNumber('live')->name('lives.demarrer');
Route::post('lives/{live}/terminer', [LiveController::class, 'terminer'])->whereNumber('live')->name('lives.terminer');
Route::post('lives/{live}/annuler', [LiveController::class, 'annuler'])->whereNumber('live')->name('lives.annuler');

Route::get('google/connecter', [LiveController::class, 'connecter'])->name('google.connecter');
Route::get('google/retour', [LiveController::class, 'retour'])->name('google.retour');
Route::delete('google', [LiveController::class, 'deconnecter'])->name('google.deconnecter');
