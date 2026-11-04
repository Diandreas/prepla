<?php

use App\Models\LanguageCenter;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Center\Marque;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Lot 0 : les points de contact partages des fonctions des centres.
 *
 * Tant qu'aucun lot n'est livre, rien ne doit changer pour personne : la marque
 * reste celle de PrePla, et aucune entree de menu ne mene vers une page absente.
 */
beforeEach(fn () => $this->withoutVite());

function responsableDuSocle(): User
{
    $centre = LanguageCenter::create([
        'name' => 'Centre Socle', 'slug' => 'centre-socle', 'owner_email' => 'x@exemple.test', 'is_active' => true,
    ]);
    $r = User::factory()->create();
    UserProfile::factory()->for($r)->create(['onboarding_completed_at' => now()]);
    $centre->members()->attach($r->id, ['role' => 'center_admin', 'joined_at' => now()]);

    return $r;
}

test('la marque partagee est celle de PrePla par defaut', function () {
    $this->actingAs(responsableDuSocle())
        ->get(route('center.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('marque.nom', 'PrePla')
            ->where('marque.logo_url', Marque::LOGO_PREPLA)
            ->where('marque.couleurs.primaire', '#4A90E2')
            ->where('marque.masquer_prepla', false)
        );
});

test('la marque est partagee aussi avec un eleve sans centre', function () {
    $u = User::factory()->create();
    UserProfile::factory()->for($u)->create(['onboarding_completed_at' => now()]);

    $this->actingAs($u)->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('marque.nom', 'PrePla'));
});

test('le menu ne propose que les pages dont la route existe', function () {
    $attendu = [
        'personnalisation' => Route::has('center.personnalisation.edit'),
        'acces' => Route::has('center.acces.index'),
        'releves' => Route::has('admin.releves.index'),
        'parametres' => Route::has('center.parametres'),
    ];

    $this->actingAs(responsableDuSocle())
        ->get(route('center.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('menus.personnalisation', $attendu['personnalisation'])
            ->where('menus.acces', $attendu['acces'])
            ->where('menus.releves', $attendu['releves'])
            ->where('menus.parametres', $attendu['parametres'])
        );
});

test('les fichiers de routes dedies sont charges dans les bons groupes', function () {
    $web = file_get_contents(base_path('routes/web.php'));

    foreach (['centre-personnalisation', 'centre-acces', 'parrainage', 'admin-releves'] as $f) {
        expect(file_exists(base_path("routes/{$f}.php")))->toBeTrue()
            ->and($web)->toContain("require __DIR__.'/{$f}.php'");
    }

    // Toute route des fichiers du centre herite de auth + EnsureCenterStaff, et
    // celles des releves de auth + EnsureSuperAdmin.
    foreach (Route::getRoutes() as $r) {
        $nom = (string) $r->getName();
        if (str_starts_with($nom, 'center.personnalisation') || str_starts_with($nom, 'center.acces')) {
            expect($r->gatherMiddleware())->toContain(\App\Http\Middleware\EnsureCenterStaff::class);
        }
        if (str_starts_with($nom, 'admin.releves')) {
            expect($r->gatherMiddleware())->toContain(\App\Http\Middleware\EnsureSuperAdmin::class);
        }
    }
});
