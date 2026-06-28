<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Models\UserError;
use App\Models\UserExerciseAttempt;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');

        $students = $center->students()
            ->with('classrooms:id,name')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'classrooms' => $u->classrooms
                    ->where('center_id', $center->id)
                    ->pluck('name')
                    ->values(),
                'attempts' => UserExerciseAttempt::where('user_id', $u->id)->count(),
            ]);

        return Inertia::render('center/students/index', [
            'students' => $students,
        ]);
    }

    public function show(Request $request, User $user): Response
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');

        // Isolation: the user must be a student of THIS center.
        abort_unless(
            $user->centers()->where('language_centers.id', $center->id)->exists(),
            403
        );

        $attempts = UserExerciseAttempt::where('user_id', $user->id)
            ->with('exercise.exerciseType:id,name,skill_type')
            ->latest()
            ->take(20)
            ->get()
            ->map(fn (UserExerciseAttempt $a) => [
                'id' => $a->id,
                'type' => $a->exercise?->exerciseType?->name,
                'skill' => $a->exercise?->exerciseType?->skill_type,
                'accuracy' => $a->accuracy_percent,
                'date' => $a->created_at,
            ]);

        $totalAttempts = UserExerciseAttempt::where('user_id', $user->id)->count();
        $avgAccuracy = (float) UserExerciseAttempt::where('user_id', $user->id)->avg('accuracy_percent');

        return Inertia::render('center/students/show', [
            'student' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'level' => $user->profile?->current_level,
            ],
            'stats' => [
                'total_attempts' => $totalAttempts,
                'avg_accuracy' => round($avgAccuracy, 1),
            ],
            // Reuse the existing weakness diagnostic.
            'weaknesses' => UserError::categoryStats($user->id),
            'recent_attempts' => $attempts,
        ]);
    }

    public function remove(Request $request, Classroom $classroom, User $user)
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');
        abort_unless($classroom->center_id === $center->id, 403);

        // Remove from the classroom; if no longer in any classroom of the center,
        // also free the center seat.
        $classroom->members()->detach($user->id);

        $stillInCenter = $user->classrooms()->where('center_id', $center->id)->exists();
        if (! $stillInCenter) {
            $user->centerMembership()->where('center_id', $center->id)->delete();
        }

        return back()->with('success', "{$user->name} a été retiré de la classe.");
    }
}
