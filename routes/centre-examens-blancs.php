<?php

/*
 * Examens blancs composes par le centre.
 *
 * Charge DANS le groupe center.* de routes/web.php : middleware auth +
 * EnsureCenterStaff, prefixe « center/ », noms « center. ». Le controleur
 * refuse tout examen blanc qui n'est pas celui du centre courant.
 */

use App\Http\Controllers\Center\ExamenBlancController;
use Illuminate\Support\Facades\Route;

Route::get('examens-blancs', [ExamenBlancController::class, 'index'])->name('examens-blancs.index');
Route::get('examens-blancs/creer', [ExamenBlancController::class, 'create'])->name('examens-blancs.create');
Route::post('examens-blancs', [ExamenBlancController::class, 'store'])->name('examens-blancs.store');
Route::get('examens-blancs/{mockExam}', [ExamenBlancController::class, 'show'])->whereNumber('mockExam')->name('examens-blancs.show');
Route::get('examens-blancs/{mockExam}/modifier', [ExamenBlancController::class, 'edit'])->whereNumber('mockExam')->name('examens-blancs.edit');
Route::put('examens-blancs/{mockExam}', [ExamenBlancController::class, 'update'])->whereNumber('mockExam')->name('examens-blancs.update');
Route::delete('examens-blancs/{mockExam}', [ExamenBlancController::class, 'destroy'])->whereNumber('mockExam')->name('examens-blancs.destroy');
Route::post('examens-blancs/{mockExam}/donner', [ExamenBlancController::class, 'donner'])->whereNumber('mockExam')->name('examens-blancs.donner');
