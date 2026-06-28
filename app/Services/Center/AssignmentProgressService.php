<?php

namespace App\Services\Center;

use App\Models\Assignment;
use App\Models\Exercise;
use App\Models\UserExerciseAttempt;

/**
 * Derives each student's progress on an assignment WITHOUT a dedicated
 * completion table: an exercise item is "done" when the student has a
 * UserExerciseAttempt on it created after the assignment was published.
 */
class AssignmentProgressService
{
    /**
     * @return array<int, array{user_id:int,name:string,email:string,done:int,total:int,status:string,avg_accuracy:?float}>
     */
    public function perStudent(Assignment $assignment): array
    {
        $assignment->loadMissing(['classroom.students', 'items']);

        // Exercise items only (lessons aren't auto-tracked via attempts here).
        $exerciseIds = $assignment->items
            ->where('itemable_type', Exercise::class)
            ->pluck('itemable_id')
            ->all();

        $total = count($exerciseIds);
        $publishedAt = $assignment->published_at;
        $overdue = $assignment->isOverdue();

        $students = $assignment->classroom?->students ?? collect();

        // One query: attempts by these students on these exercises after publish.
        $studentIds = $students->pluck('id')->all();
        $attempts = collect();
        if ($total > 0 && ! empty($studentIds)) {
            $q = UserExerciseAttempt::whereIn('user_id', $studentIds)
                ->whereIn('exercise_id', $exerciseIds);
            if ($publishedAt) {
                $q->where('created_at', '>=', $publishedAt);
            }
            $attempts = $q->get(['user_id', 'exercise_id', 'accuracy_percent']);
        }

        $byUser = $attempts->groupBy('user_id');

        return $students->map(function ($student) use ($byUser, $total, $overdue) {
            $rows = $byUser->get($student->id, collect());
            $doneExerciseIds = $rows->pluck('exercise_id')->unique();
            $done = $doneExerciseIds->count();
            $avg = $rows->isNotEmpty() ? round((float) $rows->avg('accuracy_percent'), 1) : null;

            if ($total > 0 && $done >= $total) {
                $status = 'done';
            } elseif ($done > 0) {
                $status = $overdue ? 'late' : 'in_progress';
            } else {
                $status = $overdue ? 'late' : 'not_started';
            }

            return [
                'user_id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'done' => $done,
                'total' => $total,
                'status' => $status,
                'avg_accuracy' => $avg,
            ];
        })->values()->all();
    }
}
