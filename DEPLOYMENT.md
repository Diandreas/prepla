# Déploiement — PrePla (prepla.mirlab.cloud)

## VPS
- IP `147.93.87.67`, user `root`, clé `~/.ssh/hostinger_vps`
- App : `/home/mirlab-prepla/htdocs/prepla.mirlab.cloud`
- Repo : `https://github.com/Diandreas/prepla.git` (branche `main`)

## Checklist AVANT de pousser
1. **Bumper le cache du service worker** : `public/sw.js` → incrémenter `CACHE_NAME`
   (`prepla-shell-v14` → `prepla-shell-v15`…). Sans ça, les utilisateurs gardent
   les anciens assets publics.
2. `npm run build` (régénère `public/build/manifest.json` avec de nouveaux hash).
3. `php artisan test` et `node --check public/sw.js`.
4. Commit + push sur `main`.

## Configuration PWA en production

Vérifier dans le `.env` du serveur (ne jamais committer les valeurs VAPID) :

```dotenv
APP_URL=https://prepla.mirlab.cloud
SESSION_SECURE_COOKIE=true
VAPID_PUBLIC_KEY=...
VAPID_PRIVATE_KEY=...
VAPID_SUBJECT=mailto:...
```

La PWA et les notifications push exigent HTTPS en production. Après modification du
`.env`, lancer `php artisan config:clear`.

## Déploiement sur le VPS
```bash
ssh -o StrictHostKeyChecking=no -i ~/.ssh/hostinger_vps root@147.93.87.67 'bash -s' <<'DEPLOY'
set -e
APP=/home/mirlab-prepla/htdocs/prepla.mirlab.cloud
cd $APP
git pull origin main
composer install --no-dev --optimize-autoloader --no-interaction 2>&1 | tail -3
npm ci --silent && npm run build 2>&1 | tail -5
php artisan migrate --force
php artisan config:clear && php artisan route:clear && php artisan view:clear
# Symlink storage UNIQUEMENT s'il manque, et en tant que mirlab-prepla (sinon nginx 404 sur /storage)
if [ ! -e public/storage ]; then sudo -u mirlab-prepla ln -s "$APP/storage/app/public" public/storage; fi
DEPLOY
```

## Vérifier que la MAJ est bien en ligne
- DevTools → Network : les fichiers `/build/assets/*.js` doivent avoir de **nouveaux hash**.
- Ouvrir `/manifest.json?v=4`, `/sw.js` et `/offline` et vérifier une réponse HTTP 200.
- Application → Manifest : vérifier les icônes, les trois captures et `display: standalone`.
- Application → Service Workers : une nouvelle version doit afficher l'invite « Mise à jour
  disponible ». Elle ne doit plus recharger automatiquement un examen en cours.
- Sur un téléphone Android puis un iPhone : installer depuis l'écran d'accueil, ouvrir en
  plein écran, tester le micro/audio et activer une notification depuis le profil.

## Pièges connus
- **« 000 » en local (ping/curl)** = c'est ton **VPN**, pas le serveur. Désactive le VPN
  pour tester depuis ta machine. Le SSH peut passer même quand le VPN bloque le HTTP.
- **Repo privé** : utiliser de préférence une deploy key SSH en lecture seule sur le VPS.
  Ne jamais enregistrer un token GitHub directement dans l'URL du remote ou dans ce fichier.
- **Symlink storage** : ne jamais lancer `php artisan storage:link` en root (nginx
  `disable_symlinks if_not_owner` → 404 sur `/storage/*`). Recréer en `mirlab-prepla`.
- **Sons / nouveaux assets** : s'ils « ne s'entendent pas / n'apparaissent pas » alors
  qu'ils sont sur le serveur → accepter l'invite de mise à jour PWA. Le bump de
  `CACHE_NAME` purge ensuite les anciens assets publics.

Pour la publication Android, suivre [`docs/pwa-play-store.md`](docs/pwa-play-store.md).
