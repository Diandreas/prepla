<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0b2d63">
    <meta name="color-scheme" content="light dark">
    <title>PrePla — Hors ligne</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            min-height: 100dvh;
            display: grid;
            place-items: center;
            padding: max(24px, env(safe-area-inset-top)) max(20px, env(safe-area-inset-right)) max(24px, env(safe-area-inset-bottom)) max(20px, env(safe-area-inset-left));
            background:
                radial-gradient(circle at 15% 10%, rgba(74, 144, 226, .22), transparent 34%),
                radial-gradient(circle at 85% 90%, rgba(245, 158, 11, .12), transparent 30%),
                #f6f8fc;
            color: #10213d;
            font-family: "Plus Jakarta Sans", Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .card {
            width: min(100%, 390px);
            padding: 30px 24px 24px;
            overflow: hidden;
            text-align: center;
            background: rgba(255, 255, 255, .94);
            border: 1px solid rgba(26, 43, 72, .10);
            border-radius: 28px;
            box-shadow: 0 24px 70px rgba(26, 43, 72, .15);
        }
        .logo {
            width: 74px;
            height: 74px;
            margin-bottom: 20px;
            border-radius: 22px;
            box-shadow: 0 12px 26px rgba(11, 45, 99, .22);
        }
        .status {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 14px;
            padding: 6px 10px;
            color: #8a5700;
            background: #fff6df;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .02em;
        }
        .status::before {
            width: 7px;
            height: 7px;
            content: "";
            background: #f59e0b;
            border-radius: 999px;
        }
        h1 { margin: 0 0 10px; font-size: clamp(24px, 7vw, 30px); line-height: 1.15; }
        p { margin: 0 auto 24px; color: #64748b; font-size: 14px; line-height: 1.65; }
        button {
            width: 100%;
            min-height: 48px;
            border: 0;
            border-radius: 15px;
            color: white;
            background: linear-gradient(135deg, #4a90e2, #286fc5);
            box-shadow: 0 9px 20px rgba(74, 144, 226, .28);
            cursor: pointer;
            font: inherit;
            font-size: 14px;
            font-weight: 800;
        }
        button:active { transform: translateY(1px); }
        .hint { display: block; margin-top: 17px; color: #94a3b8; font-size: 12px; line-height: 1.5; }
        @media (prefers-color-scheme: dark) {
            :root { color-scheme: dark; }
            body { background: radial-gradient(circle at 20% 10%, #153d73 0, transparent 35%), #061326; color: #f8fafc; }
            .card { background: rgba(10, 28, 53, .96); border-color: rgba(148, 163, 184, .16); }
            p { color: #aab7c9; }
            .status { color: #fbd38d; background: rgba(245, 158, 11, .13); }
            .hint { color: #8492a8; }
        }
    </style>
</head>
<body>
    <main class="card">
        <img class="logo" src="/icons/pwa-192-v4.png" alt="Logo PrePla" width="74" height="74">
        <div class="status">Connexion indisponible</div>
        <h1>Vous êtes hors ligne</h1>
        <p>PrePla a besoin d’une connexion pour charger de nouveaux exercices et enregistrer votre progression.</p>
        <button type="button" onclick="window.location.reload()">Réessayer</button>
        <span class="hint">Cette page se fermera automatiquement dès que la connexion reviendra.</span>
    </main>
    <script>
        window.addEventListener('online', function () { window.location.reload(); });
    </script>
</body>
</html>
