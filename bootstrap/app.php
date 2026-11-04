<?php

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\EnsureDisplayedCenter;
use App\Http\Middleware\EnsureMembreDuCentreDeLHote;
use App\Http\Middleware\FaireConfianceAuxHotes;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ResolveCenterFromHost;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Middleware\TrustHosts;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Sous-domaines et domaines propres des centres (lot B). Seuls nos
        // hotes sont acceptes (l'hote principal, *.racine, les domaines
        // verifies) : un lien fabrique pendant la requete ne peut plus pointer
        // vers un hote choisi par l'attaquant.
        $middleware->trustHosts();
        $middleware->replace(TrustHosts::class, FaireConfianceAuxHotes::class);

        // ResolveCenterFromHost en tete : le centre de l'adresse est connu
        // avant tout le reste. EnsureMembreDuCentreDeLHote apres la session.
        $middleware->web(prepend: [
            ResolveCenterFromHost::class,
        ], append: [
            EnsureMembreDuCentreDeLHote::class,
            // L'eleve d'un centre en « devoirs seulement » ne voit pas
            // l'entrainement libre : ses adresses menent a ses devoirs.
            \App\Http\Middleware\ContenuProposeAuxEleves::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            AddSecurityHeaders::class,
            EnsureDisplayedCenter::class,
        ]);
        // stripe/webhook is verified by Stripe's own signature instead of a
        // CSRF token. api/ai/* used to be exempted too, but those are
        // session-authenticated POST endpoints — exactly what CSRF protects
        // against forged cross-site requests. The frontend already sends the
        // XSRF-TOKEN header on every one of these calls, so no exemption is
        // needed.
        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
