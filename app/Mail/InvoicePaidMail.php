<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Reçu envoyé après chaque paiement réussi. Les liens (page de facture hébergée
 * + PDF) viennent directement du payload webhook Stripe — aucune dépendance
 * dompdf, aucun appel API supplémentaire.
 */
class InvoicePaidMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $amount,
        public ?string $hostedInvoiceUrl = null,
        public ?string $invoicePdfUrl = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ta facture PrePla Plus',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.invoice-paid',
            with: [
                'userName' => $this->user->name,
                'amount' => $this->amount,
                'hostedInvoiceUrl' => $this->hostedInvoiceUrl,
                'invoicePdfUrl' => $this->invoicePdfUrl,
                'subscriptionUrl' => url(route('subscription.index')),
            ],
        );
    }
}
