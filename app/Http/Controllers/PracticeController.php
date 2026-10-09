<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\LearningPathNode;
use App\Models\MockExam;
use App\Models\UserLearningProgress;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PracticeController extends Controller
{
    protected \App\Services\ExerciseScoringService $scoringService;
    protected \App\Services\StreakService $streakService;

    public function __construct(
        \App\Services\ExerciseScoringService $scoringService,
        \App\Services\StreakService $streakService
    ) {
        $this->scoringService = $scoringService;
        $this->streakService = $streakService;
    }

    /**
     * Lance une session d'apprentissage pour un nœud spécifique (Le "Set de 3").
     */
    public function startNodeSession(LearningPathNode $node): Response
    {
        $user = auth()->user();
        
        // Vérifier si l'utilisateur a accès à ce nœud
        $progress = UserLearningProgress::where('user_id', $user->id)
            ->where('node_id', $node->id)
            ->firstOrFail();

        // Récupérer 3 exercices liés à ce nœud
        // 1. Théorie/Acquisition (order_in_node = 1)
        // 2. Pratique (order_in_node = 2)
        // 3. Test/Production (order_in_node = 3)
        $exercises = Exercise::where('node_id', $node->id)
            ->with('exerciseType')
            ->orderBy('order_in_node')
            ->limit(3)
            ->get();

        // Si pas d'exercices liés au nœud, on en pioche des génériques par difficulté et type.
        // On exclut les types retirés de la rotation (diagram-labeling : pas de vraie image).
        if ($exercises->count() < 3) {
            $exercises = Exercise::where('exam_id', $node->exam_id)
                ->where('difficulty', $node->level)
                ->whereDoesntHave('exerciseType', fn ($q) => $q->whereIn('component_key', ['diagram-labeling']))
                ->with('exerciseType')
                ->inRandomOrder()
                ->limit(3)
                ->get();
        }

        // Meme garde-fou qu'en seance : pas de question impossible a l'ecran.
        $exercises = collect($exercises)->map(function ($exercise) {
            $jouables = $exercise->answerableQuestions();
            if (count($jouables) !== count($exercise->questions ?? [])) {
                $exercise->questions = $jouables;
                $exercise->syncOriginalAttribute('questions');
            }

            return $exercise;
        })->filter(fn ($exercise) => count($exercise->questions ?? []) > 0)->values();

        if ($exercises->isEmpty()) {
            return redirect()->route('practice.index')
                ->with('error', "Ces exercices etaient inutilisables. Choisis-en d'autres, ou reessaie plus tard.");
        }

        return Inertia::render('exercises/player', [
            'node' => $node,
            'exercises' => $exercises,
            'progress' => $progress,
            // Sans jeton, renvoyer la meme seance de pratique libre recreditait XP
            // et tentatives : la protection ne couvrait que le parcours.
            'sessionToken' => $this->jetonDeSeance(auth()->id()),
        ]);
    }
    public function skill(string $skill)
    {
        abort_unless(in_array($skill, ['speaking', 'listening'], true), 404);
        $examId = auth()->user()->profile?->target_exam_id;
        if ($skill === 'speaking' && ($exam = Exam::find($examId))) {
            app(\App\Services\Content\OralStarter::class)->ensure($exam, auth()->user()->profile?->current_level ?? 'A1');
        }
        $section = ExamSection::where('exam_id', $examId)->where('skill_type', $skill)
            ->whereHas('exerciseTypes')->first();
        return $section
            ? redirect()->route('practice.section', [$examId, $section])
            : redirect()->route('practice.index')->with('error', 'Cette compétence n’est pas encore disponible pour ton objectif.');
    }

    public function index()
    {
        $user = auth()->user();
        $profile = $user->profile?->load('targetExam.language');
        $targetExam = $profile?->targetExam;

        // Si l'utilisateur a un examen cible, on l'affiche directement lui, sans les autres.
        if ($targetExam) {
            return $this->examDashboard($targetExam);
        }

        // Sinon, on liste tous les examens (cas rare après onboarding)
        $exams = Exam::with('language')->whereHas('language', fn ($q) => $q->where('is_active', true))->get();
        return Inertia::render('practice/index', [
            'exams' => $exams,
            'targetExamId' => null,
        ]);
    }

    public function examDashboard(Exam $exam): Response
    {
        $exam->load(['language', 'sections' => fn ($q) => $q->where('slug', '!=', 'level-assessment')->with('exerciseTypes')]);

        $user = auth()->user();
        $sectionProgress = [];
        foreach ($exam->sections as $section) {
            $totalAttempts = $user->exerciseAttempts()
                ->whereHas('exercise', fn ($q) => $q->whereHas('exerciseType', fn ($q2) => $q2->where('section_id', $section->id)))
                ->count();
            $sectionProgress[$section->id] = $totalAttempts;
        }

        return Inertia::render('practice/exam-dashboard', [
            'exam' => $exam,
            'sectionProgress' => $sectionProgress,
            'learnerLevel' => $user->profile?->current_level ?? 'A1',
            'canSimulate' => ! in_array($user->profile?->current_level ?? 'A1', ['A0', 'A1', 'A2'], true)
                || MockExam::where('is_published', true)->whereHas('exercises')
                    ->whereHas('blueprint', fn ($q) => $q->where('exam_id', $exam->id)->where('level', $user->profile?->current_level ?? 'A1'))->exists(),
        ]);
    }

    /**
     * "Pratiquer par type" : ouvre un exercice de ce type au niveau de l'apprenant.
     * Bouton "Autre exercice" rappelle cette route pour en obtenir un différent.
     *
     * L'ordre importe. On servait d'abord n'importe quel exercice existant du bon
     * niveau : dès qu'il y en avait un, il revenait à chaque clic — le vivier ne
     * grandissait plus, la génération n'était plus jamais appelée, et « Autre
     * exercice » ramenait les mêmes cinq questions indéfiniment. Refaire ce qu'on
     * connaît déjà n'apprend rien.
     *
     * On cherche donc d'abord ce que l'apprenant n'a pas encore fait — y compris la
     * série préparée, qui ne coûte aucun appel —, puis on génère du neuf, et on ne
     * rejoue un exercice déjà vu qu'en dernier ressort. La bibliothèque sans IA garde
     * ainsi son rôle : du contenu gratuit et immédiat tant qu'il reste inédit, un
     * plancher quand le fournisseur ne répond plus, jamais un substitut à la variété.
     */
    public function drillByType(Exam $exam, \App\Models\ExerciseType $exerciseType, \App\Services\AI\ExerciseGeneratorService $generator, \App\Services\Content\StarterPracticeLibrary $library, \App\Services\Content\ExerciseTypeSuitability $pertinence)
    {
        $user = auth()->user();
        $difficulty = $user->profile?->current_level ?? 'B1';
        $exerciseType->loadMissing('section');
        abort_unless($exerciseType->section?->exam_id === $exam->id, 404);

        // La galerie filtre deja, mais ce lien s'atteint aussi directement (favori,
        // ancienne adresse) : sans ce controle, un apprenant A1 recevait « decrivez
        // l'evolution du chomage en 150 mots ».
        if ($raison = $pertinence->raison($exerciseType, $difficulty)) {
            return redirect()->route('practice.section', [$exam->id, $exerciseType->section_id])
                ->with('error', $raison);
        }

        $pool = fn () => Exercise::where('exam_id', $exam->id)
            ->where('exercise_type_id', $exerciseType->id)
            ->where('difficulty', $difficulty)
            ->whereNull('center_id')
            ->whereNull('lesson_id')
            ->whereNull('node_id')
            ->whereNull('mock_exam_id');

        $alreadyDone = \App\Models\UserExerciseAttempt::where('user_id', $user->id)->pluck('exercise_id');

        // 1. Un exercice que cet apprenant n'a pas encore fait.
        $exercise = $pool()->whereNotIn('id', $alreadyDone)->inRandomOrder()->first();

        // 2. La série préparée, tant qu'elle lui est inédite : gratuite et immédiate,
        //    même quand le fournisseur d'IA n'a plus de quota.
        if (!$exercise) {
            $starter = $library->ensure($exam, $exerciseType, $difficulty);
            if ($starter && !$alreadyDone->contains($starter->id)) {
                $exercise = $starter;
            }
        }

        // 3. Tout a été fait : c'est le moment de produire du neuf.
        if (!$exercise) {
            try {
                $exam->loadMissing('language');
                $exercise = $generator->generate($exerciseType, $exam, $difficulty);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('drillByType generation failed, falling back', ['error' => $e->getMessage()]);
            }
        }

        // 4. Rien de neuf nulle part : mieux vaut refaire un exercice connu que
        //    renvoyer l'apprenant sur un message d'échec.
        $exercise ??= $pool()->inRandomOrder()->first();

        if (!$exercise) {
            return redirect()->route('practice.exam', $exam->id)
                ->with('error', "Impossible de préparer un exercice de ce type pour le moment. Réessaie.");
        }

        return redirect()->route('exercise.show', $exercise->id);
    }

    public function sectionDrills(Exam $exam, ExamSection $section, \App\Services\Content\StarterPracticeLibrary $library, \App\Services\Content\ExerciseTypeSuitability $pertinence): Response
    {
        abort_unless($section->exam_id === $exam->id, 404);
        $section->load('exerciseTypes');
        $exam->load('language');
        $learnerLevel = auth()->user()->profile?->current_level ?? 'A1';
        $beginner = in_array($learnerLevel, ['A0', 'A1', 'A2'], true);

        // Galerie : les TYPES d'exercices de cette compétence. Cliquer un type →
        // drillByType (un exo au niveau du profil, biblio d'abord sinon généré).
        $exerciseTypes = $section->exerciseTypes
            // Une seule regle, partagee avec le lien direct : on ne propose pas un
            // format qui demande un niveau que l'apprenant n'a pas, ni un exercice
            // visuel qu'on ne saurait pas illustrer.
            ->filter(fn ($t) => $pertinence->convient($t, $learnerLevel))
            ->when($beginner && in_array($section->skill_type, ['listening', 'speaking'], true), fn ($types) => $types->filter(fn ($type) => in_array($type->component_key, ['mcq', 'gap-fill', 'matching', 'sentence-completion', 'short-answer', 'dictation', 'listen-repeat', 'speaking-recorder', 'build-a-sentence'], true))
                ->reject(fn ($type) => $section->skill_type === 'listening' && $type->component_key === 'matching')
                ->sortByDesc(fn ($type) => $type->slug === 'guided-introduction')
                ->unique('component_key')->take(3))
            ->unique('id')
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $beginner ? (['mcq' => 'Choisir la bonne réponse', 'gap-fill' => 'Compléter les mots', 'sentence-completion' => 'Compléter une phrase', 'speaking-recorder' => 'Répondre à voix haute', 'listen-repeat' => 'Écouter et répéter', 'matching' => 'Associer les mots', 'short-answer' => 'Répondre en quelques mots', 'dictation' => 'Écrire ce que tu entends', 'build-a-sentence' => 'Construire une phrase'][$t->component_key] ?? $t->name) : $t->name,
                'skill_type' => $t->skill_type,
                'component_key' => $t->component_key,
                'starter_available' => $library->template($exam, $t, auth()->user()->profile?->current_level ?? 'B1') !== null,
            ])
            ->values();

        return Inertia::render('practice/section-drills', [
            'exam' => $exam,
            'section' => $section,
            'exerciseTypes' => $exerciseTypes,
            'learnerLevel' => $learnerLevel,
        ]);
    }

    /**
     * Generate a few exercises for this section on the spot (the generator is no
     * longer a separate tool — it's embedded where it's needed).
     */
    public function generateSection(Exam $exam, ExamSection $section, \App\Services\AI\ExerciseGeneratorService $generator)
    {
        $section->load('exerciseTypes');
        $level = auth()->user()->profile?->current_level ?? 'B1';

        // Pick up to 3 varied types from this section and generate one each.
        $types = $section->exerciseTypes->shuffle()->take(3);
        foreach ($types as $type) {
            try {
                $generator->generate($type, $exam, $level);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Section generate failed', ['error' => $e->getMessage()]);
            }
        }

        return back()->with('success', 'Exercices générés !');
    }

    public function simulate(Exam $exam, Request $request): Response|\Illuminate\Http\RedirectResponse
    {
        $level = $request->user()->profile?->current_level ?? 'A1';
        $cefrLevels = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];
        $allowedLevels = array_slice($cefrLevels, 0, (array_search($level, $cefrLevels, true) ?: 0) + 1);
        $isBeginner = in_array($level, ['A0', 'A1', 'A2'], true);

        // Les epreuves blanches etaient ecrites a la main, presque toutes sans niveau :
        // un debutant etait renvoye sans rien. On en compose une a son niveau avec ce
        // qui existe deja, et on ne refuse que s'il n'y a vraiment pas de quoi.
        if ($isBeginner && ! MockExam::where('is_published', true)->whereHas('exercises')
            ->whereHas('blueprint', fn ($q) => $q->where('exam_id', $exam->id)->where('level', $level))->exists()) {
            $composee = app(\App\Services\Content\MockExamComposer::class)->pour($exam, $level);

            if (! $composee) {
                return redirect()->route('practice.exam', $exam)
                    ->with('error', "À ton niveau {$level}, commence par une compétence ou une séance de ton parcours. L’examen complet viendra plus tard.");
            }
        }
        $exam->load(['language', 'sections' => fn ($q) => $q->where('slug', '!=', 'level-assessment')->with('exerciseTypes')]);

        // Try to load a specific mock exam, or pick a random one for this exam
        $mockExamId = $request->query('mock_exam_id');

        $mockExam = MockExam::whereHas('blueprint', fn ($q) => $q->where('exam_id', $exam->id))
            ->whereHas('blueprint', fn ($q) => $q->where(fn ($levels) => $levels->whereNull('level')->orWhereIn('level', $allowedLevels)))
            ->when($isBeginner, fn ($q) => $q->whereHas('blueprint', fn ($blueprint) => $blueprint->where('level', $level))->whereHas('exercises'))
            ->where('is_published', true)
            ->when($mockExamId, fn ($q) => $q->whereKey($mockExamId), fn ($q) => $q->inRandomOrder())
            ->first();

        if ($isBeginner && ! $mockExam) {
            return redirect()->route('practice.exam', $exam)->with('error', 'Choisis une épreuve préparée à ton niveau, ou continue ton parcours.');
        }

        $totalTime = $mockExam?->blueprint?->total_duration_minutes
            ?? $exam->sections->sum(fn ($s) => $s->time_limit ?? 30);

        if ($mockExam) {
            // Load ALL exercises belonging to this mock exam, ordered by section
            $orderedExercises = Exercise::where('mock_exam_id', $mockExam->id)
                ->with('exerciseType')
                ->get()
                ->sortBy(fn ($ex) => $ex->exam_section_id);
        } else {
            // Fallback: pick random exercises per type (legacy behavior)
            $orderedExercises = collect();
            foreach ($exam->sections as $section) {
                foreach ($section->exerciseTypes as $type) {
                    $exercises = Exercise::where('exam_id', $exam->id)
                        ->whereIn('difficulty', $allowedLevels)
                        ->where('exercise_type_id', $type->id)
                        // General starter practice, private center or lesson content and
                        // other mock exams never join an open simulation.
                        ->whereNull('catalog_key')
                        ->whereNull('center_id')
                        ->whereNull('lesson_id')
                        ->whereNull('mock_exam_id')
                        ->with('exerciseType')
                        ->inRandomOrder()
                        ->limit(2)
                        ->get();
                    $orderedExercises = $orderedExercises->concat($exercises);
                }
            }
        }

        // List available mock exams for this exam (for the selector UI)
        $availableMockExams = MockExam::whereHas('blueprint', fn ($q) => $q->where('exam_id', $exam->id))
            ->whereHas('blueprint', fn ($q) => $q->where(fn ($levels) => $levels->whereNull('level')->orWhereIn('level', $allowedLevels)))
            ->when($isBeginner, fn ($q) => $q->whereHas('blueprint', fn ($blueprint) => $blueprint->where('level', $level))->whereHas('exercises'))
            ->where('is_published', true)
            ->withCount('exercises')
            ->get(['id', 'title', 'description']);

        // The submission is scored once, against exactly the set served here.
        $key = $this->simulationKey($request->user(), $exam);
        \Illuminate\Support\Facades\Cache::put($key, $orderedExercises->pluck('id')->values()->all(), now()->addHours(6));
        \Illuminate\Support\Facades\Cache::forget("{$key}:result");

        return Inertia::render('practice/exam-simulator', [
            'exam' => $exam,
            'exercises' => $orderedExercises->values(),
            'totalExamsTime' => $totalTime,
            'mockExam' => $mockExam,
            'availableMockExams' => $availableMockExams,
        ]);
    }

    public function submitSimulation(Request $request, Exam $exam)
    {
        $validated = $request->validate([
            'answers_by_exercise' => 'required_without:answers|array',
            'answers' => 'required_without:answers_by_exercise|array',
            'time_spent' => 'required|integer|min:0',
        ]);

        $user = $request->user();
        $key = $this->simulationKey($user, $exam);
        $lock = \Illuminate\Support\Facades\Cache::lock("{$key}:submit", 120);
        if (!$lock->block(20)) {
            return redirect()->route('dashboard')
                ->with('error', 'Ton examen blanc est encore en cours de correction. Consulte tes résultats dans un instant.');
        }

        try {
            $servedIds = \Illuminate\Support\Facades\Cache::pull($key);
            if (!is_array($servedIds) || $servedIds === []) {
                // A repeated POST receives the summary of the submission already recorded.
                if ($summary = \Illuminate\Support\Facades\Cache::get("{$key}:result")) {
                    return redirect()->route('dashboard')->with('success', $summary);
                }

                return redirect()->route('practice.simulate', $exam)
                    ->with('error', 'Cette simulation a expiré. Lance un nouvel examen blanc.');
            }

            $answersByExercise = $validated['answers_by_exercise'] ?? null;
            $totalXp = 0;
            $totalAccuracy = 0;
            $exerciseCount = 0;
            $untouchedCount = 0;

            $exercises = Exercise::whereIn('id', $servedIds)
                ->with(['exerciseType', 'exam.language'])
                ->get();

            foreach ($exercises as $exercise) {
                // Exercises may reuse question ids (q1, q2…), so answers stay grouped per exercise.
                $sourceAnswers = $answersByExercise === null
                    ? $validated['answers']
                    : ($answersByExercise[$exercise->id] ?? []);

                $exerciseAnswers = [];
                foreach ($exercise->questions as $index => $question) {
                    $qId = $question['id'] ?? (string) $index;
                    if (is_array($sourceAnswers) && isset($sourceAnswers[$qId])) {
                        $exerciseAnswers[$qId] = $sourceAnswers[$qId];
                    }
                }

                // Une partie laissée entièrement vide était écartée du calcul : la
                // moyenne ne portait que sur ce que l'apprenant avait bien voulu
                // traiter. Un examen blanc à moitié rempli annonçait « 95 % » — le
                // contraire de ce qu'on attend d'une épreuve d'entraînement, qui sert
                // justement à savoir où l'on en est. On la compte comme non traitée.
                if ($exerciseAnswers === []) {
                    $untouchedCount++;
                    continue;
                }

                $result = $this->scoringService->score($exercise, $exerciseAnswers);

                \App\Models\UserExerciseAttempt::create([
                    'user_id' => $user->id,
                    'exercise_id' => $exercise->id,
                    'answers' => $exerciseAnswers,
                    'score' => $result['score'],
                    'accuracy_percent' => $result['accuracy'],
                    'time_spent' => 0, // Split time is hard to track perfectly here
                    'xp_earned' => $result['xp'],
                    'feedback' => $result['feedback'],
                ]);

                $totalXp += $result['xp'];
                $totalAccuracy += $result['accuracy'];
                $exerciseCount++;
            }

            if ($exerciseCount === 0) {
                return redirect()->route('practice.simulate', $exam)
                    ->with('error', 'Aucune réponse de cet examen blanc n’a pu être associée aux questions servies. Lance un nouvel essai.');
            }

            if ($user->profile) {
                $user->profile->increment('xp_total', $totalXp);
            }

            $avgAccuracy = round($totalAccuracy / $exerciseCount);
            $summary = "Examen blanc terminé ! Précision moyenne : {$avgAccuracy}% (+{$totalXp} XP)";

            if ($untouchedCount > 0) {
                // Le chiffre qui compte pour une épreuve : les parties non traitées
                // valent zéro, comme le jour de l'examen.
                $servedCount = $exerciseCount + $untouchedCount;
                $examAccuracy = round($totalAccuracy / $servedCount);
                $summary = "Examen blanc terminé. Sur l'ensemble de l'épreuve : {$examAccuracy}%"
                    . " — {$untouchedCount} partie(s) sur {$servedCount} sont restées vides."
                    . " Sur les parties traitées seules : {$avgAccuracy}% (+{$totalXp} XP)";
            }
            \Illuminate\Support\Facades\Cache::put("{$key}:result", $summary, now()->addMinutes(10));

            return redirect()->route('dashboard')->with('success', $summary);
        } finally {
            $lock->release();
        }
    }

    private function simulationKey(\App\Models\User $user, Exam $exam): string
    {
        return "practice-simulation:{$user->id}:{$exam->id}";
    }
}
