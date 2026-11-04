<?php

use App\Http\Middleware\ResolveCenterFromHost;
use App\Models\Assignment;
use App\Models\CenterDomain;
use App\Models\Classroom;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Models\UserProfile;
use App\Notifications\AssignmentPublishedNotification;
use App\Services\Center\Domaines;
use App\Services\Center\ResolveurDns;
use App\Support\UrlDuCentre;
use Illuminate\Http\Request;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Lot B : sous-domaines et domaines propres des centres. L'hote principal ne
 * change pas ; l'hote d'un centre ne s'ouvre qu'a ses membres et porte sa
 * marque ; un hote inconnu, reserve ou non verifie ne mene nulle part ; les
 * liens fabriques pendant la requete ne suivent jamais un hote non autorise.
 *
 * Pendant les tests, la racine des sous-domaines est l'hote de APP_URL
 * (localhost) : « ecole » donne ecole.localhost.
 */
beforeEach(function () {
    $this->withoutVite();
});

// Les hotes acceptes sont un etat statique de Symfony : on ne le laisse pas
// deborder sur les autres fichiers de tests.
afterEach(function () {
    Request::setTrustedHosts([]);
});

function domCentre(string $nom, array $attributs = []): LanguageCenter
{
    return LanguageCenter::create(array_merge([
        'name' => $nom, 'slug' => \Illuminate\Support\Str::slug($nom), 'owner_email' => 'x@exemple.test', 'is_active' => true,
    ], $attributs));
}

function domMembre(LanguageCenter $centre, string $role): User
{
    $u = User::factory()->create();
    UserProfile::factory()->for($u)->create(['onboarding_completed_at' => now()]);
    $centre->members()->attach($u->id, ['role' => $role, 'joined_at' => now()]);

    return $u;
}

function domDomaine(LanguageCenter $centre, string $hote, bool $verifie = true, string $type = CenterDomain::SOUS_DOMAINE, bool $principal = true): CenterDomain
{
    return $centre->domaines()->create([
        'host' => $hote,
        'type' => $type,
        'verification_token' => 'jeton-'.$centre->id.'-'.strlen($hote),
        'verified_at' => $verifie ? now() : null,
        'is_primary' => $principal,
    ]);
}

function domRacine(): string
{
    return Domaines::racine();
}

function domUrl(string $hote, string $chemin = '/'): string
{
    return 'http://'.$hote.$chemin;
}

/** Une route du super administrateur, toujours sur l'hote principal (route() suit l'hote de la derniere requete). */
function domAdmin(string $nom, mixed $parametres = []): string
{
    return domUrl(domRacine(), route($nom, $parametres, false));
}

// ───────────────────────────── Hote principal ─────────────────────────────

test("l'hote principal ne change rien : accueil public, centre choisi en session, pas de noindex", function () {
    $a = domCentre('Centre A');
    $b = domCentre('Centre B');
    domDomaine($b, 'b.'.domRacine());
    $u = domMembre($a, 'center_admin');
    $b->members()->attach($u->id, ['role' => 'center_admin', 'joined_at' => now()]);

    $this->get(domUrl(domRacine(), '/'))->assertOk()->assertHeaderMissing('X-Robots-Tag');

    // Sur l'hote principal, le centre choisi en session reste celui-la.
    $this->actingAs($u)->withSession([User::CENTRE_COURANT => $a->id])
        ->get(domUrl(domRacine(), '/center'))
        ->assertOk()
        ->assertInertia(fn (Assert $p) => $p->where('auth.center.id', $a->id)->where('marque.centre_id', null));
});

// ───────────────────────────── Hotes refuses ─────────────────────────────

test('un hote inconnu sous la racine donne 404', function () {
    $this->get(domUrl('inconnu.'.domRacine(), '/login'))->assertNotFound();
});

test('un sous-domaine reserve donne 404, meme sous la racine', function () {
    foreach (['www', 'api', 'admin', 'mail', 'staging'] as $etiquette) {
        $this->get(domUrl($etiquette.'.'.domRacine(), '/login'))->assertNotFound();
    }
});

test("un hote non autorise est refuse par TrustHosts, sans fabriquer de lien sur cet hote", function () {
    $u = User::factory()->create(['email' => 'cible@exemple.test']);

    $this->get('http://attaquant.example.com/login')->assertStatus(400);

    // La demande de reinitialisation ne part pas : aucun courriel ne porte l'hote de l'attaquant.
    $this->post('http://attaquant.example.com/forgot-password', ['email' => $u->email])->assertStatus(400);
    $messages = app('mailer')->getSymfonyTransport()->messages();
    expect(collect($messages)->filter(fn ($m) => str_contains($m->toString(), 'attaquant.example.com')))->toBeEmpty();
});

test("le lien de reinitialisation suit l'hote autorise du centre", function () {
    $c = domCentre('Ecole Propre');
    domDomaine($c, 'prepa.ecole.cm', true, CenterDomain::DOMAINE_PROPRE);
    $u = User::factory()->create(['email' => 'eleve@exemple.test']);

    $this->post('http://prepa.ecole.cm/forgot-password', ['email' => $u->email])->assertRedirect();

    $messages = collect(app('mailer')->getSymfonyTransport()->messages());
    expect($messages)->toHaveCount(1)
        ->and(quoted_printable_decode($messages->first()->toString()))->toContain('http://prepa.ecole.cm/reset-password/');
});

test("un domaine non verifie n'est pas resolu", function () {
    $c = domCentre('Centre Patient');
    domDomaine($c, 'attente.'.domRacine(), false);
    domDomaine($c, 'pas-encore.ecole.cm', false, CenterDomain::DOMAINE_PROPRE, false);

    $this->get(domUrl('attente.'.domRacine(), '/login'))->assertNotFound();
    $this->get('http://pas-encore.ecole.cm/login')->assertStatus(400);
    expect(Domaines::centreDeLHote('attente.'.domRacine()))->toBeNull()
        ->and(Domaines::domainesPropresVerifies())->not->toContain('pas-encore.ecole.cm');
});

test('un centre ferme affiche la page « centre indisponible » sur son adresse', function () {
    $c = domCentre('Centre Ferme', ['is_active' => false]);
    domDomaine($c, 'ferme.'.domRacine());

    $this->get(domUrl('ferme.'.domRacine(), '/login'))
        ->assertStatus(503)
        ->assertSee('est indisponible')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

// ───────────────────────────── Marque et noindex ─────────────────────────────

test("sur l'hote du centre A, la marque est celle de A, et la page n'est pas indexee", function () {
    $a = domCentre('Centre A', ['display_name' => 'Académie A', 'brand_primary' => '#AA3300']);
    $b = domCentre('Centre B', ['display_name' => 'Bureau B', 'brand_primary' => '#0033AA']);
    domDomaine($a, 'a.'.domRacine());
    domDomaine($b, 'b.'.domRacine());

    $reponse = $this->get(domUrl('a.'.domRacine(), '/login'));
    $reponse->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertSee('<meta name="robots" content="noindex, follow">', false)
        ->assertInertia(fn (Assert $p) => $p->where('marque.centre_id', $a->id)->where('marque.nom', 'Académie A'));

    // Un membre de A, sur l'adresse de A, voit A meme s'il est aussi chez B.
    $u = domMembre($a, 'teacher');
    $b->members()->attach($u->id, ['role' => 'center_admin', 'joined_at' => now()]);
    $this->actingAs($u)->withSession([User::CENTRE_COURANT => $b->id])
        ->get(domUrl('a.'.domRacine(), '/center'))
        ->assertOk()
        ->assertInertia(fn (Assert $p) => $p->where('auth.center.id', $a->id)
            ->where('auth.center.role', 'teacher')
            ->where('marque.centre_id', $a->id)
            // Pas de changement de centre sur une adresse qui fixe le centre.
            ->where('auth.centres', []));
});

test("l'accueil d'une adresse de centre mene a la connexion, et elle n'a pas de sitemap", function () {
    $c = domCentre('Centre Accueil');
    domDomaine($c, 'accueil.'.domRacine());
    $u = domMembre($c, 'student');

    $this->get(domUrl('accueil.'.domRacine(), '/'))->assertRedirect(route('login'));
    $this->get(domUrl('accueil.'.domRacine(), '/sitemap.xml'))->assertNotFound();
    $this->actingAs($u)->get(domUrl('accueil.'.domRacine(), '/'))->assertRedirect(route('dashboard'));
});

// ───────────────────────────── Isolation ─────────────────────────────

test("un membre de A, sur l'hote de B, n'entre pas dans l'espace de B", function () {
    $a = domCentre('Centre A');
    $b = domCentre('Centre B');
    domDomaine($b, 'b.'.domRacine());
    $responsableA = domMembre($a, 'center_admin');
    $eleveA = domMembre($a, 'student');

    foreach (['/center', '/center/students', '/dashboard', '/settings/profile'] as $chemin) {
        $this->actingAs($responsableA)->get(domUrl('b.'.domRacine(), $chemin))
            ->assertRedirect(route('centre.autre-etablissement'));
    }
    $this->actingAs($eleveA)->get(domUrl('b.'.domRacine(), '/dashboard'))
        ->assertRedirect(route('centre.autre-etablissement'));

    // Une ecriture non plus, et un appel JSON recoit un refus net.
    $this->actingAs($responsableA)->post(domUrl('b.'.domRacine(), '/center/classes'), ['name' => 'Intruse'])
        ->assertRedirect(route('centre.autre-etablissement'));
    expect(Classroom::where('name', 'Intruse')->exists())->toBeFalse();
    $this->actingAs($responsableA)->getJson(domUrl('b.'.domRacine(), '/center/students'))->assertForbidden();

    // La page d'explication ne montre que le nom de B et renvoie vers A.
    $this->actingAs($responsableA)->get(domUrl('b.'.domRacine(), '/etablissement'))
        ->assertOk()
        ->assertInertia(fn (Assert $p) => $p->component('centre-autre-etablissement')
            ->where('centreIci', 'Centre B')
            ->has('sesCentres', 1)
            ->where('sesCentres.0.nom', 'Centre A'));
});

test("un compte sans centre, sur l'hote d'un centre, est envoye vers « rejoindre »", function () {
    $c = domCentre('Centre C');
    domDomaine($c, 'c.'.domRacine());
    $libre = User::factory()->create();
    UserProfile::factory()->for($libre)->create(['onboarding_completed_at' => now()]);

    $this->actingAs($libre)->get(domUrl('c.'.domRacine(), '/dashboard'))->assertRedirect(route('center.join'));
    $this->actingAs($libre)->get(domUrl('c.'.domRacine(), '/join'))->assertOk();
});

test("sur l'hote de B, on ne rejoint pas une classe de A", function () {
    $a = domCentre('Centre A');
    $b = domCentre('Centre B');
    domDomaine($b, 'b.'.domRacine());
    $classeA = Classroom::create(['center_id' => $a->id, 'name' => 'Classe A', 'invite_code' => 'CODEAAAA']);
    $libre = User::factory()->create();
    UserProfile::factory()->for($libre)->create(['onboarding_completed_at' => now()]);

    $this->actingAs($libre)->post(domUrl('b.'.domRacine(), '/join'), ['code' => 'CODEAAAA'])
        ->assertSessionHasErrors('code');
    expect($a->members()->where('users.id', $libre->id)->exists())->toBeFalse()
        ->and($classeA->members()->count())->toBe(0);
});

test("le super administrateur garde l'acces sur l'hote d'un centre", function () {
    $c = domCentre('Centre D');
    domDomaine($c, 'd.'.domRacine());
    $admin = User::factory()->create(['role' => 'super_admin']);
    UserProfile::factory()->for($admin)->create(['onboarding_completed_at' => now()]);

    $this->actingAs($admin)->get(domUrl('d.'.domRacine(), '/admin/centers'))->assertOk();
});

// ───────────────────────────── Gestion par le super administrateur ─────────────────────────────

test('seul le super administrateur gere les adresses, et un domaine se cherche dans son centre', function () {
    $a = domCentre('Centre A');
    $b = domCentre('Centre B');
    $domaineB = domDomaine($b, 'b.'.domRacine());
    $responsableA = domMembre($a, 'center_admin');

    $this->actingAs($responsableA)->post(domAdmin('admin.centers.domaines.store', $a), ['host' => 'pirate'])->assertForbidden();
    $this->actingAs($responsableA)->delete(domAdmin('admin.centers.domaines.destroy', [$b, $domaineB]))->assertForbidden();
    expect(CenterDomain::count())->toBe(1);

    // Le domaine de B, demande au nom de A : introuvable.
    $admin = User::factory()->create(['role' => 'super_admin']);
    $this->actingAs($admin)->delete(domAdmin('admin.centers.domaines.destroy', [$a, $domaineB]))->assertNotFound();
    expect($domaineB->fresh())->not->toBeNull();
});

test("le super administrateur ajoute un sous-domaine, actif et principal tout de suite", function () {
    $c = domCentre('Ecole');
    $admin = User::factory()->create(['role' => 'super_admin']);

    $this->actingAs($admin)->post(domAdmin('admin.centers.domaines.store', $c), ['host' => ' Ecole '])
        ->assertSessionHasNoErrors();

    $d = CenterDomain::firstWhere('host', 'ecole.'.domRacine());
    expect($d)->not->toBeNull()
        ->and($d->type)->toBe(CenterDomain::SOUS_DOMAINE)
        ->and($d->estVerifie())->toBeTrue()
        ->and($d->is_primary)->toBeTrue();

    $this->actingAs($admin)->get(domAdmin('admin.centers.show', $c))
        ->assertInertia(fn (Assert $p) => $p->has('domaines', 1)->where('domaines.0.host', 'ecole.'.domRacine())
            ->where('domainesReglages.racine', domRacine()));

    auth()->logout();
    $this->get(domUrl('ecole.'.domRacine(), '/login'))->assertOk();
});

test('les adresses reservees, principales, invalides ou deja prises sont refusees', function () {
    $a = domCentre('Centre A');
    $b = domCentre('Centre B');
    domDomaine($b, 'prise.'.domRacine());
    $admin = User::factory()->create(['role' => 'super_admin']);

    foreach (['www', 'admin', 'prise', domRacine(), 'prepla.mirlab.cloud', 'a', 'pas un domaine', '10.0.0.1', 'x.y.'.domRacine()] as $saisie) {
        $this->actingAs($admin)->post(domAdmin('admin.centers.domaines.store', $a), ['host' => $saisie])
            ->assertSessionHasErrors('host');
    }
    expect($a->domaines()->count())->toBe(0);
});

test('un domaine propre ne mene au centre qu\'apres verification DNS (TXT ou CNAME)', function () {
    $c = domCentre('Ecole Propre');
    $admin = User::factory()->create(['role' => 'super_admin']);

    $this->actingAs($admin)->post(domAdmin('admin.centers.domaines.store', $c), ['host' => 'https://Prepa.Ecole.CM/'])
        ->assertSessionHasNoErrors();
    $d = CenterDomain::firstWhere('host', 'prepa.ecole.cm');
    expect($d->type)->toBe(CenterDomain::DOMAINE_PROPRE)->and($d->estVerifie())->toBeFalse();
    $this->get('http://prepa.ecole.cm/login')->assertStatus(400);

    // Rien dans le DNS : refus explique, toujours rien.
    $this->mock(ResolveurDns::class, function ($m) {
        $m->shouldReceive('txt')->andReturn([]);
        $m->shouldReceive('cname')->andReturn(['ailleurs.example.net']);
    });
    $this->actingAs($admin)->post(domAdmin('admin.centers.domaines.verifier', [$c, $d]))->assertSessionHas('error');
    expect($d->fresh()->estVerifie())->toBeFalse();

    // Le TXT attendu : verifie, principal, accepte et resolu.
    $this->mock(ResolveurDns::class, function ($m) use ($d) {
        $m->shouldReceive('txt')->with('_prepla.prepa.ecole.cm')->andReturn(['"'.$d->valeurTxt().'"']);
        $m->shouldReceive('cname')->andReturn([]);
    });
    $this->actingAs($admin)->post(domAdmin('admin.centers.domaines.verifier', [$c, $d]))->assertSessionHas('success');
    expect($d->fresh()->estVerifie())->toBeTrue()->and($d->fresh()->is_primary)->toBeTrue();
    auth()->logout();
    $this->get('http://prepa.ecole.cm/login')->assertOk();

    // Un second domaine, verifie par CNAME vers la cible.
    $this->actingAs($admin)->post(domAdmin('admin.centers.domaines.store', $c), ['host' => 'cours.ecole.cm']);
    $e = CenterDomain::firstWhere('host', 'cours.ecole.cm');
    $this->mock(ResolveurDns::class, function ($m) {
        $m->shouldReceive('txt')->andReturn([]);
        $m->shouldReceive('cname')->with('cours.ecole.cm')->andReturn([config('domaines.cible_cname')]);
    });
    $this->actingAs($admin)->post(domAdmin('admin.centers.domaines.verifier', [$c, $e]))->assertSessionHas('success');
    expect($e->fresh()->estVerifie())->toBeTrue()->and($e->fresh()->is_primary)->toBeFalse();
});

test("retirer un domaine le rend aussitot inaccessible et passe la main a l'adresse suivante", function () {
    $c = domCentre('Centre E');
    $admin = User::factory()->create(['role' => 'super_admin']);
    $premier = domDomaine($c, 'e.'.domRacine());
    $second = domDomaine($c, 'e2.'.domRacine(), true, CenterDomain::SOUS_DOMAINE, false);

    // Mis en cache par une premiere visite.
    $this->get(domUrl('e.'.domRacine(), '/login'))->assertOk();

    $this->actingAs($admin)->delete(domAdmin('admin.centers.domaines.destroy', [$c, $premier]))->assertSessionHas('success');
    $this->get(domUrl('e.'.domRacine(), '/login'))->assertNotFound();
    expect($second->fresh()->is_primary)->toBeTrue();

    // Rendre principale une adresse non verifiee : refuse.
    $attente = domDomaine($c, 'attente.ecole.cm', false, CenterDomain::DOMAINE_PROPRE, false);
    $this->actingAs($admin)->patch(domAdmin('admin.centers.domaines.principal', [$c, $attente]))->assertSessionHas('error');
    expect($attente->fresh()->is_primary)->toBeFalse();
});

// ───────────────────────────── Liens hors requete ─────────────────────────────

test('les liens hors requete partent du domaine principal du centre, sinon de PrePla', function () {
    $avec = domCentre('Avec Domaine');
    $sans = domCentre('Sans Domaine');
    domDomaine($avec, 'prepa.ecole.cm', true, CenterDomain::DOMAINE_PROPRE);
    // Comme en production : https, sans port.
    config(['app.url' => 'https://prepla.mirlab.cloud']);

    expect(UrlDuCentre::route($avec, 'dashboard'))->toBe('https://prepa.ecole.cm/dashboard')
        ->and(UrlDuCentre::route($sans, 'dashboard'))->toBe(route('dashboard'))
        ->and(UrlDuCentre::route(null, 'dashboard'))->toBe(route('dashboard'));

    $prof = domMembre($avec, 'teacher');
    $classe = Classroom::create(['center_id' => $avec->id, 'name' => 'Classe', 'invite_code' => 'CODEBBBB']);
    $devoir = Assignment::create(['classroom_id' => $classe->id, 'created_by' => $prof->id, 'title' => 'Devoir 1']);
    $eleve = domMembre($avec, 'student');

    $mail = AssignmentPublishedNotification::forAssignment($devoir)->toMail($eleve);
    expect($mail->actionUrl)->toBe('https://prepa.ecole.cm/dashboard');
});

test("la saisie d'une adresse est normalisee", function () {
    expect(Domaines::normaliser('HTTPS://Prepa.Ecole.CM:443/chemin?x=1'))->toBe('prepa.ecole.cm')
        ->and(Domaines::normaliser('ecole.cm.'))->toBe('ecole.cm')
        ->and(Domaines::normaliser('user@ecole.cm'))->toBeNull()
        ->and(Domaines::normaliser('   '))->toBeNull()
        ->and(ResolveCenterFromHost::centre(Request::create('/')))->toBeNull();
});

test("supprimer un centre efface ses adresses, meme deja en cache", function () {
    $c = domCentre('Centre Supprime');
    domDomaine($c, 'supprime.'.domRacine());
    domDomaine($c, 'supprime.ecole.cm', true, CenterDomain::DOMAINE_PROPRE, false);
    $admin = User::factory()->create(['role' => 'super_admin']);

    $this->get(domUrl('supprime.'.domRacine(), '/login'))->assertOk();
    expect(Domaines::domainesPropresVerifies())->toContain('supprime.ecole.cm');

    $this->actingAs($admin)->delete(domAdmin('admin.centers.destroy', $c))->assertRedirect();
    auth()->logout();

    expect(CenterDomain::count())->toBe(0)
        ->and(Domaines::centreDeLHote('supprime.'.domRacine()))->toBeNull()
        ->and(Domaines::domainesPropresVerifies())->toBe([]);
    $this->get(domUrl('supprime.'.domRacine(), '/login'))->assertNotFound();
});
