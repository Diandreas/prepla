<?php

namespace App\Listeners;

use App\Models\CenterCommission;
use App\Models\User;
use App\Services\Center\FactureDuPaiement;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Events\WebhookReceived;

/**
 * Commission de parrainage : 25 % de chaque facture Stripe payee par un compte
 * qu'un centre a amene (/r/{code}), et sa reprise en cas de remboursement.
 *
 * Branche sur WebhookReceived et NON sur WebhookHandled : Cashier n'emet
 * WebhookHandled que pour les types qu'il traite lui-meme (methodes handle* de
 * son WebhookController), et charge.refunded n'en fait pas partie : le
 * remboursement n'arriverait jamais ici. WebhookReceived part pour chaque
 * evenement dont la signature est valide. Le listener est branche par la
 * decouverte automatique de Laravel (methode handle typee), pas dans
 * AppServiceProvider, pour ne pas tourner deux fois.
 *
 * Volontairement SYNCHRONE (pas de ShouldQueue) : la production n'a pas de
 * processus queue:work. Si l'ecriture echoue, l'exception remonte, le webhook
 * repond en erreur et Stripe renvoie l'evenement plus tard.
 *
 * L'unicite vient de la base (center_commissions.stripe_invoice_id unique), pas
 * du cache : HandleCashierWebhook marque l'evenement AVANT de le traiter, un
 * echec y perdrait la commission. Ici, un evenement rejoue retombe sur la meme
 * cle et n'ecrit rien.
 *
 * Deux formes de donnees Stripe sont lues : l'ancienne (facture.charge,
 * paiement.invoice) et celle de l'API basil qu'utilise Cashier, ou ces deux
 * champs n'existent plus (voir FactureDuPaiement).
 */
class EnregistrerCommission
{
    /** Les mois des releves suivent l'heure de Douala, comme les eleves actifs. */
    public const FUSEAU = 'Africa/Douala';

    public function handle(WebhookReceived $event): void
    {
        $objet = $event->payload['data']['object'] ?? [];

        match ($event->payload['type'] ?? null) {
            'invoice.payment_succeeded' => $this->facturePayee($objet),
            'charge.refunded' => $this->remboursement($objet),
            default => null,
        };
    }

    private function facturePayee(array $facture): void
    {
        $montant = (int) ($facture['amount_paid'] ?? 0);
        $factureId = $facture['id'] ?? null;
        // Les factures a 0 (essai, proratisation nulle) ne rapportent rien.
        if ($montant <= 0 || ! $factureId) {
            return;
        }

        $user = $this->compteParraine($facture['customer'] ?? null);
        if (! $user) {
            return;
        }

        $quand = $facture['status_transitions']['paid_at'] ?? $facture['created'] ?? null;

        $this->ecrire([
            'center_id' => $user->referred_by_center_id,
            'user_id' => $user->id,
            'stripe_invoice_id' => $factureId,
            'stripe_charge_id' => $this->paiementDeLaFacture($facture),
            'stripe_subscription_id' => $this->abonnement($facture),
            'amount_paid' => $montant,
            'currency' => strtoupper((string) ($facture['currency'] ?? 'eur')),
            'rate' => CenterCommission::TAUX,
            'commission' => (int) round($montant * CenterCommission::TAUX / 100),
            'periode' => $this->periode($quand),
            'type' => CenterCommission::CHARGE,
        ]);
    }

    /**
     * charge.refunded porte le CUMUL rembourse sur le paiement. La ligne ne
     * reprend que l'ecart avec les remboursements deja comptes, et sa cle
     * contient ce cumul : un second remboursement partiel fait une nouvelle
     * ligne, le meme evenement rejoue retombe sur la meme cle.
     */
    private function remboursement(array $paiement): void
    {
        $chargeId = $paiement['id'] ?? null;
        $cumul = (int) ($paiement['amount_refunded'] ?? 0);
        if (! $chargeId || $cumul <= 0) {
            return;
        }

        $user = $this->compteParraine($paiement['customer'] ?? null);
        if (! $user) {
            return;
        }

        // Ne reprend que ce qui a ete compte pour ce compte. Sans aucune
        // commission d'origine possible, pas d'appel a Stripe.
        $commissions = CenterCommission::where('type', CenterCommission::CHARGE)->where('user_id', $user->id);
        if (! (clone $commissions)->exists()) {
            return;
        }

        // D'abord le paiement lui-meme s'il a ete note, puis sa facture.
        $origine = (clone $commissions)->where('stripe_charge_id', $chargeId)->first();
        if (! $origine && ($factureId = $this->factureDuPaiement($paiement))) {
            $origine = (clone $commissions)->where('stripe_invoice_id', $factureId)->first();
        }
        if (! $origine) {
            return;
        }

        $dejaRepris = -1 * (int) CenterCommission::where('type', CenterCommission::REMBOURSEMENT)
            ->where('stripe_charge_id', $chargeId)
            ->sum('amount_paid');
        $ecart = min($cumul, $origine->amount_paid) - $dejaRepris;
        if ($ecart <= 0) {
            return;
        }

        $this->ecrire([
            'center_id' => $origine->center_id,
            'user_id' => $user->id,
            'stripe_invoice_id' => "remb:{$chargeId}:{$cumul}",
            'stripe_charge_id' => $chargeId,
            'stripe_subscription_id' => $origine->stripe_subscription_id,
            'amount_paid' => -$ecart,
            'currency' => $origine->currency,
            'rate' => $origine->rate,
            'commission' => -1 * (int) round($ecart * $origine->rate / 100),
            'periode' => $this->periode(null),
            'type' => CenterCommission::REMBOURSEMENT,
        ]);
    }

    /**
     * Le compte Stripe, s'il a ete amene par un centre qui existe encore et
     * dont il n'est pas devenu membre du personnel depuis.
     */
    private function compteParraine(?string $stripeId): ?User
    {
        if (! $stripeId) {
            return null;
        }

        $user = User::where('stripe_id', $stripeId)->whereNotNull('referred_by_center_id')->first();
        $centre = $user?->centreParrain;
        if (! $user || ! $centre) {
            return null;
        }

        if ($centre->compteParmiSonPersonnel($user)) {
            Log::info('Commission ignoree : compte du personnel du centre parrain', ['center_id' => $centre->id, 'user_id' => $user->id]);

            return null;
        }

        return $user;
    }

    /**
     * La facture qu'un paiement a reglee : ancienne forme (paiement.invoice),
     * sinon, en API basil, par son PaymentIntent (un appel Stripe).
     */
    private function factureDuPaiement(array $paiement): ?string
    {
        if (is_string($paiement['invoice'] ?? null)) {
            return $paiement['invoice'];
        }

        $intention = $paiement['payment_intent'] ?? null;

        return is_string($intention) && $intention !== ''
            ? app(FactureDuPaiement::class)->pourPaymentIntent($intention)
            : null;
    }

    /**
     * Le paiement (ch_...) d'une facture quand Stripe le donne : ancienne forme
     * (facture.charge), ou liste payments developpee. En API basil il manque
     * le plus souvent : le remboursement retrouve alors la facture autrement.
     */
    private function paiementDeLaFacture(array $facture): ?string
    {
        if (is_string($facture['charge'] ?? null)) {
            return $facture['charge'];
        }

        foreach ($facture['payments']['data'] ?? [] as $p) {
            $charge = $p['payment']['charge'] ?? null;
            if (is_string($charge)) {
                return $charge;
            }
        }

        return null;
    }

    private function ecrire(array $ligne): void
    {
        try {
            CenterCommission::create($ligne);
        } catch (UniqueConstraintViolationException) {
            // Evenement deja compte : Stripe l'a renvoye, ou deux livraisons se croisent.
        }
    }

    private function abonnement(array $facture): ?string
    {
        // Ancienne forme de l'API Stripe, puis la forme 2025 (parent.subscription_details).
        $id = $facture['subscription'] ?? $facture['parent']['subscription_details']['subscription'] ?? null;

        return is_string($id) ? $id : null;
    }

    /** Mois (YYYY-MM, heure de Douala) d'un horodatage Stripe ; maintenant a defaut. */
    private function periode(mixed $horodatage): string
    {
        $quand = is_numeric($horodatage)
            ? Carbon::createFromTimestamp((int) $horodatage)
            : now();

        return $quand->setTimezone(self::FUSEAU)->format('Y-m');
    }
}
