<?php

namespace App\Policies;

use App\Models\Exercise;
use App\Models\User;

class ExercisePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isSuperAdmin() ? true : null;
    }

    public function create(User $user): bool
    {
        return $user->isCenterStaff();
    }

    /** Only staff of the owning center may edit center content. */
    public function update(User $user, Exercise $exercise): bool
    {
        return $exercise->center_id !== null
            && $user->isCenterStaff()
            && $user->center()?->id === $exercise->center_id;
    }

    public function delete(User $user, Exercise $exercise): bool
    {
        return $this->update($user, $exercise);
    }
}
