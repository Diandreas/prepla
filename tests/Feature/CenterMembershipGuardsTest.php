<?php

use App\Models\CenterUser;
use App\Models\Classroom;
use App\Models\CurriculumSkeleton;
use App\Models\Exam;
use App\Models\Language;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Models\UserProfile;

function espaceAvecClasses(): array
{
    $language = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $examA = Exam::create(['language_id' => $language->id, 'slug' => 'goethe-a1', 'name' => 'Goethe A1']);
    $examB = Exam::create(['language_id' => $language->id, 'slug' => 'goethe-b1', 'name' => 'Goethe B1']);

    $center = LanguageCenter::create([
        'name' => 'Ecole de langues', 'slug' => 'ecole-'.uniqid(),
        'owner_email' => 'direction@exemple.test', 'is_active' => true, 'seats_limit' => 50,
    ]);

    $membre = function (string $role) use ($center) {
        $user = User::factory()->create();
        UserProfile::factory()->for($user)->create(['onboarding_completed_at' => now()]);
        $center->members()->attach($user->id, ['role' => $role, 'joined_at' => now()]);

        return $user;
    };

    $admin = $membre('center_admin');
    $prof = $membre('teacher');

    $classeA = $center->classrooms()->create([
        'name' => 'Allemand A1', 'exam_id' => $examA->id, 'invite_code' => Classroom::generateInviteCode(),
    ]);
    $classeB = $center->classrooms()->create([
        'name' => 'Allemand B1', 'exam_id' => $examB->id, 'invite_code' => Classroom::generateInviteCode(),
    ]);
    $classeA->members()->attach($prof->id, ['role_in_class' => 'teacher']);
    $classeB->members()->attach($prof->id, ['role_in_class' => 'teacher']);

    return compact('center', 'admin', 'prof', 'classeA', 'classeB', 'examA', 'examB');
}

/**
 * Rejoindre la classe de son professeur ECRASAIT l'examen cible de l'apprenant.
 * Son programme restait ecrit pour l'ancien examen : le tableau de bord ne
 * retrouvait plus rien et le parcours — des semaines de travail — etait gele.
 */
test('rejoindre une classe ne gele pas le parcours deja commence', function () {
    $e = espaceAvecClasses();

    $eleve = User::factory()->create();
    UserProfile::factory()->for($eleve)->create([
        'target_exam_id' => $e['examA']->id, 'current_level' => 'A1', 'onboarding_completed_at' => now(),
    ]);
    CurriculumSkeleton::create([
        'user_id' => $eleve->id, 'exam_id' => $e['examA']->id,
        'objectives' => [[
            'order' => 0, 'title' => 'Objectif deja commence', 'concept' => 'grammar.basic',
            'level' => 'A1', 'status' => 'current', 'priority' => 'normal',
        ]],
        'current_objective_index' => 0,
    ]);

    $this->actingAs($eleve)->post(route('center.join.store'), ['code' => $e['classeB']->invite_code])
        ->assertRedirect(route('dashboard'));

    expect($eleve->profile->fresh()->target_exam_id)->toBe($e['examA']->id)
        ->and(CurriculumSkeleton::where('user_id', $eleve->id)->sole()->objectives)->toHaveCount(1)
        ->and($e['classeB']->students()->whereKey($eleve->id)->exists())->toBeTrue();
});

test('un eleve sans parcours herite de l examen de sa classe', function () {
    $e = espaceAvecClasses();

    $eleve = User::factory()->create();
    UserProfile::factory()->for($eleve)->create(['target_exam_id' => null, 'onboarding_completed_at' => now()]);

    $this->actingAs($eleve)->post(route('center.join.store'), ['code' => $e['classeB']->invite_code])
        ->assertRedirect(route('dashboard'));

    expect($eleve->profile->fresh()->target_exam_id)->toBe($e['examB']->id);
});

/**
 * Un eleve deja inscrit ne pouvait plus rejoindre AUCUNE autre classe, et le
 * message lui parlait d'un centre alors qu'il changeait de classe.
 */
test('un eleve rejoint une seconde classe de son etablissement', function () {
    $e = espaceAvecClasses();

    $eleve = User::factory()->create();
    UserProfile::factory()->for($eleve)->create(['onboarding_completed_at' => now()]);
    $e['center']->members()->attach($eleve->id, ['role' => 'student', 'joined_at' => now()]);
    $e['classeA']->members()->attach($eleve->id, ['role_in_class' => 'student']);

    $this->actingAs($eleve)->get(route('center.join'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('alreadyInCenter', false));

    $this->actingAs($eleve)->post(route('center.join.store'), ['code' => $e['classeB']->invite_code])
        ->assertRedirect(route('dashboard'));

    expect($e['classeB']->students()->whereKey($eleve->id)->exists())->toBeTrue()
        ->and($e['classeA']->students()->whereKey($eleve->id)->exists())->toBeTrue()
        ->and(CenterUser::where('user_id', $eleve->id)->count())->toBe(1);
});

test('un enseignant ne s inscrit pas comme eleve avec le code de sa classe', function () {
    $e = espaceAvecClasses();

    $this->actingAs($e['prof'])->post(route('center.join.store'), ['code' => $e['classeA']->invite_code])
        ->assertSessionHasErrors('code');

    expect($e['classeA']->students()->whereKey($e['prof']->id)->exists())->toBeFalse()
        ->and($e['prof']->fresh()->centerRole())->toBe('teacher');
});

/**
 * La route de retrait acceptait n'importe quel membre : un identifiant saisi a la
 * main suffisait pour qu'un enseignant sorte le responsable de l'etablissement et
 * lui supprime tout son acces.
 */
test('un enseignant ne peut pas retirer le responsable de l espace', function () {
    $e = espaceAvecClasses();

    $this->actingAs($e['prof'])
        ->delete(route('center.classes.students.remove', [$e['classeA']->id, $e['admin']->id]))
        ->assertForbidden();

    expect($e['admin']->fresh()->centerRole())->toBe('center_admin')
        ->and(CenterUser::where('user_id', $e['admin']->id)->exists())->toBeTrue();
});

test('un enseignant retire bien un eleve de sa classe', function () {
    $e = espaceAvecClasses();

    $eleve = User::factory()->create();
    UserProfile::factory()->for($eleve)->create(['onboarding_completed_at' => now()]);
    $e['center']->members()->attach($eleve->id, ['role' => 'student', 'joined_at' => now()]);
    $e['classeA']->members()->attach($eleve->id, ['role_in_class' => 'student']);

    $this->actingAs($e['prof'])
        ->delete(route('center.classes.students.remove', [$e['classeA']->id, $eleve->id]))
        ->assertRedirect();

    expect($e['classeA']->students()->whereKey($eleve->id)->exists())->toBeFalse()
        ->and(CenterUser::where('user_id', $eleve->id)->exists())->toBeFalse();
});

/**
 * Creation d'un centre : quand l'adresse appartenait deja a un espace, le
 * rattachement etait ignore en silence mais le centre etait cree quand meme et le
 * message annoncait « rattache comme administrateur ». Resultat : un espace que
 * personne ne pouvait ouvrir.
 */
test('creer un centre avec une adresse deja rattachee ne cree rien', function () {
    $e = espaceAvecClasses();

    $superAdmin = User::factory()->create(['role' => 'super_admin']);
    UserProfile::factory()->for($superAdmin)->create(['onboarding_completed_at' => now()]);

    $avant = LanguageCenter::count();

    $this->actingAs($superAdmin)->post(route('admin.centers.store'), [
        'name' => 'Nouvel espace',
        'seats_limit' => 20,
        'admin_name' => 'Deja pris',
        'admin_email' => $e['admin']->email,
    ])->assertSessionHasErrors('admin_email');

    expect(LanguageCenter::count())->toBe($avant);
});

test('creer un centre rattache vraiment son administrateur', function () {
    $superAdmin = User::factory()->create(['role' => 'super_admin']);
    UserProfile::factory()->for($superAdmin)->create(['onboarding_completed_at' => now()]);

    $this->actingAs($superAdmin)->post(route('admin.centers.store'), [
        'name' => 'Espace tout neuf',
        'seats_limit' => 20,
        'admin_name' => 'Responsable',
        'admin_email' => 'responsable@exemple.test',
    ])->assertRedirect();

    $centre = LanguageCenter::where('name', 'Espace tout neuf')->sole();
    $admin = User::where('email', 'responsable@exemple.test')->sole();

    expect(CenterUser::where('center_id', $centre->id)->where('user_id', $admin->id)->where('role', 'center_admin')->exists())
        ->toBeTrue();
});
