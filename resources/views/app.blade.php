<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        {{-- Apply dark mode before first paint to avoid FOUC --}}
        <script>
            (function() {
                var a = localStorage.getItem('appearance') || 'system';
                var isDark = a === 'dark' || (a === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                if (isDark) document.documentElement.classList.add('dark');
            })();
        </script>

        <title inertia>{{ config('app.name', 'PrePla') }} — Préparation aux examens de langue avec l'IA</title>

        {{-- SEO : ces balises vivent dans le Blade (pas dans React) car sans SSR
             les crawlers sociaux (WhatsApp, Facebook, LinkedIn) n'exécutent pas
             le JS et ne verraient rien. Google exécute le JS, mais le HTML
             initial reste la source la plus fiable. --}}
        <meta name="description" content="Prépare ton examen d'anglais, de français ou d'allemand (IELTS, TOEFL, DELF, Goethe…) avec des exercices personnalisés générés par IA, un test de niveau et un parcours adapté. Essai gratuit 7 jours.">
        <link rel="canonical" href="{{ url()->current() }}">

        {{-- Open Graph / Twitter --}}
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="PrePla">
        <meta property="og:title" content="PrePla — Préparation aux examens de langue avec l'IA">
        <meta property="og:description" content="Exercices personnalisés générés par IA, test de niveau et parcours adapté pour réussir ton examen de langue. Essai gratuit 7 jours.">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="{{ url('/icons/pwa-512.png') }}">
        <meta property="og:locale" content="fr_FR">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="PrePla — Préparation aux examens de langue avec l'IA">
        <meta name="twitter:description" content="Exercices personnalisés générés par IA, test de niveau et parcours adapté pour réussir ton examen de langue.">
        <meta name="twitter:image" content="{{ url('/icons/pwa-512.png') }}">

        @if (request()->routeIs('home'))
        {{-- Données structurées : uniquement sur la landing publique --}}
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "WebApplication",
            "name": "PrePla",
            "url": "{{ url('/') }}",
            "applicationCategory": "EducationalApplication",
            "operatingSystem": "Web",
            "description": "Plateforme de préparation aux examens de langue (anglais, français, allemand) avec exercices générés par IA, test de niveau et parcours personnalisé.",
            "inLanguage": ["fr", "en", "de"],
            "offers": {
                "@type": "Offer",
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

        <link rel="icon" type="image/png" sizes="192x192" href="/icons/pwa-192.png?v=3">
        <link rel="icon" type="image/png" sizes="512x512" href="/icons/pwa-512.png?v=3">
        <link rel="shortcut icon" href="/icons/pwa-192.png?v=3">
        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#1A2B48">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="PrePla">
        <link rel="apple-touch-icon" href="/icons/pwa-192.png?v=3">

    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
