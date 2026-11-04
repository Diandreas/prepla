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
        {{-- La marque de la page (app/Services/Center/Marque.php) : un centre en
             marque blanche remplace PrePla dans le titre, et ses couleurs sont
             posees des le premier affichage (resources/js/lib/marque-centre.js
             les tient ensuite a jour d'une page a l'autre). --}}
        @php($marquePage = $page['props']['marque'] ?? null)
        <title inertia>{{ \App\Support\Referencement::pour()['titre'] }} — {{ ($marquePage['masquer_prepla'] ?? false) ? $marquePage['nom'] : 'PrePla' }}</title>
        @include('partials.marque-centre', ['marque' => $marquePage])

        @include('partials.meta')

        @if (request()->routeIs('home'))
        @php($videoAccueil = \App\Support\VideoAccueil::donnees())
        {{-- Données structurées : uniquement sur la landing publique. L'abonnement
             est facturé en dollars : la monnaie annoncée était l'euro. --}}
        <script type="application/ld+json">
        {
            "@@context": "https://schema.org",
            "@@type": "WebApplication",
            "name": "PrePla",
            "url": "{{ url('/') }}",
            "applicationCategory": "EducationalApplication",
            "operatingSystem": "Web",
            "description": "Plateforme de préparation aux examens de langue (anglais, français, allemand) : test de niveau, plan de révision et exercices corrigés.",
            "inLanguage": ["fr", "en", "de"],
            "offers": {
                "@@type": "Offer",
                "price": "0",
                "priceCurrency": "USD",
                "description": "Gratuit : 3 exercices par jour ; 7 jours d'exercices illimités à l'inscription."
            }
        }
        </script>
        @if ($videoAccueil)
        <script type="application/ld+json">
        {
            "@@context": "https://schema.org",
            "@@type": "VideoObject",
            "name": "PrePla, par où commencer ?",
            "description": "En 34 secondes : choisir son examen, évaluer son niveau et savoir quoi travailler.",
            "inLanguage": "fr",
            "duration": "PT34S",
            "uploadDate": "2026-11-01",
            "thumbnailUrl": "{{ url($videoAccueil['sources']['affiche_1280_jpg']) }}",
            "contentUrl": "{{ url($videoAccueil['sources']['paysage']) }}"
        }
        </script>
        {{-- L'affiche est le plus grand élément du haut de page : chargée tout de
             suite. En économie de données, seulement la petite. --}}
        @if (request()->header('Save-Data') === 'on')
        <link rel="preload" as="image" type="image/webp" href="{{ $videoAccueil['sources']['affiche_640'] }}" fetchpriority="high">
        @else
        <link rel="preload" as="image" type="image/webp" href="{{ $videoAccueil['sources']['affiche_640'] }}" imagesrcset="{{ $videoAccueil['sources']['affiche_640'] }} 640w, {{ $videoAccueil['sources']['affiche_1280'] }} 1280w" imagesizes="(min-width: 1024px) 640px, 100vw" fetchpriority="high">
        @endif
        @endif
        @endif

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600|cormorant-garamond:400,500,600,700i,700|plus-jakarta-sans:400,500,600,700,800" rel="stylesheet" />

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
