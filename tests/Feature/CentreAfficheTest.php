<?php

use App\Models\Classroom;
use App\Models\LanguageCenter;
use App\Models\TeacherInvitation;
use App\Models\User;
use App\Models\UserProfile;

/**
 * Le centre courant vaut pour TOUS les onglets : il est range en session.
 *
 * Une responsable de deux centres ouvre, dans un onglet, la page d'invitation
 * du centre A ; dans un autre, elle passe dans le centre B ; puis elle envoie le
 * formulaire du premier onglet. L'invitation partait dans B — avec la classe,
 * la bibliotheque d'exercices et les medias de la future enseignante. Une
 * classe, un exercice prive ou un fichier prepares pour A atterrissaient de
 * meme dans B, et devenaient visibles de son personnel.
 *
 * Chaque requete d'ecriture porte desormais le centre AFFICHE par la page ; s'il
 * n'est plus le centre courant, rien n'est ecrit.
 */
beforeEach(fn () => $this->withoutVite());

function responsableDeDeuxCentres(): array
{
    $centre = fn (string $nom) => LanguageCenter::create([
        'name' => $nom, 'slug' => \Illuminate\Support\Str::slug($nom), 'owner_email' => 'x@exemple.test', 'is_active' => true,
    ]);
    $a = $centre('Centre Akwa');
    $b = $centre('Centre Bonamoussadi');

    $r = User::factory()->create();
    UserProfile::factory()->for($r)->create(['onboarding_completed_at' => now()]);
    $a->members()->attach($r->id, ['role' => 'center_admin', 'joined_at' => now()]);
    $b->members()->attach($r->id, ['role' => 'center_admin', 'joined_at' => now()]);

    return compact('a', 'b', 'r');
}

/** La responsable est passee dans B ; la page envoyee affichait $affiche. */
function depuisUnOngletEnRetard(array $d, ?LanguageCenter $affiche)
{
    $test = test()->actingAs($d['r'])->withSession([User::CENTRE_COURANT => $d['b']->id]);

    return $affiche ? $test->withHeader('X-Prepla-Centre', (string) $affiche->id) : $test;
}

test('une classe preparee pour A ne se cree pas dans B', function () {
    $d = responsableDeDeuxCentres();

    depuisUnOngletEnRetard($d, $d['a'])
        ->post(route('center.classes.store'), ['name' => 'Groupe du soir A'])
        ->assertRedirect()
        ->assertSessionHas('error', fn (string $m) => str_contains($m, 'Centre Bonamoussadi'));

    expect(Classroom::count())->toBe(0);
});

test('une invitation preparee pour A ne part pas dans B', function () {
    $d = responsableDeDeuxCentres();

    depuisUnOngletEnRetard($d, $d['a'])
        ->post(route('teach.invitations.store'), ['name' => 'Awa', 'email' => 'awa@exemple.test', 'classroom_name' => 'Groupe A'])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(TeacherInvitation::count())->toBe(0);
});

test('un appel direct recoit un refus explicite, pas une page', function () {
    $d = responsableDeDeuxCentres();

    depuisUnOngletEnRetard($d, $d['a'])
        ->postJson(route('center.classes.store'), ['name' => 'Groupe du soir A'])
        ->assertStatus(409)
        ->assertJsonPath('centre_courant', $d['b']->id);

    expect(Classroom::count())->toBe(0);
});

test('quand la page affiche le centre courant, tout se passe normalement', function () {
    $d = responsableDeDeuxCentres();

    depuisUnOngletEnRetard($d, $d['b'])
        ->post(route('center.classes.store'), ['name' => 'Groupe du soir B'])
        ->assertSessionHasNoErrors()
        ->assertSessionMissing('error');

    expect(Classroom::where('center_id', $d['b']->id)->count())->toBe(1);
});

test('sans centre annonce, la requete passe comme avant', function () {
    $d = responsableDeDeuxCentres();

    depuisUnOngletEnRetard($d, null)
        ->post(route('center.classes.store'), ['name' => 'Groupe du soir B'])
        ->assertSessionMissing('error');

    expect(Classroom::where('center_id', $d['b']->id)->count())->toBe(1);
});

test('on peut toujours changer de centre depuis un onglet en retard', function () {
    $d = responsableDeDeuxCentres();

    depuisUnOngletEnRetard($d, $d['a'])
        ->post(route('center.switch'), ['center_id' => $d['a']->id])
        ->assertSessionMissing('error');

    expect(session(User::CENTRE_COURANT))->toBe($d['a']->id);
});
