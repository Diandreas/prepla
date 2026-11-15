# Live class (Google Meet)

Le module « Live class » permet à un centre de faire cours en direct à une classe.
**La vidéo passe entièrement par Google Meet** : PrePla n'héberge ni ne relaie
aucun flux audio ou vidéo, le petit serveur ne porte que du JSON léger.

PrePla ajoute autour du cours : planification, notifications, bandeau
« Live en cours — Rejoindre », compte à rebours, fichier calendrier `.ics`,
main levée, questions votées, sondages et quiz éclair, réactions rapides,
ressources partagées, chat de la séance, notes de l'élève, puis le bilan
(présence, durée, enregistrement, résumé, devoir, replay).

## 1. Activer côté Google Cloud

Utiliser **le même projet Google Cloud et la même application OAuth** que
Google Drive.

1. Console Google Cloud → *APIs & Services* → *Library* → activer
   **Google Meet REST API**.
2. *OAuth consent screen* → *Scopes* → ajouter :
   - `https://www.googleapis.com/auth/meetings.space.created` (créer les salles) ;
   - `https://www.googleapis.com/auth/meetings.space.readonly` (lire après coup
     la présence et l'enregistrement des conférences).
   Ces scopes sont « sensibles » : tant que l'application n'est pas vérifiée par
   Google, seuls les comptes déclarés en *Test users* peuvent se connecter.
3. *Credentials* → client OAuth « Web application » → ajouter l'URI de
   redirection : `https://<domaine>/center/google/retour`.

## 2. Configuration PrePla (`.env`, hors git)

```
GOOGLE_DRIVE_CLIENT_ID=...         # déjà présent si Drive est configuré
GOOGLE_DRIVE_CLIENT_SECRET=...
# Facultatif, si on veut des identifiants distincts ou plus lisibles :
GOOGLE_MEET_CLIENT_ID=...
GOOGLE_MEET_CLIENT_SECRET=...
GOOGLE_MEET_REDIRECT_URI=https://<domaine>/center/google/retour
GOOGLE_MEET_ACCESS_TYPE=OPEN       # OPEN ou TRUSTED, réglage par défaut
```

`GOOGLE_MEET_*` prime, sinon `GOOGLE_DRIVE_*` est repris (`config/services.php`,
clé `google_meet`). **Sans identifiants, le module est masqué** : pas d'entrée
dans le menu, pas de bouton dans les classes, routes en 404, pas de bandeau élève.

## 3. Fonctionnement

- Chaque enseignant (ou responsable) connecte **son** compte Google depuis la page
  *Live class* (`/center/lives`). Flux OAuth avec `state` anti-CSRF ; jetons
  d'accès et de renouvellement **chiffrés** en base (`center_google_accounts`).
- Sans compte connecté, on peut coller un lien `https://meet.google.com/...`
  existant (aucune présence Google ne sera alors lue).
- Création : `POST https://meet.googleapis.com/v2/spaces` avec
  `config.accessType` :
  - **OPEN** : toute personne qui a le lien entre sans demander. Le plus simple
    pour des élèves qui ont des comptes Gmail variés ou pas de compte.
  - **TRUSTED** : les comptes du domaine Workspace du centre et les invités
    entrent directement, les autres « frappent » et l'enseignant les admet.
- Une **série récurrente** (ex. chaque mardi 18 h, 2 à 26 semaines) crée une
  séance PrePla par semaine qui partagent **la même salle Meet** (un espace Meet
  se réutilise indéfiniment).
- Les élèves de la classe sont prévenus (notification push, e-mail à défaut),
  à la planification et au démarrage.
- « Rejoindre » passe par PrePla (`/live/{id}/rejoindre`) : la présence PrePla
  est notée, puis l'élève est envoyé vers Meet (ouvert 10 min avant l'heure).
- La salle PrePla (`/live/{id}`) relit l'état toutes les 3 s (sondage HTTP court,
  limité par `throttle`), seulement quand l'onglet est visible.
- À la fin (« Terminer le live »), si le compte a le scope `readonly`, PrePla lit
  `conferenceRecords` (filtre `space.name`), leurs `participants` (nom affiché,
  début, fin) et leurs `recordings` (lien Drive). La présence est rapprochée par
  nom ; à défaut, présence PrePla = a cliqué « Rejoindre » ou a interagi.
- Bilan : lien d'enregistrement (récupéré ou collé), résumé, devoir à faire après,
  publication du replay pour les élèves de la classe.
- Replay comme étape du parcours : une fois le replay publié (lien + case
  cochée), le bilan propose « Ajouter le replay au parcours de la classe » ; la
  page Parcours de la classe a aussi un onglet « Replays de live ». L'étape
  (`classroom_path_steps.live_session_id`, `exercise_id` vide) mène l'élève vers
  `/live/{id}/replay`, qui note `live_participants.replay_vu_at` puis renvoie
  vers l'enregistrement. Elle est faite si l'élève a assisté au live (a cliqué
  « Rejoindre ») ou ouvert le replay. Replay dépublié ou live annulé : l'étape
  n'est plus servie aux élèves et elle est signalée côté centre.

## 4. Comptes Workspace ou Gmail gratuit

| | Gmail gratuit | Google Workspace (Business Standard et +) / Education |
|---|---|---|
| Durée d'un appel à 3 personnes ou plus | **60 minutes maximum** | 24 h |
| Participants | 100 | 150 à 500 selon l'offre |
| Enregistrement | **non** | oui (fichier dans le Drive de l'organisateur) |
| `accessType` TRUSTED | peu utile (pas de domaine) | domaine du centre |
| Présence via l'API | oui | oui |

Conséquences pratiques :
- avec un compte Gmail gratuit, un cours de groupe est **coupé au bout de
  60 minutes** : planifier des séances de 55 minutes, ou relancer une salle ;
- sans Workspace, pas d'enregistrement Meet : l'enseignant peut enregistrer
  autrement et coller le lien dans le bilan ;
- le compte qui crée la salle en est l'organisateur : c'est son offre Google qui
  fixe les limites.

## 5. Isolation

- Un élève ne voit que les lives des classes (non archivées) dont il est élève.
- L'équipe : `ClassroomPolicy::update` (le responsable pour son centre,
  l'enseignant pour ses classes), jamais un autre centre (404).
- Les ressources partagées ne peuvent venir que de la médiathèque et des
  exercices **publiés** du centre du live. Un média `provider = google_drive`
  (quand la colonne existe) est partagé par son lien Drive.

## 6. Limites connues

- Pas de websocket : l'interactivité a jusqu'à ~3 s de latence.
- Le rapprochement de présence Google se fait par nom affiché.
- Le replay ne peut entrer que dans le parcours de SA classe (les élèves d'une
  autre classe n'ont pas accès à la salle) ; la copie de parcours ne le reprend pas.
