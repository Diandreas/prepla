<?php

/*
 * Le createur d'exercices et ses liens avec les devoirs.
 *
 * Charge DANS le groupe center.* de routes/web.php : middleware auth +
 * EnsureCenterStaff, prefixe « center/ », noms « center. ».
 *
 * « assignments/create/vivier » a deux segments : il ne se confond pas avec
 * « assignments/{assignment} », declare plus haut dans le groupe.
 */

use App\Http\Controllers\Center\AssignmentController;
use App\Http\Controllers\Center\ExerciseBuilderController;
use Illuminate\Support\Facades\Route;

// Les parties officielles de l'examen, par type : « Lesen, Teil 2 » sur les cartes.
Route::get('exercises/parties', [ExerciseBuilderController::class, 'parties'])->name('exercises.parties');

// La banque PrePla de l'examen de la classe, dans la creation d'un devoir.
Route::get('assignments/create/vivier', [AssignmentController::class, 'vivier'])->name('assignments.vivier');

// Ajouter des exercices a un devoir existant, depuis sa page.
Route::post('assignments/{assignment}/exercises', [AssignmentController::class, 'addExercises'])->name('assignments.exercises.add');
