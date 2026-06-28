<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\User;

class AssignmentPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isSuperAdmin() ? true : null;
    }

    public function view(User $user, Assignment $assignment): bool
    {
        return $this->canManageClassroom($user, $assignment->classroom);
    }

    public function create(User $user): bool
    {
        return $user->isCenterStaff();
    }

    public function update(User $user, Assignment $assignment): bool
    {
        return $this->canManageClassroom($user, $assignment->classroom);
    }

    public function delete(User $user, Assignment $assignment): bool
    {
        return $this->canManageClassroom($user, $assignment->classroom);
    }

    /** Same rule as managing the underlying classroom. */
    protected function canManageClassroom(User $user, ?Classroom $classroom): bool
    {
        if (! $classroom || $user->center()?->id !== $classroom->center_id) {
            return false;
        }

        if ($user->centerRoleIs('center_admin')) {
            return true;
        }

        return $classroom->teachers()->whereKey($user->id)->exists();
    }
}
