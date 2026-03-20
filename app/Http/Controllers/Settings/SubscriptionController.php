<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function index(): Response
    {
        $profile = auth()->user()->profile;

        return Inertia::render('settings/subscription', [
            'currentPlan' => $profile->plan ?? 'free',
        ]);
    }

    public function upgrade(Request $request)
    {
        $user = auth()->user();
        $profile = $user->profile;

        // Ici, on simulerait un paiement Stripe/PayPal. 
        // Pour l'instant, on active directement le premium.
        $profile->update(['plan' => 'premium']);

        return back()->with('status', 'subscription-updated');
    }

    public function cancel(Request $request)
    {
        $profile = auth()->user()->profile;
        $profile->update(['plan' => 'free']);

        return back()->with('status', 'subscription-cancelled');
    }
}
