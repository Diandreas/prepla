<?php

/*
 * Delegues : codes promo personnels et commissions.
 *
 * Charge au niveau racine de routes/web.php, dans le seul groupe web : le lien
 * /d/{code} s'ouvre SANS compte. Chaque route qui demande un compte declare son
 * propre middleware.
 */

use App\Http\Controllers\DelegueController;
use App\Http\Controllers\SuperAdmin\DelegueController as DeleguesAdmin;
use App\Http\Middleware\EnsureSuperAdmin;
use Illuminate\Support\Facades\Route;

Route::get('d/{code}', [DelegueController::class, 'lien'])
    ->middleware('throttle:30,1')
    ->name('delegue.lien');

Route::middleware('auth')->group(function () {
    Route::post('abonnement/code', [DelegueController::class, 'appliquer'])
        ->middleware('throttle:10,1')
        ->name('delegue.appliquer');
    Route::get('mon-code', [DelegueController::class, 'monCode'])->name('delegue.mon-code');
});

Route::middleware(['auth', EnsureSuperAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('delegues', [DeleguesAdmin::class, 'index'])->name('delegues.index');
    Route::post('delegues', [DeleguesAdmin::class, 'store'])->name('delegues.store');
    Route::put('delegues/{delegue}', [DeleguesAdmin::class, 'update'])->name('delegues.update');
    Route::post('delegues/{delegue}/verser', [DeleguesAdmin::class, 'verser'])->name('delegues.verser');
});
