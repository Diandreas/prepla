<?php

/*
 * Activation des comptes payes par Mobile Money. Charge DANS le groupe admin.*
 * de routes/web.php : auth + EnsureSuperAdmin, prefixe « admin/ ».
 */

use App\Http\Controllers\SuperAdmin\ActivationController;
use Illuminate\Support\Facades\Route;

Route::get('activations', [ActivationController::class, 'index'])->name('activations.index');
Route::post('activations', [ActivationController::class, 'store'])->name('activations.store');
Route::delete('activations/{activation}', [ActivationController::class, 'destroy'])->name('activations.destroy');
