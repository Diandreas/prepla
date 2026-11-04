<?php

namespace App\Providers;

use App\Listeners\HandleCashierWebhook;
use App\Listeners\SendWelcomeEmail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
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
        // Les durees relatives s'ecrivent en francais, comme le reste de
        // l'interface. Sans cela, l'apprenant lisait « tu as bute dessus
        // 13 seconds ago » sur une page entierement en francais.
        \Carbon\Carbon::setLocale('fr');

        Event::listen(Registered::class, SendWelcomeEmail::class);
        Event::listen(WebhookHandled::class, HandleCashierWebhook::class);

        // Commission de parrainage (lot D) : sans modifier HandleCashierWebhook,
        // un listener sur WebhookReceived (et non WebhookHandled, que Cashier
        // n'emet pas pour charge.refunded). Son unicite vient de la base
        // (stripe_invoice_id unique), pas du cache.
        // App\Listeners\EnregistrerCommission n'est PAS enregistre ici : la
        // decouverte automatique de Laravel (app/Listeners, methode handle typee)
        // le branche deja. L'ajouter ici le ferait tourner deux fois par
        // evenement (php artisan event:list le montre).

        // Periodes d'appartenance aux centres (lot E) : l'observer ecrit l'entree
        // et la sortie de chaque membre, pour compter un eleve parti en cours de mois.
        // members()->attach()/detach() et les suppressions par requete n'y passent
        // pas : les controleurs utilisent CenterUser::create() et $lien->delete(),
        // et centres:actifs rattrape le reste (PeriodesAppartenance::reconcilier).
        \App\Models\CenterUser::observe(\App\Observers\CenterUserObserver::class);

        // Some local PHP builds ship without a CA store: point outbound HTTPS at a bundle
        // rather than disabling certificate verification.
        if ($caBundle = config('services.http.ca_bundle')) {
            Http::globalOptions(['verify' => $caBundle]);
        }

        // AI/TTS/STT endpoints (Mistral, Deepgram) had zero rate limiting —
        // any account could script calls in a loop and run up the API bill.
        // Per-user, generous enough for legitimate practice but not for abuse.
        RateLimiter::for('ai-calls', function ($request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });
    }
}
