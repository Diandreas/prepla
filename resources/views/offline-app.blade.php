<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <title>Mes téléchargements — {{ config('marque.nom') }}</title>

        {{-- Aucune donnée personnelle ni jeton de session ici : cette page doit pouvoir
             s'ouvrir hors ligne depuis le cache, et lit ensuite les données locales. --}}
        <meta name="robots" content="noindex">

        <script>
            (function () {
                var a = localStorage.getItem('appearance') || 'system';
                var isDark = a === 'dark' || (a === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                if (isDark) document.documentElement.classList.add('dark');
            })();
        </script>

        @vite(['resources/css/app.css', 'resources/js/offline.tsx'])

        <link rel="icon" type="image/png" sizes="192x192" href="{{ config('marque.icone_192') }}">
        <link rel="manifest" href="{{ \App\Support\MarqueInstance::urlDuManifeste() }}">
        <meta name="theme-color" content="{{ \App\Support\MarqueInstance::couleursPersonnalisees() ? \App\Support\MarqueInstance::couleurSecondaire() : '#0b2d63' }}">
        <meta name="color-scheme" content="light dark">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-title" content="{{ config('marque.nom_court') }}">
    </head>
    <body class="font-sans antialiased">
        <div id="offline-root"></div>
    </body>
</html>
