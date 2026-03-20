<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\LearningPathNode;
use App\Models\UserLearningProgress;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PracticeController extends Controller
{
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

        // Si pas d'exercices liés au nœud, on en pioche des génériques par difficulté et type
        if ($exercises->count() < 3) {
            $exercises = Exercise::where('exam_id', $node->exam_id)
                ->where('difficulty', $node->level)
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
        $exams = Exam::with('language')->get();
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

    public function sectionDrills(Exam $exam, ExamSection $section): Response
    {
        $section->load('exerciseTypes');
        $exam->load('language');

        $exercises = \App\Models\Exercise::where('exam_id', $exam->id)
            ->whereIn('exercise_type_id', $section->exerciseTypes->pluck('id'))
            ->get()
            ->groupBy('difficulty');

        return Inertia::render('practice/section-drills', [
            'exam' => $exam,
            'section' => $section,
            'exercisesByDifficulty' => $exercises,
        ]);
    }
}
