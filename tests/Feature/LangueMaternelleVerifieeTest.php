<?php

use App\Models\Exam;
use App\Models\Language;
use App\Models\User;
use App\Models\UserProfile;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Une langue maternelle identique a la langue de l'examen est presque toujours
 * une erreur : des francophones cochaient « English » puis choisissaient le
 * TOEFL, et leurs lecons traduisaient le vocabulaire en espagnol.
 */
beforeEach(fn () => $this->withoutVite());

function lmExamen(string $langue): Exam
{
    $l = Language::firstOrCreate(['slug' => strtolower($langue).'-lm'], ['name' => $langue, 'native_name' => $langue, 'flag' => 'xx', 'is_active' => true]);

    return Exam::create(['language_id' => $l->id, 'slug' => 'ex-'.strtolower($langue).'-'.uniqid(), 'name' => "Examen {$langue}"]);
}

test('choisir un examen dans sa propre langue maternelle fait reverifier cette langue', function () {
    $u = User::factory()->create();
    $toefl = lmExamen('English');
    $this->actingAs($u)->post(route('onboarding.native-language.store'), ['native_language' => 'English']);

    $this->post(route('onboarding.exam.store'), ['exam_id' => $toefl->id])
        ->assertRedirect(route('onboarding.native-language', ['verifier' => 1]));

    $this->get(route('onboarding.native-language', ['verifier' => 1]))
        ->assertInertia(fn (Assert $p) => $p->where('langueDeLExamen', 'English'));

    // Corrigee, la langue renvoie directement a l'objectif : l'examen reste choisi.
    $this->post(route('onboarding.native-language.store'), ['native_language' => 'Français', 'verifier' => 1])
        ->assertRedirect(route('onboarding.goal'));
    expect(UserProfile::where('user_id', $u->id)->first()->only(['native_language', 'target_exam_id']))
        ->toBe(['native_language' => 'Français', 'target_exam_id' => $toefl->id]);
});

test('confirmer la meme langue ne fait pas tourner en rond', function () {
    $u = User::factory()->create();
    $toefl = lmExamen('English');
    $this->actingAs($u)->post(route('onboarding.native-language.store'), ['native_language' => 'English']);
    $this->post(route('onboarding.exam.store'), ['exam_id' => $toefl->id]);

    $this->post(route('onboarding.native-language.store'), ['native_language' => 'English', 'verifier' => 1])
        ->assertRedirect(route('onboarding.goal'));
});

test('une langue maternelle differente de l examen passe sans question', function () {
    $u = User::factory()->create();
    $goethe = lmExamen('Allemand');
    $this->actingAs($u)->post(route('onboarding.native-language.store'), ['native_language' => 'Français']);

    $this->post(route('onboarding.exam.store'), ['exam_id' => $goethe->id])->assertRedirect(route('onboarding.goal'));
    $this->get(route('onboarding.native-language'))->assertInertia(fn (Assert $p) => $p->where('langueDeLExamen', null));
});
