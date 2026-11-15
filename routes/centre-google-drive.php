<?php

/*
 * Google Drive du centre : les contenus lourds et les sauvegardes du centre
 * vivent dans SON Drive, pas sur le serveur PrePla.
 *
 * Charge DANS le groupe center.* de routes/web.php : middleware auth +
 * EnsureCenterStaff, prefixe « center/ », noms « center. ». Connexion,
 * deconnexion et sauvegarde : responsable seulement (controleur). Sans
 * identifiants Google dans le .env, tout repond 404.
 */

use App\Http\Controllers\Center\GoogleDriveController;
use Illuminate\Support\Facades\Route;

Route::get('google/connect', [GoogleDriveController::class, 'connect'])->name('google.connect');
Route::get('google/callback', [GoogleDriveController::class, 'callback'])->name('google.callback');
Route::post('google/deconnecter', [GoogleDriveController::class, 'disconnect'])->name('google.disconnect');
Route::post('google/session-envoi', [GoogleDriveController::class, 'sessionEnvoi'])->name('google.upload-session');
Route::post('google/medias', [GoogleDriveController::class, 'enregistrer'])->name('google.media');
Route::get('google/picker', [GoogleDriveController::class, 'picker'])->name('google.picker');
Route::post('google/sauvegarder', [GoogleDriveController::class, 'sauvegarder'])->name('google.backup');
