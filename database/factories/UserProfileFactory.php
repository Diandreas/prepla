<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserProfile>
 */
class UserProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'current_level' => 'A1',
            'interface_language' => 'fr',
            'native_language' => 'fr',
            'xp_total' => 0,
            'streak_current' => 0,
        ];
    }
}
