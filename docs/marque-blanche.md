# Marque blanche d'une instance

Le même dépôt sert plusieurs sites. Chaque site a son propre dossier, sa propre
base et son propre `.env` ; la marque se règle uniquement dans ce `.env`
(`config/marque.php`).

- `prepla.mirlab.cloud` : PrePla. **Aucune variable `MARQUE_*`** : tout reste
  strictement identique (vérifié par `tests/Feature/MarqueBlancheTest.php`).
- `app.fszentrum.com` : « Fliessende Sprache Zentrum », centre de langue allemande.

Ne pas confondre avec la personnalisation d'un **centre** (logo et couleurs en
base, page « Personnalisation ») : elle se pose par-dessus la marque de
l'instance.

## Variables

| Variable | Défaut | Rôle |
|---|---|---|
| `MARQUE_NOM` | `PrePla` | Nom affiché partout (titres, en-têtes, e-mails, manifeste, textes). Différent de « PrePla » = marque blanche : PrePla n'apparaît plus. |
| `MARQUE_NOM_COURT` | `MARQUE_NOM` | Nom sous l'icône de l'application installée, logo de la landing. |
| `MARQUE_SLOGAN` | `Préparation aux examens de langue` | Description du manifeste PWA. |
| `MARQUE_LOGO` | `/icons/logo.png?v=4` | Logo (chemin sous `public/`). |
| `MARQUE_LOGO_CARRE` | `/icons/pwa-512-v4.png` | Logo carré. |
| `MARQUE_FAVICON` | `/icons/pwa-192-v4.png` | Icône d'onglet. |
| `MARQUE_ICONE_192`, `MARQUE_ICONE_512`, `MARQUE_ICONE_MASKABLE`, `MARQUE_ICONE_APPLE` | icônes PrePla | Icônes PWA (192, 512, 512 « maskable », 180 Apple). |
| `MARQUE_COULEUR_PRIMAIRE` | `#4A90E2` | Boutons, liens (`--primary`). Format `#RRGGBB`. |
| `MARQUE_COULEUR_SECONDAIRE` | `#0B2D63` | Titres, barre du navigateur (`--marque-accent`, `theme-color`). |
| `MARQUE_EMAIL_CONTACT` | `prepla.mirlab@gmail.com` | E-mail du support (pied de page de l'accueil). |
| `MARQUE_TELEPHONE` | `+237 693 42 79 13` | Numéro du support, écrit tel qu'il s'affiche (WhatsApp et appels : pied de page, bouton d'aide, menu « Aide & support »). Les demandes d'un centre à l'éditeur (formule, adresse web) restent sur le numéro PrePla. |
| `MARQUE_SITE` | vide | Site vitrine. |
| `MARQUE_LANGUES_INTERFACE` | vide = toutes | Langues proposées dans les sélecteurs, ex. `de,fr,en`. |
| `MARQUE_LANGUE_DEFAUT` | vide | Langue de l'interface à la première visite. |
| `MARQUE_EXAMENS` | vide = tous | Slugs des examens proposés (onboarding, profil, pratique, examens blancs, espaces centre), ex. `goethe,telc`. |
| `MARQUE_MASQUER_KIDS` | `false` | Retire l'entrée « Kids » du menu, et les adresses `/kids…` répondent 404. |
| `MARQUE_MASQUER_PRIX_PUBLICS` | `false` | Retire la section tarifs de la landing. |
| `MARQUE_MASCOTTE` | `true` | `false` : le renard n'est plus affiché (pages d'erreur comprises : le logo carré le remplace). |

Penser aussi à `APP_NAME` et `MAIL_FROM_NAME` (expéditeur des e-mails) et à `APP_URL`.

Après modification du `.env` en production : `php artisan config:cache`, puis
`php artisan marque:verifier` (affiche la marque active, signale les fichiers
manquants, les couleurs mal écrites et les slugs d'examens inconnus ; code de
sortie non nul en cas de problème).

## Exemple : Fliessende Sprache Zentrum

```dotenv
APP_NAME="Fliessende Sprache Zentrum"
APP_URL=https://app.fszentrum.com
MAIL_FROM_NAME="Fliessende Sprache Zentrum"

MARQUE_NOM="Fliessende Sprache Zentrum"
MARQUE_NOM_COURT="FSZ"
MARQUE_SLOGAN="Deutsch lernen, Prüfungen bestehen"
MARQUE_LOGO=/marques/fszentrum/logo.png
MARQUE_LOGO_CARRE=/marques/fszentrum/logo-512.png
MARQUE_FAVICON=/marques/fszentrum/icone-192.png
MARQUE_ICONE_192=/marques/fszentrum/icone-192.png
MARQUE_ICONE_512=/marques/fszentrum/icone-512.png
MARQUE_ICONE_MASKABLE=/marques/fszentrum/icone-maskable-512.png
MARQUE_ICONE_APPLE=/marques/fszentrum/apple-touch-icon-180.png
# Couleurs À CONFIRMER avec le centre :
MARQUE_COULEUR_PRIMAIRE=#C8102E
MARQUE_COULEUR_SECONDAIRE=#1A1A1A
MARQUE_EMAIL_CONTACT=kontakt@fszentrum.com
MARQUE_TELEPHONE="+237 6XX XX XX XX"
MARQUE_SITE=https://fszentrum.com
MARQUE_LANGUES_INTERFACE=de,fr,en
MARQUE_LANGUE_DEFAUT=de
MARQUE_EXAMENS=goethe,telc
MARQUE_MASQUER_KIDS=true
MARQUE_MASQUER_PRIX_PUBLICS=true
MARQUE_MASCOTTE=false
```

Les fichiers `public/marques/fszentrum/*` sont à déposer sur le serveur (ils ne
sont pas dans le dépôt) ; `marque:verifier` les signale tant qu'ils manquent.

Clés d'API : pour l'instant la seconde instance reprend les mêmes clés que
PrePla (Mistral, Google, Stripe…) dans son `.env` ; elles seront remplacées
par les siennes plus tard, sans changement de code.

## Déployer la seconde instance sur le même VPS

Même procédure que PrePla, dans un autre dossier :
`/home/fszentrum-app/htdocs/app.fszentrum.com`.

1. DNS : `app.fszentrum.com` pointe vers le VPS ; site et certificat créés dans
   le panneau (utilisateur `fszentrum-app`).
2. Cloner le dépôt dans le dossier, `composer install --no-dev -o`,
   `npm ci && npm run build`.
3. `.env` propre à l'instance : base de données **séparée** (ne jamais pointer
   vers celle de PrePla), `APP_KEY` neuf (`php artisan key:generate`), les
   variables `MARQUE_*` ci-dessus, et les clés d'API.
4. `php artisan migrate --force`, seeders des langues et examens, puis
   `php artisan storage:link`, `config:cache`, `route:cache`, `view:cache`.
5. Déposer les logos dans `public/marques/fszentrum/`, puis
   `php artisan marque:verifier`.
6. File d'attente et planificateur : un worker et une entrée cron propres à ce
   dossier (même configuration que PrePla, chemins adaptés).
7. Chaque mise à jour : `git pull` dans **les deux** dossiers, puis la même
   suite de commandes (build, migrate, caches). Le `.env` n'est pas versionné :
   le sauvegarder à part.

## Ce que la marque change

- Titre des pages, en-têtes, logo du menu, pages de connexion, landing
  (logo, langues, tarifs), méta OG/Twitter, données structurées.
- E-mails (sujets, corps, en-tête et pied des gabarits Laravel publiés dans
  `resources/views/vendor`), notifications.
- Manifeste PWA : route dynamique `/manifest.webmanifest` (PrePla garde son
  `public/manifest.json`), icônes, `theme-color`.
- Textes i18n : `{{marque}}` (fr, en, de) et `{marque}` (en-centre) au lieu de
  « PrePla ».
- Couleurs : variables CSS injectées (`resources/views/partials/marque-centre.blade.php`).

Inchangés : clés de cache, noms de tables, routes, et le comportement de PrePla.

## Ce qui suit aussi la marque, sans réglage

- L'écran hors ligne de l'application installée (`/telechargements`) lit le nom dans `<meta name="application-name">`.
- Les icônes des notifications push prennent `MARQUE_ICONE_192`.
- Les aperçus de liens partagés (`og:image`) montrent le logo carré au lieu des images `public/og/` de PrePla.
- Les pages d'erreur 404 et 500 sont aussi en allemand, selon la langue du navigateur.
- Les pages légales gardent l'adresse de Mirlab, l'éditeur.
