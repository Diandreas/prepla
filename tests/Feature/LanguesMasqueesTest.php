<?php

use App\Models\Exam;
use App\Models\Language;
use App\Models\User;
use App\Models\UserProfile;

/**
 * Sept langues ne sont pas travaillées : leurs examens n'ont qu'un squelette
 * générique. L'interrupteur `languages.is_active` existait, et il était bien
 * baissé en base — mais il n'était respecté qu'à DEUX endroits sur neuf. Un
 * examen « masqué » restait donc proposé dans les réglages du profil, dans les
 * outils IA, dans le formulaire d'ouverture d'un espace enseignant, à la
 * création d'une classe, dans le constructeur d'exercices et dans l'invitation
 * d'un collègue. Et son adresse restait ouverte.
 */
function deuxLangues(): array
{
    $fr = Language::create(['slug' => 'french', 'name' => 'Français', 'native_name' => 'Français', 'flag' => 'fr', 'is_active' => true]);
    $ja = Language::create(['slug' => 'japanese', 'name' => 'Japonais', 'native_name' => '日本語', 'flag' => 'jp', 'is_active' => false]);

    return [
        'travaille' => Exam::create(['language_id' => $fr->id, 'slug' => 'delf-dalf', 'name' => 'DELF/DALF']),
        'masque' => Exam::create(['language_id' => $ja->id, 'slug' => 'jlpt', 'name' => 'JLPT']),
    ];
}

function apprenant(): User
{
    $u = User::create([
        'name' => 'Amina', 'email' => 'amina@exemple.test',
        'password' => 'mot-de-passe-de-test', 'email_verified_at' => now(),
    ]);
    UserProfile::create(['user_id' => $u->id, 'current_level' => 'B1', 'onboarding_completed_at' => now()]);

    return $u;
}

test('le scope ne retient que les examens des langues travaillees', function () {
    $e = deuxLangues();

    $proposables = Exam::proposables()->pluck('slug');

    expect($proposables)->toContain('delf-dalf')->not->toContain('jlpt');
});

test('un examen masque n est propose nulle part ou l on en choisit un', function () {
    $e = deuxLangues();
    $utilisateur = apprenant();

    // Les réglages du profil : c'est là qu'un apprenant change d'examen cible.
    $reponse = $this->actingAs($utilisateur)->get('/settings/profile');
    $reponse->assertOk();
    $noms = collect($reponse->viewData('page')['props']['exams'] ?? [])->pluck('slug');

    expect($noms)->toContain('delf-dalf')->not->toContain('jlpt');
});

test('l adresse d un examen masque ne s ouvre pas', function () {
    $e = deuxLangues();
    $utilisateur = apprenant();

    $this->actingAs($utilisateur)->get('/practice/'.$e['masque']->slug)->assertNotFound();
});
