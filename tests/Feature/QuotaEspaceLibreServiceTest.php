<?php

use App\Http\Middleware\EnsureExerciseQuota;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Models\UserExerciseAttempt;
use Illuminate\Support\Facades\Route;

/**
 * N'importe quel compte ouvre un espace enseignant en une etape sur /enseigner. Le
 * personnel d'un centre etant exempte de la limite gratuite, un apprenant passait
 * outre l'abonnement en un clic. L'exemption ne vaut plus que pour le personnel d'un
 * centre ouvert par PrePla ; un espace libre-service garde ses outils enseignant,
 * mais son personnel s'entraine avec la meme limite qu'un compte gratuit.
 */
beforeEach(function () {
    Route::middleware(['web', 'auth', EnsureExerciseQuota::class])
        ->get('/__test/quota-espace', fn () => 'ok');

    $language = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'goethe-a1', 'name' => 'Goethe A1']);
    $section = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'reading', 'name' => 'Reading', 'skill_type' => 'reading']);
    $type = ExerciseType::create([
        'section_id' => $section->id, 'slug' => 'mcq', 'name' => 'MCQ', 'skill_type' => 'reading', 'component_key' => 'mcq',
    ]);
    $this->exercise = Exercise::create([
        'exercise_type_id' => $type->id, 'exam_id' => $exam->id,
        'content' => [], 'questions' => [], 'difficulty' => 'A1',
    ]);
});

function quotaDuJourConsomme(User $user): void
{
    UserExerciseAttempt::factory()->count(EnsureExerciseQuota::DAILY_FREE_LIMIT)->create([
        'user_id' => $user->id,
        'exercise_id' => test()->exercise->id,
    ]);
}

function ouvrirEspaceEnseignant(User $user): LanguageCenter
{
    test()->actingAs($user)->post(route('teach.open.store'), [
        'name' => 'Cours de Zidane',
        'classroom_name' => 'Groupe du mardi',
    ])->assertRedirect(route('center.dashboard'));

    return LanguageCenter::where('owner_email', $user->email)->latest('id')->firstOrFail();
}

test('un compte qui ouvre un espace sur /enseigner reste limite a 3 exercices par jour', function () {
    $user = User::factory()->create();
    $center = ouvrirEspaceEnseignant($user);

    expect($center->estLibreService())->toBeTrue()
        ->and($user->fresh()->isCenterStaff())->toBeTrue();

    quotaDuJourConsomme($user);

    $this->actingAs($user)->get('/__test/quota-espace')
        ->assertRedirect(route('subscription.index'));
});

test('un compte qui ouvre un espace sur /enseigner entre normalement sous son quota', function () {
    $user = User::factory()->create();
    ouvrirEspaceEnseignant($user);

    $this->actingAs($user)->get('/__test/quota-espace')->assertOk();
});

test('le personnel d un centre cree par le super administrateur reste exempte', function () {
    $superAdmin = User::factory()->create(['role' => 'super_admin']);
    $responsable = User::factory()->create(['email' => 'direction@ecole-client.test']);

    $this->actingAs($superAdmin)->post(route('admin.centers.store'), [
        'name' => 'École cliente',
        'seats_limit' => 30,
        'admin_name' => 'Direction',
        'admin_email' => 'direction@ecole-client.test',
    ])->assertRedirect();

    $center = LanguageCenter::where('owner_email', 'direction@ecole-client.test')->firstOrFail();
    expect($center->estLibreService())->toBeFalse()
        ->and($center->exempteSonPersonnelDuQuota())->toBeTrue();

    quotaDuJourConsomme($responsable);

    $this->actingAs($responsable->fresh())->get('/__test/quota-espace')->assertOk();
});

test('un enseignant de la demonstration, centre sans marque libre-service, reste exempte', function () {
    // Le seeder de demo cree « Institut Linguae » avec settings = null.
    $center = LanguageCenter::create([
        'name' => 'Institut Linguae', 'slug' => 'institut-linguae-demo',
        'owner_email' => 'demo-admin@demo.test', 'is_active' => true, 'settings' => null,
    ]);
    $teacher = User::factory()->create();
    $center->members()->attach($teacher->id, ['role' => 'teacher', 'joined_at' => now()]);

    quotaDuJourConsomme($teacher);

    $this->actingAs($teacher)->get('/__test/quota-espace')->assertOk();
});

test('un abonne PrePla Plus qui ouvre un espace reste illimite', function () {
    $user = User::factory()->create(['stripe_id' => 'cus_espace']);
    $user->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_espace',
        'stripe_status' => 'active',
        'stripe_price' => 'price_test',
        'quantity' => 1,
    ]);
    ouvrirEspaceEnseignant($user);

    UserExerciseAttempt::factory()->count(EnsureExerciseQuota::DAILY_FREE_LIMIT + 5)->create([
        'user_id' => $user->id,
        'exercise_id' => $this->exercise->id,
    ]);

    $this->actingAs($user)->get('/__test/quota-espace')->assertOk();
});

test('un eleve inscrit dans un espace libre-service garde le comportement actuel', function () {
    $center = LanguageCenter::create([
        'name' => 'Cours de Zidane', 'slug' => 'cours-zidane', 'owner_email' => 'zidane@exemple.test',
        'is_active' => true, 'settings' => [LanguageCenter::LIBRE_SERVICE => true],
    ]);
    $student = User::factory()->create();
    $center->members()->attach($student->id, ['role' => 'student', 'joined_at' => now()]);

    quotaDuJourConsomme($student);

    // Son travail lui est donne par son professeur : le middleware ne l'arrete pas.
    $this->actingAs($student)->get('/__test/quota-espace')->assertOk();
});

test('un enseignant d un centre client qui ouvre aussi son espace reste exempte, meme en agissant dans cet espace', function () {
    // Le centre client, ouvert par PrePla : pas de marque libre-service.
    $client = LanguageCenter::create([
        'name' => 'École cliente', 'slug' => 'ecole-cliente',
        'owner_email' => 'direction@ecole-client.test', 'is_active' => true, 'settings' => null,
    ]);
    $teacher = User::factory()->create();
    $client->members()->attach($teacher->id, ['role' => 'teacher', 'joined_at' => now()]);

    // Il ouvre son propre espace : il y devient responsable et y agit par defaut.
    $espace = ouvrirEspaceEnseignant($teacher);
    expect($teacher->fresh()->center()?->id)->toBe($espace->id);

    quotaDuJourConsomme($teacher);

    $this->actingAs($teacher->fresh())->get('/__test/quota-espace')->assertOk();
});

test('un enseignant d un centre client reste exempte sans choix de centre en session', function () {
    $client = LanguageCenter::create([
        'name' => 'École cliente', 'slug' => 'ecole-cliente-2',
        'owner_email' => 'direction2@ecole-client.test', 'is_active' => true, 'settings' => null,
    ]);
    $espace = LanguageCenter::create([
        'name' => 'Cours perso', 'slug' => 'cours-perso', 'owner_email' => 'prof@exemple.test',
        'is_active' => true, 'settings' => [LanguageCenter::LIBRE_SERVICE => true],
    ]);
    $teacher = User::factory()->create();
    $client->members()->attach($teacher->id, ['role' => 'teacher', 'joined_at' => now()]);
    $espace->members()->attach($teacher->id, ['role' => 'center_admin', 'joined_at' => now()]);

    // Sans choix, le role de responsable de l'espace libre-service passe devant.
    expect($teacher->center()?->id)->toBe($espace->id);

    quotaDuJourConsomme($teacher);

    $this->actingAs($teacher)->get('/__test/quota-espace')->assertOk();
});

test('le personnel de plusieurs espaces libre-service reste limite', function () {
    $user = User::factory()->create();
    foreach (['un', 'deux'] as $i) {
        $espace = LanguageCenter::create([
            'name' => "Espace $i", 'slug' => "espace-$i", 'owner_email' => $user->email,
            'is_active' => true, 'settings' => [LanguageCenter::LIBRE_SERVICE => true],
        ]);
        $espace->members()->attach($user->id, ['role' => $i === 'un' ? 'center_admin' : 'teacher', 'joined_at' => now()]);
    }

    quotaDuJourConsomme($user);

    $this->actingAs($user)->get('/__test/quota-espace')
        ->assertRedirect(route('subscription.index'));
});
