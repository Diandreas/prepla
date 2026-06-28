# Déploiement — PrePla (prepla.mirlab.cloud)

## VPS
- IP `147.93.87.67`, user `root`, clé `~/.ssh/hostinger_vps`
- App : `/home/mirlab-prepla/htdocs/prepla.mirlab.cloud`
- Repo : `https://github.com/Diandreas/prepla.git` (branche `main`)

## Checklist AVANT de pousser
1. **Bumper le cache du service worker** : `public/sw.js` → incrémenter `CACHE_NAME`
   (`prepla-v3` → `prepla-v4`…). Sans ça, les utilisateurs gardent l'ancien cache.
2. `npm run build` (régénère `public/build/manifest.json` avec de nouveaux hash).
3. Commit + push sur `main`.

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
- Application → Service Workers : la nouvelle version doit s'activer ; la page se recharge
  automatiquement (logique `controllerchange` dans `resources/js/app.tsx`).

## Pièges connus
- **« 000 » en local (ping/curl)** = c'est ton **VPN**, pas le serveur. Désactive le VPN
  pour tester depuis ta machine. Le SSH peut passer même quand le VPN bloque le HTTP.
- **Repo privé** : pour déployer, soit le repo est temporairement public, soit le remote
  du serveur a un token valide :
  `git remote set-url origin https://Diandreas:<TOKEN>@github.com/Diandreas/prepla.git`
- **Symlink storage** : ne jamais lancer `php artisan storage:link` en root (nginx
  `disable_symlinks if_not_owner` → 404 sur `/storage/*`). Recréer en `mirlab-prepla`.
- **Sons / nouveaux assets** : s'ils « ne s'entendent pas / n'apparaissent pas » alors
  qu'ils sont sur le serveur → c'est l'ancien bundle JS servi par le SW. Le bump de
  `CACHE_NAME` + le reload auto règlent ça.
