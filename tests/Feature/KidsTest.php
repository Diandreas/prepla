<?php

use App\Models\KidsEnfant;
use App\Models\KidsResultat;
use App\Models\User;
use App\Services\Kids\KidsProgres;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * PrePla Kids : un parent ajoute ses enfants, l'enfant joue depuis la session
 * de son parent, gagne des etoiles qui ouvrent les themes suivants.
 */
beforeEach(fn () => $this->withoutVite());

function kidsParent(array $attrs = []): User
{
    return User::factory()->create($attrs);
}

function kidsEnfant(User $parent, array $attrs = []): KidsEnfant
{
    return KidsEnfant::create(array_merge(['parent_id' => $parent->id, 'prenom' => 'Lina', 'age' => 7, 'avatar' => '🦊', 'langue' => 'english', 'langue_aide' => 'fr'], $attrs));
}

test('la page PrePla Kids se presente a qui n a pas de compte', function () {
    $this->get(route('kids.accueil'))->assertOk()
        ->assertInertia(fn (Assert $p) => $p->component('kids/accueil')->where('connecte', false));
});

test('un parent inscrit depuis PrePla Kids arrive a l espace parent, pas au questionnaire adulte', function () {
    $this->get(route('kids.inscription'))->assertRedirect(route('register'));
    $this->post(route('register'), [
        'name' => 'Maman Lina', 'email' => 'maman@exemple.test', 'phone' => '+237 6 55 44 33 22',
        'password' => 'motdepasse-solide', 'password_confirmation' => 'motdepasse-solide',
    ])->assertRedirect(route('kids.parents'));

    $this->get(route('kids.parents'))->assertOk()->assertInertia(fn (Assert $p) => $p->component('kids/parents'));
});

test('le parent ajoute un enfant, et lui seul peut le faire jouer ou le modifier', function () {
    $parent = kidsParent();
    $this->actingAs($parent)->post(route('kids.enfants.store'), ['prenom' => 'Lina', 'age' => 7, 'avatar' => '🐼', 'langue' => 'german', 'langue_aide' => 'fr'])
        ->assertSessionHasNoErrors();
    $lina = KidsEnfant::sole();
    expect($lina->only(['prenom', 'langue', 'parent_id']))->toBe(['prenom' => 'Lina', 'langue' => 'german', 'parent_id' => $parent->id]);

    $autre = kidsParent();
    $this->actingAs($autre)->post(route('kids.jouer', $lina))->assertForbidden();
    $this->actingAs($autre)->put(route('kids.enfants.update', $lina), ['prenom' => 'X', 'age' => 7, 'avatar' => '🐼', 'langue' => 'german', 'langue_aide' => 'fr'])->assertForbidden();
});

test('l enfant choisi voit sa carte : seul le premier theme est ouvert au depart', function () {
    $parent = kidsParent();
    $lina = kidsEnfant($parent);

    $this->actingAs($parent)->post(route('kids.jouer', $lina))->assertRedirect(route('kids.carte'));
    $this->get(route('kids.carte'))->assertInertia(fn (Assert $p) => $p->component('kids/carte')
        ->where('enfant.prenom', 'Lina')
        ->where('themes.0.ouvert', true)
        ->where('themes.1.ouvert', false)
        ->where('themes.1.verrou', 'etoiles'));
});

test('les mots sont dans la langue apprise, avec la traduction dans la langue d aide', function () {
    expect(KidsProgres::mots('animaux', 'german', 'fr')[0])->toMatchArray(['image' => '🐶', 'mot' => 'der Hund', 'aide' => 'le chien'])
        ->and(KidsProgres::mots('animaux', 'french', 'fr')[0]['aide'])->toBeNull()
        ->and(KidsProgres::mots('animaux', 'french', 'en')[0])->toMatchArray(['image' => '🐶', 'mot' => 'le chien', 'aide' => 'dog'])
        // Sans illustration deposee, l'emoji suffit.
        ->and(KidsProgres::mots('ecole', 'english')[3]['fichier'])->toBeNull()
        ->and(KidsProgres::cle('school bag'))->toBe('school-bag')
        ->and(KidsProgres::cle('T-shirt'))->toBe('t-shirt');
});

test('une partie rapporte des etoiles, et rejouer moins bien ne fait rien perdre', function () {
    $parent = kidsParent();
    $lina = kidsEnfant($parent);
    $this->actingAs($parent)->post(route('kids.jouer', $lina));

    $this->postJson(route('kids.resultat'), ['theme' => 'animaux', 'activite' => 'ecoute', 'score' => 75, 'duree' => 60, 'mots_rates' => ['cow']])
        ->assertOk()->assertJson(['etoiles' => 2, 'gagnees' => 2, 'total' => 2, 'serie' => 1]);
    $this->postJson(route('kids.resultat'), ['theme' => 'animaux', 'activite' => 'ecoute', 'score' => 30])
        ->assertJson(['etoiles' => 0, 'gagnees' => 0, 'total' => 2]);
    $this->postJson(route('kids.resultat'), ['theme' => 'animaux', 'activite' => 'ecoute', 'score' => 100])
        ->assertJson(['etoiles' => 3, 'gagnees' => 1, 'total' => 3]);
});

test('quatre etoiles sur un theme ouvrent le suivant', function () {
    $parent = kidsParent();
    $lina = kidsEnfant($parent);
    $this->actingAs($parent)->post(route('kids.jouer', $lina));

    $this->postJson(route('kids.resultat'), ['theme' => 'couleurs', 'activite' => 'ecoute', 'score' => 100])->assertForbidden();
    $this->postJson(route('kids.resultat'), ['theme' => 'animaux', 'activite' => 'decouvrir', 'score' => 100]);
    $this->postJson(route('kids.resultat'), ['theme' => 'animaux', 'activite' => 'ecoute', 'score' => 70]);

    expect(KidsProgres::themeOuvert($lina->fresh(), 'couleurs', $parent))->toBeTrue();
    $this->get(route('kids.theme', 'couleurs'))->assertOk()->assertInertia(fn (Assert $p) => $p->component('kids/theme')->where('theme.slug', 'couleurs'));
});

test('au-dela des themes gratuits, il faut PrePla Plus', function () {
    $parent = kidsParent();
    $lina = kidsEnfant($parent);
    // Les trois themes gratuits reussis.
    foreach (['animaux', 'couleurs', 'nombres'] as $theme) {
        KidsResultat::create(['enfant_id' => $lina->id, 'theme' => $theme, 'activite' => 'decouvrir', 'score' => 100, 'etoiles' => 3]);
        KidsResultat::create(['enfant_id' => $lina->id, 'theme' => $theme, 'activite' => 'ecoute', 'score' => 100, 'etoiles' => 3]);
    }

    $carte = collect(KidsProgres::carte($lina, $parent))->keyBy('slug');
    expect($carte['famille']['ouvert'])->toBeFalse()->and($carte['famille']['verrou'])->toBe('plus');

    $parent->forceFill(['premium_until' => now()->addMonth()])->save();
    expect(KidsProgres::themeOuvert($lina, 'famille', $parent->fresh()))->toBeTrue();
});

test('le parent voit le bilan de son enfant, et sortir du mode enfant le ramene a son espace', function () {
    $parent = kidsParent();
    $lina = kidsEnfant($parent);
    $this->actingAs($parent)->post(route('kids.jouer', $lina));
    $this->postJson(route('kids.resultat'), ['theme' => 'animaux', 'activite' => 'ecoute', 'score' => 90, 'duree' => 120, 'mots_rates' => ['lion']]);

    $this->post(route('kids.quitter'))->assertRedirect(route('kids.parents'));
    $this->get(route('kids.carte'))->assertRedirect(route('kids.accueil'));

    $this->get(route('kids.parents'))->assertInertia(fn (Assert $p) => $p
        ->where('enfants.0.bilan.etoiles', 3)
        ->where('enfants.0.bilan.mots_appris', 8)
        ->where('enfants.0.bilan.parties_semaine', 1)
        ->where('enfants.0.bilan.minutes_semaine', 2)
        ->where('enfants.0.bilan.a_revoir', ['lion']));
});

test('chaque theme a huit mots, une image et trois langues', function () {
    foreach (config('kids.themes') as $slug => $theme) {
        expect($theme['mots'])->toHaveCount(8, $slug);
        foreach ($theme['mots'] as $mot) {
            expect($mot)->toHaveCount(4)->and(array_filter($mot))->toHaveCount(4);
        }
        expect(array_unique(array_column($theme['mots'], 1)))->toHaveCount(8, "mots anglais en double dans {$slug}");
    }
});
