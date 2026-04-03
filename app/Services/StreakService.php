<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class StreakService
{
    /**
     * Records activity for a user and updates their streak.
     */
    public function recordActivity(User $user): array
    {
        $profile = $user->profile;
        if (!$profile) {
            return ['streak' => 0, 'incremented' => false];
        }

        $today = Carbon::today();
        $lastDate = $profile->streak_last_date ? Carbon::parse($profile->streak_last_date)->startOfDay() : null;

        // If already recorded today, do nothing
        if ($lastDate && $lastDate->equalTo($today)) {
            return ['streak' => $profile->streak_current, 'incremented' => false];
        }

        $incremented = false;
        if ($lastDate && $lastDate->equalTo($today->copy()->subDay())) {
            // Consecutive day
            $profile->increment('streak_current');
            $incremented = true;
        } else {
            // Streak broken or first time
            $profile->update(['streak_current' => 1]);
            $incremented = true;
        }

        $profile->update(['streak_last_date' => $today]);

        return [
            'streak' => $profile->streak_current,
            'incremented' => $incremented
        ];
    }
}
