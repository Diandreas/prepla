<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExerciseType;
use App\Services\AI\ExerciseGeneratorService;
use App\Services\AI\WritingCorrectorService;
use App\Services\AI\ExplainerService;
use App\Services\AI\MistralService;
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
        $profile = auth()->user()->profile;

        return Inertia::render('ai-tools/generator', [
            'exams' => $exams,
            'targetExamId' => $profile?->target_exam_id,
            'userLevel' => $profile?->current_level,
        ]);
    }

    public function generateExercise(Request $request, ExerciseGeneratorService $generator)
    {
        $validated = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'exercise_type_ids' => 'required|array',
            'exercise_type_ids.*' => 'exists:exercise_types,id',
            'difficulty' => 'required|in:A1,A2,B1,B2,C1,C2',
        ]);

        $exam = Exam::findOrFail($validated['exam_id']);
        $exercises = $generator->generateBatch($validated['exercise_type_ids'], $exam, $validated['difficulty']);

        // Load relationships for the results page
        foreach($exercises as $ex) {
            $ex->load('exerciseType');
        }

        return Inertia::render('ai-tools/batch-results', [
            'exercises' => $exercises,
            'exam' => $exam->load('language'),
            'difficulty' => $validated['difficulty'],
        ]);
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

        $nativeLanguage = auth()->user()->profile?->native_language ?: 'Français';

        $result = $corrector->correct(
            $validated['text'],
            $validated['task_description'] ?? '',
            'IELTS',
            $nativeLanguage,
        );

        // Keep the submitted text so the result page can render it with inline
        // highlights on each corrected span.
        $result['submitted_text'] = $validated['text'];

        return back()->with('correction', $result);
    }

    public function extractWritingImage(Request $request, MistralService $mistral)
    {
        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,jpg,png,webp|max:8192',
        ]);

        $file = $request->file('image');
        $dataUrl = 'data:' . $file->getMimeType() . ';base64,' . base64_encode(
            file_get_contents($file->getRealPath())
        );

        $text = $mistral->ocr($dataUrl);

        if ($text === null) {
            return response()->json([
                'error' => "Impossible de lire le texte sur l'image. Réessayez avec une photo plus nette et bien cadrée.",
            ], 422);
        }

        return response()->json(['text' => $text]);
    }

    public function explainer(): Response
    {
        return Inertia::render('ai-tools/explainer');
    }

    public function askExplainer(Request $request, ExplainerService $explainer)
    {
        $validated = $request->validate([
            'messages' => 'required|array',
            'messages.*.role' => 'required|in:user,assistant,system',
            'messages.*.content' => 'required|string',
        ]);

        $response = $explainer->chat($validated['messages']);

        return response()->json(['reply' => $response]);
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
