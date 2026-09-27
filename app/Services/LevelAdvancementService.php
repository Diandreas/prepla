<?php

namespace App\Services;

use App\Models\CurriculumSkeleton;
use App\Models\Exam;
use App\Models\LevelAssessment;
use App\Models\UserExerciseAttempt;
use App\Models\UserProfile;

class LevelAdvancementService
{
    private const CEFR_ORDER = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

    /** Précision moyenne exigée sur les dernières séances pour monter d'un cran. */
    public const ADVANCE_THRESHOLD = 70;

    /**
     * Monte l'apprenant d'un niveau quand il a bouclé tout son niveau courant.
     *
     * Jusqu'ici, rien n'appelait ce service : la table des évaluations est restée
     * vide depuis l'ouverture, et personne n'a jamais changé de niveau après son
     * test de positionnement. Un apprenant pouvait finir un parcours entier, des
     * pronoms de base à la fluidité avancée, en restant marqué A1.
     *
     * Règle retenue : tous les objectifs de son niveau sont terminés, et ses
     * dernières séances tiennent la moyenne exigée.
     */
    public function assessAfterObjective(int $userId, CurriculumSkeleton $skeleton): ?string
    {
        $profile = UserProfile::where('user_id', $userId)->first();
        if (!$profile) {
            return null;
        }

        $currentLevel = $profile->current_level ?? 'A1';
        $nextLevel = $this->getNextLevel($currentLevel);
        if (!$nextLevel) {
            return null; // déjà au sommet de l'échelle
        }

        $objectives = $skeleton->objectives ?? [];
        $foundAtLevel = false;

        foreach ($objectives as $index => $objective) {
            if ($skeleton->levelForObjective($index, $currentLevel) !== $currentLevel) {
                continue;
            }

            $foundAtLevel = true;
            if (($objective['status'] ?? 'pending') !== 'done') {
                return null; // il reste du travail à ce niveau
            }
        }

        if (!$foundAtLevel) {
            return null;
        }

        $recent = UserExerciseAttempt::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit(20)
            ->pluck('accuracy_percent');

        // Trop peu de séances pour juger : on ne promeut pas sur un coup de chance.
        if ($recent->count() < 5 || $recent->avg() < self::ADVANCE_THRESHOLD) {
            return null;
        }

        LevelAssessment::create([
            'user_id' => $userId,
            'exam_id' => $skeleton->exam_id,
            'assessed_level' => $nextLevel,
            'previous_level' => $currentLevel,
            'assessment_type' => 'curriculum',
            'score_details' => [
                'average_accuracy' => round($recent->avg(), 1),
                'threshold' => self::ADVANCE_THRESHOLD,
                'sessions_considered' => $recent->count(),
            ],
            'assessed_at' => now(),
        ]);

        $profile->update(['current_level' => $nextLevel]);

        return $nextLevel;
    }

    public function assessAfterBossNode(int $userId, int $examId, float $score): ?string
    {
        $profile = UserProfile::where('user_id', $userId)->first();
        if (!$profile) {
            return null;
        }

        $currentLevel = $profile->current_level ?? 'A1';
        $threshold = self::ADVANCE_THRESHOLD;

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
