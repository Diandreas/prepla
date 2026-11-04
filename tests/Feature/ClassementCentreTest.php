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
 * Le responsable d'un centre range ses classes et ses eleves par salle, par
 * niveau, par langue. La page des eleves comptait les exercices un eleve apres
 * l'autre ; elle donne maintenant aussi le score, la derniere activite et le
 * statut, en un nombre fixe de requetes.
 */
function centreAClasser(): array
{
    $allemand = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => '🇩🇪']);
    $francais = Language::create(['slug' => 'french', 'name' => 'Français', 'native_name' => 'Français', 'flag' => '🇫🇷']);
    $goethe = Exam::create(['language_id' => $allemand->id, 'slug' => 'goethe-a2', 'name' => 'Goethe A2']);
    $tcf = Exam::create(['language_id' => $francais->id, 'slug' => 'tcf', 'name' => 'TCF']);
    $section = ExamSection::create(['exam_id' => $goethe->id, 'slug' => 'grammar', 'name' => 'Grammar', 'skill_type' => 'grammar']);
    $type = ExerciseType::create([
        'section_id' => $section->id, 'slug' => 'mcq', 'name' => 'MCQ', 'skill_type' => 'grammar', 'component_key' => 'mcq',
    ]);
    $exercice = Exercise::create([
        'exam_id' => $goethe->id, 'exercise_type_id' => $type->id,
        'difficulty' => 'A2', 'content' => [], 'questions' => [],
    ]);

    // L'examen par defaut du centre est le TCF : une classe sans examen est en francais.
    $centre = LanguageCenter::create([
        'name' => 'Institut Test', 'slug' => 'institut-test', 'owner_email' => 'direction@exemple.test',
        'is_active' => true, 'default_exam_id' => $tcf->id,
    ]);

    $personnel = function (LanguageCenter $centre, string $role) {
        $u = User::factory()->create();
        UserProfile::factory()->for($u)->create(['onboarding_completed_at' => now()]);
        $centre->members()->attach($u->id, ['role' => $role, 'joined_at' => now()]);

        return $u;
    };
    $admin = $personnel($centre, 'center_admin');
    $prof1 = $personnel($centre, 'teacher');
    $prof2 = $personnel($centre, 'teacher');

    $classeAllemand = $centre->classrooms()->create([
        'name' => 'Allemand du soir', 'level' => 'a2', 'room' => 'Salle 2', 'schedule' => 'Lun et mer 17 h – 19 h',
        'exam_id' => $goethe->id, 'invite_code' => Classroom::generateInviteCode(),
    ]);
    $classeAllemand->members()->attach($prof1->id, ['role_in_class' => 'teacher']);

    $classeFrancais = $centre->classrooms()->create([
        'name' => 'Français B1', 'level' => 'B1', 'exam_id' => null, 'invite_code' => Classroom::generateInviteCode(),
    ]);
    $classeFrancais->members()->attach($prof2->id, ['role_in_class' => 'teacher']);

    $eleve = function (LanguageCenter $centre, string $nom, array $classes, array $profil = []) {
        $u = User::factory()->create(['name' => $nom]);
        UserProfile::factory()->for($u)->create(['onboarding_completed_at' => now()] + $profil);
        $centre->members()->attach($u->id, ['role' => 'student', 'joined_at' => now()]);
        foreach ($classes as $classe) {
            $classe->members()->attach($u->id, ['role_in_class' => 'student']);
        }

        return $u;
    };
    // Awa suit les deux classes ; Paul le francais ; Zoe n'a pas de classe.
    $awa = $eleve($centre, 'Awa', [$classeAllemand, $classeFrancais]);
    $paul = $eleve($centre, 'Paul', [$classeFrancais]);
    $zoe = $eleve($centre, 'Zoé', [], ['current_level' => 'C1', 'target_exam_id' => $goethe->id]);

    // Un autre centre, qui ne doit jamais apparaitre.
    $autre = LanguageCenter::create(['name' => 'Concurrent', 'slug' => 'concurrent', 'owner_email' => 'x@exemple.test', 'is_active' => true]);
    $adminAutre = $personnel($autre, 'center_admin');
    $classeAutre = $autre->classrooms()->create(['name' => 'Classe voisine', 'level' => 'A1', 'room' => 'Salle 9', 'invite_code' => Classroom::generateInviteCode()]);
    $classeAutre->members()->attach($adminAutre->id, ['role_in_class' => 'teacher']);
    $intrus = $eleve($autre, 'Intrus', [$classeAutre]);

    return compact('centre', 'admin', 'prof1', 'prof2', 'classeAllemand', 'classeFrancais', 'awa', 'paul', 'zoe', 'autre', 'adminAutre', 'intrus', 'exercice', 'goethe');
}

function tentativeClassement(User $eleve, Exercise $exercice, float $precision, $quand): void
{
    $tentative = UserExerciseAttempt::create([
        'user_id' => $eleve->id, 'exercise_id' => $exercice->id, 'answers' => [],
        'score' => $precision, 'accuracy_percent' => $precision, 'time_spent' => 60, 'xp_earned' => 5, 'feedback' => [],
    ]);
    $tentative->created_at = $quand;
    $tentative->save();
}

/** Les props Inertia d'une reponse, en tableaux simples. */
function propsDe($reponse): array
{
    return json_decode(json_encode($reponse->viewData('page')['props']), true);
}

test('la salle et l horaire d une classe sont enregistres, valides et affiches', function () {
    $c = centreAClasser();
    $this->actingAs($c['admin']);

    $this->post(route('center.classes.store'), [
        'name' => 'Anglais débutants', 'level' => 'A1', 'room' => 'Salle 4', 'schedule' => 'Sam 9 h – 12 h',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $classe = Classroom::where('name', 'Anglais débutants')->sole();
    expect($classe->room)->toBe('Salle 4')->and($classe->schedule)->toBe('Sam 9 h – 12 h');

    $this->patch(route('center.classes.update', $classe), ['name' => 'Anglais débutants', 'room' => 'Salle 5', 'schedule' => ''])
        ->assertSessionHasNoErrors();
    expect($classe->fresh()->room)->toBe('Salle 5')->and($classe->fresh()->schedule)->toBeNull();

    // Trop long : refuse, rien n'est ecrit.
    $this->patch(route('center.classes.update', $classe), ['name' => 'Anglais débutants', 'room' => str_repeat('x', 61), 'schedule' => str_repeat('y', 121)])
        ->assertSessionHasErrors(['room', 'schedule']);
    $this->post(route('center.classes.store'), ['name' => 'Trop longue', 'room' => str_repeat('x', 61)])
        ->assertSessionHasErrors('room');
    expect($classe->fresh()->room)->toBe('Salle 5')
        ->and(Classroom::where('name', 'Trop longue')->exists())->toBeFalse();

    $this->get(route('center.classes.show', $classe))->assertOk()
        ->assertInertia(fn ($page) => $page->where('classroom.room', 'Salle 5')->where('classroom.schedule', null));
});

test('la page classes donne langue, niveau, salle, horaire et enseignants', function () {
    $c = centreAClasser();

    $classes = collect(propsDe($this->actingAs($c['admin'])->get(route('center.classes.index'))->assertOk())['classrooms'])->keyBy('name');

    expect($classes->keys()->sort()->values()->all())->toBe(['Allemand du soir', 'Français B1'])
        ->and($classes['Allemand du soir']['langue'])->toBe(['cle' => 'german', 'nom' => 'Allemand', 'drapeau' => '🇩🇪'])
        // Le niveau saisi en minuscules est range avec les autres A2.
        ->and($classes['Allemand du soir']['level'])->toBe('A2')
        ->and($classes['Allemand du soir']['room'])->toBe('Salle 2')
        ->and($classes['Allemand du soir']['schedule'])->toBe('Lun et mer 17 h – 19 h')
        ->and($classes['Allemand du soir']['teachers'])->toBe([['id' => $c['prof1']->id, 'name' => $c['prof1']->name]])
        ->and($classes['Allemand du soir']['students_count'])->toBe(1)
        // Sans examen : la langue de l'examen par defaut du centre.
        ->and($classes['Français B1']['langue']['cle'])->toBe('french')
        ->and($classes['Français B1']['room'])->toBeNull()
        ->and($classes['Français B1']['students_count'])->toBe(2);
});

test('la page eleves donne classes, langue, niveau, score et statut, a la frontiere des 7 jours', function () {
    $c = centreAClasser();
    // A la seconde : la base ne garde pas les microsecondes.
    $this->freezeSecond();

    // Awa : exactement 7 jours, encore active. Paul : une seconde de plus, a relancer.
    tentativeClassement($c['awa'], $c['exercice'], 40, now()->subDays(20));
    tentativeClassement($c['awa'], $c['exercice'], 81, now()->subDays(7));
    tentativeClassement($c['paul'], $c['exercice'], 70, now()->subDays(7)->subSecond());

    $props = propsDe($this->actingAs($c['admin'])->get(route('center.students.index'))->assertOk());
    $eleves = collect($props['students'])->keyBy('name');

    expect($props['joursAvantRelance'])->toBe(7)
        ->and($eleves->keys()->all())->toBe(['Awa', 'Paul', 'Zoé'])
        ->and($eleves['Awa']['statut'])->toBe('actif')
        ->and($eleves['Awa']['exercices'])->toBe(2)
        ->and($eleves['Awa']['score_moyen'])->toBe(61)
        ->and(collect($eleves['Awa']['classes'])->pluck('name')->all())->toBe(['Allemand du soir', 'Français B1'])
        ->and(collect($eleves['Awa']['classes'])->pluck('room')->all())->toBe(['Salle 2', null])
        ->and(collect($eleves['Awa']['langues'])->pluck('cle')->all())->toBe(['german', 'french'])
        ->and($eleves['Awa']['niveaux'])->toBe(['A2', 'B1'])
        ->and($eleves['Awa']['derniere_activite'])->toBe(now()->subDays(7)->toIso8601String())
        ->and($eleves['Paul']['statut'])->toBe('a_relancer')
        ->and($eleves['Paul']['niveaux'])->toBe(['B1'])
        // Sans classe : le niveau declare et la langue de l'examen vise.
        ->and($eleves['Zoé']['statut'])->toBe('jamais')
        ->and($eleves['Zoé']['exercices'])->toBe(0)
        ->and($eleves['Zoé']['score_moyen'])->toBeNull()
        ->and($eleves['Zoé']['classes'])->toBe([])
        ->and($eleves['Zoé']['niveaux'])->toBe(['C1'])
        ->and($eleves['Zoé']['niveau_du_profil'])->toBeTrue()
        ->and($eleves['Zoé']['examen_vise'])->toBe('Goethe A2')
        ->and(collect($eleves['Zoé']['langues'])->pluck('nom')->all())->toBe(['Allemand']);
});

test('un enseignant ne voit ni les classes ni les eleves d une autre classe', function () {
    $c = centreAClasser();
    $this->actingAs($c['prof1']);

    $classes = collect(propsDe($this->get(route('center.classes.index'))->assertOk())['classrooms']);
    expect($classes->pluck('name')->all())->toBe(['Allemand du soir']);

    $eleves = collect(propsDe($this->get(route('center.students.index'))->assertOk())['students']);
    // Paul (francais seulement) et Zoe (sans classe) restent invisibles ; Awa
    // apparait, mais sans la classe de francais de son collegue.
    expect($eleves->pluck('name')->all())->toBe(['Awa'])
        ->and(collect($eleves[0]['classes'])->pluck('name')->all())->toBe(['Allemand du soir'])
        ->and(collect($eleves[0]['langues'])->pluck('cle')->all())->toBe(['german']);

    $repartition = propsDe($this->get(route('center.dashboard'))->assertOk())['repartition'];
    expect(collect($repartition['langues'])->pluck('cle')->all())->toBe(['german'])
        ->and(collect($repartition['niveaux'])->pluck('cle')->all())->toBe(['A2']);
});

test('un centre ne voit rien d un autre centre', function () {
    $c = centreAClasser();

    $this->actingAs($c['admin']);
    $classes = collect(propsDe($this->get(route('center.classes.index')))['classrooms'])->pluck('name');
    $eleves = collect(propsDe($this->get(route('center.students.index')))['students'])->pluck('name');
    expect($classes)->not->toContain('Classe voisine')->and($eleves)->not->toContain('Intrus');

    $this->actingAs($c['adminAutre']);
    $classes = collect(propsDe($this->get(route('center.classes.index')))['classrooms'])->pluck('name')->all();
    $eleves = collect(propsDe($this->get(route('center.students.index')))['students'])->pluck('name')->all();
    expect($classes)->toBe(['Classe voisine'])->and($eleves)->toBe(['Intrus']);
});

test('le tableau de bord repartit eleves et classes par langue et par niveau', function () {
    $c = centreAClasser();

    $props = propsDe($this->actingAs($c['admin'])->get(route('center.dashboard'))->assertOk());
    $repartition = $props['repartition'];

    // Les eleves des classes, comme les autres cartes : Awa et Paul. Zoe, sans
    // classe, n'est ni dans « Semaine » ni dans la repartition.
    expect($props['semaine']['eleves'])->toBe(2)
        ->and($repartition['langues'])->toBe([
            ['cle' => 'german', 'libelle' => 'Allemand', 'drapeau' => '🇩🇪', 'classes' => 1, 'eleves' => 1],
            ['cle' => 'french', 'libelle' => 'Français', 'drapeau' => '🇫🇷', 'classes' => 1, 'eleves' => 2],
        ])->and($repartition['niveaux'])->toBe([
            ['cle' => 'A2', 'libelle' => 'A2', 'classes' => 1, 'eleves' => 1],
            ['cle' => 'B1', 'libelle' => 'B1', 'classes' => 1, 'eleves' => 2],
        ]);
});

test('la repartition prend le niveau declare quand la classe n en a pas', function () {
    $c = centreAClasser();
    $sansNiveau = $c['centre']->classrooms()->create(['name' => 'Conversation', 'invite_code' => Classroom::generateInviteCode()]);
    $c['zoe']->classrooms()->attach($sansNiveau->id, ['role_in_class' => 'student']);

    $props = propsDe($this->actingAs($c['admin'])->get(route('center.dashboard'))->assertOk());
    $niveaux = collect($props['repartition']['niveaux'])->keyBy('cle');

    expect($props['semaine']['eleves'])->toBe(3)
        // Zoe : sa classe est sans niveau, elle a declare C1 (comme sur la page Eleves).
        ->and($niveaux['C1']['eleves'])->toBe(1)
        ->and($niveaux['-']['classes'])->toBe(1)
        ->and($niveaux['-']['eleves'])->toBe(0);
});

test('une classe archivee ne fait plus voir ses eleves a son enseignant', function () {
    $c = centreAClasser();
    // Paul suivait aussi une classe de prof1, archivee depuis.
    $archivee = $c['centre']->classrooms()->create([
        'name' => 'Ancienne classe', 'level' => 'C1', 'invite_code' => Classroom::generateInviteCode(), 'archived_at' => now(),
    ]);
    $archivee->members()->attach($c['prof1']->id, ['role_in_class' => 'teacher']);
    $archivee->members()->attach($c['paul']->id, ['role_in_class' => 'student']);

    $this->actingAs($c['prof1']);
    $eleves = collect(propsDe($this->get(route('center.students.index'))->assertOk())['students']);
    expect($eleves->pluck('name')->all())->toBe(['Awa']);

    $props = propsDe($this->get(route('center.dashboard'))->assertOk());
    expect($props['semaine']['eleves'])->toBe(1)
        ->and(collect($props['repartition']['niveaux'])->pluck('cle')->all())->toBe(['A2']);
});

test('le groupe Examen range une classe sans examen avec l examen du centre', function () {
    $c = centreAClasser();
    $c['classeAllemand']->members()->attach($c['paul']->id, ['role_in_class' => 'student']);

    $classes = collect(propsDe($this->actingAs($c['admin'])->get(route('center.classes.index'))->assertOk())['classrooms'])->keyBy('name');

    expect($classes['Français B1']['exam'])->toBe('TCF')
        // Les eleves de chaque classe, pour compter les personnes d'un groupe.
        ->and(collect($classes['Allemand du soir']['student_ids'])->sort()->values()->all())->toBe(collect([$c['awa']->id, $c['paul']->id])->sort()->values()->all())
        ->and(collect($classes['Français B1']['student_ids'])->sort()->values()->all())->toBe(collect([$c['awa']->id, $c['paul']->id])->sort()->values()->all());
});

test('le tableau de bord et la page eleves relancent les memes eleves', function () {
    $c = centreAClasser();
    $this->freezeSecond();
    tentativeClassement($c['awa'], $c['exercice'], 80, now()->subDays(7));
    tentativeClassement($c['paul'], $c['exercice'], 80, now()->subDays(7)->subSecond());

    $this->actingAs($c['admin']);
    $aRelancer = collect(propsDe($this->get(route('center.dashboard'))->assertOk())['a_relancer'])->pluck('name')->all();
    $statuts = collect(propsDe($this->get(route('center.students.index')))['students'])->pluck('statut', 'name');

    expect($aRelancer)->toBe(['Paul'])
        ->and($statuts['Awa'])->toBe('actif')
        ->and($statuts['Paul'])->toBe('a_relancer');
});

test('le nombre de requetes de la page eleves ne croit pas avec le nombre d eleves', function () {
    $c = centreAClasser();
    $this->actingAs($c['admin']);

    $ajouter = function (int $n) use ($c) {
        foreach (range(1, $n) as $i) {
            $u = User::factory()->create();
            UserProfile::factory()->for($u)->create(['onboarding_completed_at' => now(), 'target_exam_id' => $c['goethe']->id]);
            $c['centre']->members()->attach($u->id, ['role' => 'student', 'joined_at' => now()]);
            $c['classeAllemand']->members()->attach($u->id, ['role_in_class' => 'student']);
            UserExerciseAttempt::create([
                'user_id' => $u->id, 'exercise_id' => $c['exercice']->id, 'answers' => [],
                'score' => 50, 'accuracy_percent' => 50, 'time_spent' => 60, 'xp_earned' => 5, 'feedback' => [],
            ]);
        }
    };

    $compter = function () {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get(route('center.students.index'))->assertOk();
        $n = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $n;
    };

    $ajouter(2);
    // Un premier passage met en cache ce qui ne se lit qu'une fois (reglages, centre).
    $compter();
    $avant = $compter();
    $ajouter(10);
    $apres = $compter();

    expect(count(propsDe($this->get(route('center.students.index')))['students']))->toBe(15)
        ->and($apres)->toBe($avant);
});
