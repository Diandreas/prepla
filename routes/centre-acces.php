<?php

/*
 * Acces vendus par le centre a ses eleves, formule « revente » (lot C).
 *
 * Charge DANS le groupe center.* de routes/web.php : middleware auth +
 * EnsureCenterStaff, prefixe « center/ », noms « center. ». EnsureDisplayedCenter
 * (en-tete X-Prepla-Centre) protege deja toute ecriture, il est dans le groupe web.
 *
 * EnsureCenterStaff laisse passer un enseignant : le controleur reserve chaque
 * action au responsable (centerRole() === 'center_admin').
 *
 * Le menu du responsable affiche « Accès vendus » des que la route nommee
 * center.acces.index existe (HandleInertiaRequests, cle menus).
 *
 * Prevu : GET acces (center.acces.index), POST acces, POST acces/{grant}/couper.
 */

use App\Http\Controllers\Center\AccesController;
use Illuminate\Support\Facades\Route;

Route::get('acces', [AccesController::class, 'index'])->name('acces.index');
Route::post('acces', [AccesController::class, 'store'])->name('acces.store');
Route::post('acces/{grant}/couper', [AccesController::class, 'couper'])
    ->whereNumber('grant')
    ->name('acces.couper');
