<?php

use App\Http\Controllers\Center\PersonnalisationController;
use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\Language;
use App\Models\LanguageCenter;
use App\Models\TeacherInvitation;
use App\Models\User;
use App\Models\UserProfile;
use App\Notifications\AssignmentPublishedNotification;
use App\Services\Center\Couleurs;
use App\Services\Center\Marque;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Lot A : la personnalisation d'un centre (nom affiche, logo, couleurs, marque
 * blanche, examens proposes), reservee a son responsable, et ce qu'en voient
 * ses eleves et son equipe. Un centre ne touche jamais ceux des autres.
 */
beforeEach(function () {
    $this->withoutVite();
    Storage::fake('public');
});

/** Deux langues, trois examens proposables (et un d'une langue masquee). */
function persoExamens(): array
{
    $fr = Language::create(['slug' => 'french', 'name' => 'Français', 'native_name' => 'Français', 'flag' => 'fr', 'is_active' => true]);
    $de = Language::create(['slug' => 'german', 'name' => 'Allemand', 'native_name' => 'Deutsch', 'flag' => 'de', 'is_active' => true]);
    $ja = Language::create(['slug' => 'japanese', 'name' => 'Japonais', 'native_name' => '日本語', 'flag' => 'jp', 'is_active' => false]);

    return [
        'delf' => Exam::create(['language_id' => $fr->id, 'slug' => 'delf-dalf', 'name' => 'DELF/DALF']),
        'tcf' => Exam::create(['language_id' => $fr->id, 'slug' => 'tcf', 'name' => 'TCF']),
        'goethe' => Exam::create(['language_id' => $de->id, 'slug' => 'goethe', 'name' => 'Goethe']),
        'jlpt' => Exam::create(['language_id' => $ja->id, 'slug' => 'jlpt', 'name' => 'JLPT']),
    ];
}

function persoCentre(string $nom, array $attributs = []): LanguageCenter
{
    return LanguageCenter::create(array_merge([
        'name' => $nom, 'slug' => \Illuminate\Support\Str::slug($nom), 'owner_email' => 'x@exemple.test', 'is_active' => true,
    ], $attributs));
}

function persoMembre(LanguageCenter $centre, string $role, array $profil = []): User
{
    $u = User::factory()->create();
    UserProfile::factory()->for($u)->create(array_merge(['onboarding_completed_at' => now()], $profil));
    $centre->members()->attach($u->id, ['role' => $role, 'joined_at' => now()]);

    return $u;
}

function persoPng(string $nom = 'logo.png', int $ko = 0): UploadedFile
{
    $f = UploadedFile::fake()->image($nom, 64, 64);

    return $ko > 0 ? $f->size($ko) : $f;
}

/** Les champs du formulaire, tous examens coches par defaut. */
function persoFormulaire(array $examens, array $champs = []): array
{
    return array_merge([
        'display_name' => null,
        'brand_primary' => null,
        'brand_accent' => null,
        'hide_prepla_branding' => false,
        'examens' => collect($examens)->reject(fn (Exam $e) => $e->slug === 'jlpt')->pluck('id')->all(),
    ], $champs);
}

// ───────────────────────────── Acces ─────────────────────────────

test('un enseignant recoit 403 sur toute la personnalisation', function () {
    $examens = persoExamens();
    $centre = persoCentre('Centre Akwa');
    $prof = persoMembre($centre, 'teacher');

    $this->actingAs($prof)->get(route('center.personnalisation.edit'))->assertForbidden();
    $this->actingAs($prof)->patch(route('center.personnalisation.update'), persoFormulaire($examens, ['display_name' => 'Pirate']))->assertForbidden();
    $this->actingAs($prof)->post(route('center.personnalisation.logo.store'), ['logo' => persoPng()])->assertForbidden();
    $this->actingAs($prof)->delete(route('center.personnalisation.logo.destroy'))->assertForbidden();

    expect($centre->fresh()->display_name)->toBeNull();
});

test('un eleve et un compte sans centre n y ont pas acces', function () {
    $centre = persoCentre('Centre Akwa');
    $eleve = persoMembre($centre, 'student');
    $seul = User::factory()->create();

    $this->actingAs($eleve)->get(route('center.personnalisation.edit'))->assertForbidden();
    $this->actingAs($seul)->get(route('center.personnalisation.edit'))->assertForbidden();
});

test('le responsable ouvre la page, et le menu la propose', function () {
    $examens = persoExamens();
    $centre = persoCentre('Centre Akwa');
    $centre->examensProposes()->sync([$examens['tcf']->id]);
    $resp = persoMembre($centre, 'center_admin');

    $this->actingAs($resp)->get(route('center.personnalisation.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $p) => $p
            ->component('center/personnalisation')
            ->where('centre.id', $centre->id)
            ->where('retenus', [$examens['tcf']->id])
            ->where('menus.personnalisation', true)
            // Tous les examens proposables, groupes par langue ; jamais la langue masquee.
            ->has('examens', 2)
        );
});

// ───────────────────────────── Enregistrement ─────────────────────────────

test('le responsable enregistre nom, couleurs, marque blanche et examens', function () {
    $examens = persoExamens();
    $centre = persoCentre('Centre Akwa');
    $resp = persoMembre($centre, 'center_admin');

    $this->actingAs($resp)->patch(route('center.personnalisation.update'), persoFormulaire($examens, [
        'display_name' => '  Akwa Langues  ',
        'brand_primary' => '#0f766e',
        'brand_accent' => '#9A3412',
        'hide_prepla_branding' => true,
        'examens' => [$examens['delf']->id, $examens['goethe']->id],
    ]))->assertRedirect()->assertSessionHasNoErrors();

    $centre->refresh();
    expect($centre->display_name)->toBe('Akwa Langues')
        ->and($centre->brand_primary)->toBe('#0F766E')
        ->and($centre->brand_accent)->toBe('#9A3412')
        ->and($centre->hide_prepla_branding)->toBeTrue()
        ->and($centre->examensRetenus())->toEqualCanonicalizing([$examens['delf']->id, $examens['goethe']->id]);
});

test('tout cocher revient a tout proposer, et c est idempotent', function () {
    $examens = persoExamens();
    $centre = persoCentre('Centre Akwa');
    $resp = persoMembre($centre, 'center_admin');

    foreach ([1, 2] as $_) {
        $this->actingAs($resp)->patch(route('center.personnalisation.update'), persoFormulaire($examens))->assertSessionHasNoErrors();
    }

    expect($centre->examensRetenus())->toBeNull()
        ->and(\DB::table('center_exam')->where('center_id', $centre->id)->count())->toBe(0);
});

test('les couleurs trop claires ou mal formees sont refusees', function () {
    $examens = persoExamens();
    $centre = persoCentre('Centre Akwa');
    $resp = persoMembre($centre, 'center_admin');

    $this->actingAs($resp)->patch(route('center.personnalisation.update'), persoFormulaire($examens, ['brand_primary' => '#FFEB3B']))
        ->assertSessionHasErrors('brand_primary');
    $this->actingAs($resp)->patch(route('center.personnalisation.update'), persoFormulaire($examens, ['brand_accent' => 'red;}body{']))
        ->assertSessionHasErrors('brand_accent');

    expect($centre->fresh()->brand_primary)->toBeNull()->and($centre->fresh()->brand_accent)->toBeNull();
});

test('un examen d une langue masquee, ou aucun examen, est refuse', function () {
    $examens = persoExamens();
    $centre = persoCentre('Centre Akwa');
    $resp = persoMembre($centre, 'center_admin');

    $this->actingAs($resp)->patch(route('center.personnalisation.update'), persoFormulaire($examens, ['examens' => [$examens['jlpt']->id]]))
        ->assertSessionHasErrors('examens.0');
    $this->actingAs($resp)->patch(route('center.personnalisation.update'), persoFormulaire($examens, ['examens' => []]))
        ->assertSessionHasErrors('examens');
});

// ───────────────────────────── Logo ─────────────────────────────

test('un logo png est range sous un nom genere, et remplace l ancien', function () {
    $centre = persoCentre('Centre Akwa');
    $resp = persoMembre($centre, 'center_admin');

    $this->actingAs($resp)->post(route('center.personnalisation.logo.store'), ['logo' => persoPng('mon logo.png')])
        ->assertSessionHasNoErrors();
    $premier = $centre->fresh()->logo_path;

    expect($premier)->toMatch('#^center-branding/'.$centre->id.'/[0-9a-f-]{36}\.png$#');
    Storage::disk('public')->assertExists($premier);

    $this->actingAs($resp)->post(route('center.personnalisation.logo.store'), ['logo' => UploadedFile::fake()->image('b.webp', 32, 32)])
        ->assertSessionHasNoErrors();
    $second = $centre->fresh()->logo_path;

    expect($second)->not->toBe($premier)->toEndWith('.webp');
    Storage::disk('public')->assertMissing($premier);
    Storage::disk('public')->assertExists($second);

    $this->actingAs($resp)->delete(route('center.personnalisation.logo.destroy'))->assertSessionHasNoErrors();
    expect($centre->fresh()->logo_path)->toBeNull();
    Storage::disk('public')->assertMissing($second);
});

test('un SVG est refuse, meme deguise en png', function () {
    $centre = persoCentre('Centre Akwa');
    $resp = persoMembre($centre, 'center_admin');
    $svg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>';

    // Un vrai fichier envoye : son type se lit dans le contenu, pas dans son nom.
    $envoi = function (string $nom) use ($svg) {
        $chemin = tempnam(sys_get_temp_dir(), 'svg');
        file_put_contents($chemin, $svg);

        return new UploadedFile($chemin, $nom, null, null, true);
    };

    foreach (['logo.svg', 'logo.png'] as $nom) {
        $this->actingAs($resp)->post(route('center.personnalisation.logo.store'), ['logo' => $envoi($nom)])
            ->assertSessionHasErrors('logo');
    }
    $this->actingAs($resp)->post(route('center.personnalisation.logo.store'), [
        'logo' => UploadedFile::fake()->createWithContent('logo.svg', $svg),
    ])->assertSessionHasErrors('logo');

    expect($centre->fresh()->logo_path)->toBeNull();
    expect(Storage::disk('public')->allFiles())->toBe([]);
});

test('un logo de plus de 1 Mo est refuse', function () {
    $centre = persoCentre('Centre Akwa');
    $resp = persoMembre($centre, 'center_admin');

    $this->actingAs($resp)->post(route('center.personnalisation.logo.store'), ['logo' => persoPng('lourd.png', PersonnalisationController::LOGO_KO_MAX + 1)])
        ->assertSessionHasErrors('logo');
    $this->actingAs($resp)->post(route('center.personnalisation.logo.store'), ['logo' => persoPng('juste.png', PersonnalisationController::LOGO_KO_MAX)])
        ->assertSessionHasNoErrors();
});

// ───────────────────────────── Isolation ─────────────────────────────

test('le responsable de B ne modifie que B, jamais A', function () {
    $examens = persoExamens();
    $a = persoCentre('Centre Akwa', ['display_name' => 'Akwa', 'brand_primary' => '#0F766E']);
    $b = persoCentre('Centre Bonamoussadi');
    persoMembre($a, 'center_admin');
    $respB = persoMembre($b, 'center_admin');

    // A a un logo : le responsable de B ne peut ni le voir ni le retirer.
    Storage::disk('public')->put("center-branding/{$a->id}/logo-a.png", 'x');
    $a->update(['logo_path' => "center-branding/{$a->id}/logo-a.png"]);

    $this->actingAs($respB)->get(route('center.personnalisation.edit'))
        ->assertInertia(fn (Assert $p) => $p->where('centre.id', $b->id)->where('centre.logo_url', null));

    $this->actingAs($respB)->patch(route('center.personnalisation.update'), persoFormulaire($examens, [
        'display_name' => 'Bonamoussadi', 'examens' => [$examens['goethe']->id],
    ]))->assertSessionHasNoErrors();
    $this->actingAs($respB)->delete(route('center.personnalisation.logo.destroy'))->assertSessionHasNoErrors();

    expect($a->fresh()->display_name)->toBe('Akwa')
        ->and($a->fresh()->brand_primary)->toBe('#0F766E')
        ->and($a->fresh()->logo_path)->toBe("center-branding/{$a->id}/logo-a.png")
        ->and($a->fresh()->examensRetenus())->toBeNull()
        ->and($b->fresh()->display_name)->toBe('Bonamoussadi');
    Storage::disk('public')->assertExists("center-branding/{$a->id}/logo-a.png");
});

test('un formulaire du centre A envoye apres etre passe dans B n ecrit rien', function () {
    $examens = persoExamens();
    $a = persoCentre('Centre Akwa');
    $b = persoCentre('Centre Bonamoussadi');
    $resp = persoMembre($a, 'center_admin');
    $b->members()->attach($resp->id, ['role' => 'center_admin', 'joined_at' => now()]);

    // Centre courant : B. La page affichait A (en-tete X-Prepla-Centre).
    $this->actingAs($resp)->withSession([User::CENTRE_COURANT => $b->id])
        ->withHeader('X-Prepla-Centre', (string) $a->id)
        ->patch(route('center.personnalisation.update'), persoFormulaire($examens, ['display_name' => 'Ecrit au mauvais endroit']))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($a->fresh()->display_name)->toBeNull()->and($b->fresh()->display_name)->toBeNull();
});

// ───────────────────────────── Marque vue par les membres ─────────────────────────────

test('un eleve voit la marque de son centre, un autre eleve celle du sien', function () {
    $a = persoCentre('Centre Akwa', ['display_name' => 'Akwa Langues', 'brand_primary' => '#0F766E', 'brand_accent' => '#9A3412']);
    $b = persoCentre('Centre Bonamoussadi');
    $eleveA = persoMembre($a, 'student');
    $eleveB = persoMembre($b, 'student');
    $profA = persoMembre($a, 'teacher');

    $this->actingAs($eleveA)->get('/settings/profile')
        ->assertInertia(fn (Assert $p) => $p
            ->where('marque.nom', 'Akwa Langues')
            ->where('marque.centre_id', $a->id)
            ->where('marque.couleurs.primaire', '#0F766E')
            ->where('marque.theme.texte_sur_primaire', '#FFFFFF')
            ->where('marque.masquer_prepla', false)
        );

    // B n'a rien personnalise : ses eleves gardent PrePla, jamais la marque de A.
    $this->actingAs($eleveB)->get('/settings/profile')
        ->assertInertia(fn (Assert $p) => $p->where('marque.nom', 'PrePla')->where('marque.centre_id', null));

    // L'equipe du centre la voit aussi.
    $this->actingAs($profA)->get(route('center.dashboard'))
        ->assertInertia(fn (Assert $p) => $p->where('marque.nom', 'Akwa Langues'));
});

test('les couleurs du centre sont posees des le premier affichage', function () {
    $a = persoCentre('Centre Akwa', ['brand_primary' => '#0F766E']);
    $eleve = persoMembre($a, 'student');

    $html = $this->actingAs($eleve)->get('/settings/profile')->assertOk()->getContent();

    expect($html)->toContain('<style id="marque-centre">')
        ->toContain('--primary:#0F766E')
        ->toContain('--primary-foreground:#FFFFFF');
});

test('un centre ferme ou un super administrateur garde la marque PrePla', function () {
    $a = persoCentre('Centre Akwa', ['display_name' => 'Akwa Langues', 'is_active' => false]);
    $eleve = persoMembre($a, 'student');
    $admin = User::factory()->create(['role' => 'super_admin']);

    expect(app(Marque::class)->pourUtilisateur($eleve)['nom'])->toBe('PrePla')
        ->and(app(Marque::class)->pourUtilisateur($admin)['nom'])->toBe('PrePla');
});

test('le centre resolu par l hote, quand le lot B le pose, prime', function () {
    $a = persoCentre('Centre Akwa', ['display_name' => 'Akwa Langues']);
    $requete = \Illuminate\Http\Request::create('/');
    $requete->attributes->set(Marque::CENTRE_DE_L_HOTE, $a);

    expect(app(Marque::class)->pourRequete($requete)['nom'])->toBe('Akwa Langues')
        ->and(app(Marque::class)->pourRequete(\Illuminate\Http\Request::create('/'))['nom'])->toBe('PrePla');
});

test('les pages publiques d invitation et de classe portent la marque du centre', function () {
    $a = persoCentre('Centre Akwa', ['display_name' => 'Akwa Langues']);
    $classe = $a->classrooms()->create(['name' => 'B1 soir', 'invite_code' => Classroom::generateInviteCode()]);
    [, $jeton] = TeacherInvitation::issue([
        'name' => 'Zidane', 'email' => 'zidane@exemple.test', 'center_id' => $a->id, 'role' => 'teacher',
        'classroom_name' => 'B2', 'invited_by' => persoMembre($a, 'center_admin')->id,
    ]);

    $this->get(route('teach.invitation.show', $jeton))
        ->assertInertia(fn (Assert $p) => $p->where('marque.nom', 'Akwa Langues')->where('marque.centre_id', $a->id));

    $visiteur = User::factory()->create();
    UserProfile::factory()->for($visiteur)->create(['onboarding_completed_at' => now()]);
    $this->actingAs($visiteur)->get(route('center.join', ['code' => strtolower($classe->invite_code)]))
        ->assertInertia(fn (Assert $p) => $p
            ->where('marque.nom', 'Akwa Langues')
            ->where('code', $classe->invite_code)
            ->where('centreInvitant.classe', 'B1 soir')
        );

    // Un code inconnu ne revele rien.
    $this->actingAs($visiteur)->get(route('center.join', ['code' => 'INCONNU1']))
        ->assertInertia(fn (Assert $p) => $p->where('marque.nom', 'PrePla')->where('centreInvitant', null));
});

// ───────────────────────────── Examens proposes ─────────────────────────────

test('un examen non retenu disparait des listes des eleves et du personnel', function () {
    $examens = persoExamens();
    $centre = persoCentre('Centre Akwa');
    $centre->examensProposes()->sync([$examens['delf']->id, $examens['goethe']->id]);
    $resp = persoMembre($centre, 'center_admin');
    $eleve = persoMembre($centre, 'student');
    $sansExamen = persoMembre($centre, 'student', ['target_exam_id' => null]);
    $libre = User::factory()->create();
    UserProfile::factory()->for($libre)->create(['onboarding_completed_at' => now()]);

    $noms = fn ($reponse, string $cle) => collect($reponse->viewData('page')['props'][$cle])->pluck('name')->all();

    // Eleve : reglages du profil, choix de l'examen a l'inscription, outils IA, entrainement.
    expect($noms($this->actingAs($eleve)->get('/settings/profile'), 'exams'))->toEqualCanonicalizing(['DELF/DALF', 'Goethe']);
    $langues = collect($this->actingAs($eleve)->get(route('onboarding.exam'))->viewData('page')['props']['languages']);
    expect($langues->pluck('exams')->flatten(1)->pluck('name')->all())->toEqualCanonicalizing(['DELF/DALF', 'Goethe']);
    expect($noms($this->actingAs($eleve)->get(route('ai-tools.generator')), 'exams'))->not->toContain('TCF');
    expect($noms($this->actingAs($sansExamen)->get(route('practice.index')), 'exams'))->toEqualCanonicalizing(['DELF/DALF', 'Goethe']);

    // Personnel : classes, createur d'exercices, invitation d'un collegue.
    expect($noms($this->actingAs($resp)->get(route('center.classes.index')), 'exams'))->toEqualCanonicalizing(['DELF/DALF', 'Goethe']);
    expect($noms($this->actingAs($resp)->get(route('center.exercises.create')), 'exams'))->toEqualCanonicalizing(['DELF/DALF', 'Goethe']);
    expect($noms($this->actingAs($resp)->get(route('teach.invitations')), 'exams'))->toEqualCanonicalizing(['DELF/DALF', 'Goethe']);

    // Hors de ce centre, rien ne change : tous les examens proposables.
    expect($noms($this->actingAs($libre)->get('/settings/profile'), 'exams'))->toEqualCanonicalizing(['DELF/DALF', 'TCF', 'Goethe']);
});

test('un examen non retenu ne peut pas etre choisi par un formulaire', function () {
    $examens = persoExamens();
    $centre = persoCentre('Centre Akwa');
    $centre->examensProposes()->sync([$examens['delf']->id]);
    $resp = persoMembre($centre, 'center_admin');
    $eleve = persoMembre($centre, 'student', ['target_exam_id' => $examens['tcf']->id]);

    $this->actingAs($eleve)->post(route('onboarding.exam.store'), ['exam_id' => $examens['goethe']->id])
        ->assertSessionHasErrors('exam_id');
    $this->actingAs($resp)->post(route('center.classes.store'), ['name' => 'B1', 'exam_id' => $examens['goethe']->id])
        ->assertSessionHasErrors('exam_id');
    $this->actingAs($resp)->post(route('center.classes.store'), ['name' => 'B1', 'exam_id' => $examens['delf']->id])
        ->assertSessionHasNoErrors();

    // L'examen deja choisi reste accepte : changer un autre reglage n'est pas bloque.
    $this->actingAs($eleve)->patch(route('profile.update_learning'), ['target_exam_id' => $examens['tcf']->id])
        ->assertSessionHasNoErrors();
    $this->actingAs($eleve)->patch(route('profile.update_learning'), ['target_exam_id' => $examens['goethe']->id])
        ->assertSessionHasErrors('target_exam_id');
});

// ───────────────────────────── Marque blanche ─────────────────────────────

test('en marque blanche, PrePla disparait du titre et l offre PrePla Plus est masquee', function () {
    $centre = persoCentre('Centre Akwa', ['display_name' => 'Akwa Langues', 'hide_prepla_branding' => true]);
    $eleve = persoMembre($centre, 'student');

    $reponse = $this->actingAs($eleve)->get('/settings/profile')->assertOk();
    expect($reponse->getContent())->toMatch('#<title inertia>[^<]*— Akwa Langues</title>#');
    $reponse->assertInertia(fn (Assert $p) => $p->where('marque.masquer_prepla', true)->where('offrePrePlaMasquee', true));

    $this->actingAs($eleve)->get(route('subscription.index'))->assertRedirect(route('dashboard'));
    $this->actingAs($eleve)->post(route('subscription.checkout'), ['price_id' => 'x'])->assertRedirect(route('dashboard'));

    // Sans marque blanche, l'offre reste.
    $centre->update(['hide_prepla_branding' => false]);
    expect(app(Marque::class)->offrePrePlaMasquee($eleve))->toBeFalse();
    expect($this->actingAs($eleve)->get('/settings/profile')->getContent())->toContain('— PrePla</title>');
});

test('en marque blanche, l offre PrePla Plus reste a qui le quota gratuit limite', function () {
    // Sans tarif Stripe, la page d'abonnement est servie sans appel a Stripe.
    config(['services.stripe.prices.monthly' => null, 'services.stripe.prices.annual' => null]);

    // Responsable d'un espace libre-service (/enseigner) : limite a 3 exercices par jour.
    $espace = persoCentre('Espace Zidane', [
        'display_name' => 'Cours Zidane', 'hide_prepla_branding' => true,
        'formule' => LanguageCenter::LIBRE_SERVICE, 'settings' => [LanguageCenter::LIBRE_SERVICE => true],
    ]);
    $resp = persoMembre($espace, 'center_admin');
    expect(\App\Http\Middleware\EnsureExerciseQuota::exempte($resp))->toBeFalse()
        ->and(app(Marque::class)->offrePrePlaMasquee($resp))->toBeFalse();
    $this->actingAs($resp)->get(route('subscription.index'))->assertOk();
    $this->actingAs($resp)->get('/settings/profile')
        ->assertInertia(fn (Assert $p) => $p->where('offrePrePlaMasquee', false));

    // Eleve d'un centre en revente sans acces ouvert : meme limite, meme offre.
    $revente = persoCentre('Centre Revente', ['display_name' => 'Revente Langues', 'hide_prepla_branding' => true, 'formule' => 'revente']);
    $eleve = persoMembre($revente, 'student');
    expect(\App\Http\Middleware\EnsureExerciseQuota::exempte($eleve))->toBeFalse()
        ->and(app(Marque::class)->offrePrePlaMasquee($eleve))->toBeFalse();
    $this->actingAs($eleve)->get(route('subscription.index'))->assertOk();
});

test('en marque blanche, le message qui menait a l abonnement arrive au tableau de bord', function () {
    $centre = persoCentre('Centre Akwa', ['display_name' => 'Akwa Langues', 'hide_prepla_branding' => true]);
    $eleve = persoMembre($centre, 'student');

    // Comme EnsureExerciseQuota : un message flash, puis la page d'abonnement.
    // Comme EnsureExerciseQuota : une requete pose le message et renvoie vers l'abonnement.
    \Illuminate\Support\Facades\Route::middleware(['web', 'auth'])
        ->get('/__test/vers-abonnement', fn () => redirect()->route('subscription.index')->with('error', 'Message du quota'));

    $this->actingAs($eleve)->get('/__test/vers-abonnement')->assertRedirect(route('subscription.index'));
    $this->actingAs($eleve)->get(route('subscription.index'))->assertRedirect(route('dashboard'));

    // Le tableau de bord, ou la redirection mene, le recoit encore.
    $this->actingAs($eleve)->get(route('dashboard'))
        ->assertInertia(fn (Assert $p) => $p->where('flash.error', 'Message du quota'));
});

test('l avis de devoir est signe par le centre, sans PrePla en marque blanche', function () {
    $centre = persoCentre('Centre Akwa', ['display_name' => 'Akwa Langues', 'hide_prepla_branding' => true]);
    $prof = persoMembre($centre, 'teacher');
    $eleve = persoMembre($centre, 'student');
    $classe = $centre->classrooms()->create(['name' => 'B1', 'invite_code' => Classroom::generateInviteCode()]);
    $devoir = Assignment::create(['classroom_id' => $classe->id, 'created_by' => $prof->id, 'title' => 'Lecture 3']);

    $avis = AssignmentPublishedNotification::forAssignment($devoir, $prof->name);
    expect($avis->centreNom)->toBe('Akwa Langues')->and($avis->masquerPrepla)->toBeTrue();

    $mail = $avis->toMail($eleve);
    $html = (string) $mail->render();
    expect($mail->subject)->toBe('Akwa Langues · Nouveau devoir : Lecture 3')
        ->and($mail->salutation)->toBe("L'équipe de Akwa Langues")
        ->and($html)->toContain('Akwa Langues')
        ->and(preg_replace('#<title>.*?</title>#s', '', $html))->not->toContain('PrePla');

    // Sans marque blanche : signe par le centre, mise en page PrePla habituelle.
    $centre->update(['hide_prepla_branding' => false]);
    $mail = AssignmentPublishedNotification::forAssignment($devoir->fresh(), $prof->name)->toMail($eleve);
    expect($mail->salutation)->toBe("L'équipe de Akwa Langues")->and($mail->markdown)->toBe('notifications::email');
});

// ───────────────────────────── Couleurs et demonstration ─────────────────────────────

test('le calcul de contraste cote serveur suit la formule WCAG', function () {
    expect(round(Couleurs::contraste('#000000', '#FFFFFF'), 1))->toBe(21.0)
        ->and(round(Couleurs::contraste(Marque::PRIMAIRE_PREPLA, '#FFFFFF'), 2))->toBe(3.29)
        ->and(Couleurs::texteSur('#FFD54F'))->toBe(Couleurs::TEXTE_FONCE)
        ->and(Couleurs::contraste(Couleurs::pourFondSombre('#0B2D63'), Couleurs::FOND_SOMBRE))->toBeGreaterThanOrEqual(Couleurs::CONTRASTE_MINIMAL);
});

test('la commande de demonstration donne une marque a la demo, sans rien supprimer, et peut etre relancee', function () {
    $demo = persoCentre('Institut Linguae', ['slug' => 'institut-linguae-demo']);
    $eleve = persoMembre($demo, 'student');
    $autre = persoCentre('Centre client');

    $this->artisan('prepla:demo-marque')->assertSuccessful();
    $demo->refresh();
    expect($demo->logo_path)->toBe("center-branding/{$demo->id}/demo-logo.png")
        ->and($demo->brand_primary)->toBe('#0F766E')
        ->and($demo->brand_accent)->toBe('#9A3412')
        ->and(Couleurs::contraste($demo->brand_primary, '#FFFFFF'))->toBeGreaterThanOrEqual(Couleurs::CONTRASTE_MINIMAL);
    expect(substr(Storage::disk('public')->get($demo->logo_path), 0, 8))->toBe("\x89PNG\r\n\x1a\n");

    // Relancee : rien ne change, rien n'est supprime.
    $this->artisan('prepla:demo-marque')->expectsOutputToContain('rien à changer')->assertSuccessful();
    expect($demo->fresh()->only(['logo_path', 'brand_primary', 'brand_accent']))->toBe($demo->only(['logo_path', 'brand_primary', 'brand_accent']))
        ->and($demo->members()->count())->toBe(1)
        ->and($autre->fresh()->aUneMarque())->toBeFalse()
        ->and(app(Marque::class)->pourUtilisateur($eleve)['logo_url'])->toContain('demo-logo.png');
});

test('la commande de demonstration refuse de tourner en production', function () {
    $demo = persoCentre('Institut Linguae', ['slug' => 'institut-linguae-demo']);
    app()->detectEnvironment(fn () => 'production');

    try {
        $this->artisan('prepla:demo-marque')->assertFailed();
    } finally {
        app()->detectEnvironment(fn () => 'testing');
    }

    expect($demo->fresh()->aUneMarque())->toBeFalse();
});
