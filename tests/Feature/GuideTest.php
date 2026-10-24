<?php

use App\Models\Exam;
use App\Models\Language;
use App\Models\User;
use App\Models\UserProfile;

/**
 * La plateforme expliquait chaque ecran une fois qu'on y etait, mais personne
 * ne disait a l'apprenant COMMENT s'y prendre. Le guide finit sur SON etape a
 * lui, pas sur un conseil general.
 */
function apprenantDuGuide(array $profil = []): User
{
    $langue = Language::firstOrCreate(['slug' => 'german'],
        ['name' => 'Allemand', 'native_name' => 'Deutsch', 'flag' => 'de', 'is_active' => true]);
    $exam = Exam::firstOrCreate(['slug' => 'guide-exam'], ['language_id' => $langue->id, 'name' => 'telc Deutsch']);

    $u = User::create(['name' => 'Fatou Sow', 'email' => 'guide'.uniqid().'@exemple.test',
        'password' => 'mot-de-passe-de-test', 'email_verified_at' => now()]);
    UserProfile::create(array_merge([
        'user_id' => $u->id, 'current_level' => 'B1', 'target_exam_id' => $exam->id,
        'onboarding_completed_at' => now(),
    ], $profil));

    return $u;
}

test('le guide salue l apprenant et finit sur son etape', function () {
    $props = $this->actingAs(apprenantDuGuide())->get('/guide')->viewData('page')['props'];

    expect($props['portrait']['prenom'])->toBe('Fatou')
        ->and($props['portrait']['prochaine_etape']['quoi'])->not->toBeEmpty()
        ->and($props['portrait']['prochaine_etape']['pourquoi'])->not->toBeEmpty();
});

test('le guide n est pas ouvert sans compte', function () {
    $this->get('/guide')->assertRedirect();
});

test('le guide n invente pas une echeance absente', function () {
    $props = $this->actingAs(apprenantDuGuide())->get('/guide')->viewData('page')['props'];

    expect($props['portrait']['jours_avant_examen'])->toBeNull();
});

