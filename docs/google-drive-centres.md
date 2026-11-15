# Google Drive des centres

Chaque centre range ses contenus lourds (vidéos, audios, PDF) et ses sauvegardes
dans **son propre Google Drive**, comme la sauvegarde WhatsApp. Le serveur
PrePla ne garde que la fiche du média (titre, source et droits, id Drive).

Sans les clés ci-dessous dans le `.env`, la fonction est masquée : la
médiathèque garde l'envoi local habituel et rien n'apparaît dans les paramètres.

## 1. Préparer Google Cloud (une seule fois, par l'administrateur PrePla)

1. Ouvrir https://console.cloud.google.com et créer (ou choisir) un projet « PrePla ».
2. **API et services > Bibliothèque** : activer **Google Drive API** et **Google Picker API**.
3. **Écran de consentement OAuth** :
   - type « Externe », nom de l'application « PrePla », e-mail d'assistance, logo ;
   - domaine autorisé : `mirlab.cloud` ; liens vers la politique de confidentialité et les conditions ;
   - scopes : `openid`, `email`, `https://www.googleapis.com/auth/drive.file`.
     **Aucun autre scope** : `drive.file` n'est pas un scope restreint, donc pas
     d'audit de sécurité payant. L'application ne voit que les fichiers qu'elle a
     créés ou que le centre choisit dans le sélecteur ;
   - publier l'application (passer de « Test » à « En production ») pour que tous
     les centres puissent se connecter. Google peut demander une vérification
     simple de la marque (quelques jours).
4. **Identifiants > Créer > ID client OAuth** :
   - type « Application Web » ;
   - origine JavaScript autorisée : `https://prepla.mirlab.cloud` ;
   - URI de redirection autorisé : `https://prepla.mirlab.cloud/center/google/callback`.
   - Noter l'**ID client** et le **code secret**.
5. **Identifiants > Créer > Clé API** (pour le sélecteur Google Picker) :
   - restreindre la clé aux sites `https://prepla.mirlab.cloud/*` ;
   - restreindre aux API « Google Picker API ».
6. **Numéro du projet** (Paramètres du projet / Tableau de bord) : c'est l'`APP_ID` du Picker.

## 2. Mettre les clés sur le serveur

Dans le `.env` du serveur (jamais dans git) :

```
GOOGLE_DRIVE_CLIENT_ID=...
GOOGLE_DRIVE_CLIENT_SECRET=...
GOOGLE_DRIVE_API_KEY=...
GOOGLE_DRIVE_APP_ID=...
```

Puis `php artisan config:cache`. Sans `GOOGLE_DRIVE_API_KEY`, le bouton
« Choisir dans mon Drive » est masqué ; l'envoi direct marche quand même.

Le planificateur (`schedule:run`) lance chaque dimanche à 4 h (Douala)
`php artisan centres:sauvegarder-drive`. On peut la lancer à la main,
éventuellement pour un seul centre : `--centre=12`.

## 3. Ce que voit le centre

- **Paramètres du centre > Stockage Google Drive** (responsable seulement) :
  état, compte Google connecté, date de la dernière sauvegarde, boutons
  « Connecter Google Drive », « Sauvegarder maintenant », « Déconnecter ».
- À la connexion, PrePla crée dans son Drive le dossier
  **« PrePla - <nom du centre> »** avec `Videos`, `Audios`, `Documents`, `Sauvegardes`.
- **Médiathèque** : « Envoyer vidéos, audios ou PDF » envoie le fichier
  **directement du navigateur vers Google** (par morceaux de 8 Mo, avec barre de
  progression et reprise après coupure) ; le fichier ne passe pas par PrePla.
  « Choisir dans mon Drive » ouvre le sélecteur Google. La source et les droits
  restent obligatoires. Un avertissement rappelle que chaque fichier ajouté
  devient **lisible par toute personne qui a son lien** (nécessaire pour la lecture).
- Sans Drive connecté : l'envoi local reste possible (limite actuelle) et un
  encart propose de connecter Google Drive.
- Retirer un fichier de la médiathèque ne le supprime pas du Drive.
- Déconnecter révoque l'accès de PrePla ; les fichiers restent dans le Drive,
  mais les élèves ne peuvent plus les lire dans PrePla.

## 4. Lecture par les élèves

Les médias Drive passent par `/media/drive/{id}` : PrePla vérifie que l'élève
est membre du centre (sinon 404), puis :

- **PDF et documents** : redirection vers la page de lecture Google Drive ;
- **audio, vidéo, image** : relais en flux avec l'en-tête `Range`. Une simple
  redirection vers le lien Drive ne marche pas dans `<audio>`/`<video>` (Google
  renvoie une page HTML d'avertissement pour les gros fichiers et ne gère pas
  les plages), donc pas d'avance rapide ni de lecture sur iPhone. Le relais
  n'écrit rien sur le disque : il transmet les octets au fur et à mesure.

## 5. Sécurité

- Le jeton de rafraîchissement est chiffré en base (`APP_KEY`) et n'est jamais
  envoyé au navigateur. Le navigateur ne reçoit qu'un jeton d'accès court pour
  le sélecteur et l'adresse d'une session d'envoi.
- Les sauvegardes ne contiennent que les contenus du centre (exercices,
  parcours, examens blancs, classes, liste des médias), sans liste d'élèves ni
  résultats.
