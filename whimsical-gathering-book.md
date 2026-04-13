# Plan de correction pédagogique — Phase 1 : Fondations (Piliers 1, 3, 4, 9)

> **Scope confirmé** : seule la **Phase 1** est à exécuter maintenant (Piliers 1, 3, 4, **9**). Les piliers 2, 5, 6, 7, 8 restent documentés plus bas comme roadmap future mais ne sont PAS à implémenter dans ce passage.
>
> **Ajout utilisateur** : Pilier 9 — Génération **progressive** et **adaptative** du programme de cours, comme un vrai enseignant qui ajuste son cours en fonction des difficultés des élèves (pas un syllabus figé d'avance).

## Context

**Problème identifié :** PrePla est actuellement une application d'**exercices**, pas un système pédagogique. Elle ressemble à une salle de sport sans coach : l'apprenant a des machines (exercices générés par IA) mais personne pour lui apprendre, corriger ses mouvements, ou adapter la charge. Un utilisateur A1 ne peut pas devenir B2 juste en faisant des exercices — il a besoin **d'enseignement**.

**Audit pédagogique (du point de vue d'un enseignant) — 10 failles critiques :**

1. **Aucun contenu de cours** — L'app ne contient AUCUNE leçon, aucune explication grammaticale, aucun apprentissage structuré. Que des exercices. On demande à l'apprenant de deviner les règles depuis les corrections IA.
2. **Difficulté non adaptative** — `ExerciseGeneratorService` génère toujours la même difficulté pour un niveau donné. Pas de progression à l'intérieur d'un niveau, pas d'ajustement si l'apprenant réussit/échoue.
3. **Roadmap déterministe** — `RoadmapGeneratorService::scheduleNodes()` distribue linéairement sans tenir compte des performances. Les prérequis (`prerequisites` JSON) existent en DB mais ne sont jamais vérifiés.
4. **Pas de vraie répétition espacée pour les erreurs** — SM-2 existe dans `Vocabulary` mais jamais appelé. Les erreurs (`UserError`) sont stockées mais pas réinjectées dans le parcours.
5. **Feedback IA sans contexte utilisateur** — `MistralEvaluationService` évalue chaque exercice isolément, sans savoir que l'apprenant a déjà échoué 3 fois sur le même concept.
6. **Pas de feedback de prononciation** — Speaking évalué uniquement par transcription texte Deepgram → Mistral. Aucune analyse phonétique, aucun retour sur l'accent, le rythme, les phonèmes manquants.
7. **Placement test trop court** — 5-8 questions suffisent à peine à distinguer A1/A2, impossible de diagnostiquer par compétence (reading vs listening vs writing).
8. **Mock exams non diagnostiques** — Les simulations d'examen donnent un score mais n'identifient pas les faiblesses spécifiques.
9. **Objectifs non dynamiques** — L'utilisateur fixe un objectif à l'inscription, jamais réajusté selon sa progression réelle.
10. **Pas de catégorisation des erreurs** — Une erreur de conjugaison et une erreur de vocabulaire sont traitées pareil. Impossible de dire "tu as un problème récurrent avec le subjonctif".

**Objectif du plan :** transformer PrePla d'une "salle de sport sans coach" en un "professeur particulier numérique" — un système où un apprenant A1 peut atteindre B2 en autonomie.

---

## Architecture cible — Les 8 piliers pédagogiques

### Pilier 1 — Contenu de cours (LE plus critique)

**Faille :** L'app n'enseigne rien, elle teste seulement.

**Solution :** Introduire un modèle `Lesson` attaché à chaque `LearningPathNode`.

```
LearningPathNode (existe)
  ├── Lesson (NOUVEAU)
  │     ├── theory_markdown : explication du concept (règle + exemples)
  │     ├── video_url : optionnel
  │     ├── key_takeaways : JSON (3-5 points clés)
  │     └── common_mistakes : JSON (pièges typiques)
  └── Exercises (existe) : viennent APRÈS la leçon
```

**Flow utilisateur :**
1. Ouvrir un nœud → voir d'abord la **leçon** (5-10 min de lecture)
2. QCM de compréhension rapide (3 questions) — "as-tu compris la règle ?"
3. Exercices d'application (existant)
4. Si échec répété sur les exercices → retour automatique à la leçon avec surbrillance du concept raté

**Fichiers à créer :**
- `app/Models/Lesson.php`
- `database/migrations/xxxx_create_lessons_table.php`
- `resources/js/pages/learning/lesson.tsx`
- `app/Http/Controllers/LessonController.php`

**Contenu initial :** Les leçons ne sont **PAS** générées en batch à l'avance → voir Pilier 9 ci-dessous.

---

### Pilier 9 — Programme de cours généré progressivement et adaptativement (NOUVEAU)

**Faille :** Un enseignant n'arrive pas avec un syllabus figé pour les 6 prochains mois. Il prépare la leçon suivante en fonction de comment la précédente s'est passée. Si les élèves ont galéré sur le Present Perfect, la prochaine leçon revient dessus au lieu d'enchaîner sur le Past Perfect. PrePla doit faire pareil.

**Principe :** Au lieu de pré-générer tout le parcours au démarrage, **générer la leçon suivante juste-in-time**, à la demande, en prenant en compte l'historique de l'apprenant.

**Architecture :**

```
État actuel (PROBLÉMATIQUE) :
  Inscription → RoadmapGenerator génère N nœuds figés → Exercices
  (le plan ne bouge jamais, même si l'élève galère)

État cible :
  Inscription → Skeleton du syllabus (objectifs macro seulement)
                    ↓
              Prochaine leçon générée à la demande
                    ↓
          Prompt Mistral inclut :
            - Niveau actuel
            - Objectif (examen visé)
            - 10 dernières erreurs catégorisées (Pilier 4)
            - Concepts où maîtrise < 60% (Pilier 2, Phase 2)
            - Concepts déjà maîtrisés (éviter la répétition)
            - Leçons déjà vues (continuité narrative)
```

**Flow utilisateur :**

1. Inscription → `CurriculumPlannerService::buildSkeleton($user, $exam, $level)` crée un **squelette** : une liste ordonnée de ~30 objectifs macro (ex: "Maîtriser les temps du passé", "Lexique du travail", "Structuration d'un essai argumenté") — pas de contenu détaillé.

2. Quand l'apprenant ouvre sa "prochaine leçon" → `NextLessonGenerator::generate($user)` :
   - Lit le skeleton → identifie l'objectif macro courant
   - Analyse les erreurs récentes (`user_errors` catégorisés — Pilier 4)
   - Analyse les leçons précédentes
   - Appelle Mistral avec un prompt du type :
     > "Tu es un enseignant d'anglais pour un apprenant {{level}}. Son objectif courant : {{macro_objective}}. Ses 5 dernières erreurs : {{errors}}. Ses 3 dernières leçons : {{titles}}. Génère la prochaine leçon : titre, concept ciblé, explication (500 mots), 5 exemples, 3 pièges, 3 QCM de vérif. Si les erreurs indiquent une faiblesse sur un concept déjà vu, fais une leçon de **consolidation** au lieu d'avancer."
   - Sauvegarde le résultat comme une `Lesson` attachée à un `LearningPathNode` créé dynamiquement
   - Génère aussi les exercices d'application en fonction du même contexte

3. Si l'apprenant échoue > 2 fois sur les exercices d'une leçon → `NextLessonGenerator` **ne progresse pas** : la leçon suivante est une **reprise** du même concept avec une approche différente (plus d'exemples, explication simplifiée).

4. Si l'apprenant cartonne (100% des exercices ≥ 3 leçons d'affilée) → **saut** dans le skeleton (avance de 2 objectifs au lieu d'1).

**Mécanisme clé : le skeleton évolue aussi**

Le skeleton n'est pas fixe. Après chaque leçon, `CurriculumPlannerService::reassess($user)` peut :
- **Insérer** un nouvel objectif (ex: détecte une faiblesse sur les articles → ajoute "Les articles définis/indéfinis" avant la suite)
- **Supprimer** un objectif déjà maîtrisé (évite les redites)
- **Réordonner** les objectifs restants en fonction des priorités détectées

**Modèle de données :**

```
curriculum_skeletons (NOUVEAU)
  - id, user_id, exam_id
  - objectives : JSON [{ order, title, concept, status: pending|current|done, priority }]
  - created_at, updated_at

lessons (NOUVEAU, voir Pilier 1)
  - id, user_id, node_id (nullable), skeleton_objective_index
  - title, theory_markdown, key_takeaways, common_mistakes
  - generated_at, based_on_errors : JSON (traçabilité)
  - status : draft | published | consolidation | remedial
```

**Point clé : pas de pré-génération**. Les leçons sont créées **à la demande**, une par une. Ça veut dire :
- Pas d'attente longue à l'inscription (juste le skeleton = 1 appel Mistral)
- Chaque leçon est fraîche et contextuelle
- Coût API Mistral réparti dans le temps au lieu d'un gros batch
- L'utilisateur ne voit jamais de "leçons en avance" figées

**Interaction avec l'existant (`RoadmapGeneratorService`) :**

Le `RoadmapGeneratorService` actuel devient **obsolète pour les nouveaux utilisateurs**, remplacé par `CurriculumPlannerService` + `NextLessonGenerator`. Pour les utilisateurs existants avec un roadmap déjà généré, on garde une compatibilité : fallback sur l'ancien système si pas de skeleton.

**Fichiers à créer :**
- `app/Services/Curriculum/CurriculumPlannerService.php` — skeleton + reassess
- `app/Services/Curriculum/NextLessonGenerator.php` — génération just-in-time
- `app/Models/CurriculumSkeleton.php` + migration
- `app/Http/Controllers/LessonController.php` — endpoint `GET /lessons/next` qui déclenche la génération si nécessaire

**Fichiers à modifier :**
- `app/Http/Controllers/OnboardingController.php` — appeler `CurriculumPlannerService::buildSkeleton()` au lieu de `RoadmapGeneratorService::generateForUser()` pour les nouveaux users
- `app/Services/ExerciseScoringService.php` — après chaque exercice d'une leçon, déclencher `CurriculumPlannerService::reassess()` si seuil atteint
- `resources/js/pages/learning/lesson.tsx` — bouton "Prochaine leçon" qui déclenche la génération JIT avec loader "Ton prof prépare ta prochaine leçon…"

---

### Pilier 2 — Difficulté réellement adaptative

**Faille :** `app/Services/AI/ExerciseGeneratorService.php` (lignes 78-146) génère toujours la même difficulté.

**Solution :** Introduire un **score de maîtrise par concept** (`concept_mastery` table) et ajuster la difficulté des exercices générés.

```php
// Nouveau : app/Services/AdaptiveDifficultyService.php
public function nextDifficultyFor($user, $concept): string {
    $mastery = ConceptMastery::where('user_id', $user->id)
        ->where('concept', $concept)->first();
    if (!$mastery) return 'easy';
    if ($mastery->score >= 0.85) return 'hard';
    if ($mastery->score >= 0.60) return 'medium';
    return 'easy';
}
```

Injecter ce service dans `ExerciseGeneratorService::generate()` pour adapter le prompt Mistral :
```
"Generate a {{difficulty}} exercise on {{concept}} for a {{level}} learner
who has {{mastery_score}}% mastery on this concept."
```

**Fichiers à modifier :**
- `app/Services/AI/ExerciseGeneratorService.php` (injection service + prompt)
- Créer `app/Services/AdaptiveDifficultyService.php`
- Créer `app/Models/ConceptMastery.php` + migration
- `app/Services/ExerciseScoringService.php` — mettre à jour `ConceptMastery` après chaque exercice

---

### Pilier 3 — Répétition espacée pour les erreurs (pas seulement le vocabulaire)

**Faille :** SM-2 implémenté dans `Vocabulary` mais jamais appelé pour les `UserError`.

**Solution :** Étendre SM-2 à toutes les erreurs.

1. Ajouter colonnes SM-2 à `user_errors` : `ease_factor`, `interval_days`, `next_review_at`, `easiness` (`review_count` existe déjà).
2. Créer `app/Services/ErrorSpacedRepetitionService.php` avec `schedule(UserError $e, bool $correct)` (copie de la logique SM-2 du vocabulaire).
3. Dans le player : **avant** de générer un nouvel exercice, vérifier s'il existe une `UserError` due (`next_review_at <= now()`) sur le même `skill_type` → la réinjecter comme exercice de révision.

**Fichiers à modifier :**
- `database/migrations/xxxx_add_sm2_to_user_errors.php`
- `app/Services/ErrorSpacedRepetitionService.php` (nouveau)
- `app/Http/Controllers/ExerciseController.php` — méthode `next()` : prioriser les erreurs dues
- `app/Services/ExerciseScoringService.php` — appeler `ErrorSpacedRepetitionService::schedule()` après évaluation

---

### Pilier 4 — Catégorisation + diagnostic des erreurs

**Faille :** Une erreur de subjonctif et une faute d'orthographe sont traitées pareil.

**Solution :** Taxonomie d'erreurs + ajout au prompt Mistral pour catégoriser automatiquement.

**Taxonomie proposée :**
```
grammar.tense / grammar.agreement / grammar.word-order / grammar.article
vocabulary.lexical / vocabulary.register / vocabulary.collocation
spelling / punctuation / coherence / pragmatic
listening.detail / listening.inference / reading.skim / reading.scan
speaking.pronunciation / speaking.fluency / speaking.accuracy
writing.structure / writing.cohesion
```

Modifier `MistralEvaluationService` pour renvoyer un champ supplémentaire :
```json
"error_category": "grammar.tense",
"error_subcategory": "past_simple_vs_present_perfect"
```

Stocker dans `user_errors.category` + `user_errors.subcategory`. Permettre à la dashboard d'afficher : **"Tu as 12 erreurs récurrentes sur le Present Perfect — clique ici pour voir la leçon dédiée"**.

**Fichiers à modifier :**
- `app/Services/AI/MistralEvaluationService.php` (prompt + parsing)
- `database/migrations/xxxx_add_category_to_user_errors.php`
- `resources/js/pages/results/*.tsx` (nouvelle section "Tes points faibles")

---

### Pilier 5 — Placement test diagnostic par compétence

**Faille :** `app/Services/AI/PlacementTestService.php` (lignes 16-97) → 5-8 questions mixtes = diagnostic imprécis.

**Solution :** Test adaptatif en 4 phases (une par compétence) de 5 questions chacune, avec algorithme CAT (Computer-Adaptive Testing) simplifié :
- Question 1 au niveau estimé (B1 par défaut)
- Si correct → niveau +1, sinon niveau -1
- 5 questions par compétence → niveau par compétence (reading: B1, listening: A2, writing: A2, grammar: B1)
- Le **niveau global** devient la moyenne, mais le **plan pédagogique** adapte par compétence

**Fichiers à modifier :**
- `app/Services/AI/PlacementTestService.php` — refonte complète
- `app/Models/UserProfile.php` — ajouter `levels_by_skill` JSON
- `resources/js/pages/onboarding/placement-test.tsx` — 4 phases

---

### Pilier 6 — Feedback de prononciation pour le Speaking

**Faille :** Speaking → Deepgram transcrit → Mistral évalue le texte. Aucun retour phonétique.

**Solution :** Utiliser les **confidence scores par mot** que Deepgram renvoie déjà dans sa réponse (`words[].confidence`). Un mot avec confidence < 0.6 indique une prononciation problématique.

**Flow :**
1. Deepgram transcrit → obtenir `words[]` avec confidences
2. Identifier les mots `confidence < 0.6` → les marquer comme "prononciation incertaine"
3. Générer via Deepgram TTS (aura) la version correcte du mot → bouton "écoute le mot correct"
4. Afficher dans le résultat speaking : "3 mots ont été difficiles à comprendre : **schedule**, **through**, **particularly** — écoute-les et réessaie"

**Fichiers à modifier :**
- `app/Services/AI/DeepgramSttService.php` — parser `words[]`
- `app/Services/AI/MistralEvaluationService.php` — intégrer le signal phonétique dans l'éval
- `resources/js/pages/exercises/player.tsx` — UI pour les mots problématiques

---

### Pilier 7 — Mock exams diagnostiques + objectifs dynamiques

**Faille :** Mock exam = score, point. Objectif fixé à l'inscription, jamais réajusté.

**Solution :**

**7a — Mock exam diagnostique :**
À la fin d'un mock exam, générer un rapport structuré :
```
Score global : 6.5 / 9 (IELTS)
  ├── Reading : 7.0 ✓ (objectif atteint)
  ├── Listening : 6.5 ✓
  ├── Writing : 5.5 ⚠️ (à travailler : task response, cohésion)
  └── Speaking : 6.0 ⚠️ (à travailler : fluidité, vocabulaire B2)

Plan recommandé :
  → 2 semaines focus Writing Task 2
  → 1 semaine prononciation + speaking spontané
```

**7b — Ajustement automatique du roadmap :**
Après chaque mock exam, appeler `RoadmapGeneratorService::adjustPlanAfterDiagnostic($user, $results)` qui :
- Réordonne les nœuds restants en priorisant les faiblesses identifiées
- Injecte des nœuds supplémentaires sur les compétences faibles
- Reporte les nœuds des compétences déjà au niveau cible

**Fichiers à modifier / créer :**
- `app/Services/AI/RoadmapGeneratorService.php` — ajouter `adjustPlanAfterDiagnostic()`
- `app/Services/MockExamAnalysisService.php` (nouveau)
- `resources/js/pages/exercises/session-report.tsx` — affichage diagnostique

---

### Pilier 8 — Validation qualité des exercices IA générés

**Faille :** Mistral génère des exercices parfois incorrects (mauvaise réponse, ambiguïtés). Aucune validation.

**Solution minimale :**
- **Double-check** : après génération, re-soumettre l'exercice au même Mistral avec le prompt "Tu es un examinateur, cet exercice est-il correct sans ambiguïté ? Réponds OUI/NON + raison". Rejeter ceux marqués NON.
- **User reporting** : bouton "signaler un problème" sur chaque exercice → table `exercise_reports` → si > 3 rapports, désactiver l'exercice.

**Fichiers à modifier :**
- `app/Services/AI/ExerciseGeneratorService.php` — méthode `validateExercise()` après génération
- `app/Models/ExerciseReport.php` + migration (nouveau)
- `resources/js/pages/exercises/player.tsx` — bouton signaler

---

## Ordre d'implémentation recommandé (par impact pédagogique)

**Phase 1 — Fondations (1-2 semaines)** — le plus d'impact sur l'apprentissage réel :
1. **Pilier 4 : Catégorisation des erreurs** — base pour tout ce qui suit (les autres piliers en dépendent)
2. **Pilier 1 : Contenu de cours** (Lessons — modèle + UI)
3. **Pilier 9 : Génération progressive et adaptative du programme** — le cœur du système "prof particulier"
4. **Pilier 3 : SM-2 pour erreurs** — consolidation à long terme

**Phase 2 — Adaptation (1 semaine) :**
4. **Pilier 2 : Difficulté adaptative** (`ConceptMastery`)
5. **Pilier 5 : Placement test par compétence**

**Phase 3 — Diagnostic et ajustement (1 semaine) :**
6. **Pilier 7 : Mock exam diagnostique + roadmap dynamique**
7. **Pilier 8 : Validation qualité exercices**

**Phase 4 — Raffinement (optionnel) :**
8. **Pilier 6 : Feedback prononciation** (plus lourd, moins critique que 1-7)

---

## Fichiers critiques à modifier ou créer

| Fichier | Action | Pilier |
|---|---|---|
| `app/Models/Lesson.php` | Créer | 1 |
| `database/migrations/xxxx_create_lessons_table.php` | Créer | 1 |
| `resources/js/pages/learning/lesson.tsx` | Créer | 1, 9 |
| `app/Http/Controllers/LessonController.php` | Créer (endpoint `/lessons/next` JIT) | 1, 9 |
| `app/Services/Curriculum/CurriculumPlannerService.php` | Créer (skeleton + reassess) | 9 |
| `app/Services/Curriculum/NextLessonGenerator.php` | Créer (génération JIT Mistral) | 9 |
| `app/Models/CurriculumSkeleton.php` | Créer + migration | 9 |
| `app/Http/Controllers/OnboardingController.php` | Modifier (appel `buildSkeleton` au lieu de roadmap figé) | 9 |
| `app/Services/AI/ExerciseGeneratorService.php` | Modifier (difficulté + validation) | 2, 8 |
| `app/Services/AdaptiveDifficultyService.php` | Créer | 2 |
| `app/Models/ConceptMastery.php` | Créer + migration | 2 |
| `database/migrations/xxxx_add_sm2_to_user_errors.php` | Créer | 3 |
| `app/Services/ErrorSpacedRepetitionService.php` | Créer | 3 |
| `app/Http/Controllers/ExerciseController.php` | Modifier (`next()` prioriser erreurs dues) | 3 |
| `app/Services/ExerciseScoringService.php` | Modifier (SM-2 + ConceptMastery) | 2, 3 |
| `app/Services/AI/MistralEvaluationService.php` | Modifier (catégorisation erreurs) | 4 |
| `database/migrations/xxxx_add_category_to_user_errors.php` | Créer | 4 |
| `app/Services/AI/PlacementTestService.php` | Refonte CAT par compétence | 5 |
| `app/Models/UserProfile.php` | Ajouter `levels_by_skill` | 5 |
| `resources/js/pages/onboarding/placement-test.tsx` | Refonte 4 phases | 5 |
| `app/Services/AI/DeepgramSttService.php` | Parser `words[]` confidences | 6 |
| `resources/js/pages/exercises/player.tsx` | UI mots problématiques + bouton signaler | 6, 8 |
| `app/Services/AI/RoadmapGeneratorService.php` | `adjustPlanAfterDiagnostic()` | 7 |
| `app/Services/MockExamAnalysisService.php` | Créer | 7 |
| `resources/js/pages/exercises/session-report.tsx` | Affichage diagnostique | 7 |
| `app/Models/ExerciseReport.php` | Créer + migration | 8 |

---

## Réutilisation de l'existant (ne pas réinventer)

- **SM-2 déjà implémenté** dans `VocabularyService` → copier la logique pour `ErrorSpacedRepetitionService`
- **`LearningPathNode.prerequisites`** existe en DB mais inutilisé → l'utiliser dans Pilier 2 pour déverrouillage
- **`LevelAdvancementService`** gère déjà la promotion de niveau → l'étendre pour ajustement dynamique (Pilier 7)
- **Mistral JSON structuré** (fix récent dans `MistralEvaluationService`) → simple extension pour catégorisation (Pilier 4)
- **Syllabus seeders** (`EnglishSyllabusSeeder`) → point d'entrée pour seeder les `Lesson` initiales
- **`applyProgressiveRamp()`** récemment ajouté dans `RoadmapGeneratorService` → base pour l'ajustement dynamique du Pilier 7

---

## Vérification

**Scope Phase 1 uniquement** — une fois implémenté, un utilisateur A1 devrait pouvoir :

1. **S'inscrire** → à la place du roadmap figé actuel, le système crée un **skeleton** de ~30 objectifs macro (appel Mistral unique)
2. **Cliquer sur "Commencer ma première leçon"** → Mistral génère à la volée une leçon sur le premier objectif (present simple), avec explication, exemples, pièges, + 3 QCM de vérification
3. **Faire les exercices de la leçon** → s'il échoue, chaque erreur est **catégorisée** (`grammar.tense.present_simple`) et stockée avec ses colonnes SM-2
4. **Cliquer sur "Prochaine leçon"** → le générateur lit les erreurs récentes → si l'élève a échoué sur le present simple, la "prochaine leçon" est une **consolidation** du même concept (approche différente), pas un saut vers le present continuous
5. **Revenir le lendemain** → les erreurs dues (SM-2) sont réinjectées en priorité dans les exercices
6. **Après 5 leçons maîtrisées** → le skeleton se réajuste (`reassess()`) : nouveaux objectifs insérés selon les faiblesses détectées, objectifs maîtrisés sautés

**Test end-to-end Phase 1 :** Créer un compte test "A1 français apprenant anglais", simuler 10 sessions, vérifier :
- [ ] À l'inscription, un `curriculum_skeleton` est créé avec ~30 objectifs
- [ ] La première leçon est générée à la demande (pas pré-générée à l'inscription)
- [ ] Les erreurs portent une `category` + `subcategory` renseignées par Mistral
- [ ] Si je simule 3 échecs consécutifs sur un concept, la leçon suivante est une consolidation de CE concept (pas une progression)
- [ ] Si je simule 3 réussites consécutives, la leçon suivante avance vers l'objectif suivant du skeleton
- [ ] Une erreur commise revient bien après ~1 jour (SM-2 sur `user_errors`)
- [ ] Le dashboard affiche les catégories d'erreurs récurrentes ("12 erreurs sur Present Perfect")

**Piliers 2, 5, 6, 7, 8 — hors scope Phase 1** — seront traités dans une phase ultérieure.
