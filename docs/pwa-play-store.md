# Publier PrePla sur Google Play

La PWA reste hébergée sur `https://prepla.mirlab.cloud`. Le Play Store distribue une
petite application Android (Trusted Web Activity, ou TWA) qui ouvre cette PWA en plein
écran. Les mises à jour ordinaires du site restent donc déployées par Laravel/Vite sans
nouvelle soumission au store.

## 1. Préparer la version web

- Déployer la PWA en HTTPS et vérifier que `/manifest.json?v=4`, `/sw.js` et `/offline`
  répondent avec HTTP 200.
- Vérifier l'installation, le micro, l'audio, la connexion et les notifications sur un
  vrai téléphone Android.
- Garder le domaine, le `start_url` et l'identité du manifeste stables.
- Préparer une politique de confidentialité publique et un compte de démonstration pour
  l'équipe de validation Google Play.
- Déployer et vérifier la page publique de suppression de compte :
  `https://prepla.mirlab.cloud/account-deletion`.

## 2. Régler le paiement avant la soumission

PrePla utilise actuellement Stripe via Laravel Cashier pour vendre un abonnement à du
contenu numérique. Une application distribuée par Google Play doit normalement utiliser
Google Play Billing pour ce type d'achat et ne doit pas diriger l'utilisateur vers Stripe
depuis l'application.

Choisir l'une de ces stratégies avant la publication :

1. intégrer Google Play Billing dans le conteneur Android et synchroniser les droits avec
   le backend Laravel ;
2. publier une version Android « consommation uniquement » : les abonnés existants se
   connectent, mais aucun bouton ni lien Stripe n'est présenté dans l'application ;
3. s'inscrire à un programme Google de facturation alternative ou d'offres externes si
   PrePla et ses pays de distribution sont éligibles.

Référence : [règles Google Play sur les paiements](https://support.google.com/googleplay/android-developer/answer/9858738).

## 3. Générer le projet Android avec Bubblewrap

Installer Bubblewrap sur une machine disposant de Node.js, d'un JDK et des outils Android :

```bash
npm install --global @bubblewrap/cli
mkdir prepla-android
cd prepla-android
bubblewrap init --manifest=https://prepla.mirlab.cloud/manifest.json
bubblewrap build
```

Valeurs conseillées pendant l'initialisation :

- nom : `PrePla` ;
- identifiant Android : `cloud.mirlab.prepla` (à valider avant la première publication,
  car il ne pourra ensuite plus être changé) ;
- URL de démarrage : `/dashboard` ;
- mode : `standalone` ;
- couleurs : celles du manifeste PWA.

Le build produit notamment `app-release-signed.apk` pour les tests locaux et
`app-release-bundle.aab` pour Google Play. Sauvegarder le keystore et ses mots de passe
hors du dépôt Git : perdre cette clé complique ou empêche les futures mises à jour.

Références : [guide TWA officiel](https://developer.chrome.com/docs/android/trusted-web-activity/quick-start) et [Bubblewrap](https://github.com/GoogleChromeLabs/bubblewrap).

## 4. Relier le domaine à l'application

Dans Play Console, activer Play App Signing puis copier l'empreinte SHA-256 du certificat
de signature de l'application. Publier ensuite ce fichier à l'adresse exacte
`https://prepla.mirlab.cloud/.well-known/assetlinks.json` :

```json
[
  {
    "relation": ["delegate_permission/common.handle_all_urls"],
    "target": {
      "namespace": "android_app",
      "package_name": "cloud.mirlab.prepla",
      "sha256_cert_fingerprints": ["EMPREINTE_SHA256_DE_PLAY_APP_SIGNING"]
    }
  }
]
```

Utiliser l'empreinte **App signing key certificate** fournie par Play Console, et pas
seulement celle de la clé d'upload. Sans validation Digital Asset Links, l'application
s'ouvrira comme un onglet navigateur avec une barre d'adresse.

## 5. Créer la fiche Play Console

1. Créer le compte développeur, régler les frais affichés par Google et terminer la
   vérification d'identité.
2. Créer l'application PrePla dans Play Console.
3. Fournir la description, l'icône 512 px, les captures, la catégorie Éducation, les
   coordonnées de support et la politique de confidentialité.
4. Compléter « App access » avec un compte de démonstration, « Data safety », la
   classification du contenu, le public cible, les déclarations publicitaires et les
   informations de paiement.
5. Importer `app-release-bundle.aab` d'abord dans le canal de test interne, puis dans un
   test fermé.
6. Vérifier sur un appareil installé depuis Google Play que la barre d'adresse ne paraît
   pas, que la connexion fonctionne et que les liens restent dans l'application.

Les textes, l'icône, l'image de présentation, les captures téléphone et les brouillons de
déclaration sont prêts dans `release/google-play/`.

Google Play utilise les Android App Bundles (`.aab`) pour les nouvelles publications :
[documentation Play Console](https://support.google.com/googleplay/android-developer/answer/9859152).

## 6. Exigence des nouveaux comptes personnels

Pour un compte développeur **personnel créé après le 13 novembre 2023**, Google demande
actuellement un test fermé avec au moins **12 testeurs inscrits sans interruption pendant
14 jours** avant de pouvoir demander l'accès à la production. Cette contrainte ne doit
pas être confondue avec le test interne facultatif.

Référence : [exigences officielles de test](https://support.google.com/googleplay/android-developer/answer/14151465).

## 7. Mises à jour suivantes

- Une modification Laravel/React ou du contenu est publiée normalement sur le serveur ;
  aucune nouvelle version Play n'est nécessaire.
- Une modification du conteneur Android, des permissions, du paiement natif ou de l'icône
  Android exige un nouveau `.aab`, un `versionCode` supérieur et une nouvelle release.
- Après chaque déploiement PWA qui modifie l'app shell, incrémenter `CACHE_NAME` dans
  `public/sw.js` pour purger les anciens assets.
