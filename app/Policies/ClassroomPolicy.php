<?php

namespace App\Policies;

use App\Models\Classroom;
use App\Models\User;

class ClassroomPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isSuperAdmin() ? true : null;
    }

    /** Staff of the same center can view a classroom. */
    public function view(User $user, Classroom $classroom): bool
    {
        return $this->sameCenter($user, $classroom);
    }

    public function create(User $user): bool
    {
        return $user->isCenterStaff();
    }

    /**
     * center_admin manages any classroom of the center; a teacher only those
     * they are attached to.
     */
    public function update(User $user, Classroom $classroom): bool
    {
        if (! $this->sameCenter($user, $classroom)) {
            return false;
        }

        if ($user->centerRoleIs('center_admin')) {
            return true;
        }

        return $classroom->teachers()->whereKey($user->id)->exists();
    }

    public function delete(User $user, Classroom $classroom): bool
    {
        return $this->sameCenter($user, $classroom) && $user->centerRoleIs('center_admin');
    }

    protected function sameCenter(User $user, Classroom $classroom): bool
    {
        return $user->center()?->id === $classroom->center_id;
    }
}
