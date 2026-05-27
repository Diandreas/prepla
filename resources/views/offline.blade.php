<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PrePla - Hors ligne</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            background: #0a0a0a;
            color: #fafafa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            max-width: 360px;
            width: 100%;
            text-align: center;
            space-y: 24px;
        }
        .icon { font-size: 64px; margin-bottom: 24px; }
        h1 { font-size: 24px; font-weight: 800; margin-bottom: 8px; }
        p { color: #a1a1aa; font-size: 14px; line-height: 1.6; margin-bottom: 24px; }
        .btn {
            display: inline-block;
            background: #f59e0b;
            color: #451a03;
            font-weight: 700;
            padding: 12px 28px;
            border-radius: 14px;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
            border: none;
        }
        .tip {
            margin-top: 32px;
            padding: 16px;
            background: #18181b;
            border-radius: 12px;
            font-size: 12px;
            color: #71717a;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">📡</div>
        <h1>Vous êtes hors ligne</h1>
        <p>Vérifiez votre connexion internet et réessayez. Vos données seront synchronisées dès que vous serez reconnecté.</p>
        <button class="btn" onclick="window.location.reload()">Réessayer</button>
        <div class="tip">
            💡 <strong>Astuce :</strong> Installez PrePla sur votre écran d'accueil pour accéder à vos derniers exercices même sans connexion.
        </div>
    </div>
</body>
</html>
