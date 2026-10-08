<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\User;
use App\Services\Content\StarterPracticeLibrary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Packs the learner downloads to practise without a connection. Corrections travel
 * with the pack, so a pack is training material a phone can inspect — never a
 * secured test, and the score it computes stays a local indication.
 */
class OfflinePackController extends Controller
{
    public function index(Request $request, StarterPracticeLibrary $library): JsonResponse
    {
        $user = $request->user();
        $profile = $user->profile?->loadMissing('targetExam.language');
        $exam = $profile?->targetExam;
        $level = $profile?->current_level ?? 'A1';

        if (!$exam) {
            return response()->json(['user' => $this->owner($user), 'level' => $level, 'packs' => []]);
        }

        $exam->loadMissing('sections.exerciseTypes');
        $packs = [];

        foreach ($exam->sections as $section) {
            foreach ($section->exerciseTypes as $type) {
                $template = $library->template($exam, $type, $level);
                if (!$template) {
                    continue;
                }

                $packs[] = [
                    'exam_id' => $exam->id,
                    'exam_name' => $exam->name,
                    'exercise_type_id' => $type->id,
                    'title' => $template['title'],
                    'description' => $template['description'],
                    'format' => $type->component_key,
                    'format_label' => $type->name,
                    'level' => $level,
                    'questions_count' => count($template['questions']),
                ];
            }
        }

        return response()->json([
            'user' => $this->owner($user),
            'level' => $level,
            'packs' => $packs,
        ]);
    }

    public function store(Request $request, Exam $exam, ExerciseType $exerciseType, StarterPracticeLibrary $library): JsonResponse
    {
        $user = $request->user();
        $exerciseType->loadMissing('section');
        abort_unless($exerciseType->section?->exam_id === $exam->id, 404);

        $level = $user->profile?->current_level ?? 'A1';
        $exercise = $library->ensure($exam, $exerciseType, $level);

        if (!$exercise) {
            return response()->json([
                'message' => "Aucun pack n'est disponible pour ce format à ton niveau.",
            ], 404);
        }

        // A downloaded pack never widens what the learner may already open online.
        $this->authorize('view', $exercise);

        return response()->json($this->pack($exercise, $user, $exam, $exerciseType));
    }

    private function pack(Exercise $exercise, User $user, Exam $exam, ExerciseType $type): array
    {
        $exam->loadMissing('language');
        $content = $exercise->content ?? [];

        return [
            'pack_id' => $exercise->catalog_key,
            'pack_version' => 1,
            'exercise_id' => $exercise->id,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'title' => $content['title'] ?? $type->name,
            'description' => $content['description'] ?? '',
            'instructions' => $content['instructions'] ?? '',
            'passage' => $content['text'] ?? $content['passage'] ?? null,
            'source_label' => $content['source_label'] ?? null,
            'exam_id' => $exam->id,
            'exam_name' => $exam->name,
            'language' => $exam->language?->slug,
            'level' => $exercise->difficulty,
            'format' => $type->component_key,
            'format_label' => $type->name,
            'questions' => array_map(fn (array $question) => [
                'id' => $question['id'],
                'type' => $question['type'] ?? $type->component_key,
                'text' => $question['text'] ?? '',
                'options' => $question['options'] ?? null,
                'correct_answer' => $question['correct_answer'] ?? null,
                'explanation' => $question['explanation'] ?? '',
            // Hors ligne, personne ne pourra reparer une question impossible : on ne
            // telecharge que celles auxquelles on peut repondre.
            ], $exercise->answerableQuestions()),
            'downloaded_at' => now()->toIso8601String(),
        ];
    }

    private function owner(User $user): array
    {
        return ['id' => $user->id, 'name' => $user->name];
    }
}
