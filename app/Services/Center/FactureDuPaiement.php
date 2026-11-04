<?php

namespace App\Services\Center;

use Laravel\Cashier\Cashier;

/**
 * Retrouve la facture Stripe qu'un paiement a reglee.
 *
 * Depuis la version d'API 2025-03-31.basil (celle de Cashier : 2025-08-27.basil),
 * la facture n'a plus de champ « charge » et le paiement plus de champ
 * « invoice ». Le lien passe par les InvoicePayment, qu'on interroge par le
 * PaymentIntent du paiement. Un seul appel Stripe, et seulement pour un
 * remboursement d'un compte parraine qui a deja rapporte une commission.
 *
 * Classe a part pour que les tests la remplacent sans appeler Stripe.
 */
class FactureDuPaiement
{
    /**
     * Identifiant de la facture (in_...) reglee par ce PaymentIntent, ou null.
     * Une erreur Stripe remonte : le webhook repond en erreur et Stripe
     * renverra l'evenement.
     */
    public function pourPaymentIntent(string $paymentIntent): ?string
    {
        $paiements = Cashier::stripe()->invoicePayments->all([
            'payment' => ['type' => 'payment_intent', 'payment_intent' => $paymentIntent],
            'limit' => 1,
        ]);

        $facture = $paiements->data[0]->invoice ?? null;

        return is_string($facture) ? $facture : ($facture->id ?? null);
    }
}
