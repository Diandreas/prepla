<?php

use App\Models\Classroom;
use App\Models\ClassroomPathStep;
use App\Models\LanguageCenter;
use App\Models\LiveParticipant;
use App\Models\LiveSession;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Center\ParcoursDeClasse;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Le replay d'un live comme etape du parcours de classe : ajout par l'equipe
 * de la classe, isolation entre classes et centres, progression de l'eleve
 * (fait quand il a assiste au live ou ouvert le replay).
 */
function prlMonde(): array
{
    config([
        'services.google_meet.client_id' => 'client-test',
        'services.google_meet.client_secret' => 'secret-test',
        'services.google_meet.redirect' => 'http://localhost/center/google/retour',
    ]);

    $membre = function (LanguageCenter $c, string $role) {
        $user = User::factory()->create();
        UserProfile::factory()->for($user)->create(['onboarding_completed_at' => now()]);
        $c->members()->attach($user->id, ['role' => $role, 'joined_at' => now()]);

        return $user;
    };
    $centre = LanguageCenter::create(['name' => 'Ecole', 'slug' => 'ecole-'.uniqid(), 'owner_email' => 'd@exemple.test', 'is_active' => true, 'seats_limit' => 50]);
    $autreCentre = LanguageCenter::create(['name' => 'Autre', 'slug' => 'autre-'.uniqid(), 'owner_email' => 'a@exemple.test', 'is_active' => true, 'seats_limit' => 50]);

    $prof = $membre($centre, 'teacher');
    $classe = $centre->classrooms()->create(['name' => 'B1 soir', 'level' => 'B1', 'invite_code' => Classroom::generateInviteCode()]);
    $classe->members()->attach($prof->id, ['role_in_class' => 'teacher']);
    $autreClasse = $centre->classrooms()->create(['name' => 'A2 matin', 'level' => 'A2', 'invite_code' => Classroom::generateInviteCode()]);
    $autreClasse->members()->attach($prof->id, ['role_in_class' => 'teacher']);

    $eleve = $membre($centre, 'student');
    $classe->members()->attach($eleve->id, ['role_in_class' => 'student']);
    $eleve2 = $membre($centre, 'student');
    $classe->members()->attach($eleve2->id, ['role_in_class' => 'student']);
    $eleveAutreClasse = $membre($centre, 'student');
    $autreClasse->members()->attach($eleveAutreClasse->id, ['role_in_class' => 'student']);

    $profAutreCentre = $membre($autreCentre, 'center_admin');
    $classeAutreCentre = $autreCentre->classrooms()->create(['name' => 'C1', 'level' => 'C1', 'invite_code' => Classroom::generateInviteCode()]);

    return compact('centre', 'autreCentre', 'prof', 'classe', 'autreClasse', 'eleve', 'eleve2', 'eleveAutreClasse', 'profAutreCentre', 'classeAutreCentre');
}

function prlLive(Classroom $classe, array $attributs = []): LiveSession
{
    return LiveSession::create($attributs + [
        'center_id' => $classe->center_id, 'classroom_id' => $classe->id,
        'title' => 'Compréhension orale', 'starts_at' => now()->subDay(), 'duration_minutes' => 60,
        'started_at' => now()->subDay(), 'ended_at' => now()->subDay()->addHour(),
        'meeting_uri' => 'https://meet.google.com/abc-defg-hij',
        'recording_url' => 'https://drive.google.com/file/d/enregistrement/view', 'replay_published' => true,
    ]);
}

it('ajoute le replay publie d un live de la classe comme etape du parcours', function () {
    $m = prlMonde();
    $live = prlLive($m['classe']);

    $this->actingAs($m['prof'])
        ->post(route('center.classes.parcours.store', $m['classe']), ['live_session_id' => $live->id, 'consigne' => 'Regardez avant jeudi'])
        ->assertSessionHasNoErrors();

    $etape = ClassroomPathStep::where('classroom_id', $m['classe']->id)->sole();
    expect($etape->live_session_id)->toBe($live->id)
        ->and($etape->exercise_id)->toBeNull()
        ->and($etape->consigne)->toBe('Regardez avant jeudi');

    // Le parcours centre le liste comme replay jouable, et marque le live deja pris.
    $this->actingAs($m['prof'])->get(route('center.classes.parcours', $m['classe']))
        ->assertInertia(fn (Assert $p) => $p
            ->where('etapes.0.kind', 'replay')
            ->where('etapes.0.titre', 'Replay : Compréhension orale')
            ->where('etapes.0.jouable', true)
            ->where('etapes.0.url', route('live.replay', $live->id))
            ->where('replays.0.id', $live->id)
            ->where('replays.0.deja_pris', true));

    // Pas deux fois le meme replay.
    $this->actingAs($m['prof'])
        ->post(route('center.classes.parcours.store', $m['classe']), ['live_session_id' => $live->id])
        ->assertSessionHasErrors('live_session_id');
    expect(ClassroomPathStep::count())->toBe(1);
});

it('refuse un replay non publie, annule, sans lien ou d une autre classe', function () {
    $m = prlMonde();
    $nonPublie = prlLive($m['classe'], ['replay_published' => false]);
    $sansLien = prlLive($m['classe'], ['recording_url' => null, 'replay_published' => false]);
    $annule = prlLive($m['classe'], ['cancelled_at' => now()]);
    $autreClasse = prlLive($m['autreClasse']);
    $autreCentre = prlLive($m['classeAutreCentre']);

    foreach ([$nonPublie, $sansLien, $annule, $autreClasse, $autreCentre] as $live) {
        $this->actingAs($m['prof'])
            ->post(route('center.classes.parcours.store', $m['classe']), ['live_session_id' => $live->id])
            ->assertSessionHasErrors('live_session_id');
    }
    expect(ClassroomPathStep::count())->toBe(0);

    // Ni exercice ni live : refuse ; les deux a la fois : refuse.
    $this->actingAs($m['prof'])->post(route('center.classes.parcours.store', $m['classe']), [])
        ->assertSessionHasErrors(['exercise_id', 'live_session_id']);
    $this->actingAs($m['prof'])->post(route('center.classes.parcours.store', $m['classe']), ['exercise_id' => 1, 'live_session_id' => prlLive($m['classe'])->id])
        ->assertSessionHasErrors('exercise_id');
    expect(ClassroomPathStep::count())->toBe(0);
});

it('interdit au centre voisin d ajouter un replay a une classe qui n est pas la sienne', function () {
    $m = prlMonde();
    $live = prlLive($m['classe']);

    $reponse = $this->actingAs($m['profAutreCentre'])
        ->post('/center/classes/'.$m['classe']->id.'/parcours', ['live_session_id' => $live->id]);
    expect($reponse->status())->toBeIn([403, 404]);
    expect(ClassroomPathStep::count())->toBe(0);
});

it('compte le replay fait quand l eleve a assiste au live ou ouvert le replay', function () {
    $m = prlMonde();
    $live = prlLive($m['classe']);
    $m['classe']->pathSteps()->create(['position' => 1, 'live_session_id' => $live->id]);
    $parcours = app(ParcoursDeClasse::class);

    $vu = $parcours->pourLEleve($m['eleve'], $m['classe']->fresh());
    expect($vu['total'])->toBe(1)
        ->and($vu['faites'])->toBe(0)
        ->and($vu['actuelle']['kind'])->toBe('replay')
        ->and($vu['actuelle']['url'])->toBe(route('live.replay', $live->id));

    // L'eleve ouvre le replay : redirige vers l'enregistrement, etape faite.
    $this->actingAs($m['eleve'])->get(route('live.replay', $live))
        ->assertRedirect('https://drive.google.com/file/d/enregistrement/view');
    expect(LiveParticipant::where('user_id', $m['eleve']->id)->value('replay_vu_at'))->not->toBeNull();
    expect($parcours->pourLEleve($m['eleve'], $m['classe']->fresh())['termine'])->toBeTrue();

    // L'autre eleve a assiste au live : fait aussi, sans ouvrir le replay.
    LiveParticipant::create(['live_session_id' => $live->id, 'user_id' => $m['eleve2']->id, 'joined_at' => now()->subDay()]);
    expect($parcours->pourLEleve($m['eleve2'], $m['classe']->fresh())['faites'])->toBe(1);
});

it('ne laisse ouvrir le replay qu aux eleves de la classe et seulement publie', function () {
    $m = prlMonde();
    $live = prlLive($m['classe']);

    $this->actingAs($m['eleveAutreClasse'])->get(route('live.replay', $live))->assertNotFound();
    $this->actingAs($m['profAutreCentre'])->get(route('live.replay', $live))->assertNotFound();

    $live->update(['replay_published' => false]);
    $this->actingAs($m['eleve'])->get(route('live.replay', $live))->assertNotFound();
    // L'equipe de la classe peut le revoir avant publication, sans compter de vue.
    $this->actingAs($m['prof'])->get(route('live.replay', $live))->assertRedirect();
    expect(LiveParticipant::whereNotNull('replay_vu_at')->count())->toBe(0);
});

it('retire le replay du parcours eleve s il est depublie, et ne le copie pas vers une autre classe', function () {
    $m = prlMonde();
    $live = prlLive($m['classe']);
    $m['classe']->pathSteps()->create(['position' => 1, 'live_session_id' => $live->id]);

    $live->update(['replay_published' => false]);
    expect(app(ParcoursDeClasse::class)->pourLEleve($m['eleve'], $m['classe']->fresh())['total'])->toBe(0);

    $this->actingAs($m['prof'])
        ->post(route('center.classes.parcours.copier', $m['autreClasse']), ['source_id' => $m['classe']->id])
        ->assertSessionHasNoErrors();
    expect($m['autreClasse']->pathSteps()->count())->toBe(0);
});

it('garde le seuil vide sur une etape replay modifiee', function () {
    $m = prlMonde();
    $live = prlLive($m['classe']);
    $etape = $m['classe']->pathSteps()->create(['position' => 1, 'live_session_id' => $live->id]);

    $this->actingAs($m['prof'])
        ->patch(route('center.classes.parcours.update', [$m['classe'], $etape]), ['titre' => 'Revoir le live', 'seuil_reussite' => 80])
        ->assertSessionHasNoErrors();
    expect($etape->fresh()->titre)->toBe('Revoir le live')
        ->and($etape->fresh()->seuil_reussite)->toBeNull();
});
