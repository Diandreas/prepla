<?php

/*
 * Parametres du centre : l'accueil qui rassemble tous les reglages du
 * responsable (marque, examens, adresse web, formule et sieges, acces vendus,
 * parrainage, equipe) et mene a chaque page de reglage.
 *
 * Charge DANS le groupe center.* de routes/web.php : middleware auth +
 * EnsureCenterStaff, prefixe « center/ », noms « center. ».
 *
 * EnsureCenterStaff laisse passer un enseignant : le controleur reserve la page
 * au responsable (centerRole() === 'center_admin').
 *
 * Le menu du responsable affiche « Paramètres du centre » des que la route
 * nommee center.parametres existe (HandleInertiaRequests, cle menus).
 *
 * GET parametres (center.parametres).
 */

use App\Http\Controllers\Center\ParametresController;
use Illuminate\Support\Facades\Route;

Route::get('parametres', ParametresController::class)->name('parametres');
