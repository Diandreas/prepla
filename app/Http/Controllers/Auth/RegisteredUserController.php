<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ConsumedTrial;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Without this check, a user could delete their account (self-service,
        // password-confirmation only) and re-register with the same email to
        // get a fresh 7-day trial indefinitely. ConsumedTrial rows survive
        // account deletion, so the trial is only ever granted once per email.
        $alreadyHadTrial = ConsumedTrial::alreadyUsedBy($user->email);

        UserProfile::create([
            'user_id' => $user->id,
            'trial_ends_at' => $alreadyHadTrial ? null : now()->addDays(7),
        ]);

        if (! $alreadyHadTrial) {
            ConsumedTrial::recordFor($user->email);
        }

        event(new Registered($user));

        Auth::login($user);

        return to_route('onboarding.native-language');
    }
}
