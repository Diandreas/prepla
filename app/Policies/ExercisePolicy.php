<?php

namespace App\Policies;

use App\Models\AssignmentItem;
use App\Models\Exercise;
use App\Models\User;

class ExercisePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isSuperAdmin() ? true : null;
    }

    /** Center content reaches its staff and the students it was assigned to; lesson practice stays with its learner. */
    public function view(User $user, Exercise $exercise): bool
    {
        if ($exercise->center_id !== null) {
            if ($user->centers()->whereKey($exercise->center_id)->wherePivotIn('role', ['center_admin', 'teacher'])->exists()) {
                return true;
            }

            return AssignmentItem::where('itemable_type', Exercise::class)
                ->where('itemable_id', $exercise->id)
                ->whereHas('assignment', fn ($query) => $query
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now())
                    ->whereIn('classroom_id', $user->classrooms()->pluck('classrooms.id')))
                ->exists();
        }

        if ($exercise->lesson_id !== null) {
            return $exercise->lesson()->where('user_id', $user->id)->exists();
        }

        return true;
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
