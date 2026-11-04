<?php

use App\Models\CenterMembershipPeriod;
use App\Models\CenterMonthlyActivity;
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
use App\Services\Center\ElevesActifs;
use App\Services\Center\PeriodesAppartenance;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Lot E : eleves actifs par mois et paliers.
 *
 * Un eleve actif a fait au moins une tentative dans le mois civil (Douala)
 * PENDANT qu'il etait eleve du centre. Le releve d'un mois termine est arrete
 * une fois, et ne bouge plus sans --forcer.
 */
beforeEach(function () {
    $this->withoutVite();
    // Le 4 novembre 2026 : octobre est termine, novembre en cours.
    $this->travelTo(Carbon::parse('2026-11-04 10:00:00', 'UTC'));
});

function actifsDecor(): array
{
    $langue = Language::create(['slug' => 'german', 'name' => 'Allemand', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::create(['language_id' => $langue->id, 'slug' => 'goethe-a2', 'name' => 'Goethe A2']);
    $section = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'grammar', 'name' => 'Grammar', 'skill_type' => 'grammar']);
    $type = ExerciseType::create([
        'section_id' => $section->id, 'slug' => 'mcq', 'name' => 'MCQ', 'skill_type' => 'grammar', 'component_key' => 'mcq',
    ]);
    $exercice = Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'difficulty' => 'A2', 'content' => [], 'questions' => [],
    ]);

    return ['exam' => $exam, 'exercice' => $exercice];
}

function actifsCentre(string $nom): LanguageCenter
{
    return LanguageCenter::create([
        'name' => $nom, 'slug' => Str::slug($nom), 'owner_email' => 'x@exemple.test', 'is_active' => true,
    ]);
}

/** Un membre ajoute PAR LE MODELE, comme le font les controleurs. */
function actifsMembre(LanguageCenter $c, string $role = 'student', string $depuis = '2026-09-01 08:00:00'): User
{
    $u = User::factory()->create();
    UserProfile::factory()->for($u)->create(['onboarding_completed_at' => now()]);
    CenterUser::create(['center_id' => $c->id, 'user_id' => $u->id, 'role' => $role, 'joined_at' => Carbon::parse($depuis, 'UTC')]);

    return $u;
}

function actifsTentative(User $u, Exercise $e, string $quandUtc): void
{
    $a = UserExerciseAttempt::create([
        'user_id' => $u->id, 'exercise_id' => $e->id, 'answers' => [], 'score' => 100,
        'accuracy_percent' => 100, 'time_spent' => 60, 'xp_earned' => 10, 'feedback' => [],
    ]);
    $a->forceFill(['created_at' => Carbon::parse($quandUtc, 'UTC'), 'updated_at' => Carbon::parse($quandUtc, 'UTC')])->saveQuietly();
}

// ─── Paliers ──────────────────────────────────────────────────────────────────

test('les bornes des paliers tombent du bon cote', function (int $n, string $palier) {
    expect(ElevesActifs::palier($n))->toBe($palier);
})->with([
    [0, 'A1'], [1, 'A1'], [50, 'A1'], [51, 'A2'], [150, 'A2'], [151, 'A3'], [300, 'A3'], [301, 'reseau'], [5000, 'reseau'],
]);

test('51 eleves actifs font passer le centre en A2 dans le releve', function () {
    $d = actifsDecor();
    $c = actifsCentre('Centre Akwa');
    $maintenant = now()->format('Y-m-d H:i:s');

    // 51 eleves inseres d'un coup : la requete compte, le palier suit.
    for ($i = 0; $i < 51; $i++) {
        $u = User::factory()->create();
        DB::table('center_membership_periods')->insert([
            'center_id' => $c->id, 'user_id' => $u->id, 'role' => 'student',
            'started_at' => '2026-09-01 00:00:00', 'created_at' => $maintenant, 'updated_at' => $maintenant,
        ]);
        DB::table('user_exercise_attempts')->insert([
            'user_id' => $u->id, 'exercise_id' => $d['exercice']->id, 'answers' => '[]', 'xp_earned' => 0,
            'created_at' => '2026-10-10 12:00:00', 'updated_at' => '2026-10-10 12:00:00',
        ]);
    }

    app(ElevesActifs::class)->releverMois('2026-10');

    $r = CenterMonthlyActivity::where('center_id', $c->id)->where('periode', '2026-10')->first();
    expect($r->active_students)->toBe(51)->and($r->palier)->toBe('A2');
});

// ─── Qui compte ───────────────────────────────────────────────────────────────

test('un eleve sans tentative n est pas compte, un eleve avec une tentative l est', function () {
    $d = actifsDecor();
    $c = actifsCentre('Centre Akwa');
    actifsMembre($c);
    $travailleur = actifsMembre($c);
    actifsTentative($travailleur, $d['exercice'], '2026-10-12 09:00:00');

    expect(app(ElevesActifs::class)->compter($c->id, '2026-10'))->toBe(1);
});

test('plusieurs tentatives du meme eleve ne le comptent qu une fois', function () {
    $d = actifsDecor();
    $c = actifsCentre('Centre Akwa');
    $u = actifsMembre($c);
    foreach (['2026-10-02 09:00:00', '2026-10-15 09:00:00', '2026-10-30 09:00:00'] as $quand) {
        actifsTentative($u, $d['exercice'], $quand);
    }

    expect(app(ElevesActifs::class)->compter($c->id, '2026-10'))->toBe(1);
});

test('le personnel du centre n est jamais compte, meme s il s entraine', function () {
    $d = actifsDecor();
    $c = actifsCentre('Centre Akwa');
    actifsTentative(actifsMembre($c, 'center_admin'), $d['exercice'], '2026-10-12 09:00:00');
    actifsTentative(actifsMembre($c, 'teacher'), $d['exercice'], '2026-10-12 09:00:00');

    expect(app(ElevesActifs::class)->compter($c->id, '2026-10'))->toBe(0);
});

test('un eleve retire le 15 reste compte s il a travaille avant, pas s il travaille apres', function () {
    $d = actifsDecor();
    $c = actifsCentre('Centre Akwa');
    $avant = actifsMembre($c);
    $apres = actifsMembre($c);
    actifsTentative($avant, $d['exercice'], '2026-10-10 09:00:00');
    actifsTentative($apres, $d['exercice'], '2026-10-20 09:00:00');

    // Retrait le 15 octobre, par le modele comme StudentController.
    $this->travelTo(Carbon::parse('2026-10-15 12:00:00', 'UTC'));
    CenterUser::where('center_id', $c->id)->whereIn('user_id', [$avant->id, $apres->id])->get()->each->delete();
    $this->travelTo(Carbon::parse('2026-11-04 10:00:00', 'UTC'));

    expect(CenterUser::where('center_id', $c->id)->count())->toBe(0)
        ->and(CenterMembershipPeriod::where('center_id', $c->id)->whereNotNull('ended_at')->count())->toBe(2)
        ->and(app(ElevesActifs::class)->compter($c->id, '2026-10'))->toBe(1);
});

test('une tentative faite avant l arrivee dans le centre ne compte pas', function () {
    $d = actifsDecor();
    $c = actifsCentre('Centre Akwa');
    $u = actifsMembre($c, 'student', '2026-10-20 08:00:00');
    actifsTentative($u, $d['exercice'], '2026-10-05 09:00:00');

    expect(app(ElevesActifs::class)->compter($c->id, '2026-10'))->toBe(0);
});

test('un eleve devenu enseignant n est compte que pour ce qu il a fait comme eleve', function () {
    $d = actifsDecor();
    $c = actifsCentre('Centre Akwa');
    $u = actifsMembre($c);

    $this->travelTo(Carbon::parse('2026-10-15 12:00:00', 'UTC'));
    CenterUser::where('center_id', $c->id)->where('user_id', $u->id)->first()->update(['role' => 'teacher']);
    $this->travelTo(Carbon::parse('2026-11-04 10:00:00', 'UTC'));

    actifsTentative($u, $d['exercice'], '2026-10-20 09:00:00');
    expect(app(ElevesActifs::class)->compter($c->id, '2026-10'))->toBe(0);

    actifsTentative($u, $d['exercice'], '2026-10-10 09:00:00');
    expect(app(ElevesActifs::class)->compter($c->id, '2026-10'))->toBe(1);
});

test('une tentative a 23 h 30 UTC le dernier jour du mois compte deja pour le mois suivant (Douala)', function () {
    $d = actifsDecor();
    $c = actifsCentre('Centre Akwa');
    $u = actifsMembre($c);
    // 31 octobre 23 h 30 UTC = 1er novembre 0 h 30 a Douala.
    actifsTentative($u, $d['exercice'], '2026-10-31 23:30:00');

    $service = app(ElevesActifs::class);
    expect($service->compter($c->id, '2026-10'))->toBe(0)
        ->and($service->compter($c->id, '2026-11'))->toBe(1);

    // Et a l'autre bout : 30 septembre 23 h 00 UTC est deja le 1er octobre a Douala.
    $v = actifsMembre($c);
    actifsTentative($v, $d['exercice'], '2026-09-30 23:00:00');
    expect($service->compter($c->id, '2026-10'))->toBe(1)
        ->and($service->compter($c->id, '2026-09'))->toBe(0);
});

test('un eleve n est compte que dans le centre ou il etait eleve', function () {
    $d = actifsDecor();
    $a = actifsCentre('Centre Akwa');
    $b = actifsCentre('Centre Bonamoussadi');
    actifsTentative(actifsMembre($a), $d['exercice'], '2026-10-12 09:00:00');

    $par = app(ElevesActifs::class)->compterParCentre('2026-10');
    expect($par[$a->id] ?? 0)->toBe(1)->and($par[$b->id] ?? 0)->toBe(0);
});

// ─── Periodes d'appartenance ──────────────────────────────────────────────────

test('l observer ouvre une periode a l entree et la ferme a la sortie', function () {
    $c = actifsCentre('Centre Akwa');
    $u = actifsMembre($c, 'student', '2026-10-01 08:00:00');

    $p = CenterMembershipPeriod::where('user_id', $u->id)->sole();
    expect($p->role)->toBe('student')
        ->and($p->started_at->format('Y-m-d H:i:s'))->toBe('2026-10-01 08:00:00')
        ->and($p->ended_at)->toBeNull();

    CenterUser::where('user_id', $u->id)->first()->delete();
    expect($p->fresh()->ended_at?->format('Y-m-d H:i:s'))->toBe('2026-11-04 10:00:00');
});

test('le retrait d un eleve par le centre ferme sa periode', function () {
    $d = actifsDecor();
    $c = actifsCentre('Centre Akwa');
    $responsable = actifsMembre($c, 'center_admin');
    $classe = $c->classrooms()->create([
        'name' => 'Groupe A', 'level' => 'A2', 'exam_id' => $d['exam']->id, 'invite_code' => Classroom::generateInviteCode(),
    ]);
    $eleve = actifsMembre($c);
    $classe->members()->attach($eleve->id, ['role_in_class' => 'student']);

    $this->actingAs($responsable)
        ->delete(route('center.classes.students.remove', [$classe->id, $eleve->id]))
        ->assertRedirect();

    expect(CenterUser::where('user_id', $eleve->id)->exists())->toBeFalse()
        ->and(CenterMembershipPeriod::where('user_id', $eleve->id)->whereNull('ended_at')->exists())->toBeFalse()
        ->and(CenterMembershipPeriod::where('user_id', $eleve->id)->whereNotNull('ended_at')->exists())->toBeTrue();
});

test('la reconciliation rattrape un attach et une suppression par requete', function () {
    $c = actifsCentre('Centre Akwa');
    $attache = User::factory()->create();
    $c->members()->attach($attache->id, ['role' => 'student', 'joined_at' => Carbon::parse('2026-10-03 08:00:00', 'UTC')]);
    $parti = actifsMembre($c);
    CenterUser::where('user_id', $parti->id)->delete(); // requete : pas d'observer

    expect(CenterMembershipPeriod::where('user_id', $attache->id)->exists())->toBeFalse();

    $r = app(PeriodesAppartenance::class)->reconcilier();
    expect($r)->toBe(['ouvertes' => 1, 'fermees' => 1])
        ->and(CenterMembershipPeriod::where('user_id', $attache->id)->sole()->started_at->format('Y-m-d'))->toBe('2026-10-03')
        ->and(CenterMembershipPeriod::where('user_id', $parti->id)->whereNull('ended_at')->exists())->toBeFalse();

    // Une seconde passe ne change plus rien.
    expect(app(PeriodesAppartenance::class)->reconcilier())->toBe(['ouvertes' => 0, 'fermees' => 0]);
});

test('la migration remplit les periodes depuis center_user', function () {
    $c = actifsCentre('Centre Akwa');
    $u = User::factory()->create();
    $c->members()->attach($u->id, ['role' => 'student', 'joined_at' => Carbon::parse('2026-06-02 08:00:00', 'UTC')]);
    $sans = User::factory()->create();
    $c->members()->attach($sans->id, ['role' => 'teacher', 'joined_at' => null]);

    $migration = require database_path('migrations/2026_11_04_500001_create_center_membership_periods_table.php');
    $migration->down();
    $migration->up();

    expect(CenterMembershipPeriod::where('user_id', $u->id)->sole()->started_at->format('Y-m-d H:i:s'))->toBe('2026-06-02 08:00:00')
        ->and(CenterMembershipPeriod::where('user_id', $sans->id)->sole()->role)->toBe('teacher');
});

// ─── Commande centres:actifs ─────────────────────────────────────────────────

test('la commande releve le mois ecoule par defaut, avec palier', function () {
    $d = actifsDecor();
    $c = actifsCentre('Centre Akwa');
    $vide = actifsCentre('Centre Bonamoussadi');
    actifsTentative(actifsMembre($c), $d['exercice'], '2026-10-12 09:00:00');

    $this->artisan('centres:actifs')->assertSuccessful();

    expect(CenterMonthlyActivity::where('periode', '2026-10')->count())->toBe(2);
    $r = CenterMonthlyActivity::where('center_id', $c->id)->first();
    expect($r->active_students)->toBe(1)->and($r->palier)->toBe('A1');
    expect(CenterMonthlyActivity::where('center_id', $vide->id)->first()->active_students)->toBe(0);
});

test('un mois deja releve n est recalcule qu avec --forcer', function () {
    $d = actifsDecor();
    $c = actifsCentre('Centre Akwa');
    actifsTentative(actifsMembre($c), $d['exercice'], '2026-10-12 09:00:00');

    $this->artisan('centres:actifs', ['--mois' => '2026-10'])->assertSuccessful();
    $premier = CenterMonthlyActivity::where('center_id', $c->id)->sole();

    // Une tentative ajoutee apres coup (correction de donnees, rattrapage) :
    actifsTentative(actifsMembre($c), $d['exercice'], '2026-10-13 09:00:00');

    $this->travelTo(Carbon::parse('2026-11-05 10:00:00', 'UTC'));
    $this->artisan('centres:actifs', ['--mois' => '2026-10'])->assertSuccessful();
    $second = CenterMonthlyActivity::where('center_id', $c->id)->sole();
    expect($second->active_students)->toBe(1)
        ->and($second->computed_at->equalTo($premier->computed_at))->toBeTrue();

    $this->artisan('centres:actifs', ['--mois' => '2026-10', '--forcer' => true])->assertSuccessful();
    expect(CenterMonthlyActivity::where('center_id', $c->id)->sole()->active_students)->toBe(2)
        ->and(CenterMonthlyActivity::count())->toBe(1);
});

test('la commande refuse le mois en cours, un mois a venir et un format invalide', function () {
    actifsCentre('Centre Akwa');

    $this->artisan('centres:actifs', ['--mois' => '2026-11'])->assertFailed();
    $this->artisan('centres:actifs', ['--mois' => '2027-01'])->assertFailed();
    $this->artisan('centres:actifs', ['--mois' => '2026-13'])->assertFailed();
    $this->artisan('centres:actifs', ['--mois' => 'octobre'])->assertFailed();

    expect(CenterMonthlyActivity::count())->toBe(0);
});

test('la commande est planifiee le 1er du mois a Douala', function () {
    $evenement = collect(app(Schedule::class)->events())
        ->first(fn ($e) => str_contains($e->command ?? '', 'centres:actifs'));

    expect($evenement)->not->toBeNull()
        ->and($evenement->expression)->toBe('0 2 1 * *')
        ->and($evenement->timezone)->toBe('Africa/Douala');
});

// ─── Super administrateur : admin/releves ─────────────────────────────────────

function actifsSuperAdmin(): User
{
    return User::factory()->create(['role' => 'super_admin']);
}

test('le super administrateur voit les eleves actifs par mois et par centre', function () {
    $d = actifsDecor();
    $c = actifsCentre('Centre Akwa');
    actifsTentative(actifsMembre($c), $d['exercice'], '2026-10-12 09:00:00');
    actifsTentative(actifsMembre($c), $d['exercice'], '2026-11-02 09:00:00');
    app(ElevesActifs::class)->releverMois('2026-10');

    $admin = actifsSuperAdmin();

    $this->actingAs($admin)->get(route('admin.releves.index', ['mois' => '2026-10']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/releves')
            ->where('actifs.mois', '2026-10')
            ->where('actifs.mois_termine', true)
            ->where('actifs.non_calcules', 0)
            ->where('actifs.lignes.0.actifs', 1)
            ->where('actifs.lignes.0.palier', 'A1')
            ->where('actifs.lignes.0.en_cours', false));

    // Le mois en cours : calcule a la volee, marque en cours.
    $this->actingAs($admin)->get(route('admin.releves.index', ['mois' => '2026-11']))
        ->assertInertia(fn ($page) => $page
            ->where('actifs.lignes.0.actifs', 1)
            ->where('actifs.lignes.0.en_cours', true));

    // Par centre : le mois en cours puis l'historique arrete.
    $this->actingAs($admin)->get(route('admin.releves.index', ['centre' => $c->id]))
        ->assertInertia(fn ($page) => $page
            ->where('actifs.centre', $c->id)
            ->has('actifs.lignes', 2)
            ->where('actifs.lignes.0.periode', '2026-11')
            ->where('actifs.lignes.1.periode', '2026-10'));
});

test('un mois termine non releve s affiche non calcule, et le bouton l arrete une fois', function () {
    $d = actifsDecor();
    $c = actifsCentre('Centre Akwa');
    actifsTentative(actifsMembre($c), $d['exercice'], '2026-10-12 09:00:00');
    $admin = actifsSuperAdmin();

    $this->actingAs($admin)->get(route('admin.releves.index', ['mois' => '2026-10']))
        ->assertInertia(fn ($page) => $page
            ->where('actifs.non_calcules', 1)
            ->where('actifs.lignes.0.actifs', null));

    $this->actingAs($admin)->post(route('admin.releves.actifs.calculer'), ['mois' => '2026-10'])
        ->assertRedirect()->assertSessionHas('success');
    $this->actingAs($admin)->post(route('admin.releves.actifs.calculer'), ['mois' => '2026-10'])
        ->assertRedirect();
    $this->actingAs($admin)->post(route('admin.releves.actifs.calculer'), ['mois' => '2026-11'])
        ->assertSessionHas('error');

    expect(CenterMonthlyActivity::count())->toBe(1)
        ->and(CenterMonthlyActivity::first()->active_students)->toBe(1);
});

test('l export CSV des actifs neutralise les formules', function () {
    $d = actifsDecor();
    $c = LanguageCenter::create([
        'name' => '=HYPERLINK("http://x")', 'slug' => 'piege', 'owner_email' => 'x@exemple.test', 'is_active' => true,
    ]);
    actifsTentative(actifsMembre($c), $d['exercice'], '2026-10-12 09:00:00');
    app(ElevesActifs::class)->releverMois('2026-10');

    $reponse = $this->actingAs(actifsSuperAdmin())->get(route('admin.releves.actifs.csv', ['mois' => '2026-10']));
    $reponse->assertOk();
    $csv = $reponse->streamedContent();

    expect($reponse->headers->get('content-type'))->toContain('text/csv')
        ->and($csv)->toContain("'=HYPERLINK")
        ->and($csv)->toContain('2026-10;1;A1');
});

test('les releves sont fermes au personnel des centres et aux apprenants', function () {
    $c = actifsCentre('Centre Akwa');
    $responsable = actifsMembre($c, 'center_admin');
    $eleve = actifsMembre($c);

    foreach ([$responsable, $eleve] as $u) {
        $this->actingAs($u)->get(route('admin.releves.index'))->assertForbidden();
        $this->actingAs($u)->get(route('admin.releves.actifs.csv'))->assertForbidden();
        $this->actingAs($u)->post(route('admin.releves.actifs.calculer'), ['mois' => '2026-10'])->assertForbidden();
    }
    expect(CenterMonthlyActivity::count())->toBe(0);

    auth()->logout();
    $this->get(route('admin.releves.index'))->assertRedirect(route('login'));
});

// ─── Responsable du centre : tableau de bord ──────────────────────────────────

test('le responsable voit les eleves actifs du mois de SON centre, pas l enseignant', function () {
    $d = actifsDecor();
    $a = actifsCentre('Centre Akwa');
    $b = actifsCentre('Centre Bonamoussadi');
    $responsableA = actifsMembre($a, 'center_admin');
    $enseignantA = actifsMembre($a, 'teacher');
    actifsTentative(actifsMembre($a), $d['exercice'], '2026-11-02 09:00:00');
    // Activite de B : ne doit jamais apparaitre chez A.
    actifsTentative(actifsMembre($b), $d['exercice'], '2026-11-02 09:00:00');
    actifsTentative(actifsMembre($b), $d['exercice'], '2026-11-03 09:00:00');

    $this->actingAs($responsableA)->get(route('center.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('actifs_mois.eleves', 1)
            ->where('actifs_mois.palier', 'A1'));

    $this->actingAs($enseignantA)->get(route('center.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('actifs_mois', null));
});
