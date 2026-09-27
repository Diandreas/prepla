# Mode hors ligne — lot 1 « noyau utilisable »

État : **local uniquement**, non déployé. Ce document décrit ce qui marche aujourd'hui,
ce qui ne marche pas encore, et comment le vérifier sur un vrai appareil.

## 1. La promesse tenue à ce stade

Après une connexion à PrepLa et le téléchargement d'un pack, l'apprenant peut couper le
réseau, ouvrir `/telechargements`, faire la série téléchargée, voir ses corrections et
son score. Le travail est conservé sur l'appareil.

Ce qui **n'est pas** encore tenu : la synchronisation avec le serveur, la reprise d'une
séance interrompue en cours de route, les cours, l'audio, et la gestion de l'espace de
stockage. Ce sont les lots 2 et 3 du brief.

## 2. Comment c'est construit

Trois responsabilités séparées, comme prévu au §10.3 du brief :

1. **Interface publique versionnée** — `/telechargements` est une page Blade sans donnée
   personnelle ni jeton, servie par une entrée Vite dédiée (`resources/js/offline.tsx`),
   indépendante d'Inertia. Elle s'ouvre donc depuis le cache, sans serveur.
2. **Packs pédagogiques** — `GET /api/offline/packs` liste les séries préparées pour
   l'examen et le niveau de l'apprenant ; `POST /api/offline/packs/{exam}/{type}` renvoie
   le pack complet (énoncés, réponses attendues, explications). Le pack est enregistré
   dans IndexedDB.
3. **Travail de l'apprenant** — base IndexedDB `prepla-offline` (v1), magasins `packs`,
   `attempts` et `meta`. Tout est rangé par identifiant de compte : sur un appareil
   partagé, les données d'un autre apprenant ne sont jamais relues.

La correction locale (`resources/js/lib/offline/scoring.js`) reprend exactement les règles
du serveur : espaces ignorés, casse ignorée, apostrophes typographiques normalisées,
accents significatifs, réponse vide jamais correcte. Les deux implémentations sont
vérifiées sur **les mêmes cas** (`tests/fixtures/offline-scoring.json`), côté PHP et côté
JavaScript, pour qu'un score obtenu hors ligne ne change pas après synchronisation.

### Règles de cache corrigées

- L'activation ne supprime plus **tous** les caches étrangers : seules les anciennes
  versions du shell (`prepla-shell-*`) sont purgées. Sans cette correction, le premier
  déploiement venu aurait effacé les packs téléchargés.
- Le service worker met en cache l'espace hors ligne **et son bundle**, résolu depuis le
  manifeste Vite (fichier d'entrée, CSS et morceaux importés), donc les noms hachés ne
  laissent jamais un cache incomplet.
- Une navigation vers `/telechargements` est servie depuis le cache quand le réseau
  manque ; les autres navigations restent réseau d'abord, pour ne jamais réafficher une
  page personnelle périmée.
- Cache passé en `prepla-shell-v22`.

## 3. Vérifications déjà faites

- **Tests PHP : 126 réussis, 1 584 assertions**, dont 6 sur les endpoints de packs
  (catalogue filtré par niveau, téléchargement idempotent, format non préparé refusé,
  examen étranger refusé, accès réservé aux connectés) et 1 de concordance des corrections.
- **Tests JavaScript : 11 réussis** (`npm run test:js`, via `node --test`) sur les mêmes
  cas partagés que le serveur.
- **TypeScript** et **ESLint** sans erreur ; build Vite réussi (entrée hors ligne : 8,7 ko,
  3,2 ko compressée).
- **Navigateur** : `/telechargements` ouvert réellement, pack injecté dans IndexedDB au
  format exact de l'API, séance déroulée de bout en bout — mauvaise réponse corrigée avec
  la réponse attendue et l'explication, bonne réponse validée, écran de résultat à 1/2, et
  tentative retrouvée dans IndexedDB (identifiant unique, `synced: false`).

## 4. Ce qui n'a pas pu être vérifié ici

Le navigateur intégré à l'outil **refuse d'enregistrer un service worker**. Donc :
l'ouverture réelle en mode avion, la purge ciblée des caches et la mise en cache du bundle
hors ligne n'ont pas été observées en fonctionnement. Le fichier est validé par lecture et
par `node --check`, mais cela ne remplace pas la recette ci-dessous.

Le bouton « Télécharger hors ligne » est couvert côté serveur par les tests, mais son
parcours complet dans l'application demande une session connectée.

## 5. Recette à faire sur un vrai appareil

1. Se connecter, ouvrir `Pratiquer` → une section de lecture, repérer un format marqué
   « Prêt sans IA », cliquer **Télécharger hors ligne** et attendre « Disponible hors ligne ».
2. Ouvrir **Mes téléchargements** : le pack doit apparaître avec son examen, son niveau et
   son format.
3. Activer le mode avion, **fermer complètement l'application**, puis la rouvrir sur
   `/telechargements`.
4. Faire la série entière : chaque réponse doit être corrigée immédiatement, avec son
   explication, sans réseau.
5. Vérifier l'écran de résultat, puis rouvrir la page : la séance doit toujours être
   comptée comme « en attente d'envoi ».
6. Rétablir le réseau : rien ne doit être envoyé pour l'instant (la synchronisation est le
   lot 2), et aucune donnée ne doit disparaître.
7. Répéter sur un second compte du même appareil : aucun pack ni aucune séance ne doit
   apparaître d'un compte à l'autre.

## 6. Suite prévue

- **Lot 2 — travail durable** : sauvegarde des réponses pendant la séance, reprise après
  fermeture, file d'envoi et endpoint Laravel de synchronisation authentifié et idempotent
  (identifiant unique par tentative, contrainte d'unicité en base) pour qu'un envoi répété
  ne crée jamais deux tentatives ni deux fois l'XP.
- **Lot 3 — cours et audio** : leçons, vocabulaire, MP3 réellement stockés, gestion de
  l'espace et suppression d'un pack.
- **Lot 4 — finition mobile** : « Continuer ma séance », compteur d'éléments en attente,
  écran clavier, zones sûres, accessibilité.
