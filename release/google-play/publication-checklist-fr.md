# Checklist de publication Google Play

## Blocages à lever avant la première soumission

- [ ] Choisir définitivement l'identifiant Android. Proposition : `cloud.mirlab.prepla`.
  Il devient difficile à changer après publication.
- [ ] Choisir la stratégie de paiement Android : Google Play Billing, programme de
  facturation alternative si éligible, ou version Android sans achat/lien Stripe.
- [ ] Créer un compte de démonstration permanent et reporter ses identifiants dans la
  section « Accès à l'application ».
- [ ] Déployer la page publique `/account-deletion` ajoutée au projet et vérifier son accès
  sans connexion.
- [ ] Confirmer toutes les lignes du brouillon « Sécurité des données » avec les réglages
  réels d'hébergement, de messagerie, de Mistral AI, Deepgram, Stripe et Google.
- [ ] Choisir précisément le public cible et répondre aux questions relatives aux enfants.

## Construire le conteneur Android TWA

- [ ] Installer Node.js, un JDK et les outils Android sur la machine de build.
- [ ] Installer Bubblewrap : `npm install --global @bubblewrap/cli`.
- [ ] Initialiser avec :
  `bubblewrap init --manifest=https://prepla.mirlab.cloud/manifest.json`.
- [ ] Utiliser le nom `PrePla`, l'URL de démarrage `/dashboard`, l'affichage `standalone`
  et l'identifiant Android choisi.
- [ ] Générer puis tester l'APK local : `bubblewrap build`.
- [ ] Conserver la clé d'upload et ses mots de passe hors du dépôt Git.
- [ ] Générer le fichier `.aab` signé destiné à Play Console.

## Relier le domaine à l'application

- [ ] Activer Play App Signing dans Play Console.
- [ ] Copier l'empreinte SHA-256 du certificat **App signing key certificate**.
- [ ] Remplacer la valeur factice dans `assetlinks.template.json`.
- [ ] Publier le résultat sous
  `https://prepla.mirlab.cloud/.well-known/assetlinks.json` avec le type
  `application/json`.
- [ ] Vérifier la liaison et l'absence de barre d'adresse dans la TWA installée depuis le
  canal de test.

## Remplir Play Console

- [ ] Importer les textes du dossier `text/`.
- [ ] Importer l'icône, l'image de présentation et les six captures téléphone.
- [ ] Renseigner la catégorie Éducation, l'email de support, le site web, la politique de
  confidentialité et l'URL de suppression de compte.
- [ ] Compléter Accès à l'application, Sécurité des données, classification du contenu,
  public cible, annonces et déclarations de paiement.
- [ ] Importer le `.aab` en test interne, corriger les alertes du rapport pré-lancement,
  puis lancer le test fermé si le compte développeur y est soumis.

## Test sur un vrai appareil Android

- [ ] Installation et mise à jour depuis Google Play.
- [ ] Connexion email et, si activée, connexion Google.
- [ ] Navigation, retour Android, rotation et clavier virtuel.
- [ ] Les 35 composants d'exercice s'affichent ; tester en priorité oral, écoute, OCR et
  écriture IA avec les API de production.
- [ ] Permission microphone demandée au bon moment et refus géré proprement.
- [ ] Permission notifications facultative, activation et désactivation fonctionnelles.
- [ ] Écran hors ligne et reprise après reconnexion.
- [ ] Suppression réelle d'un compte de test.
- [ ] Paiement conforme à la stratégie Android retenue.

## Passage en production

- [ ] Répondre à tous les retours de testeurs et alertes Play Console.
- [ ] Ajouter les notes de version en français.
- [ ] Lancer progressivement la production et surveiller les erreurs, avis et taux de
  plantage.

Références :

- [Trusted Web Activity et Bubblewrap](https://developer.chrome.com/docs/android/trusted-web-activity/quick-start)
- [Digital Asset Links pour une TWA](https://developer.chrome.com/docs/android/trusted-web-activity/android-for-web-devs)
- [Règles Google Play sur les paiements](https://support.google.com/googleplay/android-developer/answer/9858738?hl=fr)
- [Exigences de test pour les nouveaux comptes personnels](https://support.google.com/googleplay/android-developer/answer/14151465?hl=fr)
