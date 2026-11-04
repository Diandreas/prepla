<?php

/*
 * Parrainage par un centre (lot D).
 *
 * Charge au niveau racine de routes/web.php, dans le seul groupe web : le lien
 * /r/{code} s'ouvre SANS compte, puisqu'il mene a l'inscription. Une route qui
 * demande un compte declare ici son propre middleware ('auth', EnsureSuperAdmin...).
 */

use App\Http\Controllers\Center\ParrainageController as ParrainageDuCentre;
use App\Http\Controllers\ParrainageController;
use App\Http\Controllers\SuperAdmin\CommissionController;
use App\Http\Middleware\EnsureCenterStaff;
use App\Http\Middleware\EnsureSuperAdmin;
use Illuminate\Support\Facades\Route;

// Le lien partage par le centre. Limite : il ne sert pas a essayer des codes.
Route::get('r/{code}', ParrainageController::class)
    ->middleware('throttle:30,1')
    ->name('parrainage.lien');

// Le responsable : son lien, ses comptes parraines, ses commissions du mois.
// Memes protections que le groupe center.* (le controleur reserve au responsable).
Route::get('center/parrainage', ParrainageDuCentre::class)
    ->middleware(['auth', EnsureCenterStaff::class])
    ->name('center.parrainage.index');

// Le super administrateur : commissions par centre et par mois.
Route::get('admin/commissions', CommissionController::class)
    ->middleware(['auth', EnsureSuperAdmin::class])
    ->name('admin.commissions.index');
