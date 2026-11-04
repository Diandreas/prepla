{{--
    Adresse d'un centre ferme (lot B, ResolveCenterFromHost). Page autonome :
    elle ne charge ni l'application ni la session, et ne dit rien d'autre que
    le nom affiche du centre.
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Établissement indisponible</title>
    <style>
        :root { color-scheme: light dark; }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
               font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background: #f6f7f9; color: #1f2937; }
        main { max-width: 28rem; margin: 1rem; padding: 2rem; border-radius: 1rem; background: #fff;
               border: 1px solid #e5e7eb; text-align: center; }
        h1 { font-size: 1.25rem; margin: 0 0 .75rem; }
        p { margin: 0; line-height: 1.5; color: #4b5563; }
        @media (prefers-color-scheme: dark) {
            body { background: #0f172a; color: #e5e7eb; }
            main { background: #111827; border-color: #1f2937; }
            p { color: #9ca3af; }
        }
    </style>
</head>
<body>
    <main>
        <h1>« {{ $nom }} » est indisponible</h1>
        <p>L'espace de cet établissement n'est pas ouvert pour le moment. Rapprochez-vous de votre établissement pour en savoir plus.</p>
    </main>
</body>
</html>
