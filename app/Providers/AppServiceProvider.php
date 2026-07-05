<?php

namespace App\Providers;

use App\Listeners\HandleCashierWebhook;
use App\Listeners\SendWelcomeEmail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Events\WebhookHandled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(Registered::class, SendWelcomeEmail::class);
        Event::listen(WebhookHandled::class, HandleCashierWebhook::class);

        // AI/TTS/STT endpoints (Mistral, Deepgram) had zero rate limiting —
        // any account could script calls in a loop and run up the API bill.
        // Per-user, generous enough for legitimate practice but not for abuse.
        RateLimiter::for('ai-calls', function ($request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });
    }
}
