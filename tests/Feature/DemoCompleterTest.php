<?php

use App\Models\CenterUser;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Models\UserError;
use App\Models\UserExerciseAttempt;
use App\Models\UserProfile;
use App\Services\AI\WritingCorrectorService;
use App\Services\Center\SuiviApprenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * prepla:demo-completer enrichit le centre de demonstration : des classes
 * Goethe, telc, TCF, TEF, IELTS, leurs eleves et six semaines de suivi, et des
 * erreurs et productions corrigees pour les eleves DELF deja la. Elle tourne en
 * production, donc elle ne supprime ni ne modifie rien, et refuse tout centre
 * qui ne serait pas de demonstration.
 */
function vivierDeDemo(): array
{
    $langues = [
        'german' => Language::create(['slug' => 'german', 'name' => 'Allemand', 'native_name' => 'Deutsch', 'flag' => 'de']),
        'french' => Language::create(['slug' => 'french', 'name' => 'Français', 'native_name' => 'Français', 'flag' => 'fr']),
    ];

    // TEF et IELTS sont absents : leurs classes doivent etre sautees.
    $examens = [];
    foreach ([['goethe', 'Goethe-Zertifikat', 'german'], ['telc', 'telc Deutsch', 'german'], ['tcf', 'TCF', 'french'], ['delf-dalf', 'DELF/DALF', 'french']] as [$slug, $nom, $langue]) {
        $examen = Exam::create(['language_id' => $langues[$langue]->id, 'slug' => $slug, 'name' => $nom]);
        $examens[$slug] = $examen;

        $types = [];
        foreach ([['reading', 'mcq', 'mcq'], ['grammar', 'gap-fill', 'gap-fill'], ['writing', 'essay', 'essay-editor'], ['speaking', 'speaking', 'speaking-recorder']] as [$competence, $typeSlug, $composant]) {
            $section = ExamSection::create(['exam_id' => $examen->id, 'slug' => $slug.'-'.$competence, 'name' => $competence, 'skill_type' => $competence]);
            $types[$competence] = ExerciseType::create([
                'section_id' => $section->id, 'slug' => $typeSlug, 'name' => $typeSlug,
                'skill_type' => $competence, 'component_key' => $composant,
            ]);
        }

        foreach (['A1', 'B1', 'B2'] as $niveau) {
            Exercise::create([
                'exam_id' => $examen->id, 'exercise_type_id' => $types['reading']->id, 'difficulty' => $niveau,
                'content' => ['title' => "{$nom} {$niveau} · Lecture", 'passage' => 'Ein kurzer Text.'],
                'questions' => [
                    ['id' => 'q1', 'type' => 'mcq', 'text' => 'Wann beginnt der Kurs?', 'options' => ['Am Montag', 'Am Dienstag', 'Am Freitag'], 'correct_answer' => 'B', 'explanation' => 'Le texte dit « Dienstag ».'],
                    ['id' => 'q2', 'type' => 'mcq', 'text' => 'Wo ist der Kurs?', 'options' => ['Im Büro', 'In der Schule', 'Im Park'], 'correct_answer' => 'In der Schule', 'explanation' => '« in der Schule ».'],
                    ['id' => 'q3', 'type' => 'true-false', 'text' => 'Der Kurs ist kostenlos.', 'correct_answer' => 'Richtig', 'explanation' => '« kostenlos ».'],
                ],
            ]);
            Exercise::create([
                'exam_id' => $examen->id, 'exercise_type_id' => $types['grammar']->id, 'difficulty' => $niveau,
                'content' => ['title' => "{$nom} {$niveau} · Grammaire"],
                'questions' => [
                    ['id' => 'g1', 'type' => 'gap-fill', 'text' => 'Ich ___ zwanzig Jahre alt. (sein)', 'concept' => 'conjugation', 'correct_answer' => 'bin', 'explanation' => '« ich bin ».'],
                    ['id' => 'g2', 'type' => 'gap-fill', 'text' => 'Gestern habe ich ___. (lernen)', 'correct_answer' => 'gelernt', 'explanation' => 'Partizip II.'],
                ],
            ]);
            Exercise::create([
                'exam_id' => $examen->id, 'exercise_type_id' => $types['writing']->id, 'difficulty' => $niveau,
                'content' => ['title' => "{$nom} {$niveau} · Écrit"],
                'questions' => [['id' => 'w1', 'type' => 'essay', 'text' => 'Schreiben Sie eine Nachricht.']],
            ]);
            Exercise::create([
                'exam_id' => $examen->id, 'exercise_type_id' => $types['speaking']->id, 'difficulty' => $niveau,
                'content' => ['title' => "{$nom} {$niveau} · Oral"],
                'questions' => [['id' => 'o1', 'type' => 'speaking-recorder', 'text' => 'Stellen Sie sich vor.']],
            ]);
        }

        // Un format qu'on ne sait pas remplir (appariement) : jamais tire.
        Exercise::create([
            'exam_id' => $examen->id, 'exercise_type_id' => $types['reading']->id, 'difficulty' => 'B1',
            'content' => ['title' => 'Appariement'],
            'questions' => [['id' => 'm1', 'type' => 'matching', 'pairs' => [['a', 'b']]]],
        ]);
    }

    return $examens;
}

function compteDemo(string $email, string $role, LanguageCenter $centre): User
{
    $user = User::factory()->create(['email' => $email]);
    CenterUser::create(['center_id' => $centre->id, 'user_id' => $user->id, 'role' => $role, 'joined_at' => now()->subMonths(3)]);

    return $user;
}

/** Le centre de demo tel qu'il est en production : DELF seulement, sans erreur ni correction. */
function centreDeDemo(int $places = 200): array
{
    $examens = vivierDeDemo();
    $delf = $examens['delf-dalf'];

    $centre = LanguageCenter::create([
        'name' => 'Institut Linguae', 'slug' => 'institut-linguae-demo', 'owner_email' => 'demo-admin@demo.prepla.local',
        'seats_limit' => $places, 'default_exam_id' => $delf->id, 'is_active' => true,
    ]);
    compteDemo('demo-admin@demo.prepla.local', 'center_admin', $centre);
    $prof = compteDemo('prof.sophie-dupont@demo.prepla.local', 'teacher', $centre);

    $classe = Classroom::create(['center_id' => $centre->id, 'name' => 'DELF B1 — Soir', 'level' => 'B1', 'exam_id' => $delf->id, 'invite_code' => Classroom::generateInviteCode()]);
    $classe->members()->attach($prof->id, ['role_in_class' => 'teacher']);

    $lea = compteDemo('lea.dubois@demo.prepla.local', 'student', $centre);
    $nora = compteDemo('nora.faure@demo.prepla.local', 'student', $centre);
    foreach ([$lea, $nora] as $eleve) {
        UserProfile::create(['user_id' => $eleve->id, 'current_level' => 'B1', 'onboarding_completed_at' => now()->subMonths(2)]);
        $classe->members()->attach($eleve->id, ['role_in_class' => 'student']);
    }

    // Un exercice prive du centre, comme ceux de l'ancien generateur.
    $prive = Exercise::create([
        'exam_id' => $delf->id, 'center_id' => $centre->id,
        'exercise_type_id' => ExerciseType::where('skill_type', 'reading')->whereHas('section', fn ($q) => $q->where('exam_id', $delf->id))->value('id'),
        'difficulty' => 'B1', 'content' => ['title' => 'Reading — B1'],
        'questions' => [['id' => 'q1', 'text' => 'Question', 'options' => ['A', 'B'], 'correct_answer' => 'A']],
    ]);
    $redaction = Exercise::where('exam_id', $delf->id)->where('difficulty', 'B1')
        ->whereHas('exerciseType', fn ($q) => $q->where('skill_type', 'writing'))->first();

    $tentative = function (Exercise $e, array $answers, ?array $feedback, int $joursAvant) use ($lea) {
        $t = new UserExerciseAttempt([
            'user_id' => $lea->id, 'exercise_id' => $e->id, 'answers' => $answers, 'score' => 50,
            'accuracy_percent' => 55, 'time_spent' => 120, 'xp_earned' => 10, 'feedback' => $feedback,
        ]);
        $t->timestamps = false;
        $t->created_at = $t->updated_at = now()->subDays($joursAvant);
        $t->save();

        return $t;
    };
    for ($i = 0; $i < 9; $i++) {
        $tentative($prive, ['q1' => 'A'], [], 30 - $i * 2);
    }
    // Une correction deja la : elle ne doit jamais bouger.
    $corrigee = $tentative($prive, ['q1' => 'B'], [['question_id' => 'q1', 'correct' => false]], 12);
    // Une redaction ecrite, jamais corrigee : l'IA la corrige.
    $ecrite = $tentative($redaction, ['w1' => 'Bonjour madame, je vous écris pour dire que je peux pas venir samedi au cours de français.'], [], 10);
    // Une redaction reduite a « A » : rien a corriger.
    $factice = $tentative($redaction, ['w1' => 'A'], [], 9);

    return compact('examens', 'centre', 'classe', 'lea', 'nora', 'corrigee', 'ecrite', 'factice');
}

function correcteurSimule(): void
{
    app()->instance(WritingCorrectorService::class, Mockery::mock(WritingCorrectorService::class, fn ($m) => $m->shouldReceive('correct')->andReturn([
        'score' => 4.5,
        'band_scores' => ['task_achievement' => 5, 'coherence_cohesion' => 4, 'lexical_resource' => 4, 'grammar_accuracy' => 4],
        'corrections' => [['original' => 'je peux pas', 'corrected' => 'je ne peux pas', 'category' => 'grammar.negation', 'explanation' => 'Négation en deux parties.']],
        'diagnostic' => [['dimension' => 'grammatical_control', 'observation' => 'La négation perd son « ne ».', 'severity' => 'major']],
        'feedback' => 'Message compris, négation à revoir.',
    ])));
}

/** Toutes les lignes des tables que la commande peut toucher. */
function empreinte(): array
{
    $tables = ['users', 'user_profiles', 'center_user', 'classrooms', 'classroom_user', 'user_exercise_attempts', 'user_errors', 'assignments', 'assignment_items', 'exercises', 'language_centers', 'center_membership_periods'];
    $lignes = [];
    foreach ($tables as $table) {
        $lignes[$table] = DB::table($table)->get()->map(fn ($r) => (array) $r)->all();
    }

    return $lignes;
}

beforeEach(function () {
    $this->withoutVite();
    correcteurSimule();
});

test('refuse un centre dont un membre n a pas une adresse de demonstration', function () {
    $d = centreDeDemo();
    compteDemo('vrai.client@gmail.com', 'teacher', $d['centre']);
    $avant = empreinte();

    $this->artisan('prepla:demo-completer', ['--centre' => $d['centre']->id, '--appliquer' => true])
        ->expectsOutputToContain('refusé')
        ->assertExitCode(1);

    expect(empreinte())->toBe($avant);
});

test('refuse sans centre, et un centre avec un abonnement reel', function () {
    $this->artisan('prepla:demo-completer')->assertExitCode(1);

    $d = centreDeDemo();
    DB::table('subscriptions')->insert(['user_id' => $d['lea']->id, 'type' => 'default', 'stripe_id' => 'sub_123', 'stripe_status' => 'active', 'created_at' => now(), 'updated_at' => now()]);

    $this->artisan('prepla:demo-completer', ['--centre' => $d['centre']->id, '--appliquer' => true])->assertExitCode(1);
    expect(Classroom::count())->toBe(1);
});

test('sans --appliquer, la commande dit ce qu elle ferait et n ecrit rien', function () {
    $d = centreDeDemo();
    $avant = empreinte();

    $this->artisan('prepla:demo-completer', ['--centre' => $d['centre']->id])
        ->expectsOutputToContain('Rapport seulement')
        ->assertExitCode(0);

    expect(empreinte())->toBe($avant);
});

test('--appliquer ajoute les classes, les eleves, leur suivi, et saute un examen absent', function () {
    $d = centreDeDemo();

    $this->artisan('prepla:demo-completer', ['--centre' => $d['centre']->id, '--appliquer' => true])
        ->expectsOutputToContain('TEF')
        ->expectsOutputToContain('IELTS')
        ->assertExitCode(0);

    $classes = Classroom::where('center_id', $d['centre']->id)->pluck('name')->all();
    expect($classes)->toContain('Goethe A1 — Samedi matin', 'Goethe B1 — Soir', 'telc B1 — Intensif', 'TCF Canada — Soir')
        ->not->toContain('TEF Canada — Week-end', 'IELTS — Préparation');

    $goethe = Classroom::where('name', 'Goethe B1 — Soir')->first();
    expect($goethe->exam_id)->toBe($d['examens']['goethe']->id)
        ->and($goethe->level)->toBe('B1')
        ->and($goethe->students()->count())->toBe(8)
        ->and($goethe->teachers()->count())->toBe(1)
        ->and($goethe->assignments()->count())->toBe(2);
    if (Schema::hasColumn('classrooms', 'room')) {
        expect($goethe->room)->toBe('Salle 3')
            ->and(Classroom::where('name', 'DELF B1 — Soir')->value('room'))->not->toBeNull();
    }

    // Un eleve ajoute : membre du centre, examen vise, mot de passe des comptes de demo.
    $ulrich = User::where('email', 'ulrich.nguema@demo.prepla.local')->first();
    expect($ulrich)->not->toBeNull()
        ->and(CenterUser::where('user_id', $ulrich->id)->value('role'))->toBe('student')
        ->and(UserProfile::where('user_id', $ulrich->id)->value('target_exam_id'))->toBe($d['examens']['goethe']->id)
        ->and(DB::table('users')->where('id', $ulrich->id)->value('password'))
        ->toBe(DB::table('users')->where('email', 'demo-admin@demo.prepla.local')->value('password'));

    // Six semaines sur de vrais exercices de son examen, jamais un format inconnu.
    $tentatives = UserExerciseAttempt::where('user_id', $ulrich->id)->with('exercise')->get();
    expect($tentatives)->not->toBeEmpty();
    foreach ($tentatives as $t) {
        expect($t->exercise->exam_id)->toBe($d['examens']['goethe']->id)
            ->and($t->exercise->center_id)->toBeNull()
            ->and($t->exercise->content['title'])->not->toBe('Appariement')
            ->and($t->created_at->lte(now()))->toBeTrue()
            ->and($t->created_at->gte(now()->subDays(43)))->toBeTrue();
    }

    // La fiche du centre : des faiblesses avec leurs preuves, et un profil.
    $suivi = app(SuiviApprenant::class);
    expect($suivi->faiblesses($ulrich))->not->toBeEmpty()
        ->and($suivi->profil($ulrich))->not->toBeEmpty();
    // Les fautes d'allemand attendues, dans la classe : un bon eleve n'en a presque pas.
    expect(UserError::whereIn('user_id', $goethe->students()->pluck('users.id'))->where('error_category', 'grammar')->whereIn('subcategory', ['case', 'word-order', 'article', 'preposition'])->exists())->toBeTrue();

    // Un eleve « jamais venu » reste sans tentative.
    $absent = $goethe->students()->get()->first(fn (User $u) => UserExerciseAttempt::where('user_id', $u->id)->doesntExist());
    expect($absent)->not->toBeNull();

    // L'eleve DELF deja la : des erreurs et un profil, sa derniere activite inchangee.
    $derniereAvant = $d['factice']->created_at->toDateTimeString();
    expect($suivi->faiblesses($d['lea']))->not->toBeEmpty()
        ->and($suivi->profil($d['lea']))->not->toBeEmpty()
        ->and(UserExerciseAttempt::where('user_id', $d['lea']->id)->max('created_at'))->toBe($derniereAvant);
    // Sa redaction jamais corrigee l'est, la redaction « A » reste vide.
    expect($d['ecrite']->fresh()->feedback[0]['diagnostic'][0]['dimension'])->toBe('grammatical_control')
        ->and($d['factice']->fresh()->feedback)->toBe([]);
    // L'eleve DELF jamais venu reste sans rien.
    expect(UserExerciseAttempt::where('user_id', $d['nora']->id)->count())->toBe(0)
        ->and(UserError::where('user_id', $d['nora']->id)->count())->toBe(0);
});

test('relancee, elle ne cree aucun doublon', function () {
    $d = centreDeDemo();
    $this->artisan('prepla:demo-completer', ['--centre' => $d['centre']->id, '--appliquer' => true])->assertExitCode(0);
    $apres = empreinte();

    $this->artisan('prepla:demo-completer', ['--centre' => $d['centre']->id, '--appliquer' => true])->assertExitCode(0);

    expect(empreinte())->toBe($apres);
});

test('aucune ligne existante n est supprimee ni modifiee, hors champs vides', function () {
    $d = centreDeDemo();
    $avant = empreinte();

    $this->artisan('prepla:demo-completer', ['--centre' => $d['centre']->id, '--appliquer' => true])->assertExitCode(0);
    $apres = empreinte();

    foreach ($avant as $table => $lignes) {
        $nouvelles = collect($apres[$table]);
        foreach ($lignes as $ligne) {
            $meme = isset($ligne['id'])
                ? $nouvelles->firstWhere('id', $ligne['id'])
                : $nouvelles->first(fn ($n) => $n == $ligne);
            expect($meme)->not->toBeNull("{$table} : une ligne a disparu");

            // Seuls des champs VIDES peuvent avoir ete remplis.
            foreach ($ligne as $colonne => $valeur) {
                $vide = $valeur === null || $valeur === '' || $valeur === '[]';
                $permis = ($table === 'classrooms' && in_array($colonne, ['room', 'schedule'], true))
                    || ($table === 'user_exercise_attempts' && $colonne === 'feedback');
                if ($vide && $permis) {
                    continue;
                }
                expect($meme[$colonne])->toBe($valeur, "{$table}.{$colonne} #".($ligne['id'] ?? '?').' a change');
            }
        }
        expect(count($apres[$table]))->toBeGreaterThanOrEqual(count($lignes));
    }

    // La correction deja la n'a pas bouge.
    expect($d['corrigee']->fresh()->feedback)->toBe([['question_id' => 'q1', 'correct' => false]]);
});

test('respecte la limite de places, et ne l agrandit que sur demande', function () {
    // 2 eleves deja la, 12 places : 10 nouveaux au plus.
    $d = centreDeDemo(places: 12);

    $this->artisan('prepla:demo-completer', ['--centre' => $d['centre']->id, '--appliquer' => true])
        ->expectsOutputToContain('--agrandir-sieges')
        ->assertExitCode(0);
    expect($d['centre']->fresh()->seatsUsed())->toBe(12)
        ->and($d['centre']->fresh()->seats_limit)->toBe(12);

    $this->artisan('prepla:demo-completer', ['--centre' => $d['centre']->id, '--appliquer' => true, '--agrandir-sieges' => true])->assertExitCode(0);
    // 8 + 8 + 9 + 7 eleves pour les quatre examens presents, plus les 2 DELF.
    expect($d['centre']->fresh()->seatsUsed())->toBe(34)
        ->and($d['centre']->fresh()->seats_limit)->toBeGreaterThanOrEqual(34);
});

/** Les prenoms de tous les eleves que la commande ajoute. */
function prenomsAjoutes(): array
{
    $classes = (new ReflectionClassConstant(\App\Console\Commands\DemoCompleter::class, 'CLASSES'))->getValue();

    return collect($classes)->pluck('eleves')->flatten()->map(fn ($nom) => strtok($nom, ' '))->unique()->values()->all();
}

/** Les productions (redactions et oraux) d'un eleve : [competence, texte]. */
function productionsDe(User $eleve): array
{
    return UserExerciseAttempt::where('user_id', $eleve->id)->with('exercise.exerciseType')->get()
        ->filter(fn ($t) => in_array($t->exercise?->exerciseType?->skill_type, ['writing', 'speaking'], true))
        ->map(fn ($t) => [$t->exercise->exerciseType->skill_type, (string) collect((array) $t->answers)->first()])
        ->values()->all();
}

test('aucune copie ne porte le nom d un autre eleve, ni dans la fiche', function () {
    $d = centreDeDemo();
    $this->artisan('prepla:demo-completer', ['--centre' => $d['centre']->id, '--appliquer' => true])->assertExitCode(0);

    // Les copies elles-memes : aucun prenom en dur, seulement {prenom}.
    foreach (\App\Support\Demo\CopiesDeDemo::toutes() as $langue => $competences) {
        foreach ($competences as $bandes) {
            foreach ($bandes as $copies) {
                foreach ($copies as $copie) {
                    foreach (array_merge(prenomsAjoutes(), ['Hamadou', 'Steve']) as $prenom) {
                        expect(preg_match('/\b'.preg_quote($prenom, '/').'\b/u', $copie['texte']))->toBe(0, "{$prenom} dans une copie {$langue}");
                    }
                    // Chaque faute citee est bien dans le texte : la fiche la montre en contexte.
                    foreach ($copie['corrections'] as $c) {
                        expect(mb_stripos($copie['texte'], $c['original']))->not->toBeFalse();
                    }
                }
            }
        }
    }

    $eleves = User::where('email', 'like', '%@demo.prepla.local')->get();
    foreach ($eleves as $eleve) {
        $sien = strtok($eleve->name, ' ');
        $autres = array_diff(prenomsAjoutes(), [$sien]);
        $textes = collect(productionsDe($eleve))->pluck(1)
            ->concat(UserError::where('user_id', $eleve->id)->pluck('question_text'));
        foreach ($textes as $texte) {
            foreach ($autres as $prenom) {
                expect(preg_match('/\b'.preg_quote($prenom, '/').'\b/u', (string) $texte))->toBe(0, "{$eleve->email} cite {$prenom}");
            }
        }
    }

    // Une copie signee l'est du prenom de celui qui la rend.
    $signees = $eleves
        ->flatMap(fn (User $u) => collect(productionsDe($u))->map(fn ($p) => [$u, $p[1]]))
        ->filter(fn ($p) => str_contains($p[1], strtok($p[0]->name, ' ')));
    expect($signees)->not->toBeEmpty();
});

test('deux eleves d une meme classe ne rendent pas la meme copie', function () {
    $d = centreDeDemo();
    $this->artisan('prepla:demo-completer', ['--centre' => $d['centre']->id, '--appliquer' => true])->assertExitCode(0);

    $classes = Classroom::where('center_id', $d['centre']->id)->where('name', '!=', 'DELF B1 — Soir')->get();
    expect($classes)->toHaveCount(4);
    foreach ($classes as $classe) {
        $productions = collect($classe->students()->get()->flatMap(fn (User $u) => productionsDe($u)));
        expect($productions->count())->toBeGreaterThan(4);
        foreach (['writing', 'speaking'] as $competence) {
            $textes = $productions->where(0, $competence)->pluck(1);
            expect($textes->duplicates()->all())->toBe([], "{$classe->name} : la même copie rendue deux fois ({$competence})");
        }
    }
});

test('ne touche pas aux exercices des epreuves blanches', function () {
    $d = centreDeDemo();
    $goethe = $d['examens']['goethe'];
    $lecture = ExerciseType::where('skill_type', 'reading')->whereHas('section', fn ($q) => $q->where('exam_id', $goethe->id))->first();
    $blanche = \App\Models\MockExam::create([
        'blueprint_id' => \App\Models\ExamBlueprint::create([
            'exam_id' => $goethe->id, 'name' => 'Blanc', 'total_duration_minutes' => 30, 'scoring_config' => [], 'sections_config' => [],
        ])->id,
        'title' => 'Epreuve blanche',
    ]);
    // Une tentative la figerait : prepla:compose-mock-exams ne touche plus une
    // epreuve « deja travaillee ».
    Exercise::create([
        'exam_id' => $goethe->id, 'exercise_type_id' => $lecture->id, 'difficulty' => 'B1', 'mock_exam_id' => $blanche->id,
        'content' => ['title' => 'Epreuve blanche · Lesen'],
        'questions' => [['id' => 'q1', 'type' => 'mcq', 'text' => 'Was ist richtig?', 'options' => ['Ja', 'Nein'], 'correct_answer' => 'A']],
    ]);

    $this->artisan('prepla:demo-completer', ['--centre' => $d['centre']->id, '--appliquer' => true])->assertExitCode(0);

    expect(UserExerciseAttempt::whereIn('exercise_id', Exercise::whereNotNull('mock_exam_id')->pluck('id'))->count())->toBe(0)
        ->and(UserExerciseAttempt::whereHas('exercise', fn ($q) => $q->where('exam_id', $goethe->id))->count())->toBeGreaterThan(0);
});

test('l IA corrige hors de la transaction, et la faute d une question dit son concept', function () {
    $d = centreDeDemo();
    $niveaux = [];
    $base = DB::transactionLevel();
    // Une fonction, pas une fleche : la fleche copierait $niveaux.
    app()->instance(WritingCorrectorService::class, Mockery::mock(WritingCorrectorService::class, function ($m) use (&$niveaux) {
        $m->shouldReceive('correct')->andReturnUsing(function (...$args) use (&$niveaux) {
            $niveaux[] = DB::transactionLevel();

            return [
                'score' => 4.5, 'band_scores' => [],
                'corrections' => [['original' => 'je peux pas', 'corrected' => 'je ne peux pas', 'category' => 'grammar.negation', 'explanation' => 'Négation.']],
                'diagnostic' => [['dimension' => 'grammatical_control', 'observation' => 'La négation perd son « ne ».', 'severity' => 'major']],
                'feedback' => 'Négation à revoir.',
            ];
        });
    }));

    $this->artisan('prepla:demo-completer', ['--centre' => $d['centre']->id, '--appliquer' => true])->assertExitCode(0);

    // Aucun appel HTTP pendant que la transaction de la commande tient le verrou.
    expect($niveaux)->not->toBeEmpty()
        ->and(array_values(array_unique($niveaux)))->toBe([$base]);
    expect($d['ecrite']->fresh()->feedback[0]['diagnostic'][0]['dimension'])->toBe('grammatical_control')
        ->and(UserError::where('user_id', $d['lea']->id)->where('subcategory', 'negation')->exists())->toBeTrue();

    // La question « g1 » teste la conjugaison : ses fautes le disent.
    $g1 = UserError::where('question_id', 'g1')->get();
    expect($g1)->not->toBeEmpty()
        ->and($g1->pluck('subcategory')->unique()->values()->all())->toBe(['conjugation']);
});
