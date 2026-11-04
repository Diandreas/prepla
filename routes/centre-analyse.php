<?php

/*
 * Analyse IA du centre : probleme principal et levier de progression d'un eleve,
 * d'une classe ou du centre entier (App\Services\Center\AnalyseIa).
 *
 * Charge DANS le groupe center.* de routes/web.php : middleware auth +
 * EnsureCenterStaff, prefixe « center/ », noms « center. ».
 *
 * GET lit seulement l'analyse gardee (jamais d'appel a l'IA : pas de CSRF sur
 * un GET) ; POST la genere si elle n'est plus a jour, ou la refait avec
 * « actualiser » (une fois par heure et par sujet). Reponses JSON, lues par
 * resources/js/components/center/analyse-ia.tsx.
 *
 * Droits : comme les pages existantes. Un enseignant ne voit que ses classes et
 * leurs eleves, le responsable tout le centre ; l'analyse du centre est reservee
 * au responsable.
 */

use App\Http\Controllers\Center\AnalyseController;
use Illuminate\Support\Facades\Route;

Route::match(['get', 'post'], 'students/{user}/analyse', [AnalyseController::class, 'eleve'])->name('analyses.eleve');
Route::match(['get', 'post'], 'classes/{classroom}/analyse', [AnalyseController::class, 'classe'])->name('analyses.classe');
Route::match(['get', 'post'], 'analyse', [AnalyseController::class, 'centre'])->name('analyses.centre');
