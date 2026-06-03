<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors(['google' => 'Connexion Google échouée. Réessayez.']);
        }

        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if ($user) {
            // Update google_id and avatar if missing
            $user->update([
                'google_id' => $googleUser->getId(),
                'avatar'    => $user->avatar ?? $googleUser->getAvatar(),
            ]);
        } else {
            $user = User::create([
                'name'      => $googleUser->getName(),
                'email'     => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'avatar'    => $googleUser->getAvatar(),
                'password'  => null,
            ]);

            UserProfile::create([
                'user_id'      => $user->id,
                'trial_ends_at' => now()->addDays(7),
            ]);

            event(new Registered($user));
        }

        Auth::login($user, true);

        // New users (no profile or onboarding not done) → onboarding
        if (!$user->profile?->onboarding_completed_at) {
            return redirect()->route('onboarding.native-language');
        }

        return redirect()->intended(route('dashboard'));
    }
}
