<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    /** Les tarifs vivent dans la configuration : test et production n'ont pas les mêmes. */
    private function prices(): array
    {
        return [
            'monthly' => (string) config('services.stripe.prices.monthly'),
            'annual' => (string) config('services.stripe.prices.annual'),
        ];
    }

    public function index(): Response
    {
        $user = auth()->user();
        $profile = $user->profile;

        $isSubscribed = $user->subscribed('default');
        $subscription = $user->subscription('default');

        $onTrial    = $user->isOnTrial();
        $trialDaysLeft = $user->trialDaysLeft();

        // asStripeSubscription() makes a live Stripe API call — if the subscription
        // was deleted on Stripe's side (desync) or the API is briefly unavailable,
        // this throws and previously crashed the whole page for the user.
        $renewsAt = null;
        if ($subscription) {
            try {
                $renewsAt = $subscription->asStripeSubscription()->current_period_end;
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Failed to fetch Stripe subscription details', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return Inertia::render('settings/subscription', [
            'currentPlan'       => ($isSubscribed || $onTrial) ? 'premium' : 'free',
            'stripeEnabled'     => true,
            'stripeKey'         => config('cashier.key'),
            'isSubscribed'      => $isSubscribed,
            'onTrial'           => $onTrial,
            'trialDaysLeft'     => $trialDaysLeft,
            'cancelAtPeriodEnd' => $subscription?->onGracePeriod() ?? false,
            'renewsAt'          => $renewsAt,
            'plans' => [
                'monthly' => ['id' => $this->prices()['monthly'], 'amount' => 9.99, 'interval' => 'month'],
                'annual'  => ['id' => $this->prices()['annual'], 'amount' => 79.99, 'interval' => 'year'],
            ],
        ]);
    }

    public function checkout(Request $request)
    {
        $known = array_values(array_filter($this->prices()));
        $request->validate(['price_id' => ['required', 'string', Rule::in($known)]]);

        $user = auth()->user();

        // Without this guard, a double-click or two concurrent requests each
        // create a separate Stripe Checkout Session; if both get completed
        // the user ends up billed twice with two 'default' subscription rows,
        // while $user->subscription('default') silently returns only the first.
        if ($user->subscribed('default')) {
            return redirect()->route('subscription.index');
        }

        // Un tarif absent du compte Stripe (créé dans l'autre mode, ou archivé) rendait
        // une page « Server Error » à l'apprenant au moment de payer.
        try {
            return $user->newSubscription('default', $request->price_id)
                ->checkout([
                    'success_url' => route('subscription.index') . '?success=1',
                    'cancel_url'  => route('subscription.index'),
                    'locale'      => app()->getLocale(),
                ]);
        } catch (\Throwable $e) {
            Log::error('Stripe checkout failed', [
                'user_id' => $user->id,
                'price_id' => $request->price_id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', "Le paiement n'a pas pu démarrer. Réessaie dans un instant — si cela se reproduit, préviens-nous, l'erreur est de notre côté.");
        }
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

    /**
     * List past invoices so the user can download a receipt — Cashier already
     * provides this natively, it was simply never exposed in any PrePla route/UI.
     */
    public function invoices(): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();

        if (!$user->hasStripeId()) {
            return response()->json(['invoices' => []]);
        }

        $invoices = $user->invoices()->map(fn ($invoice) => [
            'id' => $invoice->id,
            'date' => $invoice->date()->translatedFormat('d M Y'),
            'total' => $invoice->total(),
            // Liens hébergés par Stripe (page + PDF) — pas de rendu PDF local.
            'hosted_url' => $invoice->asStripeInvoice()->hosted_invoice_url,
            'pdf_url' => $invoice->asStripeInvoice()->invoice_pdf,
        ]);

        return response()->json(['invoices' => $invoices]);
    }

    public function downloadInvoice(string $invoiceId)
    {
        $user = auth()->user();

        if (!$user->hasStripeId()) {
            abort(404);
        }

        // findInvoiceOrFail() vérifie que la facture appartient bien à ce client
        // Stripe (403 sinon). On redirige vers le PDF hébergé par Stripe :
        // l'ancien downloadInvoice() de Cashier exigeait dompdf, qui n'est PAS
        // installé — la route plantait en 500 depuis toujours.
        $invoice = $user->findInvoiceOrFail($invoiceId);
        $stripeInvoice = $invoice->asStripeInvoice();

        $url = $stripeInvoice->invoice_pdf ?? $stripeInvoice->hosted_invoice_url;
        abort_unless($url, 404);

        return redirect()->away($url);
    }
}
