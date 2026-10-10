<?php

use App\Models\Exam;
use App\Models\ExamBlueprint;
use App\Models\Language;
use Database\Seeders\ExamSeeder;

/**
 * Un examen a déclinaisons perdait ses épreuves FACULTATIVES : le seeder ne
 * lisait que « mandatory_sections ». Le TCF n'a donc jamais eu d'expression
 * écrite ni d'expression orale en base — alors que les deux sont obligatoires
 * pour le TCF Canada et le TCF Québec. Un candidat qui prépare son dossier
 * d'immigration ne pouvait s'entraîner ni à l'écrit ni à l'oral.
 */
test('les epreuves facultatives d un examen a declinaisons sont bien creees', function () {
    Language::firstOrCreate(
        ['slug' => 'french'],
        ['name' => 'Français', 'native_name' => 'Français', 'flag' => 'fr']
    );

    (new ExamSeeder)->run();

    $tcf = Exam::where('slug', 'tcf')->first();
    expect($tcf)->not->toBeNull();

    $slugs = $tcf->sections()->pluck('slug')->all();

    expect($slugs)->toContain('comprehension-orale')     // obligatoire
        ->toContain('maitrise-structures')               // obligatoire
        ->toContain('expression-ecrite')                 // facultative — perdue avant
        ->toContain('expression-orale');                 // facultative — perdue avant

    // Et le plan de chaque déclinaison les embarque, sinon un sujet blanc
    // continuerait de sortir sans oral ni écrit.
    $plan = ExamBlueprint::where('exam_id', $tcf->id)->whereNotNull('variant')->first();
    $modules = collect($plan->sections_config ?? [])->pluck('slug');

    expect($modules)->toContain('expression-ecrite')->toContain('expression-orale');
});
