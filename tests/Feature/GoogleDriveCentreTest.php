<?php

use App\Models\CenterGoogleDrive;
use App\Models\CenterMedia;
use App\Models\Classroom;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Client\Request as RequeteHttp;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Google Drive du centre : connexion OAuth, jeton chiffre, dossiers, envoi
 * direct (session resumable), enregistrement des medias, isolation entre
 * centres, sauvegarde JSON, fonction masquee sans identifiants Google.
 */
beforeEach(function () {
    $this->withoutVite();
    config([
        'services.google_drive.client_id' => 'client-test',
        'services.google_drive.client_secret' => 'secret-test',
        'services.google_drive.api_key' => 'cle-test',
        'services.google_drive.app_id' => '123',
    ]);
});

function driveCentre(string $nom = 'Centre Akwa'): array
{
    $centre = LanguageCenter::create([
        'name' => $nom, 'slug' => \Illuminate\Support\Str::slug($nom), 'owner_email' => 'x@exemple.test', 'is_active' => true,
    ]);
    $resp = User::factory()->create();
    UserProfile::factory()->for($resp)->create(['onboarding_completed_at' => now()]);
    $centre->members()->attach($resp->id, ['role' => 'center_admin', 'joined_at' => now()]);
    $prof = User::factory()->create();
    UserProfile::factory()->for($prof)->create(['onboarding_completed_at' => now()]);
    $centre->members()->attach($prof->id, ['role' => 'teacher', 'joined_at' => now()]);
    $eleve = User::factory()->create();
    UserProfile::factory()->for($eleve)->create(['onboarding_completed_at' => now()]);
    $centre->members()->attach($eleve->id, ['role' => 'student', 'joined_at' => now()]);

    return compact('centre', 'resp', 'prof', 'eleve');
}

function driveConnecte(LanguageCenter $centre): CenterGoogleDrive
{
    return CenterGoogleDrive::create([
        'center_id' => $centre->id, 'email' => 'centre@gmail.com', 'refresh_token' => 'rafraichir-'.$centre->id,
        'root_folder_id' => 'racine', 'connecte_le' => now(),
        'folders' => ['Videos' => 'd-videos', 'Audios' => 'd-audios', 'Documents' => 'd-docs', 'Sauvegardes' => 'd-sauv'],
    ]);
}

function fauxGoogle(): void
{
    $n = 0;
    Http::fake([
        'oauth2.googleapis.com/token' => Http::response(['access_token' => 'acces-123', 'refresh_token' => 'rafraichir-xyz', 'expires_in' => 3600]),
        'oauth2.googleapis.com/revoke' => Http::response([]),
        'openidconnect.googleapis.com/*' => Http::response(['email' => 'centre@gmail.com']),
        'www.googleapis.com/upload/drive/v3/files?uploadType=resumable*' => Http::response([], 200, ['Location' => 'https://www.googleapis.com/upload/drive/v3/files?upload_id=SESSION']),
        'www.googleapis.com/upload/drive/v3/files?uploadType=multipart*' => Http::response(['id' => 'sauvegarde-1']),
        'www.googleapis.com/drive/v3/files/*/permissions' => Http::response(['id' => 'anyoneWithLink']),
        'www.googleapis.com/drive/v3/files/fichier-1*' => Http::response(['id' => 'fichier-1', 'name' => 'cours.mp4', 'mimeType' => 'video/mp4', 'size' => '52428800']),
        'www.googleapis.com/drive/v3/files?fields=id' => function () use (&$n) {
            $n++;

            return Http::response(['id' => "dossier-{$n}"]);
        },
    ]);
}

it('masque la fonction sans identifiants Google', function () {
    config(['services.google_drive.client_id' => null, 'services.google_drive.client_secret' => null]);
    $c = driveCentre();

    $this->actingAs($c['resp'])->get(route('center.google.connect'))->assertNotFound();
    $this->actingAs($c['resp'])->get(route('center.parametres'))->assertOk()
        ->assertInertia(fn (Assert $p) => $p->where('drive', null));
    $this->actingAs($c['resp'])->get(route('center.media.page'))->assertOk()
        ->assertInertia(fn (Assert $p) => $p->where('drive', null));
});

it('envoie le responsable chez Google avec le seul scope drive.file', function () {
    $c = driveCentre();

    $r = $this->actingAs($c['resp'])->get(route('center.google.connect'))->assertRedirect();
    $url = $r->headers->get('Location');
    parse_str(parse_url($url, PHP_URL_QUERY), $q);

    expect($url)->toStartWith('https://accounts.google.com/')
        ->and($q['scope'])->toBe('openid email https://www.googleapis.com/auth/drive.file')
        ->and($q['access_type'])->toBe('offline')
        ->and($q['redirect_uri'])->toBe(route('center.google.callback'));
});

it('refuse la connexion a un enseignant', function () {
    $c = driveCentre();

    $this->actingAs($c['prof'])->get(route('center.google.connect'))->assertForbidden();
});

it('connecte le Drive, chiffre le jeton et cree les dossiers', function () {
    fauxGoogle();
    $c = driveCentre();
    $this->actingAs($c['resp'])->get(route('center.google.connect'));
    $state = session('google_drive_state')['state'];

    $this->actingAs($c['resp'])->get(route('center.google.callback', ['code' => 'code-1', 'state' => $state]))
        ->assertRedirect(route('center.parametres'));

    $drive = CenterGoogleDrive::sole();
    expect($drive->center_id)->toBe($c['centre']->id)
        ->and($drive->email)->toBe('centre@gmail.com')
        ->and($drive->refresh_token)->toBe('rafraichir-xyz')
        ->and($drive->root_folder_id)->toBe('dossier-1')
        ->and(array_keys($drive->folders))->toBe(['Videos', 'Audios', 'Documents', 'Sauvegardes']);

    // En base, le jeton est chiffre.
    $brut = DB::table('center_google_drives')->value('refresh_token');
    expect($brut)->not->toContain('rafraichir-xyz');

    Http::assertSent(fn (RequeteHttp $r) => str_contains($r->url(), 'drive/v3/files?fields=id')
        && $r['name'] === 'PrePla - Centre Akwa' && $r['mimeType'] === 'application/vnd.google-apps.folder');
    Http::assertSent(fn (RequeteHttp $r) => str_contains($r->url(), 'drive/v3/files?fields=id')
        && $r['name'] === 'Sauvegardes' && $r['parents'] === ['dossier-1']);
});

it('refuse un retour Google sans le bon state', function () {
    fauxGoogle();
    $c = driveCentre();
    $this->actingAs($c['resp'])->get(route('center.google.connect'));

    $this->actingAs($c['resp'])->get(route('center.google.callback', ['code' => 'code-1', 'state' => 'faux']))
        ->assertRedirect(route('center.parametres'));
    expect(CenterGoogleDrive::count())->toBe(0);
});

it('deconnecte en revoquant le jeton', function () {
    fauxGoogle();
    $c = driveCentre();
    driveConnecte($c['centre']);

    $this->actingAs($c['resp'])->post(route('center.google.disconnect'))->assertRedirect();

    expect(CenterGoogleDrive::count())->toBe(0);
    Http::assertSent(fn (RequeteHttp $r) => str_contains($r->url(), 'revoke') && $r['token'] === 'rafraichir-'.$c['centre']->id);
});

it('ouvre une session d envoi resumable dans le bon sous-dossier', function () {
    fauxGoogle();
    $c = driveCentre();
    driveConnecte($c['centre']);

    $this->actingAs($c['prof'])->postJson(route('center.google.upload-session'), [
        'type' => 'video', 'name' => 'cours.mp4', 'mime' => 'video/mp4', 'size' => 52428800,
    ])->assertOk()->assertJsonPath('upload_url', 'https://www.googleapis.com/upload/drive/v3/files?upload_id=SESSION');

    Http::assertSent(fn (RequeteHttp $r) => str_contains($r->url(), 'uploadType=resumable')
        && $r['parents'] === ['d-videos']
        && $r->hasHeader('X-Upload-Content-Length', '52428800')
        && $r->hasHeader('Authorization', 'Bearer acces-123'));
});

it('refuse l envoi Drive quand le centre n a pas connecte son Drive', function () {
    fauxGoogle();
    $c = driveCentre();

    $this->actingAs($c['prof'])->postJson(route('center.google.upload-session'), [
        'type' => 'video', 'name' => 'cours.mp4', 'mime' => 'video/mp4', 'size' => 10,
    ])->assertStatus(409);
});

it('enregistre le fichier du Drive avec ses droits et le rend lisible par lien', function () {
    fauxGoogle();
    $c = driveCentre();
    driveConnecte($c['centre']);

    $this->actingAs($c['prof'])->postJson(route('center.google.media'), [
        'drive_file_id' => 'fichier-1', 'title' => 'Cours 1', 'source_rights' => 'Enregistrement du centre', 'rights_certified' => true,
    ])->assertOk()->assertJsonPath('type', 'video')->assertJsonPath('provider', 'google_drive');

    $m = CenterMedia::sole();
    expect($m->provider)->toBe('google_drive')
        ->and($m->drive_file_id)->toBe('fichier-1')
        ->and($m->original_name)->toBe('cours.mp4')
        ->and($m->mime)->toBe('video/mp4')
        ->and((int) $m->size)->toBe(52428800)
        ->and($m->rights_certified_at)->not->toBeNull()
        ->and($m->url)->toBe('/media/drive/'.$m->id);

    Http::assertSent(fn (RequeteHttp $r) => str_ends_with($r->url(), 'fichier-1/permissions') && $r['type'] === 'anyone' && $r['role'] === 'reader');
});

it('exige la source et la certification des droits', function () {
    fauxGoogle();
    $c = driveCentre();
    driveConnecte($c['centre']);

    $this->actingAs($c['prof'])->postJson(route('center.google.media'), ['drive_file_id' => 'fichier-1'])
        ->assertJsonValidationErrors(['source_rights', 'rights_certified']);
    expect(CenterMedia::count())->toBe(0);
});

it('ne laisse lire un media Drive qu aux membres de son centre', function () {
    Http::fake([
        'oauth2.googleapis.com/token' => Http::response(['access_token' => 'acces-123']),
        'www.googleapis.com/drive/v3/files/*' => Http::response('octets-audio', 206, ['Content-Type' => 'audio/mpeg', 'Content-Range' => 'bytes 0-11/100']),
    ]);
    $a = driveCentre('Centre A');
    $b = driveCentre('Centre B');
    driveConnecte($a['centre']);
    $media = CenterMedia::create([
        'center_id' => $a['centre']->id, 'type' => 'audio', 'provider' => 'google_drive', 'path' => 'drive:f1',
        'drive_file_id' => 'f1', 'mime' => 'audio/mpeg', 'size' => 100,
    ]);

    $r = $this->actingAs($a['eleve'])->withHeaders(['Range' => 'bytes=0-11'])->get('/media/drive/'.$media->id);
    $r->assertStatus(206);
    expect($r->streamedContent())->toBe('octets-audio');
    Http::assertSent(fn (RequeteHttp $q) => str_contains($q->url(), 'files/f1') && $q->hasHeader('Range', 'bytes=0-11'));

    $this->actingAs($b['eleve'])->get('/media/drive/'.$media->id)->assertNotFound();
    $this->actingAs($b['resp'])->get('/media/drive/'.$media->id)->assertNotFound();
});

it('refuse d enregistrer ou supprimer un media d un autre centre', function () {
    fauxGoogle();
    $a = driveCentre('Centre A');
    $b = driveCentre('Centre B');
    driveConnecte($a['centre']);
    $media = CenterMedia::create([
        'center_id' => $a['centre']->id, 'type' => 'video', 'provider' => 'google_drive', 'path' => 'drive:f1', 'drive_file_id' => 'f1',
    ]);

    // Le centre B n'a pas de Drive : il ne peut pas utiliser celui de A.
    $this->actingAs($b['resp'])->postJson(route('center.google.upload-session'), [
        'type' => 'video', 'name' => 'x.mp4', 'mime' => 'video/mp4', 'size' => 10,
    ])->assertStatus(409);
    $this->actingAs($b['resp'])->deleteJson(route('center.media.destroy', $media))->assertForbidden();
    expect(CenterMedia::count())->toBe(1);
});

it('sauvegarde les contenus du centre en JSON sans ceux des autres', function () {
    fauxGoogle();
    $a = driveCentre('Centre A');
    $b = driveCentre('Centre B');
    $drive = driveConnecte($a['centre']);
    Classroom::create(['center_id' => $a['centre']->id, 'name' => 'Classe Alpha', 'invite_code' => 'ALPHA1']);
    Classroom::create(['center_id' => $b['centre']->id, 'name' => 'Classe Secrete', 'invite_code' => 'BETA22']);
    CenterMedia::create(['center_id' => $b['centre']->id, 'type' => 'audio', 'path' => 'x.mp3', 'title' => 'Piste de B']);

    $this->actingAs($a['resp'])->post(route('center.google.backup'))->assertRedirect(route('center.parametres'));

    Http::assertSent(function (RequeteHttp $r) {
        if (! str_contains($r->url(), 'uploadType=multipart')) {
            return false;
        }
        $corps = $r->body();

        return str_contains($corps, '"parents":["d-sauv"]')
            && str_contains($corps, 'Classe Alpha')
            && ! str_contains($corps, 'Classe Secrete')
            && ! str_contains($corps, 'Piste de B')
            && ! str_contains($corps, 'Centre B');
    });
    expect($drive->fresh()->derniere_sauvegarde_le)->not->toBeNull()
        ->and($drive->fresh()->derniere_sauvegarde_id)->toBe('sauvegarde-1');
});

it('sauvegarde chaque centre connecte par la commande hebdomadaire', function () {
    fauxGoogle();
    $a = driveCentre('Centre A');
    driveCentre('Centre B');
    driveConnecte($a['centre']);

    Artisan::call('centres:sauvegarder-drive');

    expect(CenterGoogleDrive::sole()->derniere_sauvegarde_le)->not->toBeNull();
    Http::assertSentCount(2); // jeton + depot
});

it('montre l etat du Drive dans les parametres et l encart dans la mediatheque', function () {
    $c = driveCentre();

    $this->actingAs($c['resp'])->get(route('center.media.page'))->assertOk()
        ->assertInertia(fn (Assert $p) => $p->where('drive.connecte', false)->where('drive.peut_connecter', true));

    driveConnecte($c['centre']);
    $this->actingAs($c['resp'])->get(route('center.parametres'))->assertOk()
        ->assertInertia(fn (Assert $p) => $p->where('drive.connecte', true)->where('drive.email', 'centre@gmail.com'));
});
