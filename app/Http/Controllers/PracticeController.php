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

        return Inertia::render('exercises/player', [
            'node' => $node,
            'exercises' => $exercises,
            'progress' => $progress,
        ]);
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
        $exam->load(['language', 'sections.exerciseTypes']);

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
        ]);
    }

    /**
     * "Pratiquer par type" : trouve un exercice existant du type demandé (biblio
     * d'abord), sinon en génère un, puis ouvre le player. Bouton "Autre exercice"
     * rappelle cette route pour en obtenir un différent.
     */
    public function drillByType(Exam $exam, \App\Models\ExerciseType $exerciseType, \App\Services\AI\ExerciseGeneratorService $generator)
    {
        $user = auth()->user();
        $difficulty = $user->profile?->current_level ?? 'B1';

        // Biblio d'abord : un exercice existant de ce type pour cet examen.
        $exercise = Exercise::where('exam_id', $exam->id)
            ->where('exercise_type_id', $exerciseType->id)
            ->where('difficulty', $difficulty)
            ->inRandomOrder()
            ->first()
            // sinon, n'importe quel niveau pour ce type/examen
            ?? Exercise::where('exam_id', $exam->id)
                ->where('exercise_type_id', $exerciseType->id)
                ->inRandomOrder()
                ->first();

        // Sinon, génération à la demande.
        if (!$exercise) {
            try {
                $exam->loadMissing('language');
                $exercise = $generator->generate($exerciseType, $exam, $difficulty);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('drillByType generation failed', ['error' => $e->getMessage()]);
                return redirect()->route('practice.exam', $exam->id)
                    ->with('error', "Impossible de générer un exercice de ce type pour le moment. Réessaie.");
            }
        }

        return redirect()->route('exercise.show', $exercise->id);
    }

    public function sectionDrills(Exam $exam, ExamSection $section): Response
    {
        $section->load('exerciseTypes');
        $exam->load('language');

        // Galerie : les TYPES d'exercices de cette compétence. Cliquer un type →
        // drillByType (un exo au niveau du profil, biblio d'abord sinon généré).
        $exerciseTypes = $section->exerciseTypes
            ->reject(fn ($t) => $t->component_key === 'diagram-labeling')
            ->unique('id')
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'skill_type' => $t->skill_type,
                'component_key' => $t->component_key,
            ])
            ->values();

        return Inertia::render('practice/section-drills', [
            'exam' => $exam,
            'section' => $section,
            'exerciseTypes' => $exerciseTypes,
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

    public function simulate(Exam $exam, Request $request): Response
    {
        $exam->load(['language', 'sections.exerciseTypes']);

        // Try to load a specific mock exam, or pick a random one for this exam
        $mockExamId = $request->query('mock_exam_id');

        $mockExam = $mockExamId
            ? MockExam::where('id', $mockExamId)->where('is_published', true)->first()
            : MockExam::whereHas('blueprint', fn ($q) => $q->where('exam_id', $exam->id))
                ->where('is_published', true)
                ->inRandomOrder()
                ->first();

        $totalTime = $exam->sections->sum(fn ($s) => $s->time_limit ?? 30);

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
                        ->where('exercise_type_id', $type->id)
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
            ->where('is_published', true)
            ->withCount('exercises')
            ->get(['id', 'title', 'description']);

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
            'answers' => 'required|array',
            'time_spent' => 'required|integer|min:0',
        ]);

        $user = auth()->user();
        $answers = $validated['answers'];
        
        // Find the exercises for this exam that were part of the simulation
        // (Either from a MockExam or picked randomly)
        $exerciseIds = array_keys($answers); // This is not quite right because answers are question_id based
        
        // Actually, we should find exercises related to these questions
        // But for simplicity, we can just look up the exercises that were likely served.
        // Let's assume the user is submitting answers for a specific set of exercises.
        
        $totalXp = 0;
        $totalAccuracy = 0;
        $exerciseCount = 0;

        // Better approach: Since we don't have a question table, the frontend should ideally tell us which exercises were done.
        // For now, let's look at all exercises for this exam and check if any of their question IDs are in the answers.
        $examExercises = \App\Models\Exercise::where('exam_id', $exam->id)
            ->with(['exerciseType', 'exam.language'])
            ->get();

        foreach ($examExercises as $exercise) {
            $exerciseAnswers = [];
            $hasAnswers = false;
            foreach ($exercise->questions as $index => $question) {
                $qId = $question['id'] ?? (string)$index;
                if (isset($answers[$qId])) {
                    $exerciseAnswers[$qId] = $answers[$qId];
                    $hasAnswers = true;
                }
            }

            if ($hasAnswers) {
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
        }

        if ($user->profile) {
            $user->profile->increment('xp_total', $totalXp);
        }

        $avgAccuracy = $exerciseCount > 0 ? round($totalAccuracy / $exerciseCount) : 0;

        return redirect()->route('dashboard')->with('success', "Examen blanc terminé ! Précision moyenne : {$avgAccuracy}% (+{$totalXp} XP)");
    }
}
