<?php

/*
 * Domaines des centres (lot B), cote super administrateur.
 *
 * Charge DANS le groupe admin.* de routes/web.php : middleware auth +
 * EnsureSuperAdmin, prefixe « admin/ », noms « admin. ». Le domaine est
 * toujours cherche parmi ceux du centre de l'adresse (scopeBindings) : un
 * identifiant d'un autre centre donne 404.
 */

use App\Http\Controllers\SuperAdmin\DomaineController;
use Illuminate\Support\Facades\Route;

Route::scopeBindings()->group(function () {
    Route::post('centers/{center}/domaines', [DomaineController::class, 'store'])->name('centers.domaines.store');
    Route::post('centers/{center}/domaines/{domaine}/verifier', [DomaineController::class, 'verifier'])->name('centers.domaines.verifier');
    Route::patch('centers/{center}/domaines/{domaine}/principal', [DomaineController::class, 'principal'])->name('centers.domaines.principal');
    Route::delete('centers/{center}/domaines/{domaine}', [DomaineController::class, 'destroy'])->name('centers.domaines.destroy');
});
