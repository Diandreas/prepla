<?php

namespace App\Http\Middleware;

use App\Models\UserExerciseAttempt;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $user = $request->user()?->load('profile', 'centerMembership.center');

        // Compact B2B context for conditional navigation / dashboard priority.
        $center = null;
        if ($user?->centerMembership?->center) {
            $c = $user->centerMembership->center;
            $center = [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'role' => $user->centerMembership->role, // center_admin | teacher | student
            ];
        }

        $isPremium = $user?->hasPremiumAccess() ?? false;
        // Surfaced so the UI can show "2/3 free exercises today" BEFORE the
        // EnsureExerciseQuota middleware blocks a 4th submission — the same
        // "communicate the rule before, not just after" principle already
        // applied to the mastery threshold on the exercise player.
        $exercisesToday = (!$isPremium && $user)
            ? UserExerciseAttempt::where('user_id', $user->id)->whereDate('created_at', today())->count()
            : 0;

        return array_merge(parent::share($request), [
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $user,
                'role' => $user?->role,        // global role (super_admin | student)
                'center' => $center,           // null if the user has no center
            ],
            'userProfile' => $request->user()?->profile,
            'isPremium' => $isPremium,
            'onTrial' => $request->user()?->isOnTrial() ?? false,
            'trialDaysLeft' => $request->user()?->trialDaysLeft() ?? 0,
            'freeExercisesUsedToday' => $exercisesToday,
            'freeExercisesLimit' => \App\Http\Middleware\EnsureExerciseQuota::DAILY_FREE_LIMIT,
            'vapidPublicKey' => config('webpush.vapid.public_key'),
            'flash' => [
                'correction'  => $request->session()->get('correction'),
                'success'     => $request->session()->get('success'),
                'error'       => $request->session()->get('error'),
            ],
        ]);
    }
}
