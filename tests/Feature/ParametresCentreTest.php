<?php

use App\Models\CenterAccessGrant;
use App\Models\CenterCommission;
use App\Models\CenterDomain;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\Language;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Les parametres du centre : une page d'accueil qui rassemble les reglages du
 * responsable (marque, examens, adresse, formule et sieges, acces vendus,
 * parrainage, equipe). Reservee au responsable, jamais les donnees d'un autre
 * centre, et une seule entree de menu « Paramètres du centre ».
 */
beforeEach(fn () => $this->withoutVite());

function paramCentre(string $nom, array $attributs = []): LanguageCenter
{
    return LanguageCenter::create(array_merge([
        'name' => $nom, 'slug' => Str::slug($nom), 'owner_email' => 'x@exemple.test',
        'is_active' => true, 'seats_limit' => 40,
    ], $attributs));
}

function paramMembre(LanguageCenter $centre, string $role): User
{
    $u = User::factory()->create();
    UserProfile::factory()->for($u)->create(['onboarding_completed_at' => now()]);
    $centre->members()->attach($u->id, ['role' => $role, 'joined_at' => now()]);

    return $u;
}

test('la route des parametres est chargee dans le groupe du personnel du centre', function () {
    expect(file_get_contents(base_path('routes/web.php')))->toContain("require __DIR__.'/centre-parametres.php'");

    $route = Route::getRoutes()->getByName('center.parametres');
    expect($route)->not->toBeNull()
        ->and($route->uri())->toBe('center/parametres')
        ->and($route->gatherMiddleware())->toContain(\App\Http\Middleware\EnsureCenterStaff::class);
});

test('le responsable voit une carte par reglage, avec l\'etat de son centre', function () {
    $fr = Language::create(['slug' => 'french', 'name' => 'Français', 'native_name' => 'Français', 'flag' => 'fr', 'is_active' => true]);
    $de = Language::create(['slug' => 'german', 'name' => 'Allemand', 'native_name' => 'Deutsch', 'flag' => 'de', 'is_active' => true]);
    $tcf = Exam::create(['language_id' => $fr->id, 'slug' => 'tcf', 'name' => 'TCF']);
    Exam::create(['language_id' => $de->id, 'slug' => 'goethe', 'name' => 'Goethe']);

    $centre = paramCentre('Institut Yaoundé', [
        'formule' => LanguageCenter::FORMULE_REVENTE, 'prix_acces' => 5000, 'duree_acces_jours' => 30,
    ]);
    $centre->forceFill(['display_name' => 'Yaoundé Langues', 'brand_primary' => '#1D4ED8'])->save();
    $centre->examensProposes()->sync([$tcf->id]);

    $responsable = paramMembre($centre, 'center_admin');
    paramMembre($centre, 'teacher');
    $eleve = paramMembre($centre, 'student');
    paramMembre($centre, 'student');
    Classroom::create(['center_id' => $centre->id, 'name' => 'B1 soir', 'level' => 'B1', 'invite_code' => 'PARAM1']);

    CenterDomain::create(['center_id' => $centre->id, 'host' => 'yaounde.prepla.test', 'type' => CenterDomain::SOUS_DOMAINE, 'verification_token' => Str::random(32), 'verified_at' => now(), 'is_primary' => true]);
    CenterAccessGrant::create([
        'center_id' => $centre->id, 'user_id' => $eleve->id,
        'starts_at' => now()->subDay(), 'ends_at' => now()->addDays(29),
        'amount' => 5000, 'currency' => 'XAF', 'payment_method' => 'mobile_money',
    ]);

    $this->actingAs($responsable)->get(route('center.parametres'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('center/parametres')
            ->where('centre.id', $centre->id)
            ->where('identite.nom_affiche', 'Yaoundé Langues')
            ->where('identite.primaire', '#1D4ED8')
            ->where('identite.couleurs_personnalisees', true)
            ->where('examens.tous', false)
            ->has('examens.liste', 1)
            ->where('examens.liste.0.name', 'TCF')
            ->where('examens.total_proposables', 2)
            ->has('adresse.domaines', 1)
            ->where('adresse.domaines.0.host', 'yaounde.prepla.test')
            ->where('adresse.domaines.0.verifie', true)
            ->missing('adresse.domaines.0.txt_valeur')
            ->where('formule.formule', 'revente')
            ->where('formule.sieges_utilises', 2)
            ->where('formule.sieges_limite', 40)
            ->where('formule.eleves_actifs', 0)
            ->where('formule.palier', 'A1')
            ->where('acces.en_cours', 1)
            ->where('acces.prix', 5000)
            ->where('parrainage.comptes', 0)
            ->where('parrainage.lien', route('parrainage.lien', $centre->codeParrainage()))
            ->where('equipe.responsables', 1)
            ->where('equipe.enseignants', 1)
            ->where('equipe.classes', 1)
        );
});

test('hors formule revente, la carte des acces vendus n\'apparait pas', function () {
    $centre = paramCentre('Centre Paliers');

    $this->actingAs(paramMembre($centre, 'center_admin'))->get(route('center.parametres'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('formule.formule', 'paliers')
            ->where('acces', null)
            ->where('examens.tous', true)
            ->has('adresse.domaines', 0)
        );
});

test('un enseignant du centre est refuse', function () {
    $centre = paramCentre('Centre Prof');

    $this->actingAs(paramMembre($centre, 'teacher'))
        ->get(route('center.parametres'))
        ->assertForbidden();
});

test('un eleve du centre et un compte sans centre sont refuses', function () {
    $centre = paramCentre('Centre Eleve');

    $this->actingAs(paramMembre($centre, 'student'))
        ->get(route('center.parametres'))
        ->assertForbidden();

    $seul = User::factory()->create();
    UserProfile::factory()->for($seul)->create(['onboarding_completed_at' => now()]);
    $this->actingAs($seul)->get(route('center.parametres'))->assertForbidden();
});

test('un visiteur non connecte est renvoye a la connexion', function () {
    $this->get(route('center.parametres'))->assertRedirect(route('login'));
});

test('le responsable ne voit jamais les donnees d\'un autre centre', function () {
    $a = paramCentre('Centre A', ['seats_limit' => 10]);
    $b = paramCentre('Centre B', ['seats_limit' => 99, 'formule' => LanguageCenter::FORMULE_REVENTE]);
    $responsableA = paramMembre($a, 'center_admin');

    // Le centre B a des eleves, un domaine, une commission et une classe.
    paramMembre($b, 'student');
    paramMembre($b, 'student');
    paramMembre($b, 'teacher');
    CenterDomain::create(['center_id' => $b->id, 'host' => 'centre-b.prepla.test', 'type' => CenterDomain::SOUS_DOMAINE, 'verification_token' => Str::random(32), 'verified_at' => now(), 'is_primary' => true]);
    Classroom::create(['center_id' => $b->id, 'name' => 'Classe B', 'level' => 'A2', 'invite_code' => 'PARAMB']);
    CenterCommission::create([
        'center_id' => $b->id, 'user_id' => paramMembre($b, 'student')->id, 'stripe_invoice_id' => 'in_b',
        'amount_paid' => 10000, 'currency' => 'eur', 'rate' => CenterCommission::TAUX, 'commission' => 2500,
        'periode' => now(\App\Listeners\EnregistrerCommission::FUSEAU)->format('Y-m'), 'type' => CenterCommission::CHARGE,
    ]);

    // Un identifiant dans l'adresse ne change rien : le centre est celui du responsable.
    $this->actingAs($responsableA)->get(route('center.parametres', ['center' => $b->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('centre.id', $a->id)
            ->where('centre.name', 'Centre A')
            ->where('formule.sieges_limite', 10)
            ->where('formule.sieges_utilises', 0)
            ->where('acces', null)
            ->has('adresse.domaines', 0)
            ->has('parrainage.totaux', 0)
            ->where('parrainage.lien', route('parrainage.lien', $a->codeParrainage()))
            ->where('equipe.enseignants', 0)
            ->where('equipe.classes', 0)
        );
});

test('le menu partage annonce les parametres du centre', function () {
    $centre = paramCentre('Centre Menu');

    $this->actingAs(paramMembre($centre, 'center_admin'))->get(route('center.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('menus.parametres', true)
            ->where('auth.center.role', 'center_admin')
        );
});

test('l\'entree « Paramètres du centre » est reservee au responsable dans le menu', function () {
    // Le menu est construit cote client a partir du role : on verifie la regle
    // dans le composant, et que les anciennes entrees n'y sont plus qu'en repli.
    $menu = file_get_contents(resource_path('js/components/app-sidebar.tsx'));
    $entete = file_get_contents(resource_path('js/components/app-sidebar-header.tsx'));

    expect($menu)->toContain("centerRole === 'center_admin' && menus?.parametres")
        ->and($menu)->toContain("title: tc('Paramètres du centre'),")
        ->and($menu)->toContain("url: '/center/parametres',")
        ->and($entete)->toContain("centerRole === 'center_admin' && !!menus?.parametres")
        ->and($entete)->toContain('href="/center/parametres"');

    // Un enseignant recoit le meme indicateur partage, mais son role ne lui
    // ouvre pas la page.
    $centre = paramCentre('Centre Menu Prof');
    $prof = paramMembre($centre, 'teacher');
    $this->actingAs($prof)->get(route('center.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('auth.center.role', 'teacher'));
    $this->actingAs($prof)->get(route('center.parametres'))->assertForbidden();
});

test('chaque page de reglage porte la barre d\'onglets commune', function () {
    foreach (['personnalisation', 'acces', 'parrainage', 'parametres'] as $p) {
        expect(file_get_contents(resource_path("js/pages/center/{$p}.tsx")))
            ->toContain('<OngletsParametresCentre />');
    }
});

test('« Mon compte » ne remplace « Paramètres » que pour l\'equipe, pas pour un apprenant de centre', function () {
    // Un apprenant rattache a un centre a le role student : il doit garder le
    // libelle traduit (sidebar.settings), comme avant.
    $menu = file_get_contents(resource_path('js/components/app-sidebar.tsx'));

    expect($menu)->toContain("title: centerRole === 'center_admin' || centerRole === 'teacher'")
        ->and($menu)->toContain(": t('sidebar.settings', 'Paramètres')")
        ->and($menu)->not->toContain("title: centerRole ? 'Mon compte'");
});

test('la page Identité et examens a son propre titre, distinct de l\'accueil des parametres', function () {
    $perso = file_get_contents(resource_path('js/pages/center/personnalisation.tsx'));

    // Les textes passent par t() (interface en francais ou en anglais).
    expect($perso)->toContain("<h1 className=\"text-2xl font-bold tracking-tight\">{t('Identité et examens')}</h1>")
        ->and($perso)->toContain("<Head title={t('Identité et examens — {nom}', { nom: centre.name })} />")
        ->and($perso)->not->toContain("<h1 className=\"text-2xl font-bold tracking-tight\">{t('Paramètres du centre')}</h1>");
});

test('l\'entree « Paramètres du centre » reste en surbrillance sur chaque page de reglage', function () {
    // Les pages de reglage ont leur propre adresse (/center/personnalisation...) :
    // sans cela, aucune entree du menu n'etait active une fois un reglage ouvert.
    $menu = file_get_contents(resource_path('js/components/app-sidebar.tsx'));
    $nav = file_get_contents(resource_path('js/components/nav-main.tsx'));

    expect($menu)->toContain("aussiActifSur: ['/center/personnalisation', '/center/acces', '/center/parrainage']")
        ->and($nav)->toContain('[item.url, ...(item.aussiActifSur ?? [])]');
});

test('l\'onglet Accès vendus n\'apparait qu\'en formule revente', function () {
    // La barre d'onglets lit la formule partagee du centre courant, comme la
    // carte de la page d'accueil, masquee hors revente.
    $paliers = paramCentre('Centre Paliers');
    $revente = paramCentre('Centre Revente', ['formule' => LanguageCenter::FORMULE_REVENTE, 'prix_acces' => 5000]);

    $this->actingAs(paramMembre($paliers, 'center_admin'))->get(route('center.parametres'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('auth.center.formule', 'paliers')->where('acces', null));

    $this->actingAs(paramMembre($revente, 'center_admin'))->get(route('center.parametres'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('auth.center.formule', 'revente')->whereNot('acces', null));

    $onglets = file_get_contents(resource_path('js/components/onglets-parametres-centre.tsx'));
    expect($onglets)->toContain("present: !!menus?.acces && enRevente")
        ->and($onglets)->toContain("formule === 'revente'");
});

test('sans commission ce mois-ci, la carte Parrainage ne montre pas un « 0 » sans devise', function () {
    $page = file_get_contents(resource_path('js/pages/center/parametres.tsx'));

    expect($page)->not->toMatch("/totaux\.length === 0\s*\?\s*'0'/")
        ->and($page)->toContain("? t('Aucun pour l’instant')");
});
