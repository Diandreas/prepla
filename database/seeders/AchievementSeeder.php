<?php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    public function run(): void
    {
        $achievements = [
            [
                'slug' => 'first-exercise',
                'name' => 'First Steps',
                'description' => 'Complete your first exercise',
                'icon' => 'trophy',
                'xp_reward' => 10,
                'condition_type' => 'exercises_completed',
                'condition_value' => ['count' => 1],
            ],
            [
                'slug' => 'ten-exercises',
                'name' => 'Getting Started',
                'description' => 'Complete 10 exercises',
                'icon' => 'star',
                'xp_reward' => 50,
                'condition_type' => 'exercises_completed',
                'condition_value' => ['count' => 10],
            ],
            [
                'slug' => 'fifty-exercises',
                'name' => 'Dedicated Learner',
                'description' => 'Complete 50 exercises',
                'icon' => 'medal',
                'xp_reward' => 200,
                'condition_type' => 'exercises_completed',
                'condition_value' => ['count' => 50],
            ],
            [
                'slug' => 'hundred-exercises',
                'name' => 'Century Club',
                'description' => 'Complete 100 exercises',
                'icon' => 'crown',
                'xp_reward' => 500,
                'condition_type' => 'exercises_completed',
                'condition_value' => ['count' => 100],
            ],
            [
                'slug' => 'streak-3',
                'name' => 'On a Roll',
                'description' => 'Maintain a 3-day streak',
                'icon' => 'flame',
                'xp_reward' => 30,
                'condition_type' => 'streak',
                'condition_value' => ['days' => 3],
            ],
            [
                'slug' => 'streak-7',
                'name' => 'Week Warrior',
                'description' => 'Maintain a 7-day streak',
                'icon' => 'flame',
                'xp_reward' => 100,
                'condition_type' => 'streak',
                'condition_value' => ['days' => 7],
            ],
            [
                'slug' => 'streak-30',
                'name' => 'Monthly Master',
                'description' => 'Maintain a 30-day streak',
                'icon' => 'flame',
                'xp_reward' => 500,
                'condition_type' => 'streak',
                'condition_value' => ['days' => 30],
            ],
            [
                'slug' => 'perfect-score',
                'name' => 'Perfectionist',
                'description' => 'Get 100% on any exercise',
                'icon' => 'check-circle',
                'xp_reward' => 50,
                'condition_type' => 'perfect_score',
                'condition_value' => ['count' => 1],
            ],
            [
                'slug' => 'xp-1000',
                'name' => 'XP Hunter',
                'description' => 'Earn 1,000 XP total',
                'icon' => 'zap',
                'xp_reward' => 100,
                'condition_type' => 'xp_total',
                'condition_value' => ['amount' => 1000],
            ],
            [
                'slug' => 'xp-10000',
                'name' => 'XP Legend',
                'description' => 'Earn 10,000 XP total',
                'icon' => 'zap',
                'xp_reward' => 500,
                'condition_type' => 'xp_total',
                'condition_value' => ['amount' => 10000],
            ],
            [
                'slug' => 'all-skills',
                'name' => 'Well Rounded',
                'description' => 'Complete exercises in all 4 skill types',
                'icon' => 'target',
                'xp_reward' => 100,
                'condition_type' => 'all_skills',
                'condition_value' => ['skills' => ['reading', 'listening', 'writing', 'speaking']],
            ],
            [
                'slug' => 'speed-demon',
                'name' => 'Speed Demon',
                'description' => 'Complete an exercise in under 2 minutes with 80%+ accuracy',
                'icon' => 'timer',
                'xp_reward' => 75,
                'condition_type' => 'speed',
                'condition_value' => ['max_seconds' => 120, 'min_accuracy' => 80],
            ],
        ];

        foreach ($achievements as $achievement) {
            Achievement::updateOrCreate(
                ['slug' => $achievement['slug']],
                $achievement
            );
        }
    }
}
