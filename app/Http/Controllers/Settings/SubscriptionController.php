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
                'monthly' => ['id' => self::PRICE_MONTHLY, 'amount' => 9.99, 'interval' => 'month'],
                'annual'  => ['id' => self::PRICE_ANNUAL,  'amount' => 79.99, 'interval' => 'year'],
            ],
        ]);
    }

    public function checkout(Request $request)
    {
        $request->validate(['price_id' => 'required|string|in:' . self::PRICE_MONTHLY . ',' . self::PRICE_ANNUAL]);

        $user = auth()->user();

        // Without this guard, a double-click or two concurrent requests each
        // create a separate Stripe Checkout Session; if both get completed
        // the user ends up billed twice with two 'default' subscription rows,
        // while $user->subscription('default') silently returns only the first.
        if ($user->subscribed('default')) {
            return redirect()->route('subscription.index');
        }

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
