<?php

/*
 * Releves mensuels du super administrateur (lots C, D et E) : eleves actifs et
 * paliers, part PrePla des acces vendus, commissions de parrainage.
 *
 * Charge DANS le groupe admin.* de routes/web.php : middleware auth +
 * EnsureSuperAdmin, prefixe « admin/ », noms « admin. ».
 *
 * Le menu du super administrateur affiche « Relevés » des que la route nommee
 * admin.releves.index existe (HandleInertiaRequests, cle menus).
 *
 * Prevu : GET releves (admin.releves.index), et ses exports.
 */

use App\Http\Controllers\SuperAdmin\ReleveController;
use Illuminate\Support\Facades\Route;

// La page des releves (onglets). Onglet « Eleves actifs » (lot E) : par mois ou
// par centre, export CSV, et arret manuel d'un mois termine (meme regle que la
// commande centres:actifs).
Route::get('releves', [ReleveController::class, 'index'])->name('releves.index');
Route::get('releves/actifs.csv', [ReleveController::class, 'actifsCsv'])->name('releves.actifs.csv');
Route::post('releves/actifs/calculer', [ReleveController::class, 'actifsCalculer'])->name('releves.actifs.calculer');

// Onglet « Revente » (lot C) : part PrePla des acces vendus par les centres en
// formule revente (30 %), recalcul d'un mois (brouillons seulement) et statut.
Route::get('releves/revente', [\App\Http\Controllers\SuperAdmin\ReleveReventeController::class, 'index'])->name('releves.revente');
Route::post('releves/revente/calculer', [\App\Http\Controllers\SuperAdmin\ReleveReventeController::class, 'calculer'])->name('releves.revente.calculer');
Route::patch('releves/revente/{statement}/statut', [\App\Http\Controllers\SuperAdmin\ReleveReventeController::class, 'statut'])
    ->whereNumber('statement')
    ->name('releves.revente.statut');
