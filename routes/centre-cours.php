<?php

/*
 * Createur de parcours du centre (CoursCentreController) : parcours, modules,
 * seances, blocs, apercu eleve, suivi enseignant. Herite du groupe centre
 * (auth, EnsureCenterStaff, prefixe center/, noms center.*).
 */

use App\Http\Controllers\Center\CoursCentreController as C;
use Illuminate\Support\Facades\Route;

Route::get('cours', [C::class, 'index'])->name('cours.index');
Route::post('cours', [C::class, 'store'])->name('cours.store');
Route::get('cours/{parcours}', [C::class, 'edit'])->whereNumber('parcours')->name('cours.edit');
Route::patch('cours/{parcours}', [C::class, 'update'])->whereNumber('parcours')->name('cours.update');
Route::delete('cours/{parcours}', [C::class, 'destroy'])->whereNumber('parcours')->name('cours.destroy');
Route::post('cours/{parcours}/dupliquer', [C::class, 'dupliquer'])->whereNumber('parcours')->name('cours.dupliquer');
Route::post('cours/{parcours}/ordre', [C::class, 'ordre'])->whereNumber('parcours')->name('cours.ordre');
Route::post('cours/{parcours}/modules', [C::class, 'ajouterModule'])->whereNumber('parcours')->name('cours.modules.store');
Route::post('cours/{parcours}/media', [C::class, 'televerser'])->whereNumber('parcours')->name('cours.media');
Route::get('cours/{parcours}/suivi', [C::class, 'suivi'])->whereNumber('parcours')->name('cours.suivi');

Route::patch('cours/modules/{module}', [C::class, 'modifierModule'])->name('cours.modules.update');
Route::delete('cours/modules/{module}', [C::class, 'supprimerModule'])->name('cours.modules.destroy');
Route::post('cours/modules/{module}/seances', [C::class, 'ajouterSeance'])->name('cours.seances.store');

Route::patch('cours/seances/{seance}', [C::class, 'modifierSeance'])->name('cours.seances.update');
Route::delete('cours/seances/{seance}', [C::class, 'supprimerSeance'])->name('cours.seances.destroy');
Route::post('cours/seances/{seance}/dupliquer', [C::class, 'dupliquerSeance'])->name('cours.seances.dupliquer');
Route::get('cours/seances/{seance}/apercu', [C::class, 'apercu'])->name('cours.seances.apercu');
Route::post('cours/seances/{seance}/blocs', [C::class, 'ajouterBloc'])->name('cours.blocs.store');

Route::patch('cours/blocs/{bloc}', [C::class, 'modifierBloc'])->name('cours.blocs.update');
Route::delete('cours/blocs/{bloc}', [C::class, 'supprimerBloc'])->name('cours.blocs.destroy');

Route::patch('cours/productions/{production}', [C::class, 'corriger'])->name('cours.productions.corriger');
