<?php

use App\Listeners\HandleCashierWebhook;
use App\Mail\PaymentFailedMail;
use App\Mail\SubscriptionStartedMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Laravel\Cashier\Events\WebhookHandled;

test('a new subscription webhook sends the welcome email to the matching local user', function () {
    Mail::fake();

    $user = User::factory()->create(['stripe_id' => 'cus_abc123']);

    (new HandleCashierWebhook)->handle(new WebhookHandled([
        'type' => 'customer.subscription.created',
        'data' => ['object' => ['customer' => 'cus_abc123']],
    ]));

    Mail::assertQueued(SubscriptionStartedMail::class, fn ($mail) => $mail->user->is($user));
});

test('a failed payment webhook sends the payment-failed email to the matching local user', function () {
    Mail::fake();

    $user = User::factory()->create(['stripe_id' => 'cus_def456']);

    (new HandleCashierWebhook)->handle(new WebhookHandled([
        'type' => 'invoice.payment_failed',
        'data' => ['object' => ['customer' => 'cus_def456']],
    ]));

    Mail::assertQueued(PaymentFailedMail::class, fn ($mail) => $mail->user->is($user));
});

test('a webhook for a customer with no matching local user sends nothing', function () {
    Mail::fake();

    (new HandleCashierWebhook)->handle(new WebhookHandled([
        'type' => 'customer.subscription.created',
        'data' => ['object' => ['customer' => 'cus_unknown']],
    ]));

    Mail::assertNothingQueued();
});

test('an unhandled webhook type sends nothing', function () {
    Mail::fake();

    User::factory()->create(['stripe_id' => 'cus_abc123']);

    (new HandleCashierWebhook)->handle(new WebhookHandled([
        'type' => 'customer.subscription.updated',
        'data' => ['object' => ['customer' => 'cus_abc123']],
    ]));

    Mail::assertNothingQueued();
});
