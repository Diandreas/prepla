<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    // Prix Stripe à configurer dans le dashboard Stripe puis mettre l'ID ici
    // const PREMIUM_PRICE_ID = 'price_XXXXXXXXXXXXXXXXXXXXXXXX';

    public function index(): Response
    {
        $user = auth()->user();
        $profile = $user->profile;

        // TODO (Stripe activé) : utiliser $user->subscribed('default') à la place de $profile->plan
        $isPremium = $profile->plan === 'premium';

        return Inertia::render('settings/subscription', [
            'currentPlan' => $profile->plan ?? 'free',
            'stripeEnabled' => false, // Passer à true quand les clés Stripe sont configurées
        ]);
    }

    public function upgrade()
    {
        // ============================================================
        // STRIPE DÉSACTIVÉ — décommenter ce bloc quand prêt :
        // ============================================================
        // $user = auth()->user();
        //
        // return $user->newSubscription('default', self::PREMIUM_PRICE_ID)
        //     ->checkout([
        //         'success_url' => route('subscription.index') . '?success=1',
        //         'cancel_url'  => route('subscription.index'),
        //         'locale'      => 'fr',
        //     ]);
        // ============================================================

        // Mode simulation (sans paiement réel)
        $user = auth()->user();
        $user->profile->update(['plan' => 'premium']);

        return back()->with('status', 'subscription-updated');
    }

    public function cancel()
    {
        // ============================================================
        // STRIPE DÉSACTIVÉ — décommenter ce bloc quand prêt :
        // ============================================================
        // $user = auth()->user();
        //
        // if ($user->subscribed('default')) {
        //     $user->subscription('default')->cancel();
        // }
        // ============================================================

        $user = auth()->user();
        $user->profile->update(['plan' => 'free']);

        return back()->with('status', 'subscription-cancelled');
    }

    // Webhook Stripe — à brancher dans routes/web.php quand activé
    // public function webhook(Request $request) { ... }
}
