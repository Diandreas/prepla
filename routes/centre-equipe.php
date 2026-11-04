<?php

/*
 * Attribution des classes aux enseignants, depuis la page Equipe. Charge dans
 * le groupe center.* de routes/web.php.
 */

use App\Http\Controllers\Center\EquipeClassesController;
use Illuminate\Support\Facades\Route;

Route::post('equipe/classes/{classroom}', [EquipeClassesController::class, 'attacher'])->name('equipe.classes.attacher');
Route::delete('equipe/classes/{classroom}', [EquipeClassesController::class, 'retirer'])->name('equipe.classes.retirer');
