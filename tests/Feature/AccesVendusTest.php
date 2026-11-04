<?php

use App\Http\Middleware\EnsureExerciseQuota;
use App\Models\CenterAccessGrant;
use App\Models\CenterStatement;
use App\Models\CenterUser;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Models\UserExerciseAttempt;
use App\Models\UserProfile;
use App\Services\Center\ReleveRevente;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Acces vendus par un centre en formule « revente » (lot C).
 *
 * Ses eleves suivent le quota gratuit, sauf ceux a qui le centre a ouvert un
 * acces : le centre encaisse, ouvre l'acces, et PrePla preleve 30 % des montants
 * ouverts dans le mois. Les centres paliers et libre-service ne changent pas.
 */
beforeEach(function () {
    $this->withoutVite();

    Route::middleware(['web', 'auth', EnsureExerciseQuota::class])
        ->get('/__test/quota-acces', fn () => 'ok');

    $language = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'goethe-a1', 'name' => 'Goethe A1']);
    $section = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'reading', 'name' => 'Reading', 'skill_type' => 'reading']);
    $type = ExerciseType::create([
        'section_id' => $section->id, 'slug' => 'mcq', 'name' => 'MCQ', 'skill_type' => 'reading', 'component_key' => 'mcq',
    ]);
    $this->exercise = Exercise::create([
        'exercise_type_id' => $type->id, 'exam_id' => $exam->id,
        'content' => [], 'questions' => [], 'difficulty' => 'A1',
    ]);
});

function avCentre(string $nom, string $formule = 'revente', array $extra = []): LanguageCenter
{
    return LanguageCenter::create(array_merge([
        'name' => $nom, 'slug' => Str::slug($nom), 'owner_email' => Str::slug($nom).'@exemple.test',
        'is_active' => true, 'formule' => $formule,
    ], $extra));
}

function avMembre(LanguageCenter $centre, string $role): User
{
    $u = User::factory()->create();
    UserProfile::factory()->for($u)->create(['onboarding_completed_at' => now(), 'trial_ends_at' => now()->subDay()]);
    CenterUser::create(['center_id' => $centre->id, 'user_id' => $u->id, 'role' => $role, 'joined_at' => now()]);

    return $u;
}

function avQuotaConsomme(User $u): void
{
    UserExerciseAttempt::factory()->count(EnsureExerciseQuota::DAILY_FREE_LIMIT)->create([
        'user_id' => $u->id, 'exercise_id' => test()->exercise->id,
    ]);
}

function avAcces(LanguageCenter $c, User $eleve, array $extra = []): CenterAccessGrant
{
    return CenterAccessGrant::create(array_merge([
        'center_id' => $c->id, 'user_id' => $eleve->id,
        'starts_at' => now()->subDay(), 'ends_at' => now()->addDays(29),
        'amount' => 5000, 'currency' => 'XAF', 'payment_method' => 'mobile_money',
    ], $extra));
}

function avOuvrir(User $par, User $eleve, array $extra = [])
{
    return test()->actingAs($par)->post(route('center.acces.store'), array_merge([
        'user_id' => $eleve->id, 'duree_jours' => 30, 'montant' => 5000, 'moyen' => 'mobile_money', 'reference' => 'MM-123',
    ], $extra));
}

// ───────────────────────── Regle d'acces et quota ─────────────────────────

test('un eleve d un centre en revente sans acces suit le quota gratuit', function () {
    $c = avCentre('Centre Revente');
    $eleve = avMembre($c, 'student');
    avQuotaConsomme($eleve);

    expect(EnsureExerciseQuota::exempte($eleve))->toBeFalse()
        ->and($eleve->hasPremiumAccess())->toBeFalse();

    $this->actingAs($eleve)->get('/__test/quota-acces')->assertRedirect(route('subscription.index'));
});

test('un acces ouvert par le responsable leve le quota', function () {
    $c = avCentre('Centre Revente', 'revente', ['prix_acces' => 5000, 'duree_acces_jours' => 30]);
    $responsable = avMembre($c, 'center_admin');
    $eleve = avMembre($c, 'student');
    avQuotaConsomme($eleve);

    avOuvrir($responsable, $eleve)->assertRedirect()->assertSessionHas('success');

    $acces = CenterAccessGrant::sole();
    expect($acces->center_id)->toBe($c->id)
        ->and($acces->granted_by)->toBe($responsable->id)
        ->and($acces->amount)->toBe(5000)
        ->and($acces->currency)->toBe('XAF')
        ->and($acces->reference)->toBe('MM-123')
        ->and((int) round($acces->starts_at->diffInDays($acces->ends_at)))->toBe(30)
        ->and($eleve->fresh()->hasPremiumAccess())->toBeTrue();

    $this->actingAs($eleve->fresh())->get('/__test/quota-acces')->assertOk();
});

test('un acces expire ou coupe ne leve plus le quota', function () {
    $c = avCentre('Centre Revente');
    $eleve = avMembre($c, 'student');
    avQuotaConsomme($eleve);

    $expire = avAcces($c, $eleve, ['starts_at' => now()->subDays(31), 'ends_at' => now()->subDay()]);
    expect($eleve->hasPremiumAccess())->toBeFalse();
    $this->actingAs($eleve)->get('/__test/quota-acces')->assertRedirect(route('subscription.index'));

    $coupe = avAcces($c, $eleve, ['revoked_at' => now()->subMinute()]);
    expect($eleve->hasPremiumAccess())->toBeFalse();

    // A venir : pas encore commence.
    avAcces($c, $eleve, ['starts_at' => now()->addDay(), 'ends_at' => now()->addDays(31)]);
    expect($eleve->hasPremiumAccess())->toBeFalse();
    $this->actingAs($eleve)->get('/__test/quota-acces')->assertRedirect(route('subscription.index'));
});

test('un acces ne vaut pas dans un centre desactive ou sorti de la revente', function () {
    $c = avCentre('Centre Revente');
    $eleve = avMembre($c, 'student');
    avAcces($c, $eleve);
    expect($eleve->hasPremiumAccess())->toBeTrue();

    $c->update(['is_active' => false]);
    expect($eleve->fresh()->hasPremiumAccess())->toBeFalse();

    // Repasse en paliers : ses eleves redeviennent illimites par l'exemption.
    $c->update(['is_active' => true, 'formule' => 'paliers']);
    expect($eleve->fresh()->hasPremiumAccess())->toBeFalse()
        ->and(EnsureExerciseQuota::exempte($eleve->fresh()))->toBeTrue();
});

test('l interface annonce le quota a l eleve sans acces, et le cache a l eleve qui en a un', function () {
    $c = avCentre('Centre Revente');
    $eleve = avMembre($c, 'student');

    $this->actingAs($eleve)->get(route('profile.edit'))
        ->assertInertia(fn ($page) => $page->where('isPremium', false)->where('quotaExempt', false));

    avAcces($c, $eleve);

    $this->actingAs($eleve->fresh())->get(route('profile.edit'))
        ->assertInertia(fn ($page) => $page->where('isPremium', true)->where('quotaExempt', false));
});

test('les eleves des centres paliers et libre-service gardent leur exemption', function () {
    $paliers = avCentre('Centre Paliers', 'paliers');
    $eleveP = avMembre($paliers, 'student');
    avQuotaConsomme($eleveP);

    expect(EnsureExerciseQuota::exempte($eleveP))->toBeTrue();
    $this->actingAs($eleveP)->get('/__test/quota-acces')->assertOk();

    $libre = avCentre('Espace Libre', 'libre_service', ['settings' => [LanguageCenter::LIBRE_SERVICE => true]]);
    $eleveL = avMembre($libre, 'student');
    avQuotaConsomme($eleveL);

    expect(EnsureExerciseQuota::exempte($eleveL))->toBeTrue();
    $this->actingAs($eleveL)->get('/__test/quota-acces')->assertOk();
});

test('un centre sans formule renseignee reste en paliers', function () {
    $c = LanguageCenter::create(['name' => 'Ancien', 'slug' => 'ancien', 'owner_email' => 'a@exemple.test', 'is_active' => true]);

    expect($c->fresh()->formule())->toBe('paliers')
        ->and($c->fresh()->estEnRevente())->toBeFalse();
});

test('le marqueur libre-service l emporte sur la colonne', function () {
    $c = avCentre('Espace', 'revente', ['settings' => [LanguageCenter::LIBRE_SERVICE => true]]);
    $eleve = avMembre($c, 'student');
    avAcces($c, $eleve);

    expect($c->formule())->toBe('libre_service')
        ->and($eleve->hasPremiumAccess())->toBeFalse()
        ->and(EnsureExerciseQuota::exempte($eleve))->toBeTrue();
});

// ───────────────────────── Droits et isolation ─────────────────────────

test('un enseignant ne peut ni voir ni ouvrir ni couper un acces', function () {
    $c = avCentre('Centre Revente');
    $prof = avMembre($c, 'teacher');
    $eleve = avMembre($c, 'student');
    $acces = avAcces($c, $eleve);

    $this->actingAs($prof)->get(route('center.acces.index'))->assertForbidden();
    avOuvrir($prof, $eleve)->assertForbidden();
    $this->actingAs($prof)->post(route('center.acces.couper', $acces->id))->assertForbidden();

    expect(CenterAccessGrant::count())->toBe(1)
        ->and($acces->fresh()->revoked_at)->toBeNull();
});

test('un eleve et un apprenant sans centre n entrent pas', function () {
    $c = avCentre('Centre Revente');
    $eleve = avMembre($c, 'student');
    $libre = User::factory()->create();

    $this->actingAs($eleve)->get(route('center.acces.index'))->assertForbidden();
    $this->actingAs($libre)->get(route('center.acces.index'))->assertForbidden();
});

test('le centre B ne coupe pas un acces du centre A et ne le voit pas', function () {
    $a = avCentre('Centre Akwa');
    $b = avCentre('Centre Bonamoussadi');
    $eleveA = avMembre($a, 'student');
    $responsableB = avMembre($b, 'center_admin');
    $acces = avAcces($a, $eleveA);

    $this->actingAs($responsableB)->post(route('center.acces.couper', $acces->id))->assertNotFound();
    expect($acces->fresh()->revoked_at)->toBeNull();

    // Ni ouvrir un acces a l'eleve de A.
    avOuvrir($responsableB, $eleveA)->assertSessionHasErrors('user_id');
    expect(CenterAccessGrant::where('center_id', $b->id)->count())->toBe(0);

    // Sa page ne liste ni l'eleve ni l'encaissement de A.
    $this->actingAs($responsableB)->get(route('center.acces.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('center/acces')
            ->where('centre.id', $b->id)
            ->has('eleves', 0)
            ->where('mois.total', 0)
            ->has('mois.lignes', 0));
});

test('un acces ne s ouvre pas au personnel', function () {
    $c = avCentre('Centre Revente');
    $responsable = avMembre($c, 'center_admin');
    $prof = avMembre($c, 'teacher');

    avOuvrir($responsable, $prof)->assertSessionHasErrors('user_id');
    avOuvrir($responsable, $responsable)->assertSessionHasErrors('user_id');

    expect(CenterAccessGrant::count())->toBe(0);
});

test('un centre qui n est pas en revente ne vend pas d acces', function () {
    $c = avCentre('Centre Paliers', 'paliers');
    $responsable = avMembre($c, 'center_admin');
    $eleve = avMembre($c, 'student');

    avOuvrir($responsable, $eleve)->assertSessionHas('error');
    expect(CenterAccessGrant::count())->toBe(0);

    // La page reste lisible et dit pourquoi.
    $this->actingAs($responsable)->get(route('center.acces.index'))
        ->assertInertia(fn ($page) => $page->where('centre.formule', 'paliers'));
});

test('couper un acces le date et dit qui l a coupe', function () {
    $c = avCentre('Centre Revente');
    $responsable = avMembre($c, 'center_admin');
    $eleve = avMembre($c, 'student');
    $acces = avAcces($c, $eleve);

    $this->actingAs($responsable)->post(route('center.acces.couper', $acces->id), ['note' => 'Rembourse'])
        ->assertSessionHas('success');

    $acces->refresh();
    expect($acces->revoked_at)->not->toBeNull()
        ->and($acces->revoked_by)->toBe($responsable->id)
        ->and($acces->note)->toBe('Rembourse')
        ->and($eleve->fresh()->hasPremiumAccess())->toBeFalse();

    // Deux fois : rien ne change.
    $this->actingAs($responsable)->post(route('center.acces.couper', $acces->id))->assertSessionHas('error');
});

test('un acces en cours est prolonge, pas superpose', function () {
    $c = avCentre('Centre Revente');
    $responsable = avMembre($c, 'center_admin');
    $eleve = avMembre($c, 'student');
    $premier = avAcces($c, $eleve, ['starts_at' => now()->subDays(10), 'ends_at' => now()->addDays(20)]);

    avOuvrir($responsable, $eleve, ['duree_jours' => 30])->assertSessionHas('success');

    $second = CenterAccessGrant::latest('id')->first();
    expect($second->id)->not->toBe($premier->id)
        ->and($second->starts_at->equalTo($premier->ends_at))->toBeTrue()
        ->and($second->ends_at->equalTo($premier->ends_at->copy()->addDays(30)))->toBeTrue();
});

test('un eleve retire de sa classe et du centre perd son acces', function () {
    $c = avCentre('Centre Revente');
    $responsable = avMembre($c, 'center_admin');
    $eleve = avMembre($c, 'student');
    $classe = Classroom::create(['center_id' => $c->id, 'name' => 'Groupe A', 'invite_code' => 'ABC123']);
    $classe->members()->attach($eleve->id, ['role_in_class' => 'student']);
    $acces = avAcces($c, $eleve);

    expect($eleve->hasPremiumAccess())->toBeTrue();

    $this->actingAs($responsable)
        ->delete(route('center.classes.students.remove', [$classe, $eleve]))
        ->assertSessionHas('success');

    expect(CenterUser::where('center_id', $c->id)->where('user_id', $eleve->id)->exists())->toBeFalse()
        ->and($acces->fresh()->revoked_at)->not->toBeNull()
        ->and($acces->fresh()->revoked_by)->toBe($responsable->id)
        ->and($eleve->fresh()->hasPremiumAccess())->toBeFalse();
});

test('un eleve qui n est plus membre perd l acces meme sans coupure enregistree', function () {
    $c = avCentre('Centre Revente');
    $eleve = avMembre($c, 'student');
    $acces = avAcces($c, $eleve);

    CenterUser::where('center_id', $c->id)->where('user_id', $eleve->id)->delete();

    expect($acces->fresh()->revoked_at)->toBeNull()
        ->and($eleve->fresh()->hasPremiumAccess())->toBeFalse();
});

// ───────────────────────── Super administrateur ─────────────────────────

test('seul le super administrateur choisit la formule et le prix', function () {
    $c = avCentre('Centre', 'paliers');
    $responsable = avMembre($c, 'center_admin');
    $admin = User::factory()->create(['role' => 'super_admin']);

    $this->actingAs($responsable)
        ->patch(route('admin.centers.formule', $c), ['formule' => 'revente', 'prix_acces' => 1])
        ->assertForbidden();
    expect($c->fresh()->formule())->toBe('paliers');

    $this->actingAs($admin)
        ->patch(route('admin.centers.formule', $c), ['formule' => 'revente', 'prix_acces' => 7500, 'duree_acces_jours' => 60])
        ->assertSessionHas('success');

    $c->refresh();
    expect($c->formule())->toBe('revente')
        ->and($c->prix_acces)->toBe(7500)
        ->and($c->dureeAcces())->toBe(60);

    $this->actingAs($admin)
        ->patch(route('admin.centers.formule', $c), ['formule' => 'inconnue'])
        ->assertSessionHasErrors('formule');

    $this->actingAs($admin)->get(route('admin.centers.show', $c))
        ->assertInertia(fn ($page) => $page->where('center.formule', 'revente')->where('center.prix_acces', 7500));
});

test('la formule libre-service et son marqueur restent d accord', function () {
    $c = avCentre('Centre', 'paliers', ['settings' => ['fuseau' => 'Africa/Douala']]);
    $admin = User::factory()->create(['role' => 'super_admin']);

    $this->actingAs($admin)->patch(route('admin.centers.formule', $c), ['formule' => 'libre_service']);
    expect($c->fresh()->estLibreService())->toBeTrue()
        ->and($c->fresh()->settings['fuseau'])->toBe('Africa/Douala');

    $this->actingAs($admin)->patch(route('admin.centers.formule', $c), ['formule' => 'paliers']);
    expect($c->fresh()->estLibreService())->toBeFalse()
        ->and($c->fresh()->formule())->toBe('paliers')
        ->and($c->fresh()->settings['fuseau'])->toBe('Africa/Douala');
});

test('un espace ouvert sur /enseigner est en formule libre-service', function () {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create(['onboarding_completed_at' => now()]);

    $this->actingAs($user)->post(route('teach.open.store'), [
        'name' => 'Cours de Zidane', 'classroom_name' => 'Groupe du mardi',
    ])->assertRedirect(route('center.dashboard'));

    $c = LanguageCenter::where('owner_email', $user->email)->sole();
    expect($c->getAttribute('formule'))->toBe('libre_service')
        ->and($c->formule())->toBe('libre_service');
});

test('la migration passe les espaces libre-service existants en formule libre-service et se defait', function () {
    $migration = require database_path('migrations/2026_11_04_000001_add_formule_to_language_centers.php');
    $migration->down();

    $id = fn (string $slug, ?array $settings) => \Illuminate\Support\Facades\DB::table('language_centers')->insertGetId([
        'name' => $slug, 'slug' => $slug, 'owner_email' => "{$slug}@exemple.test", 'is_active' => true,
        'settings' => $settings === null ? null : json_encode($settings), 'created_at' => now(), 'updated_at' => now(),
    ]);
    $client = $id('client', null);
    $libre = $id('libre', ['libre_service' => true]);

    $migration->up();

    expect(LanguageCenter::find($client)->getAttribute('formule'))->toBe('paliers')
        ->and(LanguageCenter::find($libre)->getAttribute('formule'))->toBe('libre_service')
        ->and(LanguageCenter::find($client)->prix_acces)->toBeNull();
});

// ───────────────────────── Releve mensuel ─────────────────────────

test('le releve prend 30 % des acces ouverts dans le mois, a l heure de Douala', function () {
    $c = avCentre('Centre Revente');
    $eleve = avMembre($c, 'student');

    // 23 h 30 UTC le 30 septembre : deja le 1er octobre a Douala (UTC+1).
    Carbon::setTestNow('2026-09-30 23:30:00');
    avAcces($c, $eleve, ['amount' => 5000]);
    Carbon::setTestNow('2026-10-15 10:00:00');
    avAcces($c, $eleve, ['amount' => 2500, 'revoked_at' => now()]); // coupe : compte quand meme
    avAcces($c, $eleve, ['amount' => 1]);
    // 23 h 30 UTC le 31 octobre : novembre a Douala, hors releve.
    Carbon::setTestNow('2026-10-31 23:30:00');
    avAcces($c, $eleve, ['amount' => 99999]);
    // Septembre a Douala.
    Carbon::setTestNow('2026-09-30 22:30:00');
    avAcces($c, $eleve, ['amount' => 88888]);
    Carbon::setTestNow('2026-11-01 03:00:00');

    $this->artisan('centres:releves', ['--mois' => '2026-10'])->assertSuccessful();

    $r = CenterStatement::sole();
    expect($r->center_id)->toBe($c->id)
        ->and($r->periode)->toBe('2026-10')
        ->and($r->nature)->toBe('revente')
        ->and($r->montant_brut)->toBe(7501)
        ->and($r->part_prepla)->toBe(2250) // 2250,3 arrondi
        ->and($r->statut)->toBe('brouillon')
        ->and($r->lignes)->toHaveCount(3)
        ->and(collect($r->lignes)->pluck('montant')->all())->toBe([5000, 2500, 1]);

    expect(ReleveRevente::partPrepla(5))->toBe(2) // 1,5 -> 2
        ->and(ReleveRevente::partPrepla(10000))->toBe(3000);

    Carbon::setTestNow();
});

test('le releve est idempotent et ne touche pas un releve envoye', function () {
    $c = avCentre('Centre Revente');
    $eleve = avMembre($c, 'student');

    Carbon::setTestNow('2026-10-10 12:00:00');
    avAcces($c, $eleve, ['amount' => 10000]);
    Carbon::setTestNow('2026-11-01 03:00:00');

    $this->artisan('centres:releves', ['--mois' => '2026-10'])->assertSuccessful();
    $this->artisan('centres:releves', ['--mois' => '2026-10'])->assertSuccessful();
    expect(CenterStatement::count())->toBe(1)
        ->and(CenterStatement::sole()->part_prepla)->toBe(3000);

    // Un acces saisi en retard dans le mois : le brouillon se met a jour.
    Carbon::setTestNow('2026-10-20 12:00:00');
    avAcces($c, $eleve, ['amount' => 5000]);
    Carbon::setTestNow('2026-11-02 03:00:00');
    $this->artisan('centres:releves', ['--mois' => '2026-10'])->assertSuccessful();
    expect(CenterStatement::count())->toBe(1)
        ->and(CenterStatement::sole()->montant_brut)->toBe(15000);

    // Envoye : fige.
    CenterStatement::sole()->update(['statut' => 'envoye']);
    Carbon::setTestNow('2026-10-25 12:00:00');
    avAcces($c, $eleve, ['amount' => 7000]);
    Carbon::setTestNow('2026-11-03 03:00:00');
    $this->artisan('centres:releves', ['--mois' => '2026-10'])->assertSuccessful();
    expect(CenterStatement::count())->toBe(1)
        ->and(CenterStatement::sole()->montant_brut)->toBe(15000)
        ->and(CenterStatement::sole()->statut)->toBe('envoye');

    Carbon::setTestNow();
});

test('un acces vendu reste au releve et au journal quand l eleve supprime son compte', function () {
    $c = avCentre('Centre Revente');
    $responsable = avMembre($c, 'center_admin');
    $eleve = avMembre($c, 'student');
    $eleve->update(['name' => 'Awa Ndiaye']);

    // Paye le 5 octobre, compte supprime le 20.
    Carbon::setTestNow('2026-10-05 10:00:00');
    avOuvrir($responsable, $eleve, ['montant' => 10000])->assertSessionHasNoErrors();
    $acces = CenterAccessGrant::sole();
    expect($acces->eleve_nom)->toBe('Awa Ndiaye');

    Carbon::setTestNow('2026-10-20 10:00:00');
    $this->actingAs($eleve)->delete('/settings/profile', ['password' => 'password'])
        ->assertSessionHasNoErrors();
    expect($eleve->fresh())->toBeNull();

    // La ligne reste : plus de compte, mais le nom, le montant et qui l'a ouverte.
    $acces = CenterAccessGrant::sole();
    expect($acces->user_id)->toBeNull()
        ->and($acces->eleve_nom)->toBe('Awa Ndiaye')
        ->and($acces->amount)->toBe(10000)
        ->and($acces->granted_by)->toBe($responsable->id);

    Carbon::setTestNow('2026-11-01 03:00:00');
    $this->artisan('centres:releves', ['--mois' => '2026-10'])->assertSuccessful();

    $r = CenterStatement::sole();
    expect($r->montant_brut)->toBe(10000)
        ->and($r->part_prepla)->toBe(3000)
        ->and($r->lignes)->toHaveCount(1)
        ->and($r->lignes[0]['eleve'])->toBe('Awa Ndiaye')
        ->and($r->lignes[0]['eleve_id'])->toBeNull()
        ->and($r->lignes[0]['ouvert_par'])->toBe($responsable->name);

    // La page du responsable s'affiche toujours, encaissement compris.
    $this->actingAs($responsable)->get(route('center.acces.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('mois.periode', '2026-11'));

    Carbon::setTestNow();
});

test('le releve ignore les centres paliers et separe les centres', function () {
    $a = avCentre('Centre Akwa');
    $b = avCentre('Centre Bonamoussadi');
    $paliers = avCentre('Centre Paliers', 'paliers');
    avMembre($paliers, 'student');

    Carbon::setTestNow('2026-10-10 12:00:00');
    avAcces($a, avMembre($a, 'student'), ['amount' => 1000]);
    avAcces($b, avMembre($b, 'student'), ['amount' => 3000]);
    Carbon::setTestNow('2026-11-01 03:00:00');

    $this->artisan('centres:releves')->assertSuccessful(); // par defaut : le mois ecoule

    expect(CenterStatement::where('center_id', $paliers->id)->exists())->toBeFalse()
        ->and(CenterStatement::where('center_id', $a->id)->sole()->part_prepla)->toBe(300)
        ->and(CenterStatement::where('center_id', $b->id)->sole()->part_prepla)->toBe(900)
        ->and(CenterStatement::where('center_id', $a->id)->sole()->periode)->toBe('2026-10');

    Carbon::setTestNow();
});

test('la commande refuse un mois invalide ou futur', function () {
    $this->artisan('centres:releves', ['--mois' => '2026-13'])->assertFailed();
    $this->artisan('centres:releves', ['--mois' => '2099-01'])->assertFailed();
    expect(CenterStatement::count())->toBe(0);
});

test('la commande est planifiee le 1er du mois', function () {
    $evenements = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
        ->filter(fn ($e) => str_contains($e->command ?? '', 'centres:releves'));

    expect($evenements)->toHaveCount(1)
        ->and($evenements->first()->expression)->toBe('0 3 1 * *')
        ->and($evenements->first()->timezone)->toBe('Africa/Douala');
});

test('l onglet Revente des releves est reserve au super administrateur', function () {
    $c = avCentre('Centre Revente');
    $responsable = avMembre($c, 'center_admin');
    $admin = User::factory()->create(['role' => 'super_admin']);

    Carbon::setTestNow('2026-10-10 12:00:00');
    avAcces($c, avMembre($c, 'student'), ['amount' => 2000]);
    Carbon::setTestNow('2026-11-04 12:00:00');
    $this->artisan('centres:releves', ['--mois' => '2026-10']);
    $r = CenterStatement::sole();

    $this->actingAs($responsable)->get(route('admin.releves.revente'))->assertForbidden();
    $this->actingAs($responsable)->patch(route('admin.releves.revente.statut', $r->id), ['statut' => 'paye'])->assertForbidden();
    expect($r->fresh()->statut)->toBe('brouillon');

    $this->actingAs($admin)->get(route('admin.releves.revente', ['mois' => '2026-10']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/releves-revente')
            ->where('mois', '2026-10')
            ->has('releves', 1)
            ->where('releves.0.part_prepla', 600));

    // Le mois en cours est calcule a la volee, sans rien ecrire.
    $this->actingAs($admin)->get(route('admin.releves.revente', ['mois' => '2026-11']))
        ->assertInertia(fn ($page) => $page->where('en_cours', true)->has('releves', 1)->where('releves.0.id', null));
    expect(CenterStatement::count())->toBe(1);

    $this->actingAs($admin)->patch(route('admin.releves.revente.statut', $r->id), ['statut' => 'paye'])->assertSessionHas('success');
    expect($r->fresh()->statut)->toBe('paye');

    Carbon::setTestNow();
});
