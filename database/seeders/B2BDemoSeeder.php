<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\CenterUser;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Models\UserError;
use App\Models\UserExerciseAttempt;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Peuple un centre de langue de démonstration complet (staff, classes, élèves,
 * exercices, devoirs, tentatives, erreurs) pour les démos commerciales B2B.
 *
 * JAMAIS appelé depuis DatabaseSeeder — uniquement à la main :
 *   php artisan demo:reset
 *   php artisan db:seed --class=B2BDemoSeeder
 *
 * Rejouable à volonté : nettoie entièrement le centre de démo existant (repéré
 * par son slug fixe) avant de le recréer à l'identique, pour repartir d'un état
 * propre avant chaque rendez-vous commercial même si un prospect a bidouillé
 * dans la démo précédente.
 */
class B2BDemoSeeder extends Seeder
{
    private const CENTER_SLUG = 'institut-linguae-demo';
    private const EMAIL_DOMAIN = 'demo.prepla.local';
    private const DEMO_PASSWORD = 'Demo-Prepla-2026';

    private array $summary = [];

    public function run(): void
    {
        $this->guardEnvironment();

        DB::transaction(function () {
            $this->resetExisting();

            [$exam, $typesBySkill] = $this->resolveCatalog();

            $center = $this->createCenter($exam, studentCount: 25);
            $admin = $this->createStaffAdmin($center);
            $teachers = $this->createTeachers($center, count: 3);
            $classrooms = $this->createClassrooms($center, $teachers, $exam);
            $students = $this->createStudents($classrooms);
            $exercises = $this->createExercises($center, $teachers, $exam, $typesBySkill);
            $assignmentsByClassroom = $this->createAssignmentsWithItems($classrooms, $teachers, $exercises);
            $this->createAttemptsAndErrors($students, $exercises, $assignmentsByClassroom);

            $this->backdateStructural($center, $teachers, $students, $classrooms);

            $this->summary = [
                'center' => $center,
                'admin' => $admin,
                'teacher' => $teachers->first(),
                'student' => $students->first(),
            ];
        });

        $this->printSummary();
    }

    /**
     * Un migrate:fresh --seed lancé par erreur sur un environnement partagé ne
     * doit jamais injecter un faux centre + élèves. Le seeder démo reste
     * strictement un geste volontaire.
     */
    private function guardEnvironment(): void
    {
        if (app()->environment('production') && ! env('ALLOW_DEMO_SEED', false)) {
            $this->command?->error('B2BDemoSeeder refuse de tourner en production sans ALLOW_DEMO_SEED=true.');
            throw new \RuntimeException('Demo seeding blocked in production (set ALLOW_DEMO_SEED=true to override).');
        }
    }

    /**
     * Supprime tout ce qui dépend du centre de démo existant, dans l'ordre des
     * contraintes FK, puis le centre lui-même. Le filtre email @demo.prepla.local
     * en plus du filtre par center_id est une sécurité supplémentaire : on ne
     * supprime jamais un compte qui ne serait pas explicitement un compte démo.
     */
    private function resetExisting(): void
    {
        $center = LanguageCenter::where('slug', self::CENTER_SLUG)->first();
        if (! $center) {
            return;
        }

        $classroomIds = Classroom::where('center_id', $center->id)->pluck('id');
        $assignmentIds = Assignment::whereIn('classroom_id', $classroomIds)->pluck('id');

        DB::table('assignment_items')->whereIn('assignment_id', $assignmentIds)->delete();
        Assignment::whereIn('classroom_id', $classroomIds)->delete();

        $userIds = CenterUser::where('center_id', $center->id)->pluck('user_id');
        $demoUserIds = User::whereIn('id', $userIds)
            ->where('email', 'like', '%@' . self::EMAIL_DOMAIN)
            ->pluck('id');

        UserError::whereIn('user_id', $demoUserIds)->delete();
        UserExerciseAttempt::whereIn('user_id', $demoUserIds)->delete();
        UserProfile::whereIn('user_id', $demoUserIds)->delete();

        // nullOnDelete sur exercises.center_id : on les efface explicitement,
        // sinon ils survivraient orphelins (center_id=null) au lieu de disparaître.
        Exercise::where('center_id', $center->id)->delete();

        DB::table('classroom_user')->whereIn('classroom_id', $classroomIds)->delete();
        Classroom::where('center_id', $center->id)->delete();

        CenterUser::where('center_id', $center->id)->delete();
        User::whereIn('id', $demoUserIds)->delete();

        $center->delete();
    }

    /**
     * Résout l'examen et les types d'exercices réels à utiliser — jamais d'ID
     * codé en dur (exercise_type_id/exam_id sont NOT NULL avec FK réelles).
     *
     * @return array{0: Exam, 1: array<string, ExerciseType>}
     */
    private function resolveCatalog(): array
    {
        $exam = Exam::where('slug', 'delf-dalf')->first();
        if (! $exam) {
            $exam = Exam::first();
            $this->command?->warn('Exam "delf-dalf" introuvable — utilisation du premier Exam disponible en base.');
        }
        abort_if(! $exam, 500, 'Aucun Exam en base — lancez ExamSeeder avant B2BDemoSeeder.');

        $sectionIds = ExamSection::where('exam_id', $exam->id)->pluck('id');
        $types = ExerciseType::whereIn('section_id', $sectionIds)->get();

        if ($types->isEmpty()) {
            $types = ExerciseType::limit(10)->get();
            $this->command?->warn('Aucun ExerciseType lié à cet Exam — fallback sur les types disponibles tous examens confondus.');
        }
        abort_if($types->isEmpty(), 500, 'Aucun ExerciseType en base — lancez ExamSeeder avant B2BDemoSeeder.');

        // Un type par compétence (reading/listening/writing/grammar), pour varier
        // les exercices générés plutôt que piocher 12 fois le même mcq.
        $bySkill = [];
        foreach (['grammar', 'reading', 'listening', 'writing'] as $skill) {
            $match = $types->firstWhere('skill_type', $skill);
            if ($match) {
                $bySkill[$skill] = $match;
            }
        }
        if (empty($bySkill)) {
            $bySkill['grammar'] = $types->first();
        }

        return [$exam, $bySkill];
    }

    private function createCenter(Exam $exam, int $studentCount): LanguageCenter
    {
        return LanguageCenter::create([
            'name' => 'Institut Linguae',
            'slug' => self::CENTER_SLUG,
            'owner_email' => 'demo-admin@' . self::EMAIL_DOMAIN,
            'seats_limit' => $studentCount + 5,
            'default_exam_id' => $exam->id,
            'is_active' => true,
            'settings' => null,
        ]);
    }

    private function makeUser(string $name, string $email, string $level, ?Carbon $onboardedAt = null): User
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make(self::DEMO_PASSWORD),
            'email_verified_at' => now(),
        ]);

        UserProfile::create([
            'user_id' => $user->id,
            'current_level' => $level,
            'onboarding_completed_at' => $onboardedAt ?? now()->subDays(random_int(30, 180)),
            'xp_total' => random_int(50, 800),
            'streak_current' => random_int(0, 12),
        ]);

        return $user;
    }

    private function createStaffAdmin(LanguageCenter $center): User
    {
        $admin = $this->makeUser('Camille Rousseau', 'demo-admin@' . self::EMAIL_DOMAIN, 'C1', now()->subMonths(6));

        CenterUser::create([
            'center_id' => $center->id,
            'user_id' => $admin->id,
            'role' => 'center_admin',
            'joined_at' => now()->subMonths(6),
        ]);

        return $admin;
    }

    /** @return \Illuminate\Support\Collection<int, User> */
    private function createTeachers(LanguageCenter $center, int $count): \Illuminate\Support\Collection
    {
        $names = ['Sophie Dupont', 'Marc Martin', 'Linh Nguyen', 'Karim Haddad', 'Julie Lefèvre'];
        $teachers = collect();

        for ($i = 0; $i < $count; $i++) {
            $slug = Str::slug($names[$i]);
            $teacher = $this->makeUser($names[$i], "prof.{$slug}@" . self::EMAIL_DOMAIN, 'C1', now()->subMonths(random_int(3, 6)));

            CenterUser::create([
                'center_id' => $center->id,
                'user_id' => $teacher->id,
                'role' => 'teacher',
                'joined_at' => now()->subMonths(random_int(3, 6)),
            ]);

            $teachers->push($teacher);
        }

        return $teachers;
    }

    /** @return \Illuminate\Support\Collection<int, Classroom> */
    private function createClassrooms(LanguageCenter $center, \Illuminate\Support\Collection $teachers, Exam $exam): \Illuminate\Support\Collection
    {
        $data = [
            ['name' => 'DELF A1 — Matin', 'level' => 'A1'],
            ['name' => 'DELF B1 — Soir', 'level' => 'B1'],
            ['name' => 'DELF B2 — Intensif', 'level' => 'B2'],
        ];

        return collect($data)->map(function ($d, $i) use ($center, $teachers, $exam) {
            $classroom = Classroom::create([
                'center_id' => $center->id,
                'name' => $d['name'],
                'level' => $d['level'],
                'exam_id' => $exam->id,
                'invite_code' => Classroom::generateInviteCode(),
            ]);

            $teacher = $teachers[$i % $teachers->count()];
            $classroom->members()->attach($teacher->id, ['role_in_class' => 'teacher']);

            return $classroom;
        });
    }

    /** @return \Illuminate\Support\Collection<int, User> élèves, avec 'persona' et 'classroom' attachés dynamiquement */
    private function createStudents(\Illuminate\Support\Collection $classrooms): \Illuminate\Support\Collection
    {
        $firstNames = ['Léa', 'Hugo', 'Manon', 'Nathan', 'Chloé', 'Lucas', 'Emma', 'Louis', 'Sarah', 'Adam', 'Inès', 'Rayan', 'Camille', 'Noah', 'Zoé', 'Amir', 'Julia', 'Théo', 'Nora', 'Yanis', 'Alice', 'Gabriel', 'Lina', 'Enzo', 'Maya'];
        $lastNames = ['Dubois', 'Bernard', 'Petit', 'Roux', 'Fontaine', 'Girard', 'Bonnet', 'Morel', 'Faure', 'Andre'];

        // ~60% actifs, ~25% moyens, ~15% décrocheurs — réparti sur l'ensemble
        // des élèves pour peupler ClassStatsService de façon crédible.
        $personas = [];
        for ($i = 0; $i < 25; $i++) {
            $personas[] = match (true) {
                $i < 15 => 'active',   // 60%
                $i < 21 => 'average',  // 24%
                default => 'dropout',  // 16%
            };
        }
        shuffle($personas);

        $students = collect();
        $classroomsCycle = [$classrooms[0], $classrooms[0], $classrooms[0], $classrooms[0], $classrooms[0], $classrooms[0], $classrooms[0], $classrooms[0],
                             $classrooms[1], $classrooms[1], $classrooms[1], $classrooms[1], $classrooms[1], $classrooms[1], $classrooms[1], $classrooms[1], $classrooms[1],
                             $classrooms[2], $classrooms[2], $classrooms[2], $classrooms[2], $classrooms[2], $classrooms[2], $classrooms[2], $classrooms[2]];

        for ($i = 0; $i < 25; $i++) {
            $first = $firstNames[$i];
            $last = $lastNames[$i % count($lastNames)];
            $email = Str::slug($first) . '.' . Str::slug($last) . '@' . self::EMAIL_DOMAIN;
            $classroom = $classroomsCycle[$i];

            $student = $this->makeUser("{$first} {$last}", $email, $classroom->level ?? 'A1', now()->subDays(random_int(20, 150)));
            $student->demo_persona = $personas[$i];
            $student->demo_classroom = $classroom;

            CenterUser::create([
                'center_id' => $classroom->center_id,
                'user_id' => $student->id,
                'role' => 'student',
                'joined_at' => now()->subDays(random_int(20, 150)),
            ]);
            $classroom->members()->attach($student->id, ['role_in_class' => 'student']);

            $students->push($student);
        }

        return $students;
    }

    /** @return \Illuminate\Support\Collection<int, Exercise> */
    private function createExercises(LanguageCenter $center, \Illuminate\Support\Collection $teachers, Exam $exam, array $typesBySkill): \Illuminate\Support\Collection
    {
        $exercises = collect();
        $difficulties = ['A1', 'B1', 'B2'];

        $bank = [
            'grammar' => [
                ['text' => 'Complétez : "Je ___ étudiant."', 'sentence' => '', 'options' => ['suis', 'es', 'est', 'sommes'], 'correct_answer' => 'A'],
                ['text' => 'Choisissez la forme correcte : "Hier, nous ___ au cinéma."', 'sentence' => '', 'options' => ['allons', 'sommes allés', 'irons', 'allions'], 'correct_answer' => 'B'],
            ],
            'reading' => [
                ['text' => 'D\'après le texte, à quelle heure ouvre le magasin ?', 'sentence' => '', 'options' => ['8h', '9h', '10h', '11h'], 'correct_answer' => 'B'],
            ],
            'listening' => [
                ['text' => 'Que demande la personne au téléphone ?', 'sentence' => '', 'options' => ['Une réservation', 'Un remboursement', 'Une adresse', 'Un horaire'], 'correct_answer' => 'A'],
            ],
            'writing' => [
                ['text' => 'Rédigez un court message pour annuler un rendez-vous.', 'sentence' => '', 'options' => ['A', 'B', 'C', 'D'], 'correct_answer' => 'A'],
            ],
        ];

        foreach ($typesBySkill as $skill => $type) {
            $questionsPool = $bank[$skill] ?? $bank['grammar'];

            foreach ($difficulties as $difficulty) {
                $questions = [];
                foreach ($questionsPool as $i => $q) {
                    $questions[] = array_merge($q, ['id' => 'q' . ($i + 1), 'level' => $difficulty]);
                }

                $exercise = Exercise::create([
                    'exercise_type_id' => $type->id,
                    'exam_id' => $exam->id,
                    'center_id' => $center->id,
                    'creator_id' => $teachers->random()->id,
                    'content' => ['title' => ucfirst($skill) . " — {$difficulty}"],
                    'questions' => $questions,
                    'difficulty' => $difficulty,
                    'xp_reward' => 10,
                    'is_ai_generated' => false,
                ]);

                $exercises->push($exercise);
            }
        }

        return $exercises;
    }

    /**
     * 4 devoirs par classe, dates échelonnées pour couvrir tous les statuts de
     * AssignmentProgressService (done/in_progress/late/not_started).
     *
     * @return array<int, \Illuminate\Support\Collection<int, Assignment>> devoirs par classroom_id
     */
    private function createAssignmentsWithItems(\Illuminate\Support\Collection $classrooms, \Illuminate\Support\Collection $teachers, \Illuminate\Support\Collection $exercises): array
    {
        $schedule = [
            ['title' => 'Devoir 1 — Grammaire de base', 'published_at' => now()->subWeeks(6), 'due_at' => now()->subWeeks(5)],
            ['title' => 'Devoir 2 — Compréhension', 'published_at' => now()->subWeeks(3), 'due_at' => now()->subWeek()],
            ['title' => 'Devoir 3 — Expression écrite', 'published_at' => now()->subDays(4), 'due_at' => now()->addWeek()],
            ['title' => 'Devoir 4 — Révisions', 'published_at' => now()->subHours(6), 'due_at' => now()->addWeeks(2)],
        ];

        $result = [];

        foreach ($classrooms as $classroom) {
            $classExercises = $exercises->where('difficulty', $classroom->level)->values();
            if ($classExercises->isEmpty()) {
                $classExercises = $exercises->take(3);
            }

            $assignments = collect();

            foreach ($schedule as $data) {
                $assignment = Assignment::create([
                    'classroom_id' => $classroom->id,
                    'created_by' => $classroom->teachers()->first()?->id ?? $teachers->first()->id,
                    'title' => $data['title'],
                    'instructions' => 'Complétez les exercices ci-joints avant la date limite.',
                    'due_at' => $data['due_at'],
                    'published_at' => $data['published_at'],
                ]);

                foreach ($classExercises->take(2) as $i => $exercise) {
                    $assignment->items()->create([
                        'itemable_type' => Exercise::class,
                        'itemable_id' => $exercise->id,
                        'sort_order' => $i,
                    ]);
                }

                $assignments->push($assignment);
            }

            $result[$classroom->id] = $assignments;
        }

        return $result;
    }

    /**
     * Antidate created_at/updated_at — Eloquent les écrase à l'heure courante à
     * l'insertion même si on les passe dans create(), donc on repasse par une
     * requête directe juste après.
     */
    private function backdate(string $table, int $id, Carbon $date): void
    {
        DB::table($table)->where('id', $id)->update(['created_at' => $date, 'updated_at' => $date]);
    }

    private function realisticAccuracy(string $persona): float
    {
        return match ($persona) {
            'active' => (float) random_int(70, 97),
            'average' => (float) random_int(45, 78),
            default => (float) random_int(30, 65),
        };
    }

    /**
     * @param \Illuminate\Support\Collection<int, User> $students les 25 élèves créés
     *        par createStudents(), PORTEURS des attributs dynamiques demo_persona/
     *        demo_classroom — on ne les re-requête JAMAIS depuis la base ici, sinon
     *        on récupère des instances fraîches sans ces attributs et toute la
     *        logique de persona retombe silencieusement sur le défaut 'average'.
     */
    private function createAttemptsAndErrors(\Illuminate\Support\Collection $students, \Illuminate\Support\Collection $exercises, array $assignmentsByClassroom): void
    {
        $errorCategories = [
            ['grammar', 'tense'], ['grammar', 'agreement'], ['vocabulary', 'word-formation'],
            ['vocabulary', 'lexical'], ['writing', 'coherence'], ['reading', null], ['listening', null],
        ];

        $studentsByClassroom = $students->groupBy(fn (User $s) => $s->demo_classroom->id);

        foreach ($studentsByClassroom as $classroomId => $classroomStudents) {
            $classroom = $classroomStudents->first()->demo_classroom;
            $classExercises = $exercises->where('difficulty', $classroom->level)->values();
            if ($classExercises->isEmpty()) {
                $classExercises = $exercises->take(3);
            }

            foreach ($classroomStudents as $student) {
                $persona = $student->demo_persona ?? 'average';

                // Décrocheur "jamais commencé" : aucune attempt du tout.
                if ($persona === 'dropout' && random_int(1, 100) <= 40) {
                    continue;
                }

                $attemptCount = match ($persona) {
                    'active' => random_int(20, 40),
                    'average' => random_int(8, 18),
                    default => random_int(2, 6),
                };

                // Décrocheur "actif puis arrêté" : dernière activité entre 10 et 20
                // jours pour dépasser le seuil de 7 jours de ClassStatsService.
                $mostRecentOffsetDays = $persona === 'dropout' ? random_int(10, 20) : random_int(0, 6);

                for ($i = 0; $i < $attemptCount; $i++) {
                    $exercise = $classExercises->random();
                    $accuracy = $this->realisticAccuracy($persona);

                    // Étale les tentatives sur 6 semaines, la plus récente à
                    // $mostRecentOffsetDays jours dans le passé.
                    $daysAgo = $i === 0 ? $mostRecentOffsetDays : random_int($mostRecentOffsetDays, 42);
                    $attemptDate = now()->subDays($daysAgo)->subHours(random_int(0, 23));

                    $attempt = UserExerciseAttempt::create([
                        'user_id' => $student->id,
                        'exercise_id' => $exercise->id,
                        'answers' => ['q1' => 'A'],
                        'score' => (int) round($accuracy),
                        'accuracy_percent' => $accuracy,
                        'time_spent' => random_int(45, 400),
                        'xp_earned' => $exercise->xp_reward,
                        'feedback' => [],
                    ]);
                    $this->backdate('user_exercise_attempts', $attempt->id, $attemptDate);

                    if ($accuracy < 60) {
                        [$category, $subcategory] = $errorCategories[array_rand($errorCategories)];
                        $error = UserError::create([
                            'user_id' => $student->id,
                            'exercise_id' => $exercise->id,
                            'question_id' => 'q1',
                            'question_text' => $exercise->questions[0]['text'] ?? 'Exercice de pratique',
                            'user_answer' => 'B',
                            'correct_answer' => $exercise->questions[0]['correct_answer'] ?? 'A',
                            'explanation' => 'Révisez la règle correspondante et réessayez.',
                            'skill_type' => $exercise->exerciseType->skill_type ?? 'grammar',
                            'exercise_type_slug' => $exercise->exerciseType->slug ?? 'mcq',
                            'error_category' => $category,
                            'subcategory' => $subcategory,
                            'reviewed_count' => random_int(0, 3),
                            'mastered' => (bool) random_int(0, 1),
                            'ease_factor' => 2.5,
                            'interval_days' => random_int(1, 10),
                            'next_review_at' => now()->addDays(random_int(-2, 10)),
                        ]);
                        $this->backdate('user_errors', $error->id, $attemptDate);
                    }
                }

                // Corrélation avec les devoirs : pour les devoirs déjà publiés,
                // certains élèves (selon persona) ont une attempt sur CHAQUE
                // exercice de l'assignment, juste après published_at.
                $assignments = $assignmentsByClassroom[$classroom->id] ?? collect();
                foreach ($assignments as $index => $assignment) {
                    if (! $assignment->published_at || $assignment->published_at->isFuture()) {
                        continue;
                    }

                    // Taux de complétion décroissant du devoir le plus ancien (index 0)
                    // au plus récent (index 3), et plus élevé pour les élèves actifs.
                    $completionChance = match ($index) {
                        0 => $persona === 'active' ? 90 : ($persona === 'average' ? 70 : 20),
                        1 => $persona === 'active' ? 75 : ($persona === 'average' ? 45 : 10),
                        2 => $persona === 'active' ? 55 : ($persona === 'average' ? 25 : 0),
                        default => $persona === 'active' ? 20 : 0,
                    };

                    if (random_int(1, 100) > $completionChance) {
                        continue;
                    }

                    foreach ($assignment->items as $item) {
                        if ($item->itemable_type !== Exercise::class) {
                            continue;
                        }
                        $accuracy = $this->realisticAccuracy($persona);
                        $doneAt = (clone $assignment->published_at)->addHours(random_int(2, 72));

                        $attempt = UserExerciseAttempt::create([
                            'user_id' => $student->id,
                            'exercise_id' => $item->itemable_id,
                            'answers' => ['q1' => 'A'],
                            'score' => (int) round($accuracy),
                            'accuracy_percent' => $accuracy,
                            'time_spent' => random_int(45, 400),
                            'xp_earned' => 10,
                            'feedback' => [],
                        ]);
                        $this->backdate('user_exercise_attempts', $attempt->id, $doneAt);
                    }
                }
            }
        }
    }

    private function backdateStructural(LanguageCenter $center, \Illuminate\Support\Collection $teachers, \Illuminate\Support\Collection $students, \Illuminate\Support\Collection $classrooms): void
    {
        $this->backdate('language_centers', $center->id, now()->subMonths(8));

        foreach ($classrooms as $classroom) {
            $this->backdate('classrooms', $classroom->id, now()->subMonths(random_int(4, 6)));
        }

        foreach ($teachers as $teacher) {
            $this->backdate('users', $teacher->id, now()->subMonths(random_int(4, 7)));
        }

        foreach ($students as $student) {
            $this->backdate('users', $student->id, now()->subDays(random_int(20, 150)));
        }
    }

    private function printSummary(): void
    {
        if (! $this->command || empty($this->summary)) {
            return;
        }

        $admin = $this->summary['admin'];
        $teacher = $this->summary['teacher'];
        $student = $this->summary['student'];

        $this->command->info('');
        $this->command->info('=== Démo B2B "Institut Linguae" prête ===');
        $this->command->info("Admin  : {$admin->email} / " . self::DEMO_PASSWORD);
        $this->command->info("Prof   : {$teacher->email} / " . self::DEMO_PASSWORD);
        $this->command->info("Élève  : {$student->email} / " . self::DEMO_PASSWORD);
        $this->command->info('URL    : ' . url('/center'));
        $this->command->info('');
    }
}
