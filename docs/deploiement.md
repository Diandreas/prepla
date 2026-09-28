# Déploiement en production

Production : <https://prepla.mirlab.cloud>

| | |
|---|---|
| Serveur | `root@147.93.87.67`, clé `~/.ssh/hostinger_vps` |
| Dossier | `/home/mirlab-prepla/htdocs/prepla.mirlab.cloud` |
| Propriétaire des fichiers | `mirlab-prepla` — **pas** `root` |
| Base | SQLite, `database/database.sqlite` |
| Serveur web | nginx, avec `disable_symlinks if_not_owner` |

Une session Claude dans le cloud **ne peut pas** exécuter ces commandes : elle n'a
pas la clé SSH et sa politique réseau refuse le port 22. Elle prépare le code et la
branche ; le déploiement se lance depuis une machine qui a la clé.

## Avant de commencer : vérifier la branche

Le serveur suit `main`. Du travail poussé sur une branche de développement n'y est
pas : un `git pull origin main` déploierait alors l'ancien code sans rien signaler.

```bash
git fetch origin
git log --oneline origin/main..origin/<branche-de-travail>
```

Une sortie non vide veut dire qu'il reste à fusionner dans `main` avant de déployer.
Ne jamais faire pointer le serveur sur une branche de travail.

## 1. Sauvegarder la base — toujours en premier

```bash
ssh -i ~/.ssh/hostinger_vps root@147.93.87.67 \
  "cd /home/mirlab-prepla/htdocs/prepla.mirlab.cloud && \
   cp -p database/database.sqlite database/database.sqlite.bak-\$(date +%F-%H%M%S)"
```

## 2. Récupérer le code

```bash
ssh -i ~/.ssh/hostinger_vps root@147.93.87.67 \
  "cd /home/mirlab-prepla/htdocs/prepla.mirlab.cloud && \
   git checkout -- package-lock.json && git pull origin main && git log --oneline -1"
```

`git checkout -- package-lock.json` n'est pas facultatif : `npm ci` modifie ce
fichier sur le serveur et le `git pull` refuse de passer tant qu'il diverge.

## 3. Dépendances et compilation

```bash
ssh -i ~/.ssh/hostinger_vps root@147.93.87.67 \
  "cd /home/mirlab-prepla/htdocs/prepla.mirlab.cloud && \
   composer install --no-dev --optimize-autoloader --no-interaction && \
   npm ci && npm run build"
```

## 4. Migrations

```bash
ssh -i ~/.ssh/hostinger_vps root@147.93.87.67 \
  "cd /home/mirlab-prepla/htdocs/prepla.mirlab.cloud && \
   php artisan migrate:status | tail -5 && php artisan migrate --force"
```

Lire `migrate:status` avant de lancer. **Jamais** `migrate:fresh`, **jamais**
`db:wipe`, **jamais** de seeder général : la base porte les données réelles des
apprenants.

## 5. Vider les caches et remettre les droits

```bash
ssh -i ~/.ssh/hostinger_vps root@147.93.87.67 \
  "cd /home/mirlab-prepla/htdocs/prepla.mirlab.cloud && \
   php artisan config:clear && php artisan route:clear && php artisan view:clear && \
   chown -R mirlab-prepla:mirlab-prepla storage bootstrap/cache public/build database .git"
```

Le `chown` est indispensable : tout ce que `root` vient d'écrire lui appartient, et
nginx tourne avec `disable_symlinks if_not_owner` — un fichier mal possédé donne des
403 ou des 404 silencieux sur les assets et les audios.

## 6. Vérifier — un code 200 ne suffit pas

Le contrôle intégré dit ce qu'une page servie ne dit pas. Il sort en erreur au
premier manque :

```bash
ssh -i ~/.ssh/hostinger_vps root@147.93.87.67 \
  "cd /home/mirlab-prepla/htdocs/prepla.mirlab.cloud && php artisan prepla:check"
```

Il vérifie les clés `MISTRAL_API_KEY`, `DEEPGRAM_API_KEY` et les deux identifiants de
tarif Stripe, que `APP_DEBUG` est éteint, que la base répond, que le manifeste Vite
existe, que le lien `public/storage` est là et que les dossiers sont inscriptibles.

Puis les pages, et le bundle réellement servi :

```bash
curl -s -o /dev/null -w "accueil:%{http_code}\n" -m 25 https://prepla.mirlab.cloud/
curl -s -o /dev/null -w "login:%{http_code}\n"   -m 25 https://prepla.mirlab.cloud/login

ssh -i ~/.ssh/hostinger_vps root@147.93.87.67 \
  "cd /home/mirlab-prepla/htdocs/prepla.mirlab.cloud && \
   ls public/build/assets/ | grep -E '^(player|lesson|session-report)-' && \
   tail -3 storage/logs/laravel.log"
```

## Les pièges qui coûtent du temps

1. **Le `.env` n'est pas dans git.** Il porte la clé Mistral, `STRIPE_PRICE_MONTHLY`,
   `STRIPE_PRICE_ANNUAL` et `CASHIER_CURRENCY`. Une restauration du VPS l'a effacé
   deux fois en deux jours. `.env.example` liste les clés attendues, et
   `php artisan prepla:check` dit lesquelles manquent.
2. **Le symlink `public/storage`** doit appartenir à `mirlab-prepla`, sinon les audios
   des exercices renvoient 404.
3. **Pas de `config:cache` en ce moment** : les clés lues via `env()` fonctionnent. Si
   quelqu'un l'active, tout code appelant `env()` hors d'un fichier de configuration
   casse en silence.
4. **Le serveur peut devenir injoignable un moment sans être en panne** : tous les
   ports ont timeouté une heure le 25 septembre, puis c'est revenu seul. Réessayer
   avant de conclure à une panne.
5. **Un `git pull` ne suffit jamais seul** : sans rebuild, le navigateur continue de
   servir les anciens bundles ; sans `config:clear`, les anciens réglages.

## En cas de problème

Restaurer la base depuis la sauvegarde de l'étape 1 :

```bash
ssh -i ~/.ssh/hostinger_vps root@147.93.87.67 \
  "cd /home/mirlab-prepla/htdocs/prepla.mirlab.cloud && \
   ls -t database/database.sqlite.bak-* | head -3"
```

Revenir au code précédent, puis refaire les étapes 3, 5 et 6 :

```bash
ssh -i ~/.ssh/hostinger_vps root@147.93.87.67 \
  "cd /home/mirlab-prepla/htdocs/prepla.mirlab.cloud && git log --oneline -5"
```

Une migration déjà appliquée ne se défait pas en revenant au code : vérifier
`migrate:status` avant de conclure qu'un retour arrière suffit.
