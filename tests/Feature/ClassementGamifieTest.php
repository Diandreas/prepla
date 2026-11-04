<?php

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
use Illuminate\Support\Facades\DB;

/**
 * La page Eleves du centre porte un classement, comme /leaderboard : XP de la
 * semaine, du mois et depuis le debut, serie en cours, regularite, progression.
 * Calcule pour tous les eleves en un nombre fixe de requetes.
 */
function centreGamifie(): array
{
    $langue = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => '🇩🇪']);
    $exam = Exam::create(['language_id' => $langue->id, 'slug' => 'goethe-b1', 'name' => 'Goethe B1']);
    $section = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'grammar', 'name' => 'Grammar', 'skill_type' => 'grammar']);
    $type = ExerciseType::create([
        'section_id' => $section->id, 'slug' => 'mcq', 'name' => 'MCQ', 'skill_type' => 'grammar', 'component_key' => 'mcq',
    ]);
    $exercice = Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'difficulty' => 'B1', 'content' => [], 'questions' => [],
    ]);

    $centre = LanguageCenter::create([
        'name' => 'Institut gamifie', 'slug' => 'institut-gamifie', 'owner_email' => 'd@exemple.test', 'is_active' => true,
    ]);
    $membre = function (string $role, array $profil = []) use ($centre) {
        $u = User::factory()->create();
        UserProfile::factory()->for($u)->create(['onboarding_completed_at' => now()] + $profil);
        $centre->members()->attach($u->id, ['role' => $role, 'joined_at' => now()]);

        return $u;
    };

    $admin = $membre('center_admin');
    $prof = $membre('teacher');
    $classe = $centre->classrooms()->create(['name' => 'B1', 'exam_id' => $exam->id, 'invite_code' => Classroom::generateInviteCode()]);
    $classe->members()->attach($prof->id, ['role_in_class' => 'teacher']);
    $autreClasse = $centre->classrooms()->create(['name' => 'A2', 'exam_id' => $exam->id, 'invite_code' => Classroom::generateInviteCode()]);

    $eleve = function (Classroom $c, array $profil = []) use ($membre) {
        $u = $membre('student', $profil);
        $c->members()->attach($u->id, ['role_in_class' => 'student']);

        return $u;
    };

    $tentative = function (User $u, int $xp, int $precision, $quand) use ($exercice) {
        $t = UserExerciseAttempt::factory()->create([
            'user_id' => $u->id, 'exercise_id' => $exercice->id, 'xp_earned' => $xp, 'accuracy_percent' => $precision,
        ]);
        $t->forceFill(['created_at' => $quand])->save();
    };

    return compact('admin', 'prof', 'classe', 'autreClasse', 'eleve', 'tentative');
}

function elevesDeLaPage($page): \Illuminate\Support\Collection
{
    return collect($page->toArray()['props']['students'])->keyBy('id');
}

test('chaque eleve porte ses XP par periode, sa serie, sa regularite et sa progression', function () {
    $this->travelTo(now()->startOfMonth()->addDays(20)->setTime(12, 0)); // le 21 : la semaine entiere tient dans le mois
    $c = centreGamifie();

    $awa = ($c['eleve'])($c['classe'], ['xp_total' => 900, 'streak_current' => 9, 'streak_last_date' => now()->toDateString()]);
    $paul = ($c['eleve'])($c['classe'], ['xp_total' => 0, 'streak_current' => 12, 'streak_last_date' => now()->subDays(5)->toDateString()]);

    // Awa : cette semaine 30 XP (2 exercices), plus tot dans le mois 20 XP, le mois dernier 50 XP.
    ($c['tentative'])($awa, 10, 90, now()->startOfWeek()->addHour());
    ($c['tentative'])($awa, 20, 80, now());
    ($c['tentative'])($awa, 20, 60, now()->startOfMonth()->addHour());
    ($c['tentative'])($awa, 50, 40, now()->subMonths(2));
    // Paul : rien depuis deux mois.
    ($c['tentative'])($paul, 40, 50, now()->subMonths(2));

    $this->actingAs($c['admin'])->get(route('center.students.index'))
        ->assertOk()
        ->assertInertia(function ($page) use ($awa, $paul) {
            $e = elevesDeLaPage($page);
            $a = $e[$awa->id];

            expect($a['periodes']['semaine'])->toBe(['xp' => 30, 'exercices' => 2, 'precision' => 85])
                ->and($a['periodes']['mois']['xp'])->toBe(50) // 10 + 20 + 20
                ->and($a['periodes']['total']['xp'])->toBe(900) // le profil, qui compte aussi les lecons
                ->and($a['periodes']['total']['exercices'])->toBe(4)
                ->and($a['serie'])->toBe(9)
                // Precision des 14 derniers jours (90, 80) moins celle d'avant (60, 40).
                ->and($a['progression'])->toBe(35)
                ->and($a['jours_actifs'])->toBeGreaterThanOrEqual(2);

            $p = $e[$paul->id];
            expect($p['periodes']['semaine'])->toBe(['xp' => 0, 'exercices' => 0, 'precision' => null])
                // Jamais moins que la somme des exercices, meme si le profil dit 0.
                ->and($p['periodes']['total']['xp'])->toBe(40)
                // Une serie interrompue depuis cinq jours ne compte plus.
                ->and($p['serie'])->toBe(0)
                ->and($p['progression'])->toBeNull()
                ->and($p['jours_actifs'])->toBe(0);

            return true;
        });
});

test('le classement d un enseignant ne montre que les eleves de ses classes', function () {
    $c = centreGamifie();
    $mien = ($c['eleve'])($c['classe']);
    $autre = ($c['eleve'])($c['autreClasse']);
    ($c['tentative'])($autre, 500, 100, now());

    $this->actingAs($c['prof'])->get(route('center.students.index'))
        ->assertOk()
        ->assertInertia(function ($page) use ($mien) {
            expect(elevesDeLaPage($page)->keys()->all())->toBe([$mien->id]);

            return true;
        });
});

test('le classement ne fait pas une requete par eleve', function () {
    $c = centreGamifie();
    $compter = function () use ($c) {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($c['admin'])->get(route('center.students.index'))->assertOk();
        $n = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $n;
    };

    ($c['tentative'])(($c['eleve'])($c['classe'], ['streak_current' => 3, 'streak_last_date' => now()->toDateString()]), 10, 80, now());
    $compter(); // la premiere requete met en cache des reglages
    $avecUn = $compter();

    foreach (range(1, 5) as $i) {
        ($c['tentative'])(($c['eleve'])($i % 2 ? $c['classe'] : $c['autreClasse']), 10 * $i, 70, now()->subDays($i));
    }

    expect($compter())->toBe($avecUn);
});
