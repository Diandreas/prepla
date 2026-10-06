<?php

namespace App\Services\Content;

use App\Models\{Exercise, User};

class PracticeAvailability
{
    public function forUser(?User $user): array
    {
        $exam = $user?->profile?->targetExam;
        $available = ['speaking' => false, 'listening' => false];
        if (!$exam) {
            return $available;
        }
        $level = $user->profile->current_level ?? 'A1';
        foreach (array_keys($available) as $skill) {
            $available[$skill] = Exercise::where('exam_id', $exam->id)->where('difficulty', $level)
                ->whereJsonLength('questions', '>', 0)
                ->whereNull('center_id')->whereNull('lesson_id')->whereNull('node_id')->whereNull('mock_exam_id')
                ->whereHas('exerciseType', fn ($type) => $type->where('skill_type', $skill)
                    ->when(in_array($level, ['A0', 'A1', 'A2'], true), fn ($query) => $query->whereIn('component_key', ['mcq', 'gap-fill', 'sentence-completion', 'short-answer', 'dictation', 'listen-repeat', 'speaking-recorder', 'build-a-sentence']))
                    ->whereHas('section', fn ($section) => $section->where('exam_id', $exam->id)->where('skill_type', $skill)))
                ->exists();
        }
        // These prepared oral tasks can be installed immediately, without AI.
        $available['speaking'] = $available['speaking'] || (in_array($level, ['A0', 'A1', 'A2'], true)
            && in_array($exam->language?->slug, ['english', 'french', 'german'], true));
        return $available;
    }
}
