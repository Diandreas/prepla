<?php

use App\Models\User;
use App\Models\UserError;
use App\Services\Content\ErreursDeRedaction;

/**
 * Un enseignant qui lit une copie et y voit un vocabulaire pauvre met du
 * vocabulaire au programme de la semaine suivante.
 *
 * L'application, elle, affichait la correction puis l'oubliait : aucune erreur
 * n'etait enregistree, donc ni le centre de revision ni le parcours n'en
 * savaient quoi que ce soit. On pouvait faire la meme faute d'accord dans dix
 * redactions de suite sans que rien ne change.
 */
function apprenantQuiRedige(): User
{
    return User::create(['name' => 'Aminata Bah', 'email' => 'red'.uniqid().'@exemple.test',
        'password' => 'mot-de-passe-de-test', 'email_verified_at' => now()]);
}

test('chaque correction devient une erreur memorisee et categorisee', function () {
    $u = apprenantQuiRedige();

    $categories = app(ErreursDeRedaction::class)->memoriser($u, [
        ['original' => 'je suis aller', 'corrected' => 'je suis allé', 'category' => 'grammar', 'explanation' => 'Participe passé.'],
        ['original' => 'beaucoup des gens', 'corrected' => 'beaucoup de gens', 'category' => 'grammar'],
        ['original' => 'chose', 'corrected' => 'phénomène', 'category' => 'vocabulary'],
    ], 'Hier je suis aller en ville, beaucoup des gens, une chose étrange.');

    expect(UserError::where('user_id', $u->id)->count())->toBe(3)
        ->and($categories)->toBe(['grammar', 'vocabulary']);

    $erreur = UserError::where('user_id', $u->id)->where('error_category', 'vocabulary')->first();
    expect($erreur->user_answer)->toBe('chose')
        ->and($erreur->correct_answer)->toBe('phénomène')
        ->and($erreur->skill_type)->toBe('writing')
        ->and($erreur->mastered)->toBeFalse();
});

test('la revision espacee demarre pour une erreur nouvelle', function () {
    $u = apprenantQuiRedige();

    app(ErreursDeRedaction::class)->memoriser($u, [
        ['original' => 'je suis aller', 'corrected' => 'je suis allé', 'category' => 'grammar'],
    ], 'Je suis aller.');

    $erreur = UserError::where('user_id', $u->id)->first();
    expect($erreur->next_review_at)->not->toBeNull()
        ->and($erreur->interval_days)->toBe(1);
});

test('la meme faute refaite demain reveille l erreur au lieu d en creer une seconde', function () {
    $u = apprenantQuiRedige();
    $service = app(ErreursDeRedaction::class);

    $service->memoriser($u, [['original' => 'je suis aller', 'corrected' => 'je suis allé', 'category' => 'grammar']], 'Texte un.');
    $service->memoriser($u, [['original' => 'Je suis aller', 'corrected' => 'je suis allé', 'category' => 'grammar']], 'Texte deux, un autre jour.');

    // Une erreur, pas deux : sinon le centre de revision se remplit de doublons
    // et l'apprenant revoit dix fois la meme chose.
    expect(UserError::where('user_id', $u->id)->count())->toBe(1);
});

test('l enonce garde un bout de phrase, pour que la faute soit reconnaissable', function () {
    $u = apprenantQuiRedige();

    app(ErreursDeRedaction::class)->memoriser($u, [
        ['original' => 'leur', 'corrected' => 'leurs', 'category' => 'grammar'],
    ], "Les enfants ont pris leur affaires et sont partis en courant vers l'école.");

    // « leur » tout seul ne dirait rien a l'apprenant au moment de la revision.
    $erreur = UserError::where('user_id', $u->id)->first();
    expect($erreur->question_text)->toContain('enfants')
        ->and($erreur->question_text)->toContain('leur');
});

test('une categorie inconnue est ramenee a ce que le parcours sait reprendre', function () {
    $u = apprenantQuiRedige();

    $categories = app(ErreursDeRedaction::class)->memoriser($u, [
        ['original' => 'x', 'corrected' => 'y', 'category' => 'style-inventé-par-l-IA'],
    ], 'x');

    expect($categories)->toBe(['writing'])
        ->and(UserError::where('user_id', $u->id)->value('error_category'))->toBe('writing');
});

test('une correction vide ou identique n apprend rien et n est pas gardee', function () {
    $u = apprenantQuiRedige();

    $categories = app(ErreursDeRedaction::class)->memoriser($u, [
        ['original' => '', 'corrected' => 'quelque chose'],
        ['original' => 'pareil', 'corrected' => 'pareil'],
        ['original' => 'juste', 'corrected' => ''],
        'pas un objet',
    ], 'texte');

    expect(UserError::where('user_id', $u->id)->count())->toBe(0)
        ->and($categories)->toBe([]);
});

test('une redaction entierement a reecrire ne noie pas le centre de revision', function () {
    $u = apprenantQuiRedige();

    $beaucoup = [];
    for ($i = 0; $i < 40; $i++) {
        $beaucoup[] = ['original' => 'faute'.$i, 'corrected' => 'juste'.$i, 'category' => 'grammar'];
    }
    app(ErreursDeRedaction::class)->memoriser($u, $beaucoup, 'texte');

    // Au-dela, ce n'est plus une correction mais une reecriture : en garder
    // quarante rendrait la revision decourageante et inutilisable.
    expect(UserError::where('user_id', $u->id)->count())->toBe(12);
});
