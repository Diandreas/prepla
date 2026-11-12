<?php

use App\Models\ActivationManuelle;
use App\Models\CommissionDelegue;
use App\Models\Delegue;
use App\Models\User;
use App\Models\UserProfile;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Delegues : un code promo par personne, qui rattache les etudiants qu'elle
 * amene, leur donne une reduction et lui rapporte un pourcentage de chacun de
 * leurs paiements Mobile Money.
 */
beforeEach(fn () => $this->withoutVite());

function dlAdmin(): User
{
    return User::factory()->create(['role' => 'super_admin']);
}

function dlEtudiant(string $email = 'paul@exemple.cm'): User
{
    $u = User::factory()->create(['email' => $email]);
    UserProfile::factory()->for($u)->create(['onboarding_completed_at' => now()]);

    return $u;
}

function dlDelegue(array $attrs = []): Delegue
{
    return Delegue::create(array_merge(['nom' => 'Marie Ngo', 'code' => 'MARIE', 'taux' => 20, 'reduction' => 10, 'actif' => true], $attrs));
}

test('l administrateur cree un delegue, le code est mis en majuscules sans espace', function () {
    $this->actingAs(dlAdmin())->post(route('admin.delegues.store'), [
        'nom' => 'Marie Ngo', 'code' => 'marie 2026', 'salle' => 'L2 Lettres', 'taux' => 25, 'reduction' => 15, 'actif' => true,
    ])->assertRedirect();

    expect(Delegue::sole()->only(['code', 'taux', 'reduction', 'salle']))
        ->toBe(['code' => 'MARIE2026', 'taux' => 25, 'reduction' => 15, 'salle' => 'L2 Lettres']);
});

test('deux delegues ne peuvent pas avoir le meme code, et la page est reservee a l equipe', function () {
    dlDelegue();
    $this->actingAs(dlAdmin())->post(route('admin.delegues.store'), ['nom' => 'Autre', 'code' => 'Marie', 'taux' => 20, 'reduction' => 10])
        ->assertSessionHasErrors('code');

    $this->actingAs(dlEtudiant())->get(route('admin.delegues.index'))->assertForbidden();
});

test('le lien du delegue rattache le compte cree a l inscription', function () {
    $marie = dlDelegue();

    $this->get(route('delegue.lien', 'marie'))->assertRedirect(route('register'));
    $this->post(route('register'), [
        'name' => 'Paul Etoa', 'email' => 'paul@exemple.test', 'phone' => '+237 6 55 44 33 22',
        'password' => 'motdepasse-solide', 'password_confirmation' => 'motdepasse-solide',
    ]);

    expect(User::where('email', 'paul@exemple.test')->value('delegue_id'))->toBe($marie->id);
});

test('l etudiant saisit le code sur la page d abonnement et voit le prix reduit', function () {
    config(['prepla.mobile_money.prix' => ['mensuel' => 6500, 'annuel' => 60000]]);
    $marie = dlDelegue();
    $paul = dlEtudiant();

    $this->actingAs($paul)->post(route('delegue.appliquer'), ['code' => 'marie'])->assertSessionHasNoErrors();
    expect($paul->fresh()->delegue_id)->toBe($marie->id);

    $this->actingAs($paul)->get(route('subscription.index'))->assertInertia(fn (Assert $p) => $p
        ->where('mobileMoney.prix.mensuel', 5900)
        ->where('mobileMoney.prix.annuel', 54000)
        ->where('mobileMoney.prixPlein.mensuel', 6500)
        ->where('mobileMoney.code.code', 'MARIE'));
});

test('un code inconnu est refuse, et un compte deja rattache ne change pas de delegue', function () {
    $marie = dlDelegue();
    dlDelegue(['nom' => 'Jean', 'code' => 'JEAN']);
    $paul = dlEtudiant();

    $this->actingAs($paul)->post(route('delegue.appliquer'), ['code' => 'INCONNU'])->assertSessionHasErrors('code');
    $this->actingAs($paul)->post(route('delegue.appliquer'), ['code' => 'MARIE']);
    $this->actingAs($paul)->post(route('delegue.appliquer'), ['code' => 'JEAN'])->assertSessionHasErrors('code');

    expect($paul->fresh()->delegue_id)->toBe($marie->id);
});

test('un delegue ne peut pas utiliser son propre code', function () {
    $compte = dlEtudiant('marie@exemple.cm');
    dlDelegue(['user_id' => $compte->id]);

    $this->actingAs($compte)->post(route('delegue.appliquer'), ['code' => 'MARIE'])->assertSessionHasErrors('code');
    expect($compte->fresh()->delegue_id)->toBeNull();
});

test('chaque activation payee rapporte sa commission, une activation annulee n en rapporte plus', function () {
    $admin = dlAdmin();
    $marie = dlDelegue(['taux' => 20]);
    $paul = dlEtudiant();
    $paul->forceFill(['delegue_id' => $marie->id])->save();

    $this->actingAs($admin)->post(route('admin.activations.store'), ['email' => $paul->email, 'formule' => 'mensuel', 'moyen' => 'orange', 'montant' => 5900]);
    $this->actingAs($admin)->post(route('admin.activations.store'), ['email' => $paul->email, 'formule' => 'mensuel', 'moyen' => 'mtn', 'montant' => 5900]);

    expect(CommissionDelegue::where('delegue_id', $marie->id)->pluck('commission')->all())->toBe([1180, 1180]);

    $this->actingAs($admin)->delete(route('admin.activations.destroy', ActivationManuelle::latest('id')->first()));
    expect(App\Services\Billing\Delegues::bilan($marie))->toMatchArray(['inscrits' => 1, 'abonnes' => 1, 'encaisse' => 5900, 'gagne' => 1180, 'du' => 1180]);
});

test('le code envoye avec la capture rattache le compte a l activation', function () {
    $marie = dlDelegue();
    $paul = dlEtudiant();

    $this->actingAs(dlAdmin())->post(route('admin.activations.store'), [
        'email' => $paul->email, 'formule' => 'mensuel', 'moyen' => 'orange', 'montant' => 5900, 'code_delegue' => 'marie',
    ])->assertSessionHasNoErrors();

    expect($paul->fresh()->delegue_id)->toBe($marie->id)
        ->and(CommissionDelegue::sole()->commission)->toBe(1180);
});

test('marquer verse solde ce qui est du au delegue', function () {
    $admin = dlAdmin();
    $marie = dlDelegue();
    $paul = dlEtudiant();
    $paul->forceFill(['delegue_id' => $marie->id])->save();
    $this->actingAs($admin)->post(route('admin.activations.store'), ['email' => $paul->email, 'formule' => 'mensuel', 'moyen' => 'orange', 'montant' => 6000]);

    $this->actingAs($admin)->post(route('admin.delegues.verser', $marie))->assertRedirect();

    expect(App\Services\Billing\Delegues::bilan($marie))->toMatchArray(['du' => 0, 'verse' => 1200]);
});

test('le delegue suit ses inscrits et ses gains sur sa page, sans voir les e-mails', function () {
    $compte = dlEtudiant('marie@exemple.cm');
    $marie = dlDelegue(['user_id' => $compte->id]);
    $paul = dlEtudiant('paul.etoa@exemple.cm');
    $paul->forceFill(['name' => 'Paul Etoa', 'delegue_id' => $marie->id, 'delegue_at' => now()])->save();
    $this->actingAs(dlAdmin())->post(route('admin.activations.store'), ['email' => $paul->email, 'formule' => 'mensuel', 'moyen' => 'orange', 'montant' => 5900]);

    $this->actingAs($compte)->get(route('delegue.mon-code'))->assertOk()
        ->assertInertia(fn (Assert $p) => $p->component('mon-code')
            ->where('delegue.code', 'MARIE')
            ->where('bilan.du', 1180)
            ->where('etudiants.0.prenom', 'Paul')
            ->missing('etudiants.0.email'));

    $this->actingAs($paul)->get(route('delegue.mon-code'))->assertNotFound();
});

test('un delegue cree sans preciser actif l est par defaut', function () {
    $d = Delegue::create(['nom' => 'Lea', 'code' => 'LEA']);

    expect(App\Services\Billing\Delegues::rattacher(dlEtudiant(), $d))->toBeTrue()
        ->and($d->taux)->toBe(20)->and($d->reduction)->toBe(10);
});
