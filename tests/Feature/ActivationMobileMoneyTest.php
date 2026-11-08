<?php

use App\Models\ActivationManuelle;
use App\Models\User;
use App\Models\UserProfile;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Paiement par Orange Money ou MTN MoMo : l'apprenant envoie la capture sur
 * WhatsApp, le super administrateur active son compte avec son e-mail.
 */
beforeEach(fn () => $this->withoutVite());

function mmAdmin(): User
{
    return User::factory()->create(['role' => 'super_admin']);
}

function mmApprenant(string $email = 'awa@exemple.cm'): User
{
    $u = User::factory()->create(['email' => $email]);
    UserProfile::factory()->for($u)->create(['onboarding_completed_at' => now()]);

    return $u;
}

test('le super administrateur active un mois avec l e-mail, et l acces s ouvre', function () {
    $admin = mmAdmin();
    $awa = mmApprenant();
    expect($awa->hasPremiumAccess())->toBeFalse();

    $this->actingAs($admin)->post(route('admin.activations.store'), [
        'email' => 'awa@exemple.cm', 'formule' => 'mensuel', 'moyen' => 'orange', 'montant' => 5000, 'reference' => 'OM123',
    ])->assertRedirect(route('admin.activations.index', ['email' => 'awa@exemple.cm']));

    $awa->refresh();
    expect($awa->hasPremiumAccess())->toBeTrue()
        ->and($awa->premium_until->diffInDays(now()->addDays(30), true))->toBeLessThan(1)
        ->and(ActivationManuelle::sole()->only(['email', 'formule', 'jours', 'moyen', 'montant', 'reference', 'activated_by']))
        ->toBe(['email' => 'awa@exemple.cm', 'formule' => 'mensuel', 'jours' => 30, 'moyen' => 'orange', 'montant' => 5000, 'reference' => 'OM123', 'activated_by' => $admin->id]);
});

test('un acces en cours est prolonge, pas ecrase', function () {
    $admin = mmAdmin();
    $awa = mmApprenant();
    $awa->forceFill(['premium_until' => now()->addDays(10)])->save();

    $this->actingAs($admin)->post(route('admin.activations.store'), ['email' => $awa->email, 'formule' => 'mensuel', 'moyen' => 'mtn']);

    expect($awa->fresh()->premium_until->diffInDays(now()->addDays(40), true))->toBeLessThan(1);
});

test('l acces expire de lui-meme a sa date de fin', function () {
    $awa = mmApprenant();
    $awa->forceFill(['premium_until' => now()->subMinute()])->save();

    expect($awa->fresh()->hasPremiumAccess())->toBeFalse();
});

test('annuler une activation fait reculer l acces', function () {
    $admin = mmAdmin();
    $awa = mmApprenant();
    $this->actingAs($admin)->post(route('admin.activations.store'), ['email' => $awa->email, 'formule' => 'mensuel', 'moyen' => 'orange']);

    $this->actingAs($admin)->delete(route('admin.activations.destroy', ActivationManuelle::sole()))->assertRedirect();

    expect($awa->fresh()->premium_until)->toBeNull()
        ->and($awa->fresh()->hasPremiumAccess())->toBeFalse()
        ->and(ActivationManuelle::sole()->annulee_le)->not->toBeNull();
});

test('un e-mail inconnu est signale sans rien creer', function () {
    $this->actingAs(mmAdmin())->post(route('admin.activations.store'), ['email' => 'inconnu@exemple.cm', 'formule' => 'mensuel', 'moyen' => 'orange'])
        ->assertSessionHasErrors('email');

    expect(ActivationManuelle::count())->toBe(0);
});

test('la page d activation est reservee au super administrateur', function () {
    $awa = mmApprenant();

    $this->actingAs($awa)->get(route('admin.activations.index'))->assertForbidden();
    $this->actingAs($awa)->post(route('admin.activations.store'), ['email' => $awa->email, 'formule' => 'annuel', 'moyen' => 'orange'])->assertForbidden();
    expect($awa->fresh()->premium_until)->toBeNull();

    $this->actingAs(mmAdmin())->get(route('admin.activations.index', ['email' => $awa->email]))->assertOk()
        ->assertInertia(fn (Assert $p) => $p->component('admin/activations')->where('compte.email', $awa->email)->where('compte.acces_actif', false));
});

test('la page d abonnement donne les numeros, le prix en FCFA et l e-mail pour WhatsApp', function () {
    $awa = mmApprenant();

    $this->actingAs($awa)->get(route('subscription.index'))->assertOk()
        ->assertInertia(fn (Assert $p) => $p->component('settings/subscription')
            ->where('mobileMoney.orange', '693427913')
            ->where('mobileMoney.mtn', '683735889')
            ->where('mobileMoney.whatsapp', '237693427913')
            ->where('mobileMoney.email', 'awa@exemple.cm')
            ->where('mobileMoney.prix.mensuel', 5000)
            ->where('premiumUntil', null));
});
