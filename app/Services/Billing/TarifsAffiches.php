<?php

namespace App\Services\Billing;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Cashier;

/**
 * Les prix tels que Stripe les debite, pour toutes les pages qui les affichent.
 *
 * Ecrits en dur, ils annoncaient « 9,99 € » pendant que Stripe facturait 9,99
 * USD — et la page d'accueil et la page d'abonnement pouvaient se contredire.
 * Mis en cache une heure ; en cas de panne, on retombe sur les valeurs connues.
 */
final class TarifsAffiches
{
    /** @return array{id: string, amount: float, currency: string, interval: string} */
    public function mensuel(): array
    {
        return $this->plan('monthly', 'month', 9.99);
    }

    /** @return array{id: string, amount: float, currency: string, interval: string} */
    public function annuel(): array
    {
        return $this->plan('annual', 'year', 79.99);
    }

    /** @return array{id: string, amount: float, currency: string, interval: string} */
    private function plan(string $key, string $interval, float $fallbackAmount): array
    {
        $id = (string) config("services.stripe.prices.{$key}");
        $fallback = [
            'id' => $id,
            'amount' => $fallbackAmount,
            'currency' => strtolower((string) config('cashier.currency', 'usd')),
            'interval' => $interval,
        ];

        if ($id === '' || ! config('cashier.secret')) {
            return $fallback;
        }

        $cacheKey = "stripe-price:{$id}";
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        try {
            $price = Cashier::stripe()->prices->retrieve($id);
            $plan = [
                'id' => $id,
                'amount' => ($price->unit_amount ?? 0) / 100,
                'currency' => strtolower($price->currency ?? $fallback['currency']),
                'interval' => $price->recurring->interval ?? $interval,
            ];
            Cache::put($cacheKey, $plan, now()->addHour());

            return $plan;
        } catch (\Throwable $e) {
            // Volontairement non mis en cache : une panne passagère ne doit pas figer
            // un prix approximatif pendant une heure.
            Log::warning('Stripe price lookup failed', ['price_id' => $id, 'error' => $e->getMessage()]);

            return $fallback;
        }
    }
}
