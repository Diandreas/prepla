<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <title>Mes téléchargements — PrePla</title>

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

        <link rel="icon" type="image/png" sizes="192x192" href="/icons/pwa-192-v4.png">
        <link rel="manifest" href="/manifest.json?v=4">
        <meta name="theme-color" content="#0b2d63">
        <meta name="color-scheme" content="light dark">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-title" content="PrePla">
    </head>
    <body class="font-sans antialiased">
        <div id="offline-root"></div>
    </body>
</html>
