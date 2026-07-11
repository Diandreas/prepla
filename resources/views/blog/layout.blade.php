<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — Blog PrePla</title>
    <meta name="description" content="@yield('description')">
    <link rel="canonical" href="@yield('canonical')">

    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:site_name" content="PrePla">
    <meta property="og:title" content="@yield('title')">
    <meta property="og:description" content="@yield('description')">
    <meta property="og:url" content="@yield('canonical')">
    <meta property="og:locale" content="fr_FR">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="@yield('title')">
    <meta name="twitter:description" content="@yield('description')">

    @yield('structured_data')

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cormorant-garamond:700i|plus-jakarta-sans:400,500,600,700" rel="stylesheet" />
    <link rel="icon" type="image/png" sizes="192x192" href="/icons/pwa-192.png?v=3">

    <style>
        :root { color-scheme: light dark; }
        * { box-sizing: border-box; }
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
            .card, header, footer { border-color: rgba(255,255,255,0.08) !important; }
            .card { background: #101a2e !important; }
            .badge { background: rgba(245,166,35,0.12) !important; }
        }
        a { color: #3B82E0; }
        header {
            position: sticky; top: 0; z-index: 10;
            display: flex; align-items: center; justify-content: space-between;
            padding: 1rem 1.5rem; border-bottom: 1px solid rgba(19,35,63,0.1);
            background: inherit; backdrop-filter: blur(8px);
        }
        .brand { display: inline-flex; align-items: baseline; gap: .1rem; text-decoration: none; }
        .brand span:first-child { font-family: 'Cormorant Garamond', serif; font-style: italic; font-weight: 700; font-size: 1.4rem; color: inherit; }
        .brand .gold { font-family: 'Cormorant Garamond', serif; font-style: italic; font-weight: 700; font-size: 1.4rem; color: #F5A623; }
        .cta { font-size: .8rem; font-weight: 700; padding: .5rem 1rem; border-radius: .6rem; background: #3B82E0; color: #fff; text-decoration: none; }
        .wrap { max-width: 860px; margin: 0 auto; padding: 2.5rem 1.5rem 5rem; }
        .badge { display: inline-block; font-size: .7rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #b8790a; background: rgba(245,166,35,0.12); padding: .2rem .6rem; border-radius: 999px; }
        article { max-width: 680px; margin: 0 auto; }
        .grid { display: grid; grid-template-columns: 1fr; gap: 1.1rem; margin-bottom: .5rem; }
        @media (min-width: 640px) { .grid { grid-template-columns: 1fr 1fr; } }
        .card { display: block; text-decoration: none; color: inherit; border: 1px solid rgba(19,35,63,0.1); border-radius: 1rem; padding: 1.1rem; margin-bottom: 1.25rem; background: #fff; transition: transform .15s ease; }
        .grid .card { margin-bottom: 0; }
        .card:hover { transform: translateY(-3px); }
        .card h2 { margin: 0 0 .4rem; font-size: 1.05rem; line-height: 1.35; }
        .card p { margin: 0; font-size: .87rem; opacity: .75; }
        .meta { font-size: .78rem; opacity: .6; margin-top: .6rem; }
        h1 { font-size: 1.85rem; margin-bottom: .3rem; line-height: 1.25; }
        article h2 { font-size: 1.2rem; margin-top: 2.2rem; }
        article table { width: 100%; border-collapse: collapse; margin: 1.2rem 0; font-size: .88rem; }
        article th, article td { text-align: left; padding: .5rem .7rem; border-bottom: 1px solid rgba(19,35,63,0.12); }
        article th { font-weight: 700; }
        article ul, article ol { padding-left: 1.3rem; }
        article li { margin-bottom: .4rem; }
        footer { border-top: 1px solid rgba(19,35,63,0.1); padding: 2rem 1.5rem; text-align: center; font-size: .8rem; opacity: .6; }
    </style>
</head>
<body>
    <header>
        <a href="/" class="brand"><span>Pre</span><span class="gold">Pla</span></a>
        <a href="/register" class="cta">Essai gratuit</a>
    </header>
    <div class="wrap">
        @yield('content')
    </div>
    <footer>
        <a href="/blog">Blog</a> · <a href="/">Accueil</a> · <a href="/privacy">Confidentialité</a> · <a href="/terms">CGU</a>
    </footer>
</body>
</html>
