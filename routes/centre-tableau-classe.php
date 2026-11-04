<?php

/*
 * Le tableau de bord d'une classe (TableauClasseController) : chiffres de la
 * semaine, activite, competences, classement des eleves. Reponse JSON.
 *
 * Charge DANS le groupe center.* de routes/web.php : middleware auth +
 * EnsureCenterStaff, prefixe « center/ », noms « center. ».
 *
 * Droits : ClassroomPolicy::view.
 */

use App\Http\Controllers\Center\TableauClasseController;
use Illuminate\Support\Facades\Route;

Route::get('classes/{classroom}/tableau', TableauClasseController::class)->name('classes.tableau');
