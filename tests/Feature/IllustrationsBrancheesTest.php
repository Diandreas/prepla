<?php

use App\Models\Achievement;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Language;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\File;

/**
 * Les illustrations generees ne servent que si une page les affiche : chaque
 * image des dossiers adultes doit etre citee au moins une fois par l'interface
 * (pages React ou vues Blade des erreurs), et les pages qui les portent
 * doivent repondre.
 */
it('reference chaque illustration adulte au moins une fois', function () {
    $sources = collect(File::allFiles(resource_path('js')))
        ->merge(File::allFiles(resource_path('views')))
        ->map(fn ($f) => $f->getContents())
        ->implode("\n");

    $dossiers = ['competences', 'etats', 'onboarding', 'abonnement', 'centre', 'badges', 'prepla-guide'];
    $orphelines = [];
    foreach ($dossiers as $dossier) {
        foreach (File::files(public_path("illustrations/{$dossier}")) as $fichier) {
            if ($fichier->getExtension() !== 'webp') {
                continue;
            }
            $nom = $fichier->getFilenameWithoutExtension();
            $chemin = "/illustrations/{$dossier}/{$nom}.webp";
            // Les poses du renard passent par <Renard pose="..."> : le composant compose le chemin.
            $cite = str_contains($sources, $chemin)
                || ($dossier === 'prepla-guide' && preg_match('/pose=\{?[^\n]*[\'"]'.preg_quote($nom, '/').'[\'"]/', $sources));
            if (! $cite) {
                $orphelines[] = $chemin;
            }
        }
    }

    expect($orphelines)->toBe([]);
});

it('affiche les pages de l apprenant qui portent les illustrations', function () {
    $language = Language::create(['slug' => 'french', 'name' => 'French', 'native_name' => 'Français', 'flag' => '🇫🇷']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'tcf', 'name' => 'TCF']);
    ExamSection::create(['exam_id' => $exam->id, 'slug' => 'co', 'name' => 'Compréhension orale', 'skill_type' => 'listening']);

    $nouveau = User::factory()->create();
    $this->actingAs($nouveau)->get('/onboarding/native-language')->assertOk();

    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create(['onboarding_completed_at' => now(), 'target_exam_id' => $exam->id]);
    $this->actingAs($user)->get("/practice/{$exam->id}")->assertOk();
    $this->actingAs($user)->get('/profile/achievements')->assertOk();
    $this->actingAs($user)->get('/settings/subscription')->assertOk();
});

it('affiche les etats vides illustres de l espace centre', function () {
    $centre = LanguageCenter::create(['name' => 'Centre illustre', 'slug' => 'centre-illustre', 'owner_email' => 'illustre@exemple.test', 'is_active' => true]);
    $admin = User::factory()->create();
    UserProfile::factory()->for($admin)->create(['onboarding_completed_at' => now()]);
    $centre->members()->attach($admin->id, ['role' => 'center_admin', 'joined_at' => now()]);

    $this->actingAs($admin);
    $this->get('/center')->assertOk();
    $this->get('/center/classes')->assertOk();
    $this->get('/center/students')->assertOk();
    $this->get('/center/examens-blancs')->assertOk();

    $classe = $centre->classrooms()->create(['name' => 'Débutants', 'level' => 'A1', 'invite_code' => Classroom::generateInviteCode()]);
    $this->get("/center/classes/{$classe->id}/parcours")->assertOk();
});
