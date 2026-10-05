<?php

namespace App\Services;

use App\Models\CurriculumSkeleton;
use App\Models\LearningPathNode;
use App\Models\Lesson;
use App\Models\User;

class LearningJourneyService
{
    public function nextAction(User $user): array
    {
        $path = CurriculumSkeleton::where('user_id', $user->id)
            ->where('exam_id', $user->profile?->target_exam_id)->first();
        if (! $path) {
            return ['kind' => 'onboarding', 'title' => 'Choisir mon objectif', 'description' => 'Commençons par la langue et l’examen que tu prépares.', 'url' => route('onboarding.exam')];
        }
        if ($exam = $path->pendingLevelExam()) {
            return ['kind' => 'exam', 'title' => 'Valider mon palier '.$exam['level'],
                'description' => 'Retrouve ce que tu as appris. Les notions à consolider seront reprises ensuite.',
                'url' => route('level.exam', $exam['level'])];
        }
        $objectives = $path->objectives ?? [];
        $index = $path->practiceObjectiveIndex();
        if ($index !== null) {
            $objective = $objectives[$index];
            $lesson = Lesson::where('user_id', $user->id)->where('skeleton_objective_index', $index)
                ->where('concept', $objective['concept'] ?? null)->first();
            $node = $lesson?->node ?? LearningPathNode::where('exam_id', $path->exam_id)
                ->where('title', $objective['title'])->first();

            return ['kind' => 'practice', 'title' => $lesson?->title ?? $objective['title'],
                'description' => 'Tu as terminé la leçon. Entraîne-toi à réutiliser ces notions dans des phrases.',
                'url' => $node ? route('node.start', $node) : ($lesson ? route('lessons.show', $lesson) : route('lessons.next'))];
        }
        $objective = $path->currentObjective();
        if ($path->isComplete()) {
            return ['kind' => 'continue', 'title' => 'La suite de mon parcours',
                'description' => 'Ton étape est terminée. Préparons la suivante au niveau que tu as validé.',
                'url' => route('lessons.next')];
        }

        return ['kind' => ($objective['is_remedial'] ?? false) ? 'remedial' : 'lesson',
            'title' => $objective['title'] ?? 'Ma prochaine mission',
            'description' => ($objective['is_remedial'] ?? false)
                ? 'Une explication ciblée et une courte pratique sur ce qui t’a posé problème.'
                : 'Quelques mots utiles, une explication et des exercices pour les utiliser.',
            'url' => route('lessons.next')];
    }
}
