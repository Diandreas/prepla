<?php

namespace App\Listeners;

use App\Mail\PaymentFailedMail;
use App\Mail\SubscriptionStartedMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Laravel\Cashier\Events\WebhookHandled;

/**
 * Cashier's own WebhookController already updates the local `subscriptions`
 * table on every event (handleCustomerSubscriptionCreated/Updated/Deleted).
 * This listener only adds the lifecycle EMAILS that were entirely missing —
 * a welcome email on new subscription and a payment-failure warning — by
 * reacting to the same already-working `stripe/webhook` endpoint (route
 * `cashier.webhook`), without needing any new route or duplicate Stripe call.
 */
class HandleCashierWebhook implements ShouldQueue
{
    public function handle(WebhookHandled $event): void
    {
        $type = $event->payload['type'] ?? null;
        $object = $event->payload['data']['object'] ?? [];

        match ($type) {
            'customer.subscription.created' => $this->onSubscriptionCreated($object),
            'invoice.payment_failed' => $this->onPaymentFailed($object),
            default => null,
        };
    }

    private function onSubscriptionCreated(array $object): void
    {
        $user = $this->findUserByStripeId($object['customer'] ?? null);
        if (!$user) {
            return;
        }

        Mail::to($user->email)->send(new SubscriptionStartedMail($user));
    }

    private function onPaymentFailed(array $object): void
    {
        $user = $this->findUserByStripeId($object['customer'] ?? null);
        if (!$user) {
            return;
        }

        Mail::to($user->email)->send(new PaymentFailedMail($user));
    }

    private function findUserByStripeId(?string $stripeId): ?User
    {
        if (!$stripeId) {
            return null;
        }

        $user = User::where('stripe_id', $stripeId)->first();
        if (!$user) {
            // Not necessarily an error — Cashier also fires webhook events for
            // customers created outside our subscription flow.
            Log::info('HandleCashierWebhook: no local user found for Stripe customer', ['stripe_id' => $stripeId]);
        }

        return $user;
    }
}
