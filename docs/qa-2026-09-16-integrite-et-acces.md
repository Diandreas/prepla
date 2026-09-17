# Lot « intégrité, accès et fiabilité » — contrôle local du 16 septembre 2026

État : **local uniquement**. Rien n'est déployé, aucun nouveau bundle Android n'a été produit,
aucune publication Google Play n'a été faite. Les modifications ne sont pas encore committées.

## 1. Défauts corrigés (confirmés par lecture du code, couverts par des tests)

| Défaut | Effet avant correction | Correction |
| --- | --- | --- |
| Aucun contrôle d'accès sur `exercise.show`, `exercise.submit`, `api.exercise.verify-single` | N'importe quel compte pouvait ouvrir, faire et faire corriger l'exercice privé d'un centre ou la leçon d'un autre apprenant, à partir de son seul identifiant | `ExercisePolicy::view` : contenu de centre réservé à son personnel et aux élèves ayant le devoir publié ; exercice de leçon réservé à son propriétaire |
| `node/{node}/submit` faisait confiance aux `exercise_ids` envoyés par le client | Un client pouvait faire noter n'importe quel exercice, y compris privé, et gagner de l'XP | La liste est restreinte aux exercices de l'examen du nœud et filtrée par la même règle d'accès |
| Repli de sélection du lancement de nœud | Une session pouvait servir un exercice de centre, de leçon personnelle ou d'examen blanc | Exclusion de `center_id`, `lesson_id` et `mock_exam_id` |
| Repli de sélection de la simulation d'examen | Même fuite, plus les séries d'entraînement général | Mêmes exclusions, en plus de `catalog_key` |
| `submitSimulation` parcourait **tous** les exercices de l'examen à la recherche des identifiants de question reçus | Une soumission pouvait créer plusieurs tentatives et de l'XP sur des exercices jamais servis (les identifiants `q1`, `q2`… se répètent) | La simulation mémorise l'ensemble réellement servi et n'évalue que celui-ci, une seule fois |
| Le simulateur rangeait les réponses par identifiant de question seul | Deux exercices partageant `q1` se partageaient la réponse affichée et la notation | Réponses groupées par exercice, comme le joueur de parcours ; le composant est remonté entre deux exercices |
| Renvoi d'une même soumission d'exercice | Deux tentatives et deux fois l'XP | Verrou par apprenant/exercice et réutilisation de la tentative identique récente |
| `withoutVerifying()` sur les trois appels Mistral | Vérification TLS désactivée, y compris en production | Vérification rétablie ; réglage `HTTP_CA_BUNDLE` optionnel pour les postes sans magasin de certificats |
| Changement d'examen cible dans les Paramètres | Erreur SQL (colonne `user_id` inexistante sur `learning_path_nodes`) : profil modifié mais progression non réinitialisée et feuille de route non régénérée | Suppression de la requête fautive ; les nœuds restent partagés par examen |
| Service worker : cache de toute ressource statique du domaine | Un média de centre servi sous `/storage` pouvait être conservé dans le cache partagé de l'appareil | Liste blanche (`build`, `icons`, `sounds`, `animation`, `screenshots`, `favicon.ico`, `logo.svg`), appliquée aussi au préchargement ; cache passé en `prepla-shell-v21` |
| Envoi de séance bloqué en cas d'échec | Le bouton restait sur « envoi » indéfiniment | Le joueur rend la main pour permettre un nouvel essai |

## 2. Vérifications réellement exécutées

- **Tests PHP : 119 réussis, 1 513 assertions** (`php artisan test --compact`), dont 11 nouveaux :
  9 sur les accès et l'intégrité (`tests/Feature/Security/ExerciseAccessTest.php`) et 2 sur le
  changement d'examen cible (`tests/Feature/Settings/ProfileLearningUpdateTest.php`).
- **TypeScript** : `tsc --noEmit` sans erreur.
- **ESLint** : sans erreur sur les neuf fichiers touchés, dont les deux joueurs et le simulateur,
  qui portaient 25 et 6 anomalies anciennes.
- **Build Vite** : réussi après chaque série de modifications.
- **TLS et fournisseur IA** : chaîne de certificats validée via Laravel (HEAD sur la racine de
  l'API, HTTP 404 attendu, sans clé) ; `GET /v1/models` répond **HTTP 200**, donc la clé est valide.
- **Service worker** : fichier servi correctement (HTTP 200, `application/javascript`, `v21`) et
  syntaxe validée (`node --check`).

## 3. Limites de ces vérifications

- Les tests IA utilisent des réponses simulées : ils ne prouvent pas la disponibilité du fournisseur.
- **La génération Mistral renvoie toujours HTTP 429 « Rate limit exceeded »** sur un appel minimal
  d'un seul jeton. La clé est valide : la limite vient du compte ou de l'offre, à vérifier dans la
  console Mistral par le propriétaire. Un seul appel de contrôle a été fait, sans boucle.
- **Aucune recette dans le navigateur n'a pu être faite pendant ce lot** : la session de démonstration
  avait expiré et je ne peux pas saisir de mot de passe. Restent donc à faire : fin de l'exercice à
  trous, association de bout en bout, relecture du résultat de QCM, retour arrière, abandon,
  session expirée, échec réseau et double clic.
- Le panneau navigateur intégré refuse d'enregistrer un service worker : l'activation réelle du
  cache `v21` et de sa liste blanche reste à vérifier dans un vrai navigateur, puis sur Android.
- Relecture pédagogique des 90 questions de la bibliothèque (allemand, français, anglais) : aucune
  réponse fausse ni consigne ambiguë trouvée. Points mineurs : « Leinwand » et « ausleihen » sont
  un peu au-dessus de A1 mais restent déductibles du contexte ; la correction du français exige les
  accents (`visité`, `écrit`, `réservé`), ce qui est juste sur le fond mais mériterait un message
  d'aide explicite. Cette relecture ne remplace pas une validation par un enseignant.

## 4. Points ouverts, par ordre d'importance

1. **Score oral fabriqué côté client** : pour `role-play` et `listen-repeat`, le navigateur envoie
   un marqueur `completed:NN` que le serveur accepte tel quel. Un client peut donc s'attribuer un
   score. Correction proposée : conserver côté serveur l'évaluation de chaque tour
   (`api/exercise/evaluate-turn`) et n'accepter que des marqueurs correspondants.
2. **Idempotence de la soumission de séance et de simulation** : la soumission unitaire est protégée,
   pas encore la séance de parcours. À traiter avec le lot hors ligne (identifiant unique de tentative
   et contrainte d'unicité en base, comme prévu au §10.5 du brief).
3. **`GET /lessons/next` génère une leçon par IA.** Aucun lien ne la précharge aujourd'hui, mais une
   route GET ne devrait pas avoir cet effet : la passer en POST éviterait toute génération involontaire.
4. **Le nœud est marqué « complété » quelle que soit la précision** dans le parcours historique, alors
   que le seuil de 60 % ne s'applique qu'au squelette adaptatif. Comportement à trancher.
5. **Simulation et classement** : l'XP d'un examen blanc n'alimente ni le classement hebdomadaire ni
   la série, contrairement aux exercices. Incohérence à trancher.
6. **Les erreurs d'un exercice unitaire n'alimentent pas le centre de révision** (seules celles d'une
   séance de parcours le font). À confirmer comme voulu ou non.
7. `PracticeController::startNodeSession` n'est appelé par aucune route : code mort à retirer.
8. La rotation de la clé Mistral reste conseillée, puisqu'elle a été partagée en conversation.

## 5. Découpage de commits proposé

1. Bibliothèque d'entraînement sans IA (contenus, service, migration `catalog_key`, tests) — travail de la passe précédente.
2. Joueurs d'exercice et écran de résultat (saisie, corrections, accessibilité, contrastes).
3. Accès et intégrité des exercices (règle d'accès, sélections de secours, simulation liée, anti-doublon, tests).
4. Rétablissement de la vérification TLS et réglage `HTTP_CA_BUNDLE`.
5. Correction du changement d'examen cible et ses tests.
6. Durcissement du cache du service worker (`v21`).
7. Documentation : brief de reprise et comptes rendus de QA.
