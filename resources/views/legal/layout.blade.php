<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — PrePla</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cormorant-garamond:700i|plus-jakarta-sans:400,500,600,700" rel="stylesheet" />
    <style>
        :root { color-scheme: light dark; }
        body {
            margin: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f5f8fc;
            color: #13233f;
            line-height: 1.7;
        }
        @media (prefers-color-scheme: dark) {
            body { background: #0b1322; color: #eef3fb; }
            a { color: #7db3f5; }
        }
        .wrap { max-width: 760px; margin: 0 auto; padding: 3rem 1.5rem 5rem; }
        .brand { display: inline-flex; align-items: baseline; gap: .1rem; text-decoration: none; margin-bottom: 2rem; }
        .brand span:first-child { font-family: 'Cormorant Garamond', serif; font-style: italic; font-weight: 700; font-size: 1.6rem; color: inherit; }
        .brand .gold { font-family: 'Cormorant Garamond', serif; font-style: italic; font-weight: 700; font-size: 1.6rem; color: #F5A623; }
        h1 { font-size: 1.75rem; margin-bottom: .25rem; }
        .updated { font-size: .85rem; opacity: .6; margin-bottom: 2.5rem; }
        h2 { font-size: 1.15rem; margin-top: 2.5rem; }
        a { color: #3B82E0; }
        ul { padding-left: 1.25rem; }
        li { margin-bottom: .4rem; }
        .back { display: inline-block; margin-top: 3rem; font-size: .9rem; }
    </style>
</head>
<body>
    <div class="wrap">
        <a href="/" class="brand"><span>Pre</span><span class="gold">Pla</span></a>
        <h1>@yield('title')</h1>
        <p class="updated">Dernière mise à jour : @yield('updated')</p>
        @yield('content')
        <a href="/" class="back">← Retour à l'accueil</a>
    </div>
</body>
</html>
