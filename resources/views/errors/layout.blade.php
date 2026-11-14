{{--
    Gabarit commun des pages d'erreur (404, 500). Page autonome : elle ne
    charge ni l'application React ni le build Vite, pour s'afficher meme quand
    c'est justement l'application qui est en panne.

    La langue suit le navigateur (francais par defaut, anglais si le
    navigateur le prefere) : une adresse inconnue ne passe pas par les
    middlewares qui lisent la langue du compte.

    L'illustration manquante retombe sur le renard d'accueil, puis disparait :
    jamais d'image cassee.
--}}
@php
    $en = request()->getPreferredLanguage(['fr', 'en']) === 'en';
    $t = $en ? $textes['en'] : $textes['fr'];
@endphp
<!DOCTYPE html>
<html lang="{{ $en ? 'en' : 'fr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $t['titre'] }} · PrePla</title>
    <link rel="icon" href="/favicon.ico">
    <style>
        :root {
            color-scheme: light dark;
            --fond: #f4f8fd; --carte: #ffffff; --texte: #172d4a; --doux: #4b5d75;
            --bord: #dde6f1; --primaire: #3478c8; --primaire-fonce: #24609f;
        }
        @media (prefers-color-scheme: dark) {
            :root { --fond: #0d1726; --carte: #13223a; --texte: #e8eef7; --doux: #a9b8cc; --bord: #22385a; }
        }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
               font-family: Nunito, system-ui, -apple-system, "Segoe UI", sans-serif; background: var(--fond); color: var(--texte); }
        main { width: 100%; max-width: 30rem; margin: 1rem; padding: 2rem 1.5rem; border-radius: 1.5rem; background: var(--carte);
               border: 1.5px solid var(--bord); box-shadow: 0 4px 0 #172d4a0d, 0 12px 32px #172d4a14; text-align: center; }
        img { display: block; margin: 0 auto 1rem; width: 180px; max-width: 60%; height: auto;
              animation: entree 600ms cubic-bezier(0.2, 0, 0, 1) both; transform-origin: 50% 100%; }
        .code { margin: 0; font-size: .8rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: var(--primaire); }
        h1 { font-size: 1.5rem; font-weight: 900; margin: .35rem 0 .75rem; }
        p { margin: 0; line-height: 1.55; color: var(--doux); }
        a.bouton { display: inline-block; margin-top: 1.5rem; padding: .8rem 1.6rem; border-radius: 1rem; font-weight: 800;
                   color: #fff; text-decoration: none; background: var(--primaire); box-shadow: 0 4px 0 var(--primaire-fonce);
                   transition: transform 150ms cubic-bezier(0.2, 0, 0, 1), box-shadow 150ms; }
        a.bouton:hover { transform: translateY(-2px); }
        a.bouton:active { transform: translateY(3px); box-shadow: 0 1px 0 var(--primaire-fonce); }
        a.bouton:focus-visible { outline: 3px solid var(--primaire); outline-offset: 3px; }
        @keyframes entree {
            0% { transform: translateY(16px) scale(.92, 1.06); opacity: 0; }
            40% { transform: translateY(-10px) scale(.96, 1.05); opacity: 1; }
            65% { transform: translateY(0) scale(1.06, .94); }
            100% { transform: none; }
        }
        @media (prefers-reduced-motion: reduce) {
            img, a.bouton { animation: none; transition: none; }
            a.bouton:hover, a.bouton:active { transform: none; }
        }
    </style>
</head>
<body>
    <main>
        <img src="{{ $image }}" alt="{{ $t['alt'] }}" width="180" height="180"
             onerror="if (!this.dataset.repli) { this.dataset.repli = '1'; this.src = '/illustrations/prepla-guide/welcome.png'; } else { this.remove(); }">
        <p class="code">{{ $t['code'] }}</p>
        <h1>{{ $t['titre'] }}</h1>
        <p>{{ $t['texte'] }}</p>
        <a class="bouton" href="{{ url('/') }}">{{ $t['bouton'] }}</a>
    </main>
</body>
</html>
