<?php

/*
 * Personnalisation du centre (lot A) : nom affiche, logo, couleurs, examens proposes.
 *
 * Charge DANS le groupe center.* de routes/web.php : middleware auth +
 * EnsureCenterStaff, prefixe « center/ », noms « center. ». EnsureDisplayedCenter
 * (en-tete X-Prepla-Centre) protege deja toute ecriture, il est dans le groupe web.
 *
 * EnsureCenterStaff laisse passer un enseignant : le controleur reserve chaque
 * action au responsable (centerRole() === 'center_admin').
 *
 * Le menu du responsable affiche « Personnalisation » des que la route nommee
 * center.personnalisation.edit existe (HandleInertiaRequests, cle menus).
 *
 * GET personnalisation (center.personnalisation.edit), PATCH personnalisation,
 * POST et DELETE personnalisation/logo.
 */

use App\Http\Controllers\Center\PersonnalisationController;
use Illuminate\Support\Facades\Route;

Route::get('personnalisation', [PersonnalisationController::class, 'edit'])->name('personnalisation.edit');
Route::patch('personnalisation', [PersonnalisationController::class, 'update'])->name('personnalisation.update');
Route::post('personnalisation/logo', [PersonnalisationController::class, 'storeLogo'])->name('personnalisation.logo.store');
Route::delete('personnalisation/logo', [PersonnalisationController::class, 'destroyLogo'])->name('personnalisation.logo.destroy');

// « Contenu proposé aux élèves » : devoirs + entrainement PrePla, ou devoirs seuls.
Route::patch('personnalisation/contenu-eleves', \App\Http\Controllers\Center\ContenuElevesController::class)
    ->name('personnalisation.contenu-eleves');
