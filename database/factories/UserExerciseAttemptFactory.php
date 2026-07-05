<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserExerciseAttempt>
 */
class UserExerciseAttemptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'exercise_id' => null,
            'answers' => [],
            'score' => 100,
            'accuracy_percent' => 100,
            'time_spent' => 60,
            'xp_earned' => 10,
            'feedback' => [],
        ];
    }
}
