<?php

use App\Models\Exam;
use App\Models\ExamBlueprint;
use App\Models\Language;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\AI\PlacementTestService;

/**
 * Deux defauts du parcours d'inscription, lies.
 *
 * 1. On ne pouvait declarer que « jamais etudie », « quelques notions » ou
 *    « je peux communiquer simplement ». Impossible de dire « je suis B2 »,
 *    alors que l'examen qu'on vient de choisir en annonce six. Or quelqu'un qui
 *    prepare telc B2 connait son niveau : on lui imposait vingt minutes de test
 *    pour une information qu'il avait deja.
 *
 * 2. Quand l'appel a l'IA echouait, le test de secours servait de la grammaire
 *    ANGLAISE a tout le monde — un candidat telc Deutsch repartait avec un
 *    niveau d'allemand mesure sur sa grammaire anglaise, enregistre comme
 *    « teste », et tout son parcours se construisait dessus.
 */
function apprenantEnInscription(string $langue = 'german', array $niveaux = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2']): array
{
    $l = Language::firstOrCreate(['slug' => $langue],
        ['name' => ucfirst($langue), 'native_name' => ucfirst($langue), 'flag' => 'de', 'is_active' => true]);
    $exam = Exam::firstOrCreate(['slug' => 'insc-'.$langue], ['language_id' => $l->id, 'name' => 'Examen '.$langue]);

    foreach ($niveaux as $n) {
        ExamBlueprint::firstOrCreate(['exam_id' => $exam->id, 'level' => $n, 'variant' => null],
            ['name' => 'Plan '.$n, 'total_duration_minutes' => 120, 'scoring_config' => [], 'sections_config' => []]);
    }

    $u = User::create(['name' => 'Moussa Traoré', 'email' => 'insc'.uniqid().'@exemple.test',
        'password' => 'mot-de-passe-de-test', 'email_verified_at' => now()]);
    UserProfile::create(['user_id' => $u->id, 'target_exam_id' => $exam->id, 'native_language' => 'Français']);

    return [$u, $exam];
}

test('l ecran de l objectif propose les niveaux que l examen annonce', function () {
    [$u] = apprenantEnInscription('german', ['A1', 'B1', 'B2', 'C1']);

    $props = $this->actingAs($u)->get('/onboarding/goal')->viewData('page')['props'];

    expect($props['niveauxProposes'])->toBe(['A1', 'B1', 'B2', 'C1']);
});

test('les niveaux reviennent dans l ordre du cadre europeen', function () {
    // Enregistres en desordre, ils doivent remonter de A1 a C2 : un apprenant
    // ne cherche pas « C1, A2, B2 » dans une liste.
    [$u, $exam] = apprenantEnInscription('german', ['C1', 'A2', 'B2']);

    $props = $this->actingAs($u)->get('/onboarding/goal')->viewData('page')['props'];

    expect($props['niveauxProposes'])->toBe(['A2', 'B2', 'C1']);
});

test('un niveau avance peut etre declare et evite le test', function () {
    [$u] = apprenantEnInscription();

    // C'est le coeur du probleme : le B2 etait refuse par la validation.
    $this->actingAs($u)->post('/onboarding/goal', ['current_level' => 'B2'])
        ->assertRedirect(route('onboarding.result'));

    $profil = $u->fresh()->profile;
    expect($profil->current_level)->toBe('B2')
        ->and($profil->level_source)->toBe('declared');
});

test('qui ne sait pas son niveau passe toujours le test', function () {
    [$u] = apprenantEnInscription();

    $this->actingAs($u)->post('/onboarding/goal', ['current_level' => 'TEST'])
        ->assertRedirect(route('onboarding.placement'));

    expect($u->fresh()->profile->current_level)->toBeNull();
});

test('le test de secours est ecrit dans la langue visee', function () {
    $service = app(PlacementTestService::class);
    $methode = new ReflectionMethod($service, 'getFallbackFullTest');
    $methode->setAccessible(true);

    $allemand = $methode->invoke($service, 'Allemand', 'telc Deutsch');
    $francais = $methode->invoke($service, 'Français', 'DELF');

    // Un candidat allemand ne doit pas voir « I ___ a student ».
    expect($allemand['section_a'][0]['sentence'])->toContain('Ich')
        ->and($allemand['section_b']['passage'])->not->toContain('Technology has')
        ->and($allemand['section_c']['prompt'])->toContain('Wörter');

    expect($francais['section_a'][0]['sentence'])->toContain('Je')
        ->and($francais['section_c']['prompt'])->toContain('mots');
});

test('le test de secours couvre les six paliers, du A1 au C1', function () {
    $service = app(PlacementTestService::class);
    $methode = new ReflectionMethod($service, 'getFallbackFullTest');
    $methode->setAccessible(true);

    foreach (['Anglais', 'Français', 'Allemand'] as $langue) {
        $test = $methode->invoke($service, $langue, 'Examen');
        $niveaux = array_unique(array_column($test['section_a'], 'level'));
        sort($niveaux);

        expect($niveaux)->toBe(['A1', 'A2', 'B1', 'B2', 'C1'], "langue : {$langue}");
        expect($test['section_a'])->toHaveCount(8, "langue : {$langue}");
    }
});

test('une langue sans test de secours renvoie vers la declaration', function () {
    // Plutot que d'attribuer un niveau mesure dans une autre langue.
    [$u] = apprenantEnInscription('italian');

    $this->actingAs($u)->get('/onboarding/placement')
        ->assertRedirect(route('onboarding.goal'))
        ->assertSessionHas('error');
});

test('les trois langues servies sont bien servables', function () {
    $service = app(PlacementTestService::class);

    expect($service->estServable('Allemand'))->toBeTrue()
        ->and($service->estServable('Français'))->toBeTrue()
        ->and($service->estServable('Anglais'))->toBeTrue()
        ->and($service->estServable('Italien'))->toBeFalse();
});
