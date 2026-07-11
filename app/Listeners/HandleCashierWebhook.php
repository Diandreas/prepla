<?php

namespace App\Listeners;

use App\Mail\InvoicePaidMail;
use App\Mail\PaymentFailedMail;
use App\Mail\SubscriptionStartedMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
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
        // Stripe's signature check only proves authenticity within its
        // tolerance window (default 5 min) — it does not stop the same valid
        // event from being replayed within that window. Without this guard,
        // a captured payload replayed a few times would resend the welcome/
        // payment-failed email that many times.
        $eventId = $event->payload['id'] ?? null;
        if ($eventId && ! Cache::add("stripe-webhook-processed:{$eventId}", true, now()->addHours(6))) {
            Log::info('HandleCashierWebhook: duplicate/replayed event ignored', ['event_id' => $eventId]);
            return;
        }

        $type = $event->payload['type'] ?? null;
        $object = $event->payload['data']['object'] ?? [];

        match ($type) {
            'customer.subscription.created' => $this->onSubscriptionCreated($object),
            'invoice.payment_succeeded' => $this->onInvoicePaid($object),
            'invoice.payment_failed' => $this->onPaymentFailed($object),
            default => null,
        };
    }

    /**
     * Envoie le reçu par email après chaque paiement réussi. Les URLs de facture
     * (page hébergée + PDF) sont déjà dans le payload — pas d'appel Stripe.
     */
    private function onInvoicePaid(array $object): void
    {
        $user = $this->findUserByStripeId($object['customer'] ?? null);
        if (!$user) {
            return;
        }

        // Ignore les factures à 0 € (périodes d'essai, proratisations nulles).
        $amountPaid = (int) ($object['amount_paid'] ?? 0);
        if ($amountPaid <= 0) {
            return;
        }

        $currency = strtoupper($object['currency'] ?? 'eur');
        $amount = number_format($amountPaid / 100, 2, ',', ' ') . ' ' . ($currency === 'EUR' ? '€' : $currency);

        Mail::to($user->email)->send(new InvoicePaidMail(
            $user,
            $amount,
            $object['hosted_invoice_url'] ?? null,
            $object['invoice_pdf'] ?? null,
        ));
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
