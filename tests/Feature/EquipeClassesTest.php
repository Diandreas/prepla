<?php

use App\Models\Classroom;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Str;

/**
 * Le responsable confie une classe existante a un enseignant, ou la lui retire.
 * Un enseignant peut tenir plusieurs classes ; jamais une classe d'un autre
 * centre, jamais une personne hors de l'equipe.
 */
beforeEach(fn () => $this->withoutVite());

function eqCentre(string $nom): LanguageCenter
{
    return LanguageCenter::create(['name' => $nom, 'slug' => Str::slug($nom), 'owner_email' => 'x@exemple.test', 'is_active' => true, 'seats_limit' => 40]);
}

function eqMembre(LanguageCenter $centre, string $role): User
{
    $u = User::factory()->create();
    UserProfile::factory()->for($u)->create(['onboarding_completed_at' => now()]);
    $centre->members()->attach($u->id, ['role' => $role, 'joined_at' => now()]);

    return $u;
}

function eqClasse(LanguageCenter $centre, string $nom): Classroom
{
    return $centre->classrooms()->create(['name' => $nom, 'invite_code' => Classroom::generateInviteCode()]);
}

test('le responsable attribue plusieurs classes a un enseignant puis lui en retire une', function () {
    $centre = eqCentre('Centre A');
    $admin = eqMembre($centre, 'center_admin');
    $prof = eqMembre($centre, 'teacher');
    $k1 = eqClasse($centre, 'Goethe A1');
    $k2 = eqClasse($centre, 'Goethe B1');

    $this->actingAs($admin)->post(route('center.equipe.classes.attacher', $k1), ['user_id' => $prof->id])->assertRedirect();
    $this->actingAs($admin)->post(route('center.equipe.classes.attacher', $k2), ['user_id' => $prof->id])->assertRedirect();
    // Deux fois la meme : pas de doublon.
    $this->actingAs($admin)->post(route('center.equipe.classes.attacher', $k2), ['user_id' => $prof->id])->assertRedirect();

    expect($k1->teachers()->whereKey($prof->id)->exists())->toBeTrue()
        ->and($k2->teachers()->where('users.id', $prof->id)->count())->toBe(1);

    $this->actingAs($admin)->delete(route('center.equipe.classes.retirer', $k1), ['user_id' => $prof->id])->assertRedirect();
    expect($k1->teachers()->whereKey($prof->id)->exists())->toBeFalse()
        ->and($k2->teachers()->whereKey($prof->id)->exists())->toBeTrue();

    $this->actingAs($admin)->get(route('center.equipe'))->assertOk()
        ->assertInertia(fn ($p) => $p->has('toutesLesClasses', 2));
});

test('retirer un enseignant ne retire pas un eleve de la classe', function () {
    $centre = eqCentre('Centre A');
    $admin = eqMembre($centre, 'center_admin');
    $eleve = eqMembre($centre, 'student');
    $k = eqClasse($centre, 'TCF');
    $k->members()->attach($eleve->id, ['role_in_class' => 'student']);

    // L'eleve ne fait pas partie de l'equipe : refuse.
    $this->actingAs($admin)->delete(route('center.equipe.classes.retirer', $k), ['user_id' => $eleve->id])->assertStatus(422);
    expect($k->students()->whereKey($eleve->id)->exists())->toBeTrue();
});

test('un enseignant ne peut pas attribuer de classe', function () {
    $centre = eqCentre('Centre A');
    $prof = eqMembre($centre, 'teacher');
    $k = eqClasse($centre, 'TCF');

    $this->actingAs($prof)->post(route('center.equipe.classes.attacher', $k), ['user_id' => $prof->id])->assertForbidden();
    expect($k->teachers()->whereKey($prof->id)->exists())->toBeFalse();
});

test('ni classe d un autre centre, ni personne hors de l equipe', function () {
    $a = eqCentre('Centre A');
    $b = eqCentre('Centre B');
    $admin = eqMembre($a, 'center_admin');
    $profA = eqMembre($a, 'teacher');
    $profB = eqMembre($b, 'teacher');
    $kA = eqClasse($a, 'A1');
    $kB = eqClasse($b, 'B1');

    $this->actingAs($admin)->post(route('center.equipe.classes.attacher', $kB), ['user_id' => $profA->id])->assertNotFound();
    $this->actingAs($admin)->post(route('center.equipe.classes.attacher', $kA), ['user_id' => $profB->id])->assertStatus(422);
    expect($kB->teachers()->count())->toBe(0)->and($kA->teachers()->count())->toBe(0);
});

test('le responsable cree une classe directement pour un ou plusieurs enseignants', function () {
    $centre = eqCentre('Centre A');
    $admin = eqMembre($centre, 'center_admin');
    $p1 = eqMembre($centre, 'teacher');
    $p2 = eqMembre($centre, 'teacher');
    $autre = eqMembre(eqCentre('Centre B'), 'teacher');

    $this->actingAs($admin)->get(route('center.classes.index'))->assertOk()
        ->assertInertia(fn ($p) => $p->has('enseignants', 3));

    $this->actingAs($admin)->post(route('center.classes.store'), ['name' => 'Goethe A1', 'room' => 'Salle 2', 'teacher_ids' => [$p1->id, $p2->id]])->assertRedirect();
    $k = Classroom::where('name', 'Goethe A1')->firstOrFail();
    expect($k->teachers()->pluck('users.id')->sort()->values()->all())->toBe(collect([$p1->id, $p2->id])->sort()->values()->all());

    // Personne hors de l'equipe : refuse, aucune classe creee.
    $this->actingAs($admin)->post(route('center.classes.store'), ['name' => 'Piege', 'teacher_ids' => [$autre->id]])->assertStatus(422);
    expect(Classroom::where('name', 'Piege')->exists())->toBeFalse();

    // Sans choix, la classe revient a qui la cree, comme avant.
    $this->actingAs($p1)->post(route('center.classes.store'), ['name' => 'TCF', 'teacher_ids' => [$p2->id]])->assertRedirect();
    expect(Classroom::where('name', 'TCF')->firstOrFail()->teachers()->pluck('users.id')->all())->toBe([$p1->id]);
});
