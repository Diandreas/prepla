<?php

namespace App\Services;

use App\Models\UserError;

/**
 * Pilier 3: SM-2 spaced repetition for errors.
 * Replicates the algorithm from UserVocabulary::updateSpacedRepetition()
 * but applied to user errors for long-term retention.
 */
class ErrorSpacedRepetitionService
{
    /**
     * Schedule the next review for an error using SM-2.
     *
     * @param UserError $error
     * @param bool $correct Whether the user got it right this time
     */
    public function schedule(UserError $error, bool $correct): void
    {
        // Quality mapping: correct = 4 (good), incorrect = 1 (poor)
        $quality = $correct ? 4 : 1;

        $easeFactor = $error->ease_factor ?? 2.50;
        $intervalDays = $error->interval_days ?? 1;
        $reviewCount = $error->reviewed_count ?? 0;

        if ($quality >= 3) {
            // Correct answer — progress the interval
            if ($reviewCount === 0) {
                $intervalDays = 1;
            } elseif ($reviewCount === 1) {
                $intervalDays = 6;
            } else {
                $intervalDays = (int) round($intervalDays * $easeFactor);
            }
            $reviewCount++;
        } else {
            // Incorrect — reset to beginning
            $reviewCount = 0;
            $intervalDays = 1;
        }

        // Update ease factor (SM-2 formula)
        $easeFactor = max(1.3, $easeFactor + (0.1 - (5 - $quality) * (0.08 + (5 - $quality) * 0.02)));

        $error->ease_factor = $easeFactor;
        $error->interval_days = $intervalDays;
        $error->reviewed_count = $reviewCount;
        $error->next_review_at = now()->addDays($intervalDays);
        $error->last_reviewed_at = now();

        // Mark as mastered if reviewed correctly 5+ times
        if ($correct && $reviewCount >= 5) {
            $error->mastered = true;
        }

        $error->save();
    }

    /**
     * Initialize SM-2 scheduling for a new error.
     */
    public function initializeForNewError(UserError $error): void
    {
        $error->ease_factor = 2.50;
        $error->interval_days = 1;
        $error->next_review_at = now()->addDay();
        $error->save();
    }

    /**
     * Get errors due for review for a user, optionally filtered by skill type.
     */
    public function getDueErrors(int $userId, ?string $skillType = null, int $limit = 5): \Illuminate\Support\Collection
    {
        return UserError::dueForReview($userId)
            ->when($skillType, fn($q) => $q->where('skill_type', $skillType))
            ->limit($limit)
            ->get();
    }
}
