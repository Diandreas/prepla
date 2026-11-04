<?php

/*
 * Adresse d'un centre (lot B) : la page montree a un compte connecte qui suit
 * un autre etablissement (EnsureMembreDuCentreDeLHote l'y envoie).
 *
 * Charge au niveau racine de routes/web.php.
 */

use App\Http\Controllers\AutreEtablissementController;
use Illuminate\Support\Facades\Route;

Route::get('etablissement', AutreEtablissementController::class)
    ->middleware('auth')
    ->name('centre.autre-etablissement');
