# PrePla — reprise du travail (pour n'importe quel Claude en CLI)

Dernière mise à jour : 2026-11-15. Lis ce fichier en entier avant d'agir.

## Le projet
- PrePla : préparation aux examens de langue (TCF, TEF, DELF, IELTS, Goethe, telc…) : apprenants adultes, espace centre (écoles), PrePla Kids.
- Stack : Laravel 12, Inertia et React 19, Pest, SQLite en prod.
- Prod : https://prepla.mirlab.cloud
- Dépôt local : `E:\project\prepla`, sur la branche main.
- Mémoire Claude : `C:\Users\Ncomp\.claude\projects\E--project-prepla\memory\MEMORY.md`, à lire.
- Parler au propriétaire en français simple. Il veut qu'on aille vite et qu'on déploie dès que c'est prêt.

## Règles absolues
- Ne jamais lancer `demo:reset` ni `B2BDemoSeeder` en prod.
- Ne jamais écraser ni commiter le `.env` de prod.
- Ne jamais commiter `.claude/settings.local.json` ni `.claude/launch.json`.
- Ne jamais écrire ni promettre « audios officiels » : ce sont des voix de synthèse et des textes originaux.
- Les données personnelles restent en local, et aucun mot de passe de démo dans les documents.
- Commits en français, avec cette dernière ligne : `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`

## Déployer (VPS)
```bash
ssh -i ~/.ssh/hostinger_vps root@147.93.87.67
```
Puis, dans `/home/mirlab-prepla/htdocs/prepla.mirlab.cloud` :
1. Sauvegarde : `sudo -u mirlab-prepla sqlite3 database/database.sqlite "VACUUM INTO 'storage/backups/avant-XXX.sqlite'"`
2. `git pull origin main && php artisan migrate --force`
3. Build en arrière-plan, parce que le réseau coupe : `(nohup bash -c 'npm run build > /tmp/build.log 2>&1; echo FIN >> /tmp/build.log' &)`. Attendre que `FIN` apparaisse dans `/tmp/build.log`.
4. `php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache`
5. `chown -R mirlab-prepla:mirlab-prepla storage bootstrap/cache public/build database .git`
6. `php artisan prepla:check`

Nginx : `client_max_body_size 120m` est posé dans `/etc/nginx/sites-enabled/prepla.mirlab.cloud.conf`. L'ancienne configuration est sauvegardée dans `/root/prepla.conf.bak-20261115`.

## Tests en local
- Suite complète : `php -d memory_limit=1G vendor/bin/pest --parallel --processes=6`
- Puis `npx tsc --noEmit -p .` et `npm run build`. Le build est nécessaire : sans lui, les tests d'une nouvelle page échouent sur le manifest Vite.
- Dans un worktree, copier `vendor` (une vraie copie, pas une jonction), `.env` et `public/build`.

## Déjà en prod (commit 3092f7f)
- Révision des erreurs : notée côté serveur, et un rejeu reçoit une erreur 409.
- Test de niveau sur les 4 compétences, de A0 à C2, et grilles officielles de l'écrit.
- Examens blancs créés par le centre.
- Audios du centre, avec source et droits obligatoires. Les brouillons ne sont jamais visibles des élèves.
- Animations : sons, « +N XP », poses du renard.
- Pré-génération de la leçon suivante, protégée par un verrou.
- 32 illustrations adultes en WebP.

## Fait le 2026-11-15 et en prod (commit acdae63)
- Exercices similaires corrigés côté serveur.
- Bilan de niveau affiché à l'apprenant, et passage de A0 à A1 réparé.
- Illustrations branchées.
- Correction humaine par l'enseignant, avec la page « À corriger ».
- Import CSV d'élèves et de QCM.
- Google Drive par centre : invisible tant que les clés `GOOGLE_DRIVE_*` ne sont pas dans le `.env`.

Pour toute nouvelle fusion : `git fetch && git merge origin/main`, puis `git push origin HEAD:main`. Ne PAS faire de rebase.

## Aussi en prod (commit c41a4c5)
- **Créateur de parcours complet** : modules, séances et 12 types de blocs, avec les pages `/center/cours` et `/mes-cours`. L'ancien parcours d'étapes existe toujours : il faudra décider quand le retirer.
- **Live class Google Meet** : invisible sans clés Google. Le live utilise l'adresse de retour `/center/google/retour`, qui doit AUSSI être déclarée dans Google Cloud.

À vérifier dans un navigateur :
- le glisser-déposer de l'éditeur ;
- le texte riche ;
- le micro ;
- l'affichage des PDF ;
- les vidéos YouTube et Drive intégrées.

Les envois vidéo des parcours vont jusqu'à 500 Mo, mais nginx s'arrête à 120 Mo.

## Google Cloud (le propriétaire le fait lui-même)
Il crée un projet sur https://console.cloud.google.com :
1. APIs : activer Google Drive API, Google Picker API et Google Meet REST API.
2. Écran de consentement OAuth :
   - type Externe ;
   - nom PrePla ;
   - e-mail de contact ;
   - domaine `mirlab.cloud` ;
   - scopes `openid`, `email`, `.../auth/drive.file`, `.../auth/meetings.space.created` et `.../auth/meetings.space.readonly`.
   Jusqu'à 100 testeurs en mode test. Ensuite, demander la vérification (Meet est un scope sensible).
3. Identifiants :
   - ID client OAuth, de type Application Web, avec l'origine `https://prepla.mirlab.cloud` ;
   - URIs de redirection `https://prepla.mirlab.cloud/center/google/callback` ET `https://prepla.mirlab.cloud/center/google/retour` (ajouter aussi l'URI du live si `docs/live-class-google-meet.md` en donne une autre) ;
   - une clé API, restreinte au référent `https://prepla.mirlab.cloud/*` et aux APIs Picker et Drive ;
   - l'App ID, c'est-à-dire le numéro du projet.
4. Il met lui-même ces valeurs dans le `.env` du VPS (ne jamais lui demander de les coller dans le chat) :
   ```
   GOOGLE_DRIVE_CLIENT_ID=
   GOOGLE_DRIVE_CLIENT_SECRET=
   GOOGLE_DRIVE_API_KEY=
   GOOGLE_DRIVE_APP_ID=
   ```
   Puis `php artisan config:cache`. Le détail est dans `docs/google-drive-centres.md` et `docs/live-class-google-meet.md`.
5. Ensuite Claude teste avec un vrai compte :
   - l'envoi par morceaux (CORS) ;
   - le Picker ;
   - la lecture audio et vidéo ;
   - la création d'une réunion Meet.

Comptes Gmail gratuits : les réunions Meet de groupe sont limitées à 60 minutes. Pour des cours plus longs, il faut Workspace.

## Reste à faire (après la fusion)
- Le créateur d'exercices (`zone-media.tsx`) ne permet pas encore d'envoyer un fichier vers Drive.
- Un A0 qui ne fait que des examens blancs ne passe pas A1 (il manque l'appel à `promouvoirGrandDebutant`).
- `str_word_count` donne un mauvais compte avec les accents dans `WritingCorrectorService`, `ExerciseScoringService`, `DemoCompleter` et `SonoriserEcoutes`.
- La page achievements n'est pas traduite.
- Il n'y a que 5 médailles pour 12 badges.
- Vidéo : les rendus EN et 9:16 sont dans `E:\project\prepla-films\out\`, à vérifier. Changer la voix quand le paiement ElevenLabs sera réglé.
- Vérifier les nouveaux écrans dans un navigateur, sur la démo locale (port 8001, `.env.demo`).

## Seconde instance : Fliessende Sprache Zentrum (depuis le 2026-11-16)
- https://app.fszentrum.com : site CloudPanel, utilisateur `fszentrum-app`, dossier `/home/fszentrum-app/htdocs/app.fszentrum.com`. Même dépôt, branche main.
- Base SQLite à part. Elle contient seulement la banque commune, copiée depuis PrePla (exercices sans centre, examens, examens blancs PrePla). Aucun compte PrePla.
- `.env` : copie de celui de PrePla, avec les mêmes clés pour l'instant (le propriétaire leur créera leurs propres clés). `APP_URL`, `APP_NAME` et `CACHE_PREFIX=fsz_` sont propres à ce site.
- Certificat Let's Encrypt posé le 2026-11-16, valable jusqu'au 2027-02-14 (CloudPanel le renouvelle).
- Leur logo : `public/marques/fszentrum/logo.png`, fond gris en basse définition (en demander un en SVG). Couleurs : noir #1A1A1A, rouge #DD0000, or #FFCE00. Tous les examens sont proposés, le centre choisit lui-même.
- **À chaque déploiement de PrePla**, refaire la même procédure dans ce dossier (git pull, migrate, build, caches), en `sudo -u fszentrum-app`.
- Limites connues :
  - les webhooks Stripe arrivent sur PrePla ;
  - la connexion Google n'est pas autorisée pour ce domaine ;
  - le propriétaire crée lui-même le compte admin et règle le DNS.
- En cours : configuration de marque (`config/marque.php`, variables `MARQUE_*`), branche `worktree-wf_4d32ec00-b0d-*`. Une fois fusionnée, mettre les `MARQUE_*` dans le `.env` de FSZ.
