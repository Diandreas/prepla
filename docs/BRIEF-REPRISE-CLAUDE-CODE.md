# PrepLa — brief de reprise pour Claude Code

État de passation : 15 septembre 2026. Ce document est un point de départ, pas une certification de disponibilité en production.

## 1. Demande et objectif du propriétaire

Continuer à améliorer une application existante de préparation aux examens de langue. Le propriétaire veut une application réellement utile, agréable, illustrée, cohérente en clair/sombre et aboutie sur téléphone. Il a donné de la liberté pour améliorer le design et les fonctionnalités, et demande de **tester réellement l’intérieur**, pas uniquement la landing page.

Objectif final : une PWA fiable puis une application Google Play propre, sans barre de navigateur, avec une fiche et des captures authentiques à jour. **Ne pas repartir de zéro, changer de stack, créer une autre application ou remplacer Laravel.**

La présente demande est une passation : les derniers changements sont locaux. Ne pas considérer la publication Play ou le déploiement des derniers changements comme déjà effectués.

## 2. Environnement et règles de reprise

- Projet : `E:\project\prepla`, Windows / PowerShell.
- Stack : Laravel, Inertia, React, TypeScript, Tailwind, Vite ; PWA ; conteneur Android TWA/Bubblewrap déjà présent.
- Application locale : `http://127.0.0.1:8000`.
- Site de production : `https://prepla.mirlab.cloud`.
- Base utilisée pour la recette locale : SQLite, `database/database.sqlite`. Vérifier l’environnement avant toute commande qui écrit en base.
- Le dépôt contient beaucoup de modifications non committées, certaines antérieures à cette passe. Les préserver ; inspecter `git status` et les diffs avant d’éditer. Ne pas faire de reset, de réinstallation globale ou de migration destructive.
- Ne pas committer `.env`, clés API, clés de signature Android, mots de passe, bases locales ni caches. Ne jamais afficher les secrets dans les sorties.
- La clé Mistral fournie par le propriétaire a été configurée dans `.env`, ignoré par Git. Ne pas la recopier dans un brief ou dans le frontend. Conseiller sa rotation avant production puisqu’elle a été partagée dans une conversation ; ne pas la révoquer à sa place.
- Préserver les données existantes. Ne pas lancer `migrate:fresh`, `db:wipe` ou un seeder général.
- Demander une autorisation adaptée avant une publication externe, un changement de facturation, une signature contractuelle ou une opération destructive. Le document de déploiement existant n’est pas une autorisation d’exécuter ses commandes.
- Communiquer en français simple, avec de courts points d’avancement. Distinguer fait, testé et encore incertain.

### Outils locaux

PHP 8.3 et Node sont installés. Dans le shell restreint de la dernière session ils n’étaient pas accessibles ; les commandes fonctionnaient dans le shell autorisé de la machine. Ce n’est pas une raison pour les réinstaller.

- Node : `C:\nvm4w\nodejs\node.exe`.
- PHP : paquet WinGet sous `C:\Users\Ncomp\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe` ; retrouver l’exécutable exact avec `Get-Command php` dans le shell normal.
- Serveur démarré avec `php artisan serve --host=127.0.0.1 --port=8000`. Vérifier qu’il tourne encore avant de lancer un doublon.
- Tests PHP : base de test SQLite en mémoire via `phpunit.xml` / `RefreshDatabase`, distincte de la base locale utilisateur.

## 3. Ce qui existe déjà / a été amélioré

### Interface et parcours existants

- Présentation des outils IA, entraînement, formats, résultats et leçons déjà remaniée.
- Composants réutilisables `ArtIcon`, `LearningScene`, styles de plateforme et de navigation.
- Illustrations colorées conservées sur un support clair en mode sombre, plutôt qu’inversées par un filtre CSS.
- Navigation mobile, état actif, bouton d’aide, sidebar repliée et écrans d’authentification travaillés. Il reste à faire une recette transversale ; ne pas affirmer que toutes les pages sont terminées.
- Messages d’indisponibilité IA explicites, notamment dans l’expliqueur/chat ; éviter les fausses réponses de substitution.
- Le correcteur écrit transmet l’examen et le niveau du profil. Un indice pédagogique ne doit pas être présenté comme une note officielle.
- Cache PWA actuellement `prepla-shell-v20` dans `public/sw.js`. Mise à jour proposée sans rechargement forcé d’un exercice en cours.

Voir aussi `docs/qa-ui-2026-09-14.md`, compte rendu de la passe précédente. Certaines documentations plus anciennes, notamment `docs/pedagogy.md`, décrivent des intentions et des tarifs de fournisseurs datés : vérifier le code et les sources actuelles avant de les appliquer.

### Nouvelle bibliothèque de démarrage sans génération IA

Ajoutée pendant la dernière passe : **18 séries originales, 90 questions**.

- Français, anglais, allemand.
- Niveaux indicatifs A1 et A2.
- Trois formats : QCM (`mcq`), texte à trous (`gap-fill`), association simple (`matching`).
- Cinq questions par série, correction déterministe et explications françaises.
- Une série par langue/niveau/format ; ne pas promettre de variété illimitée.
- Entraînement général, pas des annales ni des sujets officiels. Pas de validation enseignante effectuée.
- Sans génération IA ne veut **pas** dire hors ligne : il faut toujours accéder à PrepLa pour charger et enregistrer la séance.

Fichiers principaux :

- `resources/content/practice-starters/{english,french,german}.php`
- `app/Services/Content/StarterPracticeLibrary.php`
- `database/migrations/2026_09_15_000001_add_catalog_key_to_exercises.php`
- `app/Models/Exercise.php`
- `app/Http/Controllers/PracticeController.php`
- `tests/Feature/StarterPracticeLibraryTest.php`

Fonctionnement : le lancement réutilise d’abord un exercice autonome public au niveau exact ; à défaut, crée idempotemment la série disponible ; sinon conserve le recours à la génération IA. La colonne unique nullable `catalog_key` évite les doublons. Les IDs des questions sont préfixés pour éviter les collisions entre séries.

La migration de `catalog_key` a été appliquée **uniquement à la base locale**. Elle reste à déployer ailleurs avec les précautions normales.

La bibliothèque ne s’applique qu’à la compétence `reading` et aux slugs exacts `mcq`, `gap-fill`, `matching` correspondant à leur composant. Important : `matching-headings` utilise aussi le composant matching, mais ne doit pas recevoir une association simple. Les niveaux B1–C2 et les compétences audio/oral ne sont pas remplacés silencieusement par un exercice A2 écrit.

Les séries sont exclues de la sélection de secours des simulations d’examen. Les autres sélections/soumissions de simulation restent à auditer.

### Joueur d’exercice et correction

- `resources/js/components/exercises/exercise-player.tsx` : affichage du texte de référence et des consignes de la bibliothèque, titre/source, blocage de la saisie vide, garde contre un double clic d’envoi, message d’erreur d’envoi et remise à disposition des boutons.
- `gap-fill.tsx` : champ contrôlé par la réponse du parent, pas de réponse résiduelle d’une autre question, retour arrière conservant la saisie, libellé accessible.
- `matching.tsx` et `gap-fill.tsx` : contraste clair/sombre, focus et boutons accessibles.
- `resources/js/pages/exercise/result.tsx` : carte de résultat, corrections plus lisibles, réponses utilisateur et attendue avec le texte des options, source bibliothèque, action « Revoir cet exercice » honnête sur la répétition.
- Langue de lecture audio des réponses déduite de la langue de l’exercice ; explications françaises de la bibliothèque prévues en français. Lecture audio réelle non validée dans cette dernière passe.
- `resources/js/pages/exercise/show.tsx` : réinitialisation du joueur quand l’exercice change.

Attention : il existe **deux joueurs**. Le joueur unitaire est `resources/js/components/exercises/exercise-player.tsx` via `pages/exercise/show.tsx`. Le joueur de parcours est `resources/js/pages/exercises/player.tsx`. Tester les deux ; corriger l’un ne valide pas l’autre.

## 4. État exact des vérifications au moment de la passation

### Vérifications automatiques

- Dernière exécution complète : **108 tests PHP réussis, 1 437 assertions** (`php artisan test --compact`).
- Dernière vérification TypeScript : **réussie**, après correction du rendu d’une valeur `unknown` dans la page de résultat.
- Build Vite : plusieurs compilations réussies. **À relancer maintenant** : les tout derniers petits ajustements de sous-titre, icône trophée et condition de rendu ont suivi la dernière compilation.
- ESLint ciblé a signalé des `any` historiques dans le joueur unitaire. Le résultat avait aussi des remarques dont certaines ont été corrigées ; **relancer ESLint et traiter ce qui reste**, ne pas annoncer un lint global vert.
- Les tests de bibliothèque vérifient structure, réponses correctes/incorrectes, absence d’appel HTTP, idempotence, niveau exact, exclusion du contenu centre/leçon/nœud/mock, refus des mauvais formats et séparation des examens.
- Les tests IA utilisent des réponses simulées : leur succès ne prouve ni la disponibilité réelle du fournisseur ni la qualité de toutes ses réponses.

### Tests réellement faits dans le navigateur intégré

Compte local de démonstration : `demo.visual@prepla.test`, « Camille · Démo locale », Goethe exam ID 7, niveau A2. Les identifiants et garde-fous du seeder sont dans `database/seeders/LocalVisualQaSeeder.php` ; ne pas les utiliser en production. Ne pas réinitialiser le compte pour recommencer.

- Galerie lecture `http://127.0.0.1:8000/practice/7/section/27` : badges « Prêt sans IA » vérifiés ; `Matching Headings` ne porte plus ce badge.
- QCM ouvert depuis la galerie et **terminé de bout en bout**, avec une erreur volontaire : résultat **80 % / 8 XP**, correction française et tentative persistée.
- Exercice QCM local : `/exercise/1` ; résultat : `/exercise/result/1?node_completed=0`.
- Écran de correction inspecté à **390 × 844**, pas de débordement horizontal ni d’image cassée détectés sur cet écran. Cela ne constitue pas un audit de toutes les pages.
- Texte à trous ouvert en **mode sombre** : `/exercise/2`.
- Une saisie uniquement composée d’espaces ne permet pas de continuer.
- Réponse `gelernt` saisie à la question 1, question 2 affichée avec champ vide, retour question 1 : `gelernt` bien conservé.
- **Texte à trous non terminé** : recette interrompue pour cette passation.
- **Association non encore testée de bout en bout dans le navigateur** pendant cette passe.
- La mise à jour PWA a été acceptée hors exercice, avec retour à la galerie.

Le navigateur est laissé sur l’exercice à trous. Les tests du compte de démonstration ont légitimement ajouté une tentative et des XP ; ne pas confondre ces données locales avec de vrais apprenants.

## 5. Priorité immédiate : terminer proprement cette passe

1. Lire ce brief, inspecter les diffs, vérifier l’environnement et l’état du serveur.
2. Exécuter TypeScript, les tests et un **nouveau build**. Corriger les régressions avant d’élargir le périmètre.
3. Finir `/exercise/2` : cinq réponses, vérification de score/XP/explications, puis « Revoir cet exercice » et champ bien réinitialisé.
4. Tester l’association depuis la galerie : `/practice/7/drill/69`. Tester une erreur volontaire, sa correction et la reprise.
5. Revoir le résultat du QCM après reconstruction : bonne réponse complète, réponse donnée visible, trophée correct, contraste sombre.
6. Vérifier retour arrière, abandon, session expirée, échec réseau, nouvelle tentative après échec, double clic et absence de faux succès. `onError` Inertia ne garantit pas à lui seul une gestion de toutes les erreurs réseau/HTTP : tester.
7. Tester un échantillon français et anglais en plus de l’allemand. Relire pédagogiquement les 90 questions et les niveaux proposés ; prévoir une validation par une personne compétente avant une revendication forte.
8. Écrire un petit compte rendu : pages/formats testés, scénario, résultat, captures réelles si utiles, limites restantes.

## 6. Priorité haute : fiabilité, intégrité et sécurité

### Disponibilité IA

- Le dernier essai réel Mistral renvoyait **HTTP 429**, même après configuration de la clé fournie. Cause précise à confirmer : quota/crédit/limitation du compte ou du modèle. Ne pas affirmer que la clé est invalide.
- Faire un test minimal et non répétitif si nécessaire, sans afficher la clé ni consommer du quota en boucle.
- Vérifier 401/403/429/5xx, délai dépassé, réponse vide ou JSON invalide, erreurs utiles et saisie préservée.
- Vérifier aussi les services audio/STT/TTS séparément ; « Mistral configuré » ne garantit pas que l’audio fonctionne.
- Garder les exercices de bibliothèque accessibles quand l’IA est indisponible. Pour les formats non disponibles, dire clairement pourquoi, sans inventer un résultat ou un faux score.

### Points de code repérés à auditer — pas tous reproduits comme bugs

- `app/Services/AI/MistralService.php` contient `Http::withoutVerifying()` : remettre une validation TLS correcte, réparer la configuration des certificats si nécessaire ; ne pas généraliser le contournement en production.
- `PracticeController::submitSimulation` parcourt les exercices de l’examen pour retrouver les IDs de questions envoyés. Lier la soumission à **la session exacte et aux exercices réellement servis**, avec IDs non ambigus. Les IDs uniques de la nouvelle bibliothèque ne règlent pas toute cette architecture.
- `PracticeController::startNodeSession` dispose encore d’une sélection de secours générale : vérifier isolement centre/enseignant, leçon, mock et contrôle d’accès au nœud.
- `ExerciseController::show`, `submit`, vérification unitaire et routes associées : auditer accès aux exercices privés, appartenance des questions, validation des réponses et permissions ; ne pas se contenter du filtrage sur la galerie.
- La soumission unitaire lit `session('current_node_id')` : vérifier qu’un exercice libre ne valide pas un ancien nœud par erreur.
- Vérifier que les scores oraux ou marqueurs envoyés par le navigateur ne permettent pas de fabriquer un score côté client.
- Vérifier idempotence de l’enregistrement des tentatives/XP côté serveur, pas seulement le verrou d’un bouton, et cohérence résultats/classement/série.
- Vérifier que les erreurs d’une tentative unitaire alimentent bien le parcours de révision si c’est le comportement produit attendu.
- Vérifier le chargement anticipé de liens : lancer une génération/création via une route GET ne doit pas provoquer de créations ou coûts involontaires par préchargement.

Ajouter des tests de régression pour les défauts confirmés. Ne pas multiplier les changements structurels sans recette.

## 7. Recette fonctionnelle complète à organiser

Inventorier les formats depuis `ExerciseSchemaRegistry`, le registre frontend, les composants, les seeders et les configurations d’examens. Tous ne sont pas identiques malgré un même rendu.

| Famille | Vérifications indispensables |
| --- | --- |
| QCM, vrai/faux/non mentionné, associations | Choix unique/multiple selon format, conservation de sélection, correction exacte, texte de la bonne réponse |
| Textes à trous, cloze, complétion, transformation | Espaces/casse/accents/apostrophes, plusieurs champs si prévu, absence de saisie résiduelle, indices non ambigus |
| Ordonnancement, insertion, titres/informations | Vrai comportement du format, clavier/tactile, structure cohérente avec la correction |
| Écoute et dictée | Audio disponible, contrôles, erreurs, transcript non montré avant réponse si cela annule le test, langue correcte |
| Rédaction et synthèse | Consigne, comptage, maintien du brouillon, évaluation adaptée à l’examen, distinction score pédagogique/officiel |
| Oral, répétition, jeux de rôle | Permission micro, refus de permission, enregistrement/lecture/envoi, langue STT, délai et score vérifiables |
| Leçons et parcours | Reprise, quiz, validation réelle, déblocage, objectifs, erreur réseau et progression cohérente |
| Examen simulé | Ensemble réellement servi, chronomètre, navigation, soumission unique, score par section, pas de contenu privé ou hors sujet |
| Révision, dictionnaire, résultats, classement | Données vides/remplies, révision des erreurs, historique, XP/série, confidentialité |

Inclure inscription, connexion, oubli de mot de passe, profil, thème, support, installation PWA et suppression de compte. Tester centre/enseignant/admin séparément si ces fonctionnalités sont destinées au lancement ; elles n’ont pas été validées visuellement de bout en bout dans cette passe.

## 8. Design : direction et travail restant

Le souhait du propriétaire n’est pas uniquement « plus de couleurs » : il faut une interface moins plate, mais surtout lisible, cohérente et agréable pendant les cours.

- Réutiliser les composants existants avant d’ajouter de nouveaux systèmes graphiques.
- Vérifier chaque icône visuellement : un nom de fichier n’est pas une garantie du bon pictogramme. Exemple repéré : `award.png` ressemblait à une carte ; le résultat utilise maintenant `trophy.png`.
- Auditer tous les fonds d’illustration en dark mode, supprimer les inversions destructrices, garder un contraste texte/fond adapté.
- Harmoniser cartes, espacements, titres, boutons, badges, vides/erreurs/chargements, libellés français et focus clavier.
- Sidebar repliée : largeur, alignement des icônes, logo, infobulles, menu utilisateur, absence de texte qui dépasse.
- Mobile : navigation basse, safe areas, clavier virtuel, boutons accessibles au pouce, pas de contenu masqué par l’aide ou la barre fixe.
- Animations courtes et utiles ; respecter `prefers-reduced-motion`, éviter les animations qui retardent l’accès ou gênent la lecture.
- Revoir la landing page : démonstration clairement identifiée, hiérarchie, bénéfice concret et appels à l’action. Vérifier/retirer les affirmations non étayées (« milliers d’apprenants », conformité globale, promesses de réussite, formats exacts, disponibilité illimitée) et les liens factices. Ne pas inventer des témoignages ou chiffres.
- Ne pas générer des images partout par principe : illustrer là où cela aide vraiment la compréhension, l’orientation ou la motivation.

## 9. PWA, Android et Google Play

### Ce qui est déjà présent dans les fichiers

- `android/twa-manifest.json`, projet Gradle/Bubblewrap et sorties Android existantes.
- `release/google-play/android/v1.0.0/PrePla-v1.0.0-build1.aab` et `.apk` présents. **Ils ne prouvent pas que les derniers changements sont publiés ou acceptés.**
- `public/.well-known/assetlinks.json` présent ; contenu et empreinte réels à vérifier avant distribution.
- `release/google-play/` contient `graphics`, `screenshots`, `text`, `source`, métadonnées françaises, brouillons Data safety et notes d’examen.
- Archives du kit dans `release/`, dont un kit nommé avec « real-screenshots ».
- Guides : `docs/pwa-play-store.md`, `DEPLOYMENT.md` et `release/google-play/publication-checklist-fr.md`. Les relire avec prudence : certaines consignes/règles peuvent être datées.

### À faire avant publication

1. Vérifier HTTPS, manifeste, identité/start URL/scope, icônes, mode autonome, service worker, cache des pages privées, mise à jour sans perte de séance et page hors ligne honnête.
2. Tester sur **un vrai appareil Android**, puis iOS pour la PWA : installation, ouverture, reprise, retour système, liens externes, audio/micro, permissions et notifications.
3. Pour la barre de navigateur Android : vérifier la validation Digital Asset Links, le package réel et le certificat **App Signing** utilisé par Google Play, ainsi que les redirections/origines. Ne pas essayer de masquer cette barre en CSS.
4. Inspecter la version actuelle dans Play Console avec l’autorisation du propriétaire, ne pas créer une nouvelle application par défaut et ne pas inventer son état de publication.
5. Vérifier la stratégie de paiement Android : Stripe/Cashier existe côté web. Examiner les règles Google actuelles et l’éligibilité des pays/programmes, puis faire choisir la stratégie appropriée avant de modifier la facturation.
6. Vérifier clés de signature, applicationId et versionCode avant un nouveau bundle. Ne pas régénérer/perdre une identité ou une clé existante.
7. Rafraîchir la fiche et les captures **à partir de la vraie application finalisée**, sur compte de démo sans données personnelles. Le propriétaire a explicitement rejeté des captures qui ne montrent pas l’app réelle.
8. Préparer nom, courte/longue description fidèles, icône, image de présentation et captures téléphone conformes aux spécifications actuelles de la console. Vérifier langues et qualité visuelle à taille téléphone.
9. Vérifier confidentialité, suppression de compte, accès de l’équipe de review, déclarations de données/permissions/IA/publicité/public cible. Les brouillons du kit ne sont pas des déclarations déjà validées.
10. Vérifier les exigences actuelles de tests internes/fermés du compte concerné auprès des sources officielles Google. Ne pas réutiliser mécaniquement les chiffres d’un ancien guide.
11. Déployer et soumettre seulement après recette et autorisation. Vérifier ensuite la version réellement servie et l’installation depuis le canal Play concerné.

## 10. Nouvelle priorité du propriétaire : une vraie expérience mobile hors ligne

Ajout demandé après la passation : perfectionner la PWA pour pouvoir l’utiliser presque comme une application mobile hors ligne. **C’est un objectif à implémenter, pas une fonctionnalité déjà livrée.** Garder Laravel comme backend et réutiliser React ; une réécriture native n’est pas nécessaire pour ce premier périmètre.

### 10.1 Promesse produit réaliste

Après une première connexion et le téléchargement d’un pack, l’apprenant doit pouvoir couper Internet, fermer puis rouvrir PrepLa, lire ses cours, faire ses exercices préparés, voir leurs corrections et conserver son travail. Au retour du réseau, les tentatives doivent être synchronisées sans duplication ni perte.

Ne pas promettre « toute l’IA hors ligne » : la génération Mistral, les nouvelles analyses de rédaction et la transcription distante ont besoin du serveur. Un modèle embarqué serait un projet séparé, à évaluer seulement si demandé. Le conteneur Android TWA ne rend pas, à lui seul, le site utilisable hors ligne.

| Fonction | Cible hors ligne | Condition ou limite |
| --- | --- | --- |
| Ouverture et navigation | Accueil local, téléchargements, séance et reprise | Interface et dépendances installées auparavant ; pas de première installation sans réseau |
| Cours et illustrations | Lecture complète | Contenu téléchargé et vérifié |
| QCM, trous, associations | Réponses et explications immédiates | Barème et corrections inclus dans le pack ; score local d’entraînement |
| Vocabulaire et révision | Fiches et séances préparées | Données sélectionnées enregistrées sur l’appareil |
| Écoute | Lecture d’enregistrements téléchargés | L’audio doit réellement être stocké ; un lien vers un MP3 distant ne suffit pas |
| Rédaction et oral | Brouillon, enregistrement et réécoute locale si le navigateur le permet | Correction IA et transcription distante proposées lors de la reconnexion, avec accord avant envoi d’un enregistrement |
| Progression | Historique local et statut « à synchroniser » | XP/classement/déblocages officiels validés par le serveur |
| Compte et services distants | Dernières informations locales clairement datées | Connexion initiale, paiement, classement actualisé, nouvel examen IA et changement de compte exigent le réseau |

### 10.2 Pourquoi le code actuel ne suffit pas

Inspection de `public/sw.js` et `resources/js/app.tsx` :

- Le service worker met surtout des fichiers statiques en cache et renvoie `/offline` en cas d’échec de navigation. Ce n’est pas encore un espace d’apprentissage autonome.
- Les navigations Inertia dépendent de Laravel ; les requêtes `X-Inertia` ne sont pas mises en cache. `createInertiaApp` attend des données de page initiales, et les pages sont chargées par imports dynamiques Vite.
- Précharger des images/sons via `preload-assets.ts` ne garantit donc pas qu’une page d’exercice jamais ouverte ou un redémarrage complet fonctionnera hors ligne.
- Les réponses sont principalement dans l’état React : un retour entre questions peut fonctionner sans qu’une fermeture de l’application conserve la séance.
- Le service worker supprime actuellement tous les caches dont le nom diffère du cache courant. Cette règle devra être remplacée par une purge ciblée des seules anciennes versions du shell, pour ne pas effacer les futurs packs.
- Le gestionnaire `PRELOAD_URLS` n’impose actuellement que la même origine ; il faut une liste d’autorisation stricte de ressources publiques. Le cache fondé sur l’extension d’un fichier doit aussi être audité : un média privé peut avoir une extension `.mp3` ou `.png`.

**Ne pas résoudre le problème en mettant indistinctement en cache les pages Laravel connectées ou toutes les réponses GET.** Cela risquerait de conserver des données personnelles, des réponses de connexion ou des contenus d’un autre compte.

### 10.3 Architecture proposée, adaptée à ce dépôt

Conserver le site Inertia pour les fonctionnalités en ligne et ajouter un petit espace React autonome « Mes téléchargements », partageant les composants de cours/exercices existants. Une entrée publique dédiée, sans données personnelles ni token sérialisé, peut charger les données locales après ouverture. Définir explicitement son routage et ses retours hors ligne ; ne pas simuler une réponse Inertia avec le dernier tableau de bord en cache. Le choix d’un shell client autonome pour cette partie est une proposition de conception pour PrepLa ; le [guide d’architecture PWA de web.dev](https://web.dev/learn/pwa/architecture) décrit les distinctions entre navigation serveur et client.

Séparer trois responsabilités :

1. **Interface publique versionnée** : HTML neutre, CSS, JavaScript, polices et icônes nécessaires à l’espace hors ligne. Préparer une liste complète depuis le manifeste Vite, y compris ses dépendances chargées à la demande.
2. **Packs pédagogiques** : contenus structurés dans IndexedDB, médias dans des caches dédiés et contrôlés. Le choix IndexedDB pour les données structurées et Cache Storage pour les ressources suit les usages présentés par [web.dev — données hors ligne](https://web.dev/learn/pwa/offline-data).
3. **Travail de l’apprenant** : brouillons, réponses, séances et file d’envoi persistante en IndexedDB, séparés par compte. Ne pas utiliser le simple état React ou `localStorage` comme base principale des cours/enregistrements.

Commencer avec les 18 séries de la bibliothèque actuelle ; ajouter ensuite les leçons et les médias. Extraire les règles de correction nécessaires dans une logique locale testable, avec des fixtures communes PHP/TypeScript pour vérifier leur concordance. Ne pas dupliquer des règles divergentes dans les deux joueurs.

### 10.4 Téléchargements vraiment fiables

- Ajouter une action explicite « Télécharger pour hors ligne », une taille estimée, une progression et la possibilité d’annuler/reprendre. Proposer de télécharger sans audio pour économiser l’espace.
- Définir un manifeste de pack : identifiant/version du contenu, langue, niveau, formats, fichiers requis, tailles et empreintes de vérification, version minimale du lecteur et droits éventuels. Un simple hash détecte la corruption, pas une falsification par le client.
- Télécharger dans une zone temporaire ; marquer « Disponible hors ligne » seulement après contrôle de tous les éléments obligatoires. Un téléchargement interrompu ne doit pas produire un pack faussement complet.
- Mettre à jour un pack sans supprimer la version qu’utilise une séance en cours ; conserver la version de référence nécessaire à ses corrections.
- Ajouter un gestionnaire : packs prêts/incomplets, date/version, espace utilisé, supprimer un pack ou ses médias sans supprimer les réponses non synchronisées.
- Traiter manque d’espace, écriture refusée, téléchargement incomplet, cache manquant et éviction. Demander un stockage persistant via `navigator.storage.persist()` quand approprié, mais gérer le refus : l’octroi dépend du navigateur. Ce n’est pas une garantie contre l’effacement manuel des données. Voir [MDN — persist()](https://developer.mozilla.org/en-US/docs/Web/API/StorageManager/persist).

### 10.5 Sauvegarde et synchronisation : priorité à l’absence de perte

Proposition de schéma local : `packs`, `packItems`, `sessions`, `drafts`, `attempts`, `outbox`, `syncReceipts`, avec version du schéma et propriétaire. Les noms et endpoints sont à concevoir, ils n’existent pas encore comme contrat.

- Sauvegarder les réponses pendant la séance et afficher honnêtement « Enregistré sur cet appareil ». Restaurer la question, les réponses et l’état utile après fermeture brutale.
- Créer un UUID stable par tentative et une entrée d’envoi contenant les réponses, l’exercice/version et les métadonnées minimales. Ne pas stocker de clé API ou de cookie de session dans cette file.
- Créer un endpoint Laravel de synchronisation **authentifié et idempotent**. Une contrainte unique par compte/UUID et une transaction serveur doivent éviter la double tentative et les doubles XP, même si la réponse réseau est perdue et que l’envoi est répété.
- Vérifier accès au contenu et version, recalculer le score côté serveur, puis renvoyer un accusé de réception durable. Ne retirer l’entrée de la file qu’après cet accusé. Ne pas faire confiance au score, au niveau ou à l’horloge fournis par le téléphone.
- Distinguer score local, score validé et correction IA en attente. Les réponses embarquées sont inspectables sur le téléphone : ces packs conviennent à l’entraînement, pas à une épreuve sécurisée ou à une certification.
- Synchroniser à l’ouverture, au retour au premier plan et après une reconnexion réellement réussie ; proposer « Synchroniser maintenant ». `navigator.onLine` doit rester un indice, pas la preuve que Laravel est accessible.
- Background Sync peut être un complément, **pas une dépendance obligatoire** : l’API n’est pas disponible partout. Assurer le parcours lorsque l’application est ouverte, conformément à la limite de compatibilité documentée par [MDN — Background Synchronization](https://developer.mozilla.org/en-US/docs/Web/API/Background_Synchronization_API). Ne pas promettre une synchronisation immédiate avec l’application fermée.
- Prévoir délais maximum, reprise espacée des erreurs transitoires, respect des limitations, pause sur session expirée et interface pour les erreurs définitives. Pas de boucle d’envoi agressive.
- En cas de reconnexion nécessaire, conserver la file du compte A sans jamais l’envoyer sous le compte B. Rafraîchir la session/CSRF au moment de l’envoi, pas en rejouant un token ancien.
- Définir les conflits : tentatives ajoutées plutôt qu’écrasées ; brouillons concurrents conservés/récupérables ; progression et XP arbitrés côté serveur. Plusieurs onglets/appareils ne doivent pas multiplier les validations.
- Pour les contenus premium/centre et les tentatives synchronisées longtemps après leur réalisation, définir une politique explicite de droits et de dates. Une révocation ne peut pas être connue instantanément par un appareil déconnecté ; ne pas promettre le contraire.

### 10.6 Confidentialité et mises à jour

- Téléchargements personnels sur choix explicite ; expliquer les risques d’un appareil partagé. Ne pas inclure par défaut chat, documents de centre, informations de paiement ou profils complets.
- Isoler toutes les lectures/écritures locales par compte et définir la conduite à la déconnexion/suppression/changement de compte. Avertir des tentatives non envoyées avant un effacement ; ne jamais jeter silencieusement le travail.
- Une protection locale ou une base IndexedDB ne remplace pas l’authentification serveur ni un chiffrement matériel. Les scripts du même site peuvent accéder aux données locales : durcir les injections et éviter le stockage de secrets.
- Ne pas mettre de JWT/refresh token longue durée dans le cache simplement pour contourner une session expirée.
- Un déploiement ne doit ni interrompre la séance ni détruire ses données locales. Prévoir migrations IndexedDB compatibles, conservation temporaire des anciennes dépendances et activation sûre du nouveau lecteur.
- Ne pas télécharger toute la plateforme au premier lancement. Charger d’abord un noyau léger, puis les packs demandés ; privilégier les images optimisées aux GIF lourds quand cela améliore réellement les performances.

### 10.7 Ordre d’implémentation et preuve d’acceptation

**Lot 1 — noyau utilisable** : corriger les règles de cache, créer l’entrée React hors ligne, télécharger un pack de la bibliothèque et terminer une séance avec correction locale. Prouver l’ouverture après fermeture complète en mode avion.

**Lot 2 — travail durable** : sauvegarde automatique, restauration, file d’attente et synchronisation Laravel idempotente. Prouver une seule tentative et un seul crédit XP après répétition d’un même envoi.

**Lot 3 — cours et audio** : téléchargements complets, leçons, vocabulaire, MP3, gestion du stockage. Vérifier la lecture et le déplacement dans un fichier audio hors ligne sur les navigateurs cibles ; gérer les requêtes partielles si nécessaires.

**Lot 4 — finition mobile** : accueil « Continuer ma séance », états en ligne/hors ligne clairs, nombre d’éléments en attente, téléchargements, écran clavier, retours système, safe areas, accessibilité et animations sobres. Une nouvelle installation doit proposer de préparer son premier pack pendant qu’elle est connectée.

Recette minimale sur un vrai Android avec PWA installée, puis dans le conteneur Play, et sur iPhone si pris en charge :

1. Se connecter, télécharger, vérifier « prêt » ; activer le mode avion ; fermer complètement puis rouvrir l’application.
2. Ouvrir un cours/exercice téléchargé qui n’avait jamais été consulté ; répondre, changer de question, fermer et reprendre sans perte.
3. Terminer hors ligne, voir une correction et un statut local honnête ; rétablir le réseau, obtenir une seule tentative serveur.
4. Interrompre la connexion avant l’accusé serveur, rouvrir deux fenêtres et réessayer : aucun doublon.
5. Reproduire session expirée, serveur indisponible, téléchargement partiel, espace insuffisant et mise à jour du code pendant une séance.
6. Tester un changement de compte : aucune donnée ni soumission croisée ; expliquer ce qui reste en attente sur l’ancien compte.
7. Vérifier que les fonctions non disponibles affichent une explication et une alternative, pas un bouton qui tourne indéfiniment.

Ne déclarer le mode hors ligne terminé qu’après cette recette. Un écran « Vous êtes hors ligne », ou une page qui reste visible uniquement tant que l’onglet n’est pas fermé, ne satisfait pas la demande.

## 11. Livrable attendu de la suite

Procéder par lots courts : finir et vérifier le lot actuel, corriger les risques bloquants, poursuivre la recette, puis finaliser le design et la publication.

À la fin de chaque lot, fournir :

- ce qui a changé et pourquoi cela aide l’apprenant ;
- tests automatiques et scénarios réellement exécutés ;
- captures utiles en clair/sombre et mobile, sans faux écran ;
- problèmes encore ouverts, dépendances fournisseur et décisions attendues ;
- état explicite : local, déployé, build Android produit, canal de test ou production — jamais mélangés.

**Critère de réussite : une personne peut s’inscrire, choisir un objectif, apprendre, répondre, comprendre ses erreurs et reprendre sa progression sans blocage ni score trompeur. Les illustrations et animations doivent servir ce parcours.**
