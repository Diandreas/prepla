<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ConsumedTrial;
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

        $userByGoogleId = User::where('google_id', $googleUser->getId())->first();
        $userByEmail = $userByGoogleId ?: User::where('email', $googleUser->getEmail())->first();

        // A user already known by their Google ID has already proven this
        // identity before — safe to log in directly. But a match by email
        // alone (no prior Google link) could be a pre-registered account an
        // attacker set up with a victim's address and a password only the
        // attacker knows; without a verified email on that account, silently
        // logging the Google sign-in into it would be an account takeover.
        if ($userByGoogleId) {
            $user = $userByGoogleId;
            $user->update(['avatar' => $user->avatar ?? $googleUser->getAvatar()]);
        } elseif ($userByEmail) {
            if (! $userByEmail->email_verified_at) {
                return redirect()->route('login')->withErrors([
                    'google' => 'Un compte existe déjà avec cet email. Connecte-toi avec ton mot de passe, puis lie ton compte Google depuis les paramètres.',
                ]);
            }

            $user = $userByEmail;
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

            // Google has already verified this address — safe to trust it,
            // and it lets a later legitimate password-account merge for the
            // same email pass the verified-email check above. Not mass-
            // assignable on purpose, set directly.
            $user->forceFill(['email_verified_at' => now()])->save();

            // See RegisteredUserController::store() for why this check exists
            // — prevents an infinite free trial via delete + re-register.
            $alreadyHadTrial = ConsumedTrial::alreadyUsedBy($user->email);

            UserProfile::create([
                'user_id'      => $user->id,
                'trial_ends_at' => $alreadyHadTrial ? null : now()->addDays(7),
            ]);

            if (! $alreadyHadTrial) {
                ConsumedTrial::recordFor($user->email);
            }

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
