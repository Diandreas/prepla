<?php

namespace App\Http\Middleware;

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

        return array_merge(parent::share($request), [
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $user,
                'role' => $user?->role,        // global role (super_admin | student)
                'center' => $center,           // null if the user has no center
            ],
            'userProfile' => $request->user()?->profile,
            'isPremium' => $request->user()?->hasPremiumAccess() ?? false,
            'onTrial' => $request->user()?->isOnTrial() ?? false,
            'trialDaysLeft' => $request->user()?->trialDaysLeft() ?? 0,
            'vapidPublicKey' => config('webpush.vapid.public_key'),
            'flash' => [
                'correction'  => $request->session()->get('correction'),
                'success'     => $request->session()->get('success'),
                'error'       => $request->session()->get('error'),
            ],
        ]);
    }
}
