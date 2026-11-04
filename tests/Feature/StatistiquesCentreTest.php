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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Les statistiques du tableau de bord du centre, de la fiche d'un eleve et de
 * la page Contenu (StatistiquesCentre) : chiffres justes, heure du centre,
 * perimetre de l'enseignant, autre centre refuse, requetes en nombre fixe.
 *
 * Maintenant : mercredi 4 novembre 2026, 12 h UTC (13 h a Douala, UTC+1).
 */
function statTentative(User $u, Exercise $e, string $quandUtc, float $precision, int $temps = 60): void
{
    $t = new UserExerciseAttempt([
        'user_id' => $u->id, 'exercise_id' => $e->id, 'answers' => [], 'score' => $precision,
        'accuracy_percent' => $precision, 'time_spent' => $temps, 'xp_earned' => 5, 'feedback' => [],
    ]);
    $t->created_at = Carbon::parse($quandUtc, 'UTC');
    $t->save();
}

function statCentre(): array
{
    test()->travelTo(Carbon::parse('2026-11-04 12:00:00', 'UTC'));

    $langue = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => '🇩🇪']);
    $examen = Exam::create(['language_id' => $langue->id, 'slug' => 'goethe-b1', 'name' => 'Goethe B1']);
    $type = function (string $competence) use ($examen) {
        $section = ExamSection::create(['exam_id' => $examen->id, 'slug' => $competence, 'name' => $competence, 'skill_type' => $competence]);

        return ExerciseType::create(['section_id' => $section->id, 'slug' => "qcm-{$competence}", 'name' => 'QCM', 'skill_type' => $competence, 'component_key' => 'mcq']);
    };
    $tLecture = $type('reading');
    $tGrammaire = $type('grammar');
    $exercice = fn (ExerciseType $t, array $plus = []) => Exercise::create($plus + ['exam_id' => $examen->id, 'exercise_type_id' => $t->id, 'difficulty' => 'B1', 'content' => [], 'questions' => []]);
    $lecture = $exercice($tLecture);
    $grammaire = $exercice($tGrammaire);

    $centre = LanguageCenter::create(['name' => 'Institut Test', 'slug' => 'institut-test', 'owner_email' => 'd@exemple.test', 'is_active' => true]);
    $autre = LanguageCenter::create(['name' => 'Concurrent', 'slug' => 'concurrent', 'owner_email' => 'x@exemple.test', 'is_active' => true]);
    $perso = $exercice($tLecture, ['center_id' => $centre->id, 'content' => ['title' => 'Lecture maison']]);
    $persoIa = $exercice($tGrammaire, ['center_id' => $centre->id, 'is_ai_generated' => true, 'content' => ['title' => 'Grammaire IA']]);

    $membre = function (LanguageCenter $centre, string $role, string $nom = 'X') {
        $u = User::factory()->create(['name' => $nom]);
        UserProfile::factory()->for($u)->create(['onboarding_completed_at' => now()]);
        $centre->members()->attach($u->id, ['role' => $role, 'joined_at' => now()]);

        return $u;
    };
    $admin = $membre($centre, 'center_admin');
    $prof1 = $membre($centre, 'teacher');
    $prof2 = $membre($centre, 'teacher');
    $adminAutre = $membre($autre, 'center_admin');
    $etranger = $membre($autre, 'student', 'Hors centre');

    $classe = function (string $nom, User $prof) use ($centre, $examen) {
        $k = $centre->classrooms()->create(['name' => $nom, 'exam_id' => $examen->id, 'invite_code' => Classroom::generateInviteCode()]);
        $k->members()->attach($prof->id, ['role_in_class' => 'teacher']);

        return $k;
    };
    $classeA = $classe('A', $prof1);
    $classeB = $classe('B', $prof2);
    $classeC = $classe('C', $prof2);

    $awa = $membre($centre, 'student', 'Awa');
    $paul = $membre($centre, 'student', 'Paul');
    $zoe = $membre($centre, 'student', 'Zoé');
    $lea = $membre($centre, 'student', 'Léa');
    $classeA->members()->attach([$awa->id => ['role_in_class' => 'student'], $paul->id => ['role_in_class' => 'student']]);
    $classeB->members()->attach($zoe->id, ['role_in_class' => 'student']);
    $classeC->members()->attach($lea->id, ['role_in_class' => 'student']);

    // Semaine courante (depuis le 28 oct. 12 h UTC).
    statTentative($awa, $lecture, '2026-11-03 10:00:00', 80, 300);   // mardi 11 h a Douala
    statTentative($awa, $grammaire, '2026-11-02 23:30:00', 60, 200); // lundi en UTC, MARDI 0 h 30 a Douala
    statTentative($paul, $perso, '2026-11-04 09:00:00', 90, 600);    // mercredi 10 h
    statTentative($zoe, $lecture, '2026-11-04 11:00:00', 100, 3000); // mercredi 12 h
    // Semaine d'avant.
    statTentative($awa, $lecture, '2026-10-25 12:00:00', 50, 60);    // dimanche 13 h
    statTentative($lea, $grammaire, '2026-10-26 10:00:00', 70, 60);  // lundi 11 h
    statTentative($lea, $grammaire, '2026-10-26 10:00:00', 70, 60);
    // Un eleve d'un autre centre sur l'exercice du centre : jamais compte.
    statTentative($etranger, $perso, '2026-11-04 08:00:00', 0, 60);

    $devoir = function (string $titre, string $publie, string $echeance, Exercise $e) use ($classeA, $prof1) {
        $d = $classeA->assignments()->create(['created_by' => $prof1->id, 'title' => $titre, 'published_at' => Carbon::parse($publie, 'UTC'), 'due_at' => Carbon::parse($echeance, 'UTC')]);
        AssignmentItem::create(['assignment_id' => $d->id, 'itemable_type' => Exercise::class, 'itemable_id' => $e->id, 'sort_order' => 0]);

        return $d;
    };
    // Echu hier : Awa l'a fait a temps, Paul non.
    $devoir('Lecture', '2026-10-30 12:00:00', '2026-11-03 12:00:00', $lecture);
    // Echu la semaine d'avant : Awa l'a fait APRES l'echeance.
    $devoir('Ancien', '2026-10-20 12:00:00', '2026-10-26 12:00:00', $grammaire);
    // Pas encore echu : Awa l'a deja fait.
    $devoir('Futur', '2026-11-01 12:00:00', '2026-11-10 12:00:00', $grammaire);

    return compact('centre', 'autre', 'admin', 'prof1', 'prof2', 'adminAutre', 'etranger', 'classeA', 'classeB', 'classeC',
        'awa', 'paul', 'zoe', 'lea', 'lecture', 'grammaire', 'perso', 'persoIa', 'membre');
}

function statProps($reponse): array
{
    return json_decode(json_encode($reponse->assertOk()->viewData('page')['props']), true);
}

test('le tableau de bord du responsable : semaines, competences, devoirs, classes, rythme', function () {
    $c = statCentre();
    $s = statProps($this->actingAs($c['admin'])->get(route('center.dashboard')))['statistiques'];

    expect($s['semaines'])->toHaveCount(8);
    [$avant, $courante] = array_slice($s['semaines'], -2);
    expect($courante)->toMatchArray(['exercices' => 4, 'actifs' => 3, 'minutes' => 68, 'precision' => 82.5])
        ->and($avant)->toMatchArray(['exercices' => 3, 'actifs' => 2, 'minutes' => 3, 'precision' => 63.3]);

    expect($s['competences'])->toEqual([
        ['cle' => 'reading', 'libelle' => 'Compréhension écrite', 'exercices' => 4, 'precision' => 80.0],
        ['cle' => 'grammar', 'libelle' => 'Grammaire', 'exercices' => 3, 'precision' => 66.7],
    ]);

    expect($s['devoirs'])->toBe(['rendus' => 1, 'attendus' => 2, 'rendus_avant' => 0, 'attendus_avant' => 2]);

    expect(array_column($s['classes_actives'], 'name'))->toBe(['A', 'B'])
        ->and($s['classes_actives'][0])->toMatchArray(['exercices' => 3, 'avant' => 1, 'actifs' => 2])
        ->and(array_column($s['classes_decrochent'], 'name'))->toBe(['C']);

    // A l'heure de Douala : la seance de 23 h 30 UTC lundi compte pour mardi.
    expect(array_column($s['rythme']['jours'], 'exercices'))->toBe([2, 2, 2, 0, 0, 0, 1])
        ->and($s['rythme']['jours'][0]['jour'])->toBe('Lundi')
        ->and($s['rythme']['heures'][0])->toBe(1)
        ->and($s['rythme']['heures'][23])->toBe(0)
        ->and($s['rythme']['pic'])->toBe(['debut' => 10, 'fin' => 12, 'part' => 57]);
});

test('un enseignant ne voit que ses classes sur le tableau de bord', function () {
    $c = statCentre();
    $s = statProps($this->actingAs($c['prof1'])->get(route('center.dashboard')))['statistiques'];

    [$avant, $courante] = array_slice($s['semaines'], -2);
    expect($courante)->toMatchArray(['exercices' => 3, 'actifs' => 2])
        ->and($avant)->toMatchArray(['exercices' => 1, 'actifs' => 1])
        ->and(array_column($s['classes_actives'], 'name'))->toBe(['A'])
        ->and($s['classes_decrochent'])->toBe([]);

    $s2 = statProps($this->actingAs($c['prof2'])->get(route('center.dashboard')))['statistiques'];
    expect(array_slice($s2['semaines'], -1)[0]['exercices'])->toBe(1)
        ->and($s2['devoirs']['attendus'] + $s2['devoirs']['attendus_avant'])->toBe(0);
});

test('la fiche eleve : courbes, classe, competences, calendrier a l heure du centre, devoirs', function () {
    $c = statCentre();
    $s = statProps($this->actingAs($c['admin'])->get(route('center.students.show', $c['awa'])))['statistiques'];

    [$avant, $courante] = array_slice($s['semaines'], -2);
    expect($courante)->toMatchArray(['exercices' => 2, 'precision' => 70.0, 'minutes' => 8])
        ->and($avant)->toMatchArray(['exercices' => 1, 'precision' => 50.0])
        ->and(array_slice($s['semaines_classe'], -1)[0])->toMatchArray(['exercices' => 3, 'actifs' => 2, 'precision' => 76.7]);

    expect(collect($s['competences'])->pluck('precision', 'cle')->all())->toEqual(['reading' => 65.0, 'grammar' => 60.0]);

    $jours = collect($s['calendrier'])->keyBy('date');
    expect($s['calendrier'])->toHaveCount(56)
        ->and($s['calendrier'][0]['date'])->toBe('2026-09-14')
        ->and($jours['2026-11-03']['exercices'])->toBe(2)
        ->and($jours['2026-11-02']['exercices'])->toBe(0)
        ->and($jours['2026-10-25']['exercices'])->toBe(1)
        ->and($jours['2026-11-04']['futur'])->toBeFalse()
        ->and($jours['2026-11-05']['futur'])->toBeTrue();

    expect($s['devoirs'])->toMatchArray(['a_temps' => 2, 'rendus_en_retard' => 1, 'en_retard' => 0, 'a_faire' => 0]);

    $paul = statProps($this->get(route('center.students.show', $c['paul'])))['statistiques']['devoirs'];
    expect($paul)->toMatchArray(['a_temps' => 0, 'rendus_en_retard' => 0, 'en_retard' => 2, 'a_faire' => 1]);
});

test('la fiche eleve est refusee hors du perimetre', function () {
    $c = statCentre();

    $this->actingAs($c['prof1'])->get(route('center.students.show', $c['awa']))->assertOk();
    $this->actingAs($c['prof2'])->get(route('center.students.show', $c['awa']))->assertForbidden();
    $this->actingAs($c['adminAutre'])->get(route('center.students.show', $c['awa']))->assertForbidden();
});

test('la page Contenu : joue et reussite par exercice, eleves du perimetre seulement', function () {
    $c = statCentre();

    $lignes = collect(statProps($this->actingAs($c['admin'])->get(route('center.exercises.index')))['exercises'])->keyBy('id');
    expect($lignes->keys()->sort()->values()->all())->toBe([$c['perso']->id, $c['persoIa']->id])
        // L'eleve d'un autre centre (0 %) n'est pas compte.
        ->and($lignes[$c['perso']->id])->toMatchArray(['joue' => 1, 'eleves' => 1, 'reussite' => 90.0])
        ->and($lignes[$c['persoIa']->id])->toMatchArray(['joue' => 0, 'eleves' => 0, 'reussite' => null]);

    $prof1 = collect(statProps($this->actingAs($c['prof1'])->get(route('center.exercises.index')))['exercises'])->keyBy('id');
    $prof2 = collect(statProps($this->actingAs($c['prof2'])->get(route('center.exercises.index')))['exercises'])->keyBy('id');
    expect($prof1[$c['perso']->id]['joue'])->toBe(1)
        ->and($prof2[$c['perso']->id]['joue'])->toBe(0);

    $autre = statProps($this->actingAs($c['adminAutre'])->get(route('center.exercises.index')))['exercises'];
    expect($autre)->toBe([]);
});

test('le nombre de requetes ne depend pas du nombre d eleves ni d exercices', function () {
    $c = statCentre();
    $this->actingAs($c['admin']);

    $compter = function (string $url) {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get($url)->assertOk();
        $n = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $n;
    };
    $urls = [
        route('center.dashboard'),
        route('center.students.show', $c['awa']),
        route('center.exercises.index'),
    ];

    foreach ($urls as $url) {
        $compter($url);
    }
    $avant = array_map($compter, $urls);

    foreach (range(1, 10) as $i) {
        $u = ($c['membre'])($c['centre'], 'student', "Élève {$i}");
        $c['classeA']->members()->attach($u->id, ['role_in_class' => 'student']);
        statTentative($u, $i % 2 ? $c['perso'] : $c['grammaire'], '2026-11-0'.(($i % 4) + 1).' 08:00:00', 40 + $i);
        Exercise::create(['exam_id' => $c['perso']->exam_id, 'exercise_type_id' => $c['perso']->exercise_type_id, 'center_id' => $c['centre']->id, 'difficulty' => 'B1', 'content' => [], 'questions' => []]);
    }

    expect(array_map($compter, $urls))->toBe($avant);
});
