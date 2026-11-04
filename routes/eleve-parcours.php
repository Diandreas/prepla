<?php

/*
 * « Mon parcours » : le parcours construit par le centre pour la classe de
 * l'eleve (centre en « Parcours créé par le centre »).
 *
 * Charge au niveau racine de routes/web.php. Un compte qui n'est pas eleve d'un
 * centre actif est renvoye vers son accueil (MonParcoursController).
 *
 * GET mon-parcours (parcours.index).
 */

use App\Http\Controllers\MonParcoursController;
use App\Http\Middleware\EnsureOnboardingComplete;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', EnsureOnboardingComplete::class])->group(function () {
    Route::get('mon-parcours', MonParcoursController::class)->name('parcours.index');
});
