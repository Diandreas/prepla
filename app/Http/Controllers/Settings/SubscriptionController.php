<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    const PRICE_MONTHLY = 'price_1TbjMVA4jGtQdWrshf7v2nQr'; // 9.99€/mois
    const PRICE_ANNUAL  = 'price_1TbjMdA4jGtQdWrsRG1w5n9Z'; // 79.99€/an

    public function index(): Response
    {
        $user = auth()->user();
        $profile = $user->profile;

        $isSubscribed = $user->subscribed('default');
        $subscription = $user->subscription('default');

        return Inertia::render('settings/subscription', [
            'currentPlan'       => $isSubscribed ? 'premium' : ($profile->plan ?? 'free'),
            'stripeEnabled'     => true,
            'stripeKey'         => config('cashier.key'),
            'isSubscribed'      => $isSubscribed,
            'cancelAtPeriodEnd' => $subscription?->onGracePeriod() ?? false,
            'renewsAt'          => $subscription?->asStripeSubscription()->current_period_end ?? null,
            'plans' => [
                'monthly' => ['id' => self::PRICE_MONTHLY, 'amount' => 9.99, 'interval' => 'month'],
                'annual'  => ['id' => self::PRICE_ANNUAL,  'amount' => 79.99, 'interval' => 'year'],
            ],
        ]);
    }

    public function checkout(Request $request)
    {
        $request->validate(['price_id' => 'required|string|in:' . self::PRICE_MONTHLY . ',' . self::PRICE_ANNUAL]);

        $user = auth()->user();

        return $user->newSubscription('default', $request->price_id)
            ->checkout([
                'success_url' => route('subscription.index') . '?success=1',
                'cancel_url'  => route('subscription.index'),
                'locale'      => app()->getLocale(),
            ]);
    }

    public function cancel(): \Illuminate\Http\RedirectResponse
    {
        $user = auth()->user();

        if ($user->subscribed('default')) {
            $user->subscription('default')->cancel();
        }

        return back()->with('status', 'subscription-cancelled');
    }

    public function resume(): \Illuminate\Http\RedirectResponse
    {
        $user = auth()->user();

        if ($user->subscription('default')?->onGracePeriod()) {
            $user->subscription('default')->resume();
        }

        return back()->with('status', 'subscription-resumed');
    }

    public function webhook(Request $request): \Illuminate\Http\Response
    {
        // Géré automatiquement par Laravel Cashier via la route cashier.webhook
        return response('ok');
    }
}
