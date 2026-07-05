<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentFailedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Problème avec ton paiement PrePla Plus',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.payment-failed',
            with: [
                'userName' => $this->user->name,
                'subscriptionUrl' => url(route('subscription.index')),
            ],
        );
    }
}
