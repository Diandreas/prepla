<?php

namespace App\Http\Controllers\Test;

use App\Http\Controllers\Controller;
use App\Models\CurriculumSkeleton;
use App\Models\UserError;
use App\Models\UserExerciseAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Test-only controller to simulate "days passing" for an authenticated user,
 * so we can run an end-to-end 7-day study journey without changing the server clock.
 *
 * Guarded: only runs when app.debug is on OR a valid X-Sim-Token header / sim_token
 * matches env SIM_TEST_TOKEN. Otherwise it 404s (no hint that the route exists).
 */
class SimController extends Controller
{
    private function authorizeSim(Request $request): void
    {
        if (config('app.debug')) {
            return;
        }

        $expected = config('app.sim_test_token', env('SIM_TEST_TOKEN'));
        $provided = $request->header('X-Sim-Token') ?? $request->input('sim_token');

        if (!$expected || !$provided || !hash_equals((string) $expected, (string) $provided)) {
            throw new NotFoundHttpException();
        }
    }

    /**
     * Simulate one day passing for the current user:
     *  - rewind streak_last_date by 1 day so the next activity increments the streak
     *  - backdate today's exercise attempts by 1 day
     *  - pull due SM-2 error reviews back so they become due
     */
    public function advanceDay(Request $request): JsonResponse
    {
        $this->authorizeSim($request);

        $user = $request->user();
        $profile = $user->profile;

        if ($profile && $profile->streak_last_date) {
            $profile->streak_last_date = \Carbon\Carbon::parse($profile->streak_last_date)->subDay();
            $profile->save();
        }

        // Backdate attempts created "today" by one day so leaderboard/history shift back.
        $attemptsShifted = UserExerciseAttempt::where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->get();
        foreach ($attemptsShifted as $attempt) {
            $attempt->created_at = $attempt->created_at->subDay();
            $attempt->save();
        }

        // Pull every unmastered error's next_review_at back a day so SM-2 surfaces them.
        UserError::where('user_id', $user->id)
            ->where('mastered', false)
            ->whereNotNull('next_review_at')
            ->each(function (UserError $error) {
                $error->next_review_at = $error->next_review_at->subDay();
                $error->save();
            });

        $skeleton = CurriculumSkeleton::where('user_id', $user->id)->first();
        $dueErrors = UserError::dueForReview($user->id)->count();

        return response()->json([
            'ok' => true,
            'streak' => $profile?->streak_current ?? 0,
            'streak_last_date' => $profile?->streak_last_date?->toDateString(),
            'attempts_backdated' => $attemptsShifted->count(),
            'due_errors' => $dueErrors,
            'objective_index' => $skeleton?->current_objective_index ?? 0,
        ]);
    }

    /**
     * Reset the test character to a clean slate so the scenario can be re-run.
     */
    public function resetCharacter(Request $request): JsonResponse
    {
        $this->authorizeSim($request);

        $user = $request->user();

        UserExerciseAttempt::where('user_id', $user->id)->delete();
        UserError::where('user_id', $user->id)->delete();

        if ($profile = $user->profile) {
            $profile->update([
                'xp_total' => 0,
                'streak_current' => 0,
                'streak_last_date' => null,
            ]);
        }

        $skeleton = CurriculumSkeleton::where('user_id', $user->id)->first();
        if ($skeleton) {
            $objectives = $skeleton->objectives ?? [];
            foreach ($objectives as $i => &$o) {
                $o['status'] = $i === 0 ? 'current' : 'pending';
            }
            unset($o);
            $skeleton->update([
                'objectives' => $objectives,
                'current_objective_index' => 0,
                'consecutive_successes' => 0,
                'consecutive_failures' => 0,
            ]);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Test character reset.',
            'objective_index' => 0,
        ]);
    }
}
