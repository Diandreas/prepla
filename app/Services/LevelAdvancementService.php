<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\LevelAssessment;
use App\Models\UserExerciseAttempt;
use App\Models\UserProfile;

class LevelAdvancementService
{
    private const CEFR_ORDER = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

    public function assessAfterBossNode(int $userId, int $examId, float $score): ?string
    {
        $profile = UserProfile::where('user_id', $userId)->first();
        if (!$profile) {
            return null;
        }

        $currentLevel = $profile->current_level ?? 'A1';
        $threshold = 70; // 70% to advance

        if ($score < $threshold) {
            return null;
        }

        $nextLevel = $this->getNextLevel($currentLevel);
        if (!$nextLevel) {
            return null; // Already at C2
        }

        // Record assessment
        LevelAssessment::create([
            'user_id' => $userId,
            'exam_id' => $examId,
            'assessed_level' => $nextLevel,
            'previous_level' => $currentLevel,
            'assessment_type' => 'boss_test',
            'score_details' => ['score' => $score, 'threshold' => $threshold],
            'assessed_at' => now(),
        ]);

        // Update user profile
        $profile->update(['current_level' => $nextLevel]);

        return $nextLevel;
    }

    public function shouldSuggestAssessment(int $userId, int $examId): bool
    {
        $recentAttempts = UserExerciseAttempt::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit(30)
            ->pluck('accuracy_percent');

        if ($recentAttempts->count() < 10) {
            return false;
        }

        return $recentAttempts->avg() >= 85;
    }

    private function getNextLevel(string $current): ?string
    {
        $index = array_search($current, self::CEFR_ORDER);
        if ($index === false || $index >= count(self::CEFR_ORDER) - 1) {
            return null;
        }
        return self::CEFR_ORDER[$index + 1];
    }
}
