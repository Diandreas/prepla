<?php

/*
 * PrePla Kids. Charge au niveau racine de routes/web.php : /kids se presente
 * aussi a qui n'a pas de compte. Le reste demande le compte du parent.
 */

use App\Http\Controllers\KidsController;
use App\Http\Controllers\TtsController;
use Illuminate\Support\Facades\Route;

Route::get('kids', [KidsController::class, 'accueil'])->name('kids.accueil');
// « Creer mon compte parent » : l'inscription ramene ensuite a l'espace parent.
Route::get('kids/inscription', [KidsController::class, 'inscription'])->name('kids.inscription');

Route::middleware('auth')->prefix('kids')->name('kids.')->group(function () {
    Route::get('parents', [KidsController::class, 'parents'])->name('parents');
    Route::post('enfants', [KidsController::class, 'store'])->name('enfants.store');
    Route::put('enfants/{enfant}', [KidsController::class, 'update'])->name('enfants.update');
    Route::delete('enfants/{enfant}', [KidsController::class, 'destroy'])->name('enfants.destroy');
    Route::post('jouer/{enfant}', [KidsController::class, 'jouer'])->name('jouer');
    Route::post('quitter', [KidsController::class, 'quitter'])->name('quitter');
    Route::get('carte', [KidsController::class, 'carte'])->name('carte');
    Route::get('theme/{theme}', [KidsController::class, 'theme'])->name('theme');
    Route::get('jeu/{theme}/{activite}', [KidsController::class, 'jeu'])->name('jeu');
    Route::post('resultat', [KidsController::class, 'resultat'])->middleware('throttle:60,1')->name('resultat');
    // La voix des mots : le meme service que l'application, hors du questionnaire
    // adulte qu'un parent de PrePla Kids n'a pas a remplir. Fichiers mis en cache.
    Route::post('voix', [TtsController::class, 'speak'])->middleware('throttle:120,1')->name('voix');
    // « Dis-le ! » : la transcription d'un mot dit au micro.
    Route::post('dis', [KidsController::class, 'dis'])->middleware('throttle:40,1')->name('dis');
});
