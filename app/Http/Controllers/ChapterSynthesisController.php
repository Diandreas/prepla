<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\LearningPathNode;
use App\Models\Lesson;
use App\Services\AI\ExerciseGeneratorService;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Boss-level chapter synthesis.
 *
 * Triggered when the user has completed every node of a chapter at mastery (≥80%).
 * Generates a 5-question mixed-format exercise that pulls concepts from ALL the
 * lessons in the chapter, forcing retrieval from long-term memory.
 *
 * This implements the "End of chapter: synthesis boss" step of the canonical
 * learning loop documented in docs/pedagogy.md.
 */
class ChapterSynthesisController extends Controller
{
    public function start(int $chapterOrder, ExerciseGeneratorService $generator): Response
    {
        $user = auth()->user();
        $profile = $user->profile?->load('targetExam.language');
        if (!$profile?->targetExam) {
            return Inertia::render('errors/access-denied', ['message' => 'Examen non défini.']);
        }

        $exam = $profile->targetExam;

        // 1. Locate the chapter's nodes
        $chapterNodes = LearningPathNode::where('exam_id', $exam->id)
            ->where('chapter_order', $chapterOrder)
            ->orderBy('sort_order')
            ->get();

        if ($chapterNodes->isEmpty()) {
            return Inertia::render('errors/access-denied', ['message' => 'Chapitre introuvable.']);
        }

        // 2. Collect concepts from the lessons within this chapter
        $lessonConcepts = Lesson::whereIn('node_id', $chapterNodes->pluck('id'))
            ->whereNotNull('concept')
            ->pluck('concept')
            ->unique()
            ->values()
            ->all();

        // 3. Reuse or create a synthesis-typed exercise for this chapter
        $synthesisExerciseTypeSlug = 'mcq'; // start with MCQ for simplicity; future: multiple component types
        $exerciseType = ExerciseType::where('exam_id', $exam->id)
            ->where('component_key', $synthesisExerciseTypeSlug)
            ->first();
        if (!$exerciseType) {
            $exerciseType = ExerciseType::where('component_key', $synthesisExerciseTypeSlug)->first();
        }
        if (!$exerciseType) {
            return Inertia::render('errors/access-denied', ['message' => 'Type d\'exercice indisponible.']);
        }

        $cacheKey = "synthesis_chapter_{$exam->id}_{$chapterOrder}_{$profile->current_level}";
        $synthesisExercise = Exercise::where('exam_id', $exam->id)
            ->where('content->synthesis_key', $cacheKey)
            ->first();

        if (!$synthesisExercise) {
            // 4. Build a lesson context that lists all concepts to test together
            $lessonContext = [
                'title' => 'Synthèse du chapitre — ' . ($chapterNodes->first()->chapter_name ?? "Chapitre {$chapterOrder}"),
                'concept' => 'synthesis.' . implode('+', array_slice($lessonConcepts, 0, 5)),
                'native_language' => $profile->native_language ?? 'Français',
                'is_synthesis' => true,
                'concepts_to_mix' => $lessonConcepts,
            ];

            try {
                $synthesisExercise = $generator->generate($exerciseType, $exam, $profile->current_level ?? 'A1', $lessonContext);
                // Tag the exercise so we can find it next time
                $content = $synthesisExercise->content ?? [];
                $content['synthesis_key'] = $cacheKey;
                $content['chapter_order'] = $chapterOrder;
                $synthesisExercise->content = $content;
                $synthesisExercise->save();
            } catch (\Throwable $e) {
                Log::error('Chapter synthesis generation failed', ['error' => $e->getMessage()]);
                return Inertia::render('errors/access-denied', ['message' => 'Erreur lors de la génération de la synthèse.']);
            }
        }

        // 5. Render the player on a virtual node (the first node of the chapter
        // is used as a placeholder for routing — the player just needs *a* node).
        $virtualNode = $chapterNodes->first();
        $synthesisExercise->load(['exerciseType', 'exam.language']);

        return Inertia::render('exercises/player', [
            'node' => $virtualNode->load('exam.language'),
            'exercises' => collect([$synthesisExercise]),
            'progress' => null,
            'isSynthesis' => true,
            'chapterOrder' => $chapterOrder,
            'conceptsMixed' => $lessonConcepts,
        ]);
    }
}
