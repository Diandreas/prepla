<?php

use App\Models\CenterGoogleAccount;
use App\Models\CenterMedia;
use App\Models\Classroom;
use App\Models\LanguageCenter;
use App\Models\LiveMessage;
use App\Models\LiveParticipant;
use App\Models\LivePoll;
use App\Models\LiveSession;
use App\Models\User;
use App\Models\UserProfile;
use App\Notifications\LiveClassNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Module « Live class » : cours en direct Google Meet + interactivite PrePla.
 * Google est simule (Http::fake) ; isolation stricte entre centres et classes.
 */
function lcMonde(): array
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

    $responsable = $membre($centre, 'center_admin');
    $prof = $membre($centre, 'teacher');
    $autreProf = $membre($centre, 'teacher');
    $classe = $centre->classrooms()->create(['name' => 'B1 soir', 'level' => 'B1', 'invite_code' => Classroom::generateInviteCode()]);
    $classe->members()->attach($prof->id, ['role_in_class' => 'teacher']);
    $autreClasse = $centre->classrooms()->create(['name' => 'A2 matin', 'level' => 'A2', 'invite_code' => Classroom::generateInviteCode()]);
    $autreClasse->members()->attach($autreProf->id, ['role_in_class' => 'teacher']);

    $eleve = $membre($centre, 'student');
    $classe->members()->attach($eleve->id, ['role_in_class' => 'student']);
    $eleve2 = $membre($centre, 'student');
    $classe->members()->attach($eleve2->id, ['role_in_class' => 'student']);
    $eleveAutreClasse = $membre($centre, 'student');
    $autreClasse->members()->attach($eleveAutreClasse->id, ['role_in_class' => 'student']);
    $responsableAutreCentre = $membre($autreCentre, 'center_admin');

    $compte = CenterGoogleAccount::create([
        'center_id' => $centre->id, 'user_id' => $prof->id, 'google_email' => 'prof@gmail.com',
        'access_token' => 'jeton-acces', 'refresh_token' => 'jeton-refresh', 'expires_at' => now()->addHour(),
        'scopes' => 'https://www.googleapis.com/auth/meetings.space.created https://www.googleapis.com/auth/meetings.space.readonly',
    ]);

    return compact('centre', 'autreCentre', 'responsable', 'prof', 'autreProf', 'classe', 'autreClasse', 'eleve', 'eleve2', 'eleveAutreClasse', 'responsableAutreCentre', 'compte');
}

function lcLive(array $m, array $attributs = []): LiveSession
{
    return LiveSession::create($attributs + [
        'center_id' => $m['centre']->id, 'classroom_id' => $m['classe']->id, 'created_by' => $m['prof']->id,
        'title' => 'Compréhension orale', 'starts_at' => now()->subMinutes(5), 'duration_minutes' => 60,
        'started_at' => now()->subMinutes(5), 'meeting_uri' => 'https://meet.google.com/abc-defg-hij', 'meet_space_name' => 'spaces/abc',
    ]);
}

function lcFakeMeet(): void
{
    Http::fake([
        'meet.googleapis.com/v2/spaces' => Http::response(['name' => 'spaces/xyz', 'meetingUri' => 'https://meet.google.com/xyz-abcd-efg', 'meetingCode' => 'xyz-abcd-efg']),
    ]);
}

test('sans identifiants Google le module est masque', function () {
    $m = lcMonde();
    config(['services.google_meet.client_id' => null]);

    $this->actingAs($m['prof'])->get('/center/lives')->assertNotFound();
    $this->actingAs($m['prof'])->get(route('center.classes.show', $m['classe']))
        ->assertInertia(fn (Assert $p) => $p->where('menus.live', false));
    $this->actingAs($m['eleve'])->getJson('/lives/prochains')->assertOk()->assertJson(['lives' => []]);
});

test('lancer un live maintenant cree la salle Meet, previent les eleves et ouvre la salle', function () {
    $m = lcMonde();
    lcFakeMeet();
    Notification::fake();

    $r = $this->actingAs($m['prof'])->post(route('center.lives.store', $m['classe']), [
        'mode' => 'maintenant', 'title' => 'Révision', 'duration_minutes' => 45, 'access_type' => 'TRUSTED',
    ]);

    $live = LiveSession::sole();
    $r->assertRedirect(route('live.show', $live));
    expect($live->meeting_uri)->toBe('https://meet.google.com/xyz-abcd-efg')
        ->and($live->meet_space_name)->toBe('spaces/xyz')
        ->and($live->started_at)->not->toBeNull()
        ->and($live->statut())->toBe('en_cours');

    Http::assertSent(fn ($req) => $req->url() === 'https://meet.googleapis.com/v2/spaces'
        && $req->hasHeader('Authorization', 'Bearer jeton-acces')
        && $req['config']['accessType'] === 'TRUSTED');
    Notification::assertSentTo([$m['eleve'], $m['eleve2']], LiveClassNotification::class, fn ($n) => $n->maintenant);
    Notification::assertNotSentTo([$m['eleveAutreClasse'], $m['prof']], LiveClassNotification::class);
});

test('une serie recurrente cree une seance par semaine dans la meme salle', function () {
    $m = lcMonde();
    lcFakeMeet();
    Notification::fake();
    $debut = now()->addDays(2)->setTime(18, 0);

    $this->actingAs($m['prof'])->post(route('center.lives.store', $m['classe']), [
        'mode' => 'serie', 'title' => 'Atelier oral', 'agenda' => 'Partie 2', 'starts_at' => $debut->format('Y-m-d H:i'),
        'duration_minutes' => 60, 'repetitions' => 4,
    ])->assertRedirect();

    $lives = LiveSession::orderBy('starts_at')->get();
    expect($lives)->toHaveCount(4)
        ->and($lives->pluck('series_uuid')->unique())->toHaveCount(1)
        ->and($lives->pluck('meeting_uri')->unique()->all())->toBe(['https://meet.google.com/xyz-abcd-efg'])
        ->and($lives[3]->starts_at->format('Y-m-d H:i'))->toBe($debut->copy()->addWeeks(3)->format('Y-m-d H:i'));
    Http::assertSentCount(1);
    Notification::assertSentTo($m['eleve'], LiveClassNotification::class, fn ($n) => ! $n->maintenant && $n->occurrences === 4);
});

test('sans compte Google on peut coller un lien Meet ; un autre lien est refuse', function () {
    $m = lcMonde();
    Http::fake();
    Notification::fake();

    $this->actingAs($m['autreProf'])->post(route('center.lives.store', $m['autreClasse']), [
        'mode' => 'planifie', 'title' => 'Cours', 'starts_at' => now()->addDay()->format('Y-m-d H:i'), 'duration_minutes' => 60,
    ])->assertSessionHasErrors('google');

    $this->actingAs($m['autreProf'])->post(route('center.lives.store', $m['autreClasse']), [
        'mode' => 'planifie', 'title' => 'Cours', 'starts_at' => now()->addDay()->format('Y-m-d H:i'), 'duration_minutes' => 60,
        'meeting_uri' => 'https://evil.example.com/x',
    ])->assertSessionHasErrors('meeting_uri');

    $this->actingAs($m['autreProf'])->post(route('center.lives.store', $m['autreClasse']), [
        'mode' => 'planifie', 'title' => 'Cours', 'starts_at' => now()->addDay()->format('Y-m-d H:i'), 'duration_minutes' => 60,
        'meeting_uri' => 'https://meet.google.com/aaa-bbbb-ccc',
    ])->assertRedirect();

    expect(LiveSession::sole()->meeting_uri)->toBe('https://meet.google.com/aaa-bbbb-ccc');
    Http::assertNothingSent();
});

test('un enseignant ne programme que pour ses classes, jamais un autre centre', function () {
    $m = lcMonde();
    lcFakeMeet();
    Notification::fake();
    $donnees = ['mode' => 'maintenant', 'title' => 'X', 'duration_minutes' => 30];

    $this->actingAs($m['prof'])->post(route('center.lives.store', $m['autreClasse']), $donnees)->assertForbidden();
    $this->actingAs($m['responsableAutreCentre'])->post(route('center.lives.store', $m['classe']), $donnees)->assertNotFound();
    expect(LiveSession::count())->toBe(0);

    $live = lcLive($m);
    $this->actingAs($m['responsableAutreCentre'])->get(route('center.lives.show', $live))->assertNotFound();
    $this->actingAs($m['responsableAutreCentre'])->get(route('live.show', $live))->assertNotFound();
    $this->actingAs($m['autreProf'])->post(route('center.lives.terminer', $live))->assertForbidden();
    // Le responsable du centre voit tout son centre.
    $this->actingAs($m['responsable'])->get(route('center.lives.index'))
        ->assertInertia(fn (Assert $p) => $p->component('center/lives/index')->has('aVenir', 1));
    $this->actingAs($m['autreProf'])->get(route('center.lives.index'))
        ->assertInertia(fn (Assert $p) => $p->has('aVenir', 0));
});

test('un eleve ne voit que les lives de ses classes', function () {
    $m = lcMonde();
    $live = lcLive($m);
    $prochain = lcLive($m, ['title' => 'Demain', 'starts_at' => now()->addDay(), 'started_at' => null]);
    lcLive($m, ['classroom_id' => $m['autreClasse']->id, 'title' => 'Pas pour lui']);

    $this->actingAs($m['eleve'])->getJson('/lives/prochains')->assertOk()
        ->assertJsonCount(2, 'lives')
        ->assertJsonPath('lives.0.statut', 'en_cours')
        ->assertJsonPath('lives.1.title', 'Demain');

    $this->actingAs($m['eleveAutreClasse'])->get(route('live.show', $live))->assertNotFound();
    $this->actingAs($m['eleveAutreClasse'])->getJson(route('live.etat', $live))->assertNotFound();
    $this->actingAs($m['eleveAutreClasse'])->get(route('live.ics', $prochain))->assertNotFound();
    $this->actingAs($m['eleve'])->get(route('live.show', $live))
        ->assertInertia(fn (Assert $p) => $p->component('live/salle')->where('role', 'eleve'));
});

test('rejoindre note la presence PrePla et renvoie vers Meet ; trop tot c est refuse', function () {
    $m = lcMonde();
    $live = lcLive($m);
    $plusTard = lcLive($m, ['starts_at' => now()->addHours(3), 'started_at' => null]);

    $this->actingAs($m['eleve'])->get(route('live.rejoindre', $live))->assertRedirect('https://meet.google.com/abc-defg-hij');
    expect(LiveParticipant::where('user_id', $m['eleve']->id)->value('joined_at'))->not->toBeNull();

    $this->actingAs($m['eleve'])->get(route('live.rejoindre', $plusTard))->assertStatus(422);
});

test('le calendrier .ics porte le titre, l heure et le lien', function () {
    $m = lcMonde();
    $live = lcLive($m, ['starts_at' => now()->addDay()->setTime(18, 0), 'started_at' => null, 'agenda' => 'Lecture, oral']);

    $r = $this->actingAs($m['eleve'])->get(route('live.ics', $live))->assertOk();
    expect($r->headers->get('Content-Type'))->toContain('text/calendar')
        ->and($r->getContent())->toContain('BEGIN:VEVENT')->toContain('SUMMARY:Compréhension orale')
        ->toContain('DTSTART:'.$live->starts_at->copy()->utc()->format('Ymd\THis\Z'))
        ->toContain('Lecture\, oral')->toContain(route('live.show', $live));
});

test('main levee, questions votees, reactions et chat arrivent chez l enseignant', function () {
    $m = lcMonde();
    $live = lcLive($m);

    $this->actingAs($m['eleve'])->postJson(route('live.main', $live))->assertJson(['maMain' => true]);
    $q1 = $this->actingAs($m['eleve'])->postJson(route('live.message', $live), ['kind' => 'question', 'body' => 'Et le passé composé ?'])->assertCreated()->json('id');
    $q2 = $this->actingAs($m['eleve2'])->postJson(route('live.message', $live), ['kind' => 'question', 'body' => 'Combien de temps ?'])->assertCreated()->json('id');
    $this->actingAs($m['eleve'])->postJson(route('live.voter', [$live, $q2]))->assertJson(['aVote' => true]);
    $this->actingAs($m['eleve2'])->postJson(route('live.message', $live), ['kind' => 'reaction', 'body' => 'trop_vite'])->assertCreated();
    $this->actingAs($m['eleve2'])->postJson(route('live.message', $live), ['kind' => 'reaction', 'body' => 'n_importe'])->assertUnprocessable();
    $this->actingAs($m['eleve'])->postJson(route('live.message', $live), ['kind' => 'chat', 'body' => 'Bonjour !'])->assertCreated();
    // Un eleve ne partage pas de ressource et ne lance pas de sondage.
    $this->actingAs($m['eleve'])->postJson(route('live.ressource', $live), ['type' => 'lien', 'url' => 'https://exemple.test'])->assertForbidden();
    $this->actingAs($m['eleve'])->postJson(route('live.sondage', $live), ['question' => 'Q', 'options' => ['a', 'b']])->assertForbidden();

    $etat = $this->actingAs($m['prof'])->getJson(route('live.etat', $live))->assertOk();
    $etat->assertJsonPath('mains.0.name', $m['eleve']->name)
        ->assertJsonPath('questions.0.id', $q2)       // triee par votes
        ->assertJsonPath('questions.0.votes', 1)
        ->assertJsonPath('questions.0.auteur', $m['eleve2']->name)
        ->assertJsonPath('questions.1.id', $q1)
        ->assertJsonPath('reactions.trop_vite', 1)
        ->assertJsonPath('chat.0.body', 'Bonjour !');

    // Cote eleve, l'auteur d'une question reste anonyme ; le chat se lit par increment.
    $vue = $this->actingAs($m['eleve2'])->getJson(route('live.etat', $live).'?depuis='.$etat->json('chat.0.id'));
    $vue->assertJsonPath('questions.0.auteur', null)->assertJsonCount(0, 'chat')->assertJsonMissingPath('mains');

    $this->actingAs($m['prof'])->postJson(route('live.main.baisser', [$live, $m['eleve']]))->assertOk();
    $this->actingAs($m['prof'])->postJson(route('live.question.repondue', [$live, $q2]))->assertOk();
    $this->actingAs($m['prof'])->getJson(route('live.etat', $live))->assertJsonCount(0, 'mains')->assertJsonPath('questions.1.repondue', true);
});

test('sondage et quiz eclair : resultats en direct, bonne reponse revelee a la fermeture', function () {
    $m = lcMonde();
    $live = lcLive($m);

    $id = $this->actingAs($m['prof'])->postJson(route('live.sondage', $live), [
        'question' => 'Quel auxiliaire ?', 'options' => ['avoir', 'être', 'aller'], 'correct' => 1,
    ])->assertCreated()->json('id');

    $this->actingAs($m['eleve2'])->getJson(route('live.etat', $live))
        ->assertJsonPath('sondage.resultats', null)->assertJsonPath('sondage.correct', null);
    $this->actingAs($m['eleve'])->postJson(route('live.sondage.repondre', [$live, $id]), ['option' => 1])->assertOk();
    $this->actingAs($m['eleve2'])->postJson(route('live.sondage.repondre', [$live, $id]), ['option' => 7])->assertUnprocessable();
    $this->actingAs($m['eleve2'])->postJson(route('live.sondage.repondre', [$live, $id]), ['option' => 0])->assertOk();

    $this->actingAs($m['prof'])->getJson(route('live.etat', $live))
        ->assertJsonPath('sondage.resultats', [1, 1, 0])->assertJsonPath('sondage.correct', 1)->assertJsonPath('sondage.total', 2);
    $this->actingAs($m['eleve'])->getJson(route('live.etat', $live))
        ->assertJsonPath('sondage.maReponse', 1)->assertJsonPath('sondage.correct', null);

    $this->actingAs($m['prof'])->postJson(route('live.sondage.fermer', [$live, $id]))->assertOk();
    $this->actingAs($m['eleve'])->postJson(route('live.sondage.repondre', [$live, $id]), ['option' => 0])->assertUnprocessable();
    $this->actingAs($m['eleve'])->getJson(route('live.etat', $live))->assertJsonPath('sondage.correct', 1)->assertJsonPath('sondage.ferme', true);
    expect(LivePoll::find($id)->answers()->count())->toBe(2);
});

test('ressources : lien, document et exercice publie du centre ; jamais un brouillon ni un autre centre', function () {
    $m = lcMonde();
    $live = lcLive($m);
    $media = CenterMedia::create(['center_id' => $m['centre']->id, 'uploaded_by' => $m['prof']->id, 'type' => 'document', 'path' => 'centres/fiche.pdf', 'original_name' => 'fiche.pdf', 'mime' => 'application/pdf', 'size' => 10]);
    $mediaAutre = CenterMedia::create(['center_id' => $m['autreCentre']->id, 'uploaded_by' => $m['responsableAutreCentre']->id, 'type' => 'document', 'path' => 'x.pdf', 'original_name' => 'x.pdf', 'mime' => 'application/pdf', 'size' => 10]);

    $this->actingAs($m['prof'])->postJson(route('live.ressource', $live), ['type' => 'lien', 'url' => 'https://exemple.test/doc', 'titre' => 'Article'])->assertCreated();
    $this->actingAs($m['prof'])->postJson(route('live.ressource', $live), ['type' => 'document', 'id' => $media->id])->assertCreated();
    $this->actingAs($m['prof'])->postJson(route('live.ressource', $live), ['type' => 'document', 'id' => $mediaAutre->id])->assertNotFound();

    $etat = $this->actingAs($m['eleve'])->getJson(route('live.etat', $live))->assertJsonCount(2, 'ressources');
    expect($etat->json('ressources.0.titre'))->toBe('fiche.pdf')
        ->and($etat->json('ressources.1.url'))->toBe('https://exemple.test/doc');
});

test('terminer : presence lue chez Google, enregistrement recupere, bilan et replay', function () {
    $m = lcMonde();
    $live = lcLive($m);
    $m['eleve2']->update(['name' => 'Chloé Martin']);
    // eleve : a clique Rejoindre (presence PrePla) ; eleve2 : vu dans Meet.
    $this->actingAs($m['eleve'])->get(route('live.rejoindre', $live));

    Http::fake([
        'meet.googleapis.com/v2/conferenceRecords?*' => Http::response(['conferenceRecords' => [['name' => 'conferenceRecords/r1']]]),
        'meet.googleapis.com/v2/conferenceRecords/r1/participants*' => Http::response(['participants' => [
            ['signedinUser' => ['displayName' => 'chloe martin'], 'earliestStartTime' => now()->subMinutes(40)->toIso8601String(), 'latestEndTime' => now()->toIso8601String()],
        ]]),
        'meet.googleapis.com/v2/conferenceRecords/r1/recordings*' => Http::response(['recordings' => [['driveDestination' => ['exportUri' => 'https://drive.google.com/file/d/rec1/view']]]]),
    ]);

    $this->actingAs($m['prof'])->post(route('center.lives.terminer', $live))->assertRedirect(route('center.lives.show', $live));
    $live->refresh();
    expect($live->ended_at)->not->toBeNull()->and($live->recording_url)->toBe('https://drive.google.com/file/d/rec1/view');

    $this->actingAs($m['prof'])->get(route('center.lives.show', $live))->assertInertia(fn (Assert $p) => $p
        ->component('center/lives/show')
        ->where('presence', fn ($liste) => collect($liste)->firstWhere('id', $m['eleve2']->id)['source'] === 'google'
            && collect($liste)->firstWhere('id', $m['eleve2']->id)['minutes'] === 40
            && collect($liste)->firstWhere('id', $m['eleve']->id)['source'] === 'prepla'));

    // Plus d'interaction apres la fin.
    $this->actingAs($m['eleve'])->postJson(route('live.message', $live), ['kind' => 'chat', 'body' => 'trop tard'])->assertUnprocessable();

    // Replay cache tant qu'il n'est pas publie.
    $this->actingAs($m['eleve'])->get(route('live.show', $live))->assertInertia(fn (Assert $p) => $p->where('live.recording_url', null));
    $this->actingAs($m['prof'])->patch(route('center.lives.update', $live), [
        'recording_url' => 'https://drive.google.com/file/d/rec1/view', 'summary' => 'Vu : le passé composé.', 'replay_published' => true,
    ])->assertSessionHasNoErrors();
    $this->actingAs($m['eleve'])->get(route('live.show', $live))->assertInertia(fn (Assert $p) => $p
        ->where('live.recording_url', 'https://drive.google.com/file/d/rec1/view')->where('live.summary', 'Vu : le passé composé.'));
});

test('les notes de l eleve sont privees et gardees', function () {
    $m = lcMonde();
    $live = lcLive($m);

    $this->actingAs($m['eleve'])->putJson(route('live.notes', $live), ['notes' => 'avoir + participe'])->assertOk();
    $this->actingAs($m['eleve'])->get(route('live.show', $live))->assertInertia(fn (Assert $p) => $p->where('notes', 'avoir + participe'));
    $this->actingAs($m['eleve2'])->get(route('live.show', $live))->assertInertia(fn (Assert $p) => $p->where('notes', null));
    $this->actingAs($m['prof'])->putJson(route('live.notes', $live), ['notes' => 'x'])->assertForbidden();
});

test('connexion Google : state anti-CSRF verifie, jetons chiffres en base', function () {
    $m = lcMonde();
    $m['compte']->delete();

    $r = $this->actingAs($m['prof'])->get(route('center.google.connecter'));
    $url = $r->headers->get('Location');
    expect($url)->toStartWith('https://accounts.google.com/o/oauth2/v2/auth')->toContain(urlencode('https://www.googleapis.com/auth/meetings.space.created'));
    parse_str(parse_url($url, PHP_URL_QUERY), $q);

    $this->actingAs($m['prof'])->get(route('center.google.retour', ['state' => 'faux', 'code' => 'c']))->assertForbidden();

    $idToken = 'x.'.rtrim(strtr(base64_encode(json_encode(['email' => 'prof@ecole.fr'])), '+/', '-_'), '=').'.y';
    Http::fake(['oauth2.googleapis.com/token' => Http::response([
        'access_token' => 'acces-123', 'refresh_token' => 'refresh-456', 'expires_in' => 3600, 'id_token' => $idToken,
        'scope' => 'https://www.googleapis.com/auth/meetings.space.created',
    ])]);
    $this->actingAs($m['prof'])->withSession(['live_google_state' => $q['state']])
        ->get(route('center.google.retour', ['state' => $q['state'], 'code' => 'code-ok']))
        ->assertRedirect(route('center.lives.index'));

    $compte = CenterGoogleAccount::where('user_id', $m['prof']->id)->sole();
    expect($compte->access_token)->toBe('acces-123')->and($compte->google_email)->toBe('prof@ecole.fr')
        ->and(\DB::table('center_google_accounts')->where('id', $compte->id)->value('access_token'))->not->toContain('acces-123');
});

test('un jeton expire est renouvele avant la creation de la salle', function () {
    $m = lcMonde();
    Notification::fake();
    $m['compte']->update(['expires_at' => now()->subMinute()]);
    Http::fake([
        'oauth2.googleapis.com/token' => Http::response(['access_token' => 'neuf', 'expires_in' => 3600]),
        'meet.googleapis.com/v2/spaces' => Http::response(['name' => 'spaces/n', 'meetingUri' => 'https://meet.google.com/nnn-nnnn-nnn']),
    ]);

    $this->actingAs($m['prof'])->post(route('center.lives.store', $m['classe']), ['mode' => 'maintenant', 'title' => 'X', 'duration_minutes' => 30]);

    Http::assertSent(fn ($req) => str_contains($req->url(), '/v2/spaces') && $req->hasHeader('Authorization', 'Bearer neuf'));
    expect($m['compte']->fresh()->access_token)->toBe('neuf');
});
