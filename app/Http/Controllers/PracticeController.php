<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\ExerciseType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PracticeController extends Controller
{
    public function index(): Response
    {
        $profile = auth()->user()->profile;
        $exams = Exam::with('language')->get();

        return Inertia::render('practice/index', [
            'exams' => $exams,
            'targetExamId' => $profile?->target_exam_id,
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
