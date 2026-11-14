<?php

namespace App\Policies;

use App\Models\AssignmentItem;
use App\Models\ClassroomPathStep;
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

            // Un brouillon reste chez le personnel du centre.
            if ($exercise->estBrouillon()) {
                return false;
            }

            $classes = $user->classrooms()->pluck('classrooms.id');

            // Une etape du parcours construit par le centre pour l'une de ses classes.
            if (ClassroomPathStep::whereIn('classroom_id', $classes)->where('exercise_id', $exercise->id)->exists()) {
                return true;
            }

            return AssignmentItem::where('itemable_type', Exercise::class)
                ->where('itemable_id', $exercise->id)
                ->whereHas('assignment', fn ($query) => $query
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now())
                    ->whereIn('classroom_id', $classes))
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
