<?php

use App\Models\Exam;
use App\Models\Language;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Models\UserProfile;

/**
 * L'enseignant invite par lien tombait dans le questionnaire d'inscription
 * APPRENANT (« choisis ton objectif », « quel est ton niveau ») des qu'il cliquait
 * le logo, sans aucun retour vers son espace. Deux correctifs : son inscription est
 * marquee faite a la creation du compte, et le personnel sort de ce questionnaire
 * quel que soit le lien emprunte (logo, favori, page d'accueil publique).
 */
test('le personnel d un centre n est pas renvoye dans l inscription apprenant', function () {
    $language = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'goethe-a2', 'name' => 'Goethe A2']);

    $center = LanguageCenter::create([
        'name' => 'Ecole de langues', 'slug' => 'ecole-'.uniqid(),
        'owner_email' => 'direction@exemple.test', 'is_active' => true,
    ]);

    $prof = User::factory()->create();
    // Profil sans date d'inscription : c'est l'etat des comptes crees avant le
    // correctif, et celui de tout compte dont le profil serait recree.
    UserProfile::factory()->for($prof)->create([
        'target_exam_id' => $exam->id, 'onboarding_completed_at' => null, 'native_language' => null,
    ]);
    $center->members()->attach($prof->id, ['role' => 'teacher', 'joined_at' => now()]);

    $this->actingAs($prof)->get(route('dashboard'))->assertOk();
    $this->actingAs($prof)->get(route('teach.open'))->assertOk();
});

test('un apprenant sans inscription est toujours renvoye vers le questionnaire', function () {
    $eleve = User::factory()->create();
    UserProfile::factory()->for($eleve)->create([
        'onboarding_completed_at' => null, 'native_language' => null,
    ]);

    $this->actingAs($eleve)->get(route('dashboard'))
        ->assertRedirect(route('onboarding.native-language'));
});
