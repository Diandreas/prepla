<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        {{-- Apply dark mode before first paint to avoid FOUC --}}
        <script>
            (function() {
                var a = localStorage.getItem('appearance') || 'system';
                var isDark = a === 'dark' || (a === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                if (isDark) document.documentElement.classList.add('dark');
            })();
        </script>

        {{-- Chaque page publique de l'application a son titre, par nom de route
             (config/seo.php). Avant, l'accueil, l'inscription et la connexion
             annoncaient tous le meme. Inertia le remplace ensuite cote client. --}}
        <title inertia>{{ \App\Support\Referencement::pour()['titre'] }} — PrePla</title>

        @include('partials.meta')

        @if (request()->routeIs('home'))
        {{-- Données structurées : uniquement sur la landing publique --}}
        <script type="application/ld+json">
        {
            "@@context": "https://schema.org",
            "@@type": "WebApplication",
            "name": "PrePla",
            "url": "{{ url('/') }}",
            "applicationCategory": "EducationalApplication",
            "operatingSystem": "Web",
            "description": "Plateforme de préparation aux examens de langue (anglais, français, allemand) avec exercices générés par IA, test de niveau et parcours personnalisé.",
            "inLanguage": ["fr", "en", "de"],
            "offers": {
                "@@type": "Offer",
                "price": "0",
                "priceCurrency": "EUR",
                "description": "Essai gratuit de 7 jours"
            }
        }
        </script>
        @endif

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600|cormorant-garamond:400,500,600,700i,700|plus-jakarta-sans:400,500,600" rel="stylesheet" />

        @routes
        @viteReactRefresh
        @vite(['resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        @inertiaHead

        <link rel="icon" type="image/png" sizes="192x192" href="/icons/pwa-192-v4.png">
        <link rel="icon" type="image/png" sizes="512x512" href="/icons/pwa-512-v4.png">
        <link rel="shortcut icon" href="/icons/pwa-192-v4.png">
        <link rel="manifest" href="/manifest.json?v=4">
        <meta name="theme-color" content="#0b2d63">
        <meta name="color-scheme" content="light dark">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="PrePla">
        <link rel="apple-touch-icon" sizes="180x180" href="/icons/apple-touch-icon-180-v4.png">

    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
