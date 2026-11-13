<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ConsumedTrial;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Billing\Delegues;
use App\Services\Center\Parrainage;
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
            // Un numero joignable, sans imposer un format : les apprenants ecrivent
            // aussi bien +237 6XX que 06 XX, et un refus de format ferait perdre
            // l'inscription pour rien.
            'phone' => 'required|string|min:6|max:32',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'phone_prompted_at' => now(),
            'password' => Hash::make($request->password),
        ]);

        // Arrive par le lien d'un centre (/r/{code}) : le compte lui est rattache.
        Parrainage::rattacher($user, $request);
        Delegues::rattacherALInscription($user, $request);

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

        // Un parent venu de PrePla Kids va a l'espace parent, pas au questionnaire
        // des adultes (langue maternelle, examen vise) qui ne le concerne pas.
        if ($request->session()->pull('kids_parent')) {
            return to_route('kids.parents');
        }
        // Venu par le QR code d'une offre : droit a la page de paiement, au prix de l'offre.
        if ($request->session()->pull('apres_inscription') === 'subscription.index') {
            return to_route('subscription.index');
        }

        return to_route('onboarding.native-language');
    }
}
