<?php

use App\Models\AssignmentItem;
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
 * Le tableau de bord d'une classe (GET center/classes/{id}/tableau) : chiffres
 * de la semaine, six semaines d'activite, competences, classement par periode.
 * Seuls les eleves de la classe, et un nombre de requetes fixe.
 */
function tableauTentative(User $u, Exercise $e, string $quand, float $precision, int $xp, int $temps = 60): void
{
    $t = new UserExerciseAttempt([
        'user_id' => $u->id, 'exercise_id' => $e->id, 'answers' => [], 'score' => $precision,
        'accuracy_percent' => $precision, 'time_spent' => $temps, 'xp_earned' => $xp, 'feedback' => [],
    ]);
    $t->created_at = now()->modify($quand);
    $t->save();
}

function tableauCentre(): array
{
    test()->travelTo(\Illuminate\Support\Carbon::parse('2026-11-04 12:00:00'));

    $langue = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => '🇩🇪']);
    $examen = Exam::create(['language_id' => $langue->id, 'slug' => 'goethe-b1', 'name' => 'Goethe B1']);
    $exercice = function (string $competence) use ($examen) {
        $section = ExamSection::create(['exam_id' => $examen->id, 'slug' => $competence, 'name' => $competence, 'skill_type' => $competence]);
        $type = ExerciseType::create(['section_id' => $section->id, 'slug' => "qcm-{$competence}", 'name' => 'QCM', 'skill_type' => $competence, 'component_key' => 'mcq']);

        return Exercise::create(['exam_id' => $examen->id, 'exercise_type_id' => $type->id, 'difficulty' => 'B1', 'content' => [], 'questions' => []]);
    };
    $lecture = $exercice('reading');
    $grammaire = $exercice('grammar');

    $centre = LanguageCenter::create(['name' => 'Institut Test', 'slug' => 'institut-test', 'owner_email' => 'd@exemple.test', 'is_active' => true]);
    $membre = function (LanguageCenter $centre, string $role, string $nom = 'X') {
        $u = User::factory()->create(['name' => $nom]);
        UserProfile::factory()->for($u)->create(['onboarding_completed_at' => now()]);
        $centre->members()->attach($u->id, ['role' => $role, 'joined_at' => now()]);

        return $u;
    };
    $admin = $membre($centre, 'center_admin');
    $prof1 = $membre($centre, 'teacher');
    $prof2 = $membre($centre, 'teacher');

    $classeA = $centre->classrooms()->create(['name' => 'B1 soir', 'exam_id' => $examen->id, 'invite_code' => Classroom::generateInviteCode()]);
    $classeA->members()->attach($prof1->id, ['role_in_class' => 'teacher']);
    $classeB = $centre->classrooms()->create(['name' => 'B1 matin', 'exam_id' => $examen->id, 'invite_code' => Classroom::generateInviteCode()]);
    $classeB->members()->attach($prof2->id, ['role_in_class' => 'teacher']);

    $awa = $membre($centre, 'student', 'Awa Diallo');
    $paul = $membre($centre, 'student', 'Paul Martin');
    $zoe = $membre($centre, 'student', 'Zoé Klein');
    $classeA->members()->attach([$awa->id => ['role_in_class' => 'student'], $paul->id => ['role_in_class' => 'student']]);
    $classeB->members()->attach($zoe->id, ['role_in_class' => 'student']);
    $awa->profile->update(['streak_current' => 4, 'streak_last_date' => now()->toDateString()]);

    // Awa : deux jours cette semaine, une tentative la semaine d'avant, une il y a 40 jours.
    tableauTentative($awa, $lecture, '-26 hours', 80, 10, 300);
    tableauTentative($awa, $grammaire, '-2 days', 60, 10, 200);
    tableauTentative($awa, $lecture, '-10 days', 50, 5);
    tableauTentative($awa, $grammaire, '-40 days', 100, 50);
    // Paul : une tentative tres recente, qui rapporte beaucoup.
    tableauTentative($paul, $lecture, '-3 hours', 90, 30, 600);
    // Zoe est d'une autre classe : jamais comptee ici.
    tableauTentative($zoe, $lecture, '-1 hour', 100, 999, 3000);

    // Un devoir echu hier a midi (Awa l'a fait a temps, Paul apres), un autre pas encore echu.
    $devoir = $classeA->assignments()->create(['created_by' => $prof1->id, 'title' => 'Lecture', 'published_at' => now()->subDays(5), 'due_at' => now()->subDay()]);
    AssignmentItem::create(['assignment_id' => $devoir->id, 'itemable_type' => Exercise::class, 'itemable_id' => $lecture->id, 'sort_order' => 0]);
    $futur = $classeA->assignments()->create(['created_by' => $prof1->id, 'title' => 'Plus tard', 'published_at' => now()->subDay(), 'due_at' => now()->addDays(3)]);
    AssignmentItem::create(['assignment_id' => $futur->id, 'itemable_type' => Exercise::class, 'itemable_id' => $grammaire->id, 'sort_order' => 0]);

    $autre = LanguageCenter::create(['name' => 'Concurrent', 'slug' => 'concurrent', 'owner_email' => 'x@exemple.test', 'is_active' => true]);
    $adminAutre = $membre($autre, 'center_admin');

    return compact('centre', 'admin', 'prof1', 'prof2', 'classeA', 'classeB', 'awa', 'paul', 'zoe', 'lecture', 'grammaire', 'adminAutre', 'membre');
}

test('les chiffres de la semaine, les six semaines et les competences sont justes', function () {
    $c = tableauCentre();
    $t = $this->actingAs($c['admin'])->getJson(route('center.classes.tableau', $c['classeA']))->assertOk()->json();

    expect($t['kpis'])->toMatchArray([
        'eleves_total' => 2, 'eleves_actifs' => 2,
        'exercices' => 3, 'exercices_avant' => 1,
        'temps_secondes' => 1100, 'temps_avant_secondes' => 60,
        'precision' => 76.7, 'precision_avant' => 50.0,
        'devoirs' => ['rendus' => 1, 'attendus' => 2, 'devoirs' => 1],
    ]);

    expect($t['semaines'])->toHaveCount(6)
        ->and(array_column($t['semaines'], 'exercices'))->toBe([1, 0, 0, 0, 1, 3])
        ->and(array_column($t['semaines'], 'actifs'))->toBe([1, 0, 0, 0, 1, 2]);

    expect($t['competences']['semaine'])->toEqual([
        ['cle' => 'reading', 'libelle' => 'Compréhension écrite', 'exercices' => 2, 'precision' => 85.0],
        ['cle' => 'grammar', 'libelle' => 'Grammaire', 'exercices' => 1, 'precision' => 60.0],
    ]);
    expect(collect($t['competences']['tout'])->pluck('precision', 'cle')->all())->toEqual(['reading' => 73.3, 'grammar' => 80]);
});

test('le classement change selon la periode et ne montre que les eleves de la classe', function () {
    $c = tableauCentre();
    $t = $this->actingAs($c['prof1'])->getJson(route('center.classes.tableau', $c['classeA']))->assertOk()->json();

    $semaine = $t['classement']['semaine'];
    expect(array_column($semaine['eleves'], 'name'))->toBe(['Paul Martin', 'Awa Diallo'])
        ->and(array_column($semaine['eleves'], 'xp'))->toBe([30, 20])
        ->and(array_column($semaine['eleves'], 'rang'))->toBe([1, 2]);

    $awa = collect($semaine['eleves'])->firstWhere('id', $c['awa']->id);
    expect($awa)->toMatchArray(['exercices' => 2, 'precision' => 70.0, 'progression' => 20.0, 'jours_actifs' => 2, 'serie' => 4]);
    expect($semaine['plus_regulier'])->toBe($c['awa']->id)
        ->and($semaine['meilleure_progression'])->toBe($c['awa']->id);

    expect(array_column($t['classement']['mois']['eleves'], 'xp'))->toBe([30, 25]);
    expect(array_column($t['classement']['tout']['eleves'], 'name'))->toBe(['Awa Diallo', 'Paul Martin'])
        ->and(array_column($t['classement']['tout']['eleves'], 'xp'))->toBe([75, 30])
        ->and($t['classement']['tout']['eleves'][0]['progression'])->toBeNull();

    // Zoe (autre classe) et l'enseignant ne sont nulle part.
    foreach (['semaine', 'mois', 'tout'] as $p) {
        expect(array_column($t['classement'][$p]['eleves'], 'id'))->not->toContain($c['zoe']->id)->not->toContain($c['prof1']->id);
    }
});

test('un enseignant d une autre classe et un autre centre sont refuses', function () {
    $c = tableauCentre();

    $this->actingAs($c['prof2'])->getJson(route('center.classes.tableau', $c['classeA']))->assertForbidden();
    $this->actingAs($c['prof2'])->getJson(route('center.classes.tableau', $c['classeB']))->assertOk();
    $this->actingAs($c['adminAutre'])->getJson(route('center.classes.tableau', $c['classeA']))->assertForbidden();
    $this->actingAs($c['awa'])->getJson(route('center.classes.tableau', $c['classeA']))->assertStatus(403);
});

test('le nombre de requetes ne depend pas du nombre d eleves', function () {
    $c = tableauCentre();
    $this->actingAs($c['admin']);

    $compter = function () use ($c) {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->getJson(route('center.classes.tableau', $c['classeA']))->assertOk();
        $n = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $n;
    };

    $compter(); // la premiere requete remplit des caches (centre, reglages)
    $avant = $compter();
    foreach (range(1, 12) as $i) {
        $u = ($c['membre'])($c['centre'], 'student', "Élève {$i}");
        $c['classeA']->members()->attach($u->id, ['role_in_class' => 'student']);
        tableauTentative($u, $i % 2 ? $c['lecture'] : $c['grammaire'], "-{$i} days", 50 + $i, $i);
    }

    expect($compter())->toBe($avant);
    expect(count($this->getJson(route('center.classes.tableau', $c['classeA']))->json('classement.tout.eleves')))->toBe(14);
});

test('la fiche de la classe garde ses chiffres, en un nombre fixe de requetes', function () {
    $c = tableauCentre();
    $this->actingAs($c['admin']);

    $compter = function () use ($c) {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $props = $this->get(route('center.classes.show', $c['classeA']))->assertOk()->viewData('page')['props'];
        $n = count(DB::getQueryLog());
        DB::disableQueryLog();

        return [$n, json_decode(json_encode($props['stats']), true)];
    };

    $compter();
    [$avant, $stats] = $compter();
    $awa = collect($stats['per_student'])->firstWhere('user_id', $c['awa']->id);
    expect($stats['student_count'])->toBe(2)
        ->and($awa['attempts'])->toBe(4)
        ->and($awa['avg_accuracy'])->toEqual(72.5)
        ->and($awa['temps_total_secondes'])->toBe(620);

    foreach (range(1, 8) as $i) {
        $u = ($c['membre'])($c['centre'], 'student', "Élève {$i}");
        $c['classeA']->members()->attach($u->id, ['role_in_class' => 'student']);
        tableauTentative($u, $c['lecture'], '-1 day', 70, 5);
    }

    [$apres] = $compter();
    expect($apres)->toBe($avant);
});
