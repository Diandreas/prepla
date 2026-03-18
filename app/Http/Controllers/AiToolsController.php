<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExerciseType;
use App\Services\AI\ExerciseGeneratorService;
use App\Services\AI\WritingCorrectorService;
use App\Services\AI\ExplainerService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AiToolsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('ai-tools/index');
    }

    public function generator(): Response
    {
        $exams = Exam::with(['language', 'sections.exerciseTypes'])->get();

        return Inertia::render('ai-tools/generator', [
            'exams' => $exams,
        ]);
    }

    public function generateExercise(Request $request, ExerciseGeneratorService $generator)
    {
        $validated = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'exercise_type_id' => 'required|exists:exercise_types,id',
            'difficulty' => 'required|in:A1,A2,B1,B2,C1,C2',
        ]);

        $exerciseType = ExerciseType::findOrFail($validated['exercise_type_id']);
        $exam = Exam::findOrFail($validated['exam_id']);

        $exercise = $generator->generate($exerciseType, $exam, $validated['difficulty']);

        return redirect()->route('exercise.show', $exercise);
    }

    public function writingCorrector(): Response
    {
        return Inertia::render('ai-tools/writing-corrector');
    }

    public function submitWriting(Request $request, WritingCorrectorService $corrector)
    {
        $validated = $request->validate([
            'text' => 'required|string|min:10',
            'task_description' => 'nullable|string',
        ]);

        $result = $corrector->correct(
            $validated['text'],
            $validated['task_description'] ?? '',
        );

        return back()->with('correction', $result);
    }

    public function explainer(): Response
    {
        return Inertia::render('ai-tools/explainer');
    }

    public function recommendations(): Response
    {
        $profile = auth()->user()->profile?->load('targetExam.language');
        $recentAttempts = auth()->user()->exerciseAttempts()
            ->with('exercise.exerciseType')
            ->latest()
            ->take(10)
            ->get();

        return Inertia::render('ai-tools/recommendations', [
            'profile' => $profile,
            'recentAttempts' => $recentAttempts,
        ]);
    }
}
