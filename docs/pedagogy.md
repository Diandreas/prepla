# PrePla — Pédagogie & Stack technique de référence

> Document maître. À mettre à jour quand on prend des décisions structurelles.
> Dernière révision : 2026-05-27

---

## 1. Mission produit

PrePla doit permettre à un candidat de **réussir un examen de langue (IELTS, TOEFL, Goethe, DELF, TCF) sans avoir besoin d'un professeur particulier**.

Cela impose un standard élevé : tout ce qu'un bon tuteur fait, l'app doit le faire — ou expliquer pourquoi elle ne le fait pas. Si l'app ne peut pas remplacer le tuteur sur un point critique, on doit le dire honnêtement à l'utilisateur, pas le masquer.

---

## 2. Les 7 piliers pédagogiques (et l'état actuel)

| # | Pilier | État actuel | Cible |
|---|---|---|---|
| 1 | **Diagnostic initial** (placement test) | ❌ Auto-déclaration A1-C2 | CAT (Computer Adaptive Test) basé IRT, 15-20 questions |
| 2 | **Curriculum personnalisé** | 🟡 Existe (modules adaptatifs) mais pas piloté par diagnostic | Plan calibré sur niveau réel + L1 + date + score cible |
| 3 | **Pratique avec feedback** | 🟡 Mistral évalue, mais pas aligné rubriques | Évaluation alignée rubriques officielles IELTS/TOEFL/etc. |
| 4 | **Compréhension orale** | ❌ Aucun audio | TTS authentique multi-accents (Deepgram Aura) |
| 5 | **Expression orale** | 🟡 STT Deepgram + Mistral, pas de score phonétique | Fluency + Lexical + Grammar via LLM ; Pronunciation via word-confidence Deepgram |
| 6 | **Mémorisation espacée** | 🟡 SM-2 implémenté, non branché au flow | FSRS-4, déclenché à chaque rencontre de mot |
| 7 | **Stratégies d'examen** | ❌ Absent | Module dédié par examen (time management, skimming, structure essay…) |

---

## 3. Décisions techniques structurelles

### 3.1 Stack IA — budget zéro durable

| Besoin | Solution choisie | Pourquoi | Limite |
|---|---|---|---|
| **TTS (Listening)** | **Deepgram Aura** | $200 crédit gratuit (~13M chars Aura-1 ou 6.6M Aura-2). Même vendeur que STT, intégration déjà existante. | Quand crédit épuisé : pré-générer audio en batch et cacher en MP3 |
| **STT (Speaking)** | **Deepgram Nova** (déjà en place) | Confidence par mot + timestamps utilisables pour proxy de prononciation | Pas de score phonème natif (acceptable MVP) |
| **Correction d'écrit (rubriques)** | **Google Gemini 1.5 Flash** (free tier 1500 req/jour, 15/min) | Qualité supérieure à Mistral pour suivre des rubriques structurées | Garder Mistral en fallback rapide |
| **Génération d'exercices** | **Mistral** (déjà en place) | Rapide, pas cher, suffisant pour QCM/gap-fill | Pas de changement |
| **Détection erreurs grammaire** | **LanguageTool API publique** (free tier 20 req/min/IP) | 30+ langues, déterministe, complète l'IA | Rate-limit côté nous + cache des erreurs récurrentes |
| **Conversation IA / explainer** | **Groq (Llama 3.3 70B)** free tier | Free + très rapide | Fallback Mistral |
| **Observabilité erreurs** | **Sentry** free tier (5k events/mois) | Indispensable pour ne plus jamais découvrir un bug critique par un testeur | À installer dès semaine 1 |

### 3.2 Ce qui ne marche PAS sur notre VPS actuel (1 CPU / 4 GB RAM / 50 GB)

- ❌ **Coqui TTS** : a besoin de ≥ 4 GB RAM et 5-10 s par phrase sur CPU. Plantera dès 2-3 utilisateurs simultanés.
- ❌ **Montreal Forced Aligner** : Kaldi + 2 GB de modèles, plusieurs secondes CPU par requête.
- ❌ **LanguageTool self-hosted** : Java, 1-2 GB RAM supplémentaires.
- ❌ **Whisper local** : 1-3 GB RAM, lent sur CPU.

**Conclusion VPS** : on garde le 4 GB pour Laravel + MySQL + Inertia. Tout ce qui est IA passe par des APIs externes (free tier). C'est non négociable tant qu'on n'upgrade pas le VPS.

### 3.3 Migration SRS : SM-2 → FSRS-4

**Pourquoi** : FSRS-4 est ~30 % plus efficace que SM-2 (moins de répétitions pour la même rétention). Utilisé par Anki depuis 2023.

**Plan de migration** :

1. Installer `ts-fsrs` côté front : `npm i ts-fsrs`
2. Schéma DB du modèle `VocabularyWord` ou équivalent :
   ```
   - stability         FLOAT
   - difficulty        FLOAT
   - elapsed_days      INT
   - scheduled_days    INT
   - reps              INT
   - lapses            INT
   - state             ENUM('new','learning','review','relearning')
   - last_review       TIMESTAMP NULL
   - due               TIMESTAMP
   ```
3. Migration des records SM-2 existants : initialiser `stability` et `difficulty` à partir du `easiness_factor` et `interval` SM-2 (formule empirique disponible dans la doc FSRS).
4. Hook au flow : à chaque rencontre d'un mot nouveau (lesson, exercice, correction), proposer **[+ Ajouter au lexique]**. À chaque review, l'utilisateur note `Again | Hard | Good | Easy` → FSRS calcule la prochaine `due`.
5. Notifications : push (PWA déjà en place) le matin avec le nombre de mots dus.

Ressource : https://github.com/open-spaced-repetition/ts-fsrs et le paper https://github.com/open-spaced-repetition/fsrs4anki/wiki

### 3.4 Placement test (IRT 2PL simplifié)

Implémentation possible sans librairie externe, ~300 lignes PHP. Algorithme :

1. Banque de 200-300 items pré-calibrés (difficulté `b` ∈ [-3, 3], discrimination `a` ∈ [0.5, 2]). On commence avec `b ≈ 0` (≈ B1).
2. Après chaque réponse, mise à jour de l'estimation `θ` (niveau de l'utilisateur) par maximum de vraisemblance.
3. Sélection de l'item suivant : celui avec l'information de Fisher max à `θ` courant.
4. Stop quand l'erreur standard `SE(θ) < 0.3` (≈ ± un demi-niveau CEFR) ou après 25 items.
5. Conversion finale `θ → CEFR` :
   - θ ≤ -2 → A1
   - -2 < θ ≤ -1 → A2
   - -1 < θ ≤ 0 → B1
   - 0 < θ ≤ 1 → B2
   - 1 < θ ≤ 2 → C1
   - θ > 2 → C2

**Calibrage des items** : commencer avec des `b` heuristiques (basés sur CEFR-J vocabulaire/grammaire), affiner après les 200 premiers utilisateurs avec un MCMC simple ou logistique.

---

## 4. Ressources gratuites à ingérer

> 📚 **Inventaire détaillé par langue** dans des fichiers dédiés :
> - 🇬🇧 [docs/resources-english.md](resources-english.md) — IELTS, TOEFL, Cambridge
> - 🇫🇷 [docs/resources-french.md](resources-french.md) — DELF/DALF, TCF, TEF
> - 🇩🇪 [docs/resources-german.md](resources-german.md) — Goethe, TestDaF
>
> **16 fichiers déjà téléchargés sur disque** dans [docs/resources/](resources/) :
> - 4 jeux de données vocabulaire CEFR (~55 000 mots EN+FR taggés A1-C2)
> - 6 rubriques officielles d'examens (IELTS Writing/Speaking, TOEFL Speaking/Writing, DELF A1/B2)
> - 5 sample papers / Modellsätze (Goethe A1/B1/C1, TestDaF Modelltest 01 partiel)

Toutes ces ressources ont été vérifiées comme **téléchargeables et utilisables gratuitement** (recherche/éducation au minimum, parfois commercial avec attribution).

### 4.1 Vocabulaire & grammaire CEFR

| Ressource | Format | Licence | URL |
|---|---|---|---|
| **CEFR-J Vocabulary Profile v1.5** | CSV | Free (citation requise) | https://github.com/openlanguageprofiles/olp-en-cefrj |
| **Words-CEFR-Dataset** (Maximax67) | CSV + frequency | MIT | https://github.com/Maximax67/Words-CEFR-Dataset |
| **CEFR-J Grammar Profile** | Excel + scripts Perl | Free (citation) | https://cefr-j.org/download.html |
| **CEFR-J CAN-DO descriptors (650+)** | PDF | Free (citation) | https://cefr-j.org/download.html |
| **Kaggle 10k CEFR words** | CSV | Public | https://www.kaggle.com/datasets/nezahatkk/10-000-english-words-cerf-labelled |

**Action concrète** : créer une table `cefr_lexicon` avec colonnes `(word, lemma, pos, cefr_level, frequency, examples)`. Ingérer les CSV CEFR-J + Maximax67 fusionnés. Cette table devient la **source de vérité** pour :
- savoir à quel niveau introduire un mot
- proposer "ce mot est C1, hors de ta zone actuelle B1" lors d'une lecture
- générer des exercices calibrés au niveau exact de l'utilisateur

### 4.2 Audio & transcripts (Listening authentique)

| Ressource | Niveau | Format | URL |
|---|---|---|---|
| **BBC Learning English — 6 Minute English** | B1-B2 | Podcast RSS + transcript | https://www.bbc.co.uk/learningenglish |
| **BBC Learning English — Learning English Conversations** | A2-B2 | RSS audio | https://podcasts.files.bbci.co.uk/p02pc9zn.rss |
| **BBC Learning English — Stories** | A2-B1 | RSS audio | https://podcasts.files.bbci.co.uk/p02pc9s1.rss |
| **VOA Learning English** | A2-B2 | RSS audio + transcript | https://learningenglish.voanews.com |
| **News in Levels** | A1-B2 (3 niveaux par article) | Web + RSS | https://www.newsinlevels.com |
| **Deutsche Welle — Langsam gesprochene Nachrichten** | A2-B1 (allemand) | RSS audio + transcript | https://learngerman.dw.com |
| **TV5 Monde Apprendre** | A1-C1 (français) | Web | https://apprendre.tv5monde.com |
| **RFI Savoirs** | B1-C1 (français) | RSS audio | https://savoirs.rfi.fr |

**Plan d'ingestion** : un Laravel command `php artisan content:fetch-podcasts` qui :
1. Lit chaque RSS.
2. Télécharge le MP3 + transcript HTML.
3. Tague par niveau CEFR (déterminé par lisibilité Flesch-Kincaid + densité de mots > B2).
4. Stocke dans `storage/app/public/audio/` + table `content_items`.

**Attribution obligatoire** : chaque écoute affiche "Source : BBC Learning English" + lien retour. Respecter robots.txt et rate limits.

### 4.3 Stratégies d'examen

- **IELTS Liz** (gratuit, blog complet stratégies par section) : https://ieltsliz.com
- **British Council IELTS prep** (free practice tests, conseils officiels) : https://takeielts.britishcouncil.org/prepare
- **ETS TOEFL Free Practice** : https://www.ets.org/toefl/test-takers/ibt/prepare/
- **Goethe-Institut Übungsmaterialien** : https://www.goethe.de/de/spr/kup/prf.html
- **CIEP DELF/DALF exemples** : https://www.france-education-international.fr

Ces ressources servent à construire des **modules "stratégie"** : 5-10 min de lecture/vidéo par compétence, à présenter avant chaque type d'exercice.

### 4.4 Descripteurs de notation (rubriques officielles)

| Examen | Compétence | Source | Statut |
|---|---|---|---|
| IELTS | Writing | `ielts_writing_band_descriptors.pdf` (déjà sur disque) | ✅ |
| IELTS | Speaking | https://takeielts.britishcouncil.org → speaking band descriptors PDF | À récupérer |
| IELTS | Listening / Reading | Score basé sur # correct → band conversion table publique | À implémenter |
| TOEFL iBT | All sections | ETS scoring rubrics publics | À récupérer |
| Goethe | A1-C2 | https://www.goethe.de modèles d'examen | À récupérer |
| DELF/DELF | A1-C2 | France Éducation Int. — grilles officielles | À récupérer |

---

## 5. Rubrique IELTS Writing — implémentation prompt

(Rubrique officielle May 2023, lue depuis `ielts_writing_band_descriptors.pdf`)

**4 critères, chacun noté 0-9, score final = moyenne arrondie au 0.5 le plus proche.**

### Critère 1 — Task Achievement (Task 1) / Task Response (Task 2)

- Évalue si le candidat répond à la consigne, traite tous les points, prend une position claire (Task 2), couvre les key features (Task 1).
- Signal pour scoring auto : longueur (< 150 mots Task 1 / < 250 mots Task 2 = plafonné), présence des bullet points, ratio relevance/off-topic.

### Critère 2 — Coherence & Cohesion

- Organisation logique, paragraphing, cohesive devices, référence/substitution.
- Signal auto : ratio de connecteurs (however, therefore, moreover...), nombre de paragraphes, longueur moyenne paragraphe.

### Critère 3 — Lexical Resource

- Étendue du vocabulaire, mots peu communs/idiomatiques, collocations, spelling.
- Signal auto : type-token ratio, % mots niveau B2+ vs A1-B1 (via table CEFR-J), # collocations correctes, # erreurs spelling/word-formation.

### Critère 4 — Grammatical Range & Accuracy

- Variété des structures (subordonnées, conditionnelles, voix passive...), # phrases error-free, ponctuation.
- Signal auto : ratio simples/complexes (parsing dépendances), # erreurs grammaticales (LanguageTool), % phrases sans erreur.

### Prompt structuré pour Gemini Flash (à utiliser dans `WritingCorrectorService`)

```
You are an official IELTS Writing examiner.

TASK TYPE: {task_type}    // "Task 1 Academic" | "Task 1 General" | "Task 2"
PROMPT: {prompt}
CANDIDATE RESPONSE:
"""
{essay}
"""

PRECOMPUTED SIGNALS (use as hints, don't trust blindly):
- word_count: {n}
- type_token_ratio: {ttr}
- pct_b2_plus_vocab: {pct}
- complex_sentence_ratio: {ratio}
- grammar_errors_lt: {errors_array}

Apply the IELTS May 2023 band descriptors strictly. A response must FULLY fit
positive features of a band to be rated there. Bolded negative features in the
descriptor (off-topic, < 20 words, copied rubric) cap the band.

Return STRICTLY this JSON:
{
  "task_achievement": { "band": float (0-9, .5 increments), "justification": "1-2 sentences citing descriptor language" },
  "coherence_cohesion": { "band": float, "justification": "..." },
  "lexical_resource": { "band": float, "justification": "..." },
  "grammatical_range": { "band": float, "justification": "..." },
  "overall_band": float,
  "top_3_improvements": [
    { "category": "...", "issue": "specific quote from essay", "fix": "concrete rewrite suggestion" },
    ...
  ],
  "strengths": ["...", "..."]
}
```

Sauvegarder le JSON dans `writing_evaluations` avec FK sur l'attempt. Cumuler les "top_3_improvements" dans le profil d'erreur de l'utilisateur pour générer des exercices ciblés la semaine suivante.

---

## 6. Roadmap (3 sprints, ~10 semaines)

### Sprint 1 — Fondations (2 semaines)

**Objectif** : l'app actuelle ne crashe plus, mesure ce qui se passe, et applique vraiment la langue choisie.

- [ ] Fix `Array to string conversion` dans `ExerciseScoringService.php`
- [ ] Fix bouton "Générer" caché par bottom-nav mobile
- [ ] Fix examen : modal pré-démarrage + bouton valider + autosave réponses
- [ ] Audit i18n exhaustif : remplacer tous les `Vrai/Faux/Annuler/...` hardcodés par `t()`
- [ ] Synchroniser `App::setLocale()` côté Laravel avec `interface_language` du profil
- [ ] **Sentry** installé (front + back)
- [ ] Supprimer `public.zip` du repo + ajouter à `.gitignore`
- [ ] Rotation token GitHub + clé SSH GitHub + rotation pwd root VPS

### Sprint 2 — Changeurs de jeu pédagogiques (4 semaines)

**Objectif** : transformer l'app de "drill aléatoire" à "tuteur IA aligné rubriques".

- [ ] **Deepgram Aura TTS** intégré pour tous les exercices Listening
- [ ] Pré-génération + cache MP3 des audios statiques (lessons)
- [ ] **FSRS-4** : migration SM-2 → FSRS, hook "+ Lexique" partout
- [ ] Ingestion **CEFR-J vocab** dans table `cefr_lexicon`
- [ ] **WritingCorrectorService** réécrit avec Gemini Flash + rubrique IELTS
- [ ] **LanguageTool** intégré (API publique, rate limit côté nous)
- [ ] Ingestion **BBC Learning English RSS** → 50 premiers articles+audio
- [ ] Bouton "Source: BBC Learning English" sur chaque contenu importé

### Sprint 3 — Vrai tuteur (4 semaines)

**Objectif** : l'app est défendable face à "j'ai juste besoin d'un prof particulier".

- [ ] **Placement test CAT** (banque 200 items + IRT 2PL)
- [ ] Pré-test / post-test par module → métrique **gain réel**
- [ ] Re-calibration XP/trophées sur courbe `level^1.5`
- [ ] Promotion CEFR explicite avec "test de promotion" déclenché
- [ ] **Mock Exam** complet IELTS (2h45) avec score final aligné
- [ ] **Pronunciation proxy** Deepgram (confidence × fluency × completeness)
- [ ] Module "Stratégies d'examen" par section IELTS

---

## 7. Ce qu'on ne peut PAS faire gratuitement (à arbitrer avec l'utilisateur)

Honnêteté complète : pour aller au-delà du MVP, certains choix coûtent.

### 7.1 Doit-on absolument avoir une carte bancaire ?

**OUI**, à terme. Sans carte, on ne peut pas :
- Upgrader Deepgram quand les $200 sont consommés (≈ 6 mois d'usage modéré, ou 1-2 mois en croissance)
- Utiliser OpenAI/Anthropic pour la correction premium (Gemini Flash gratuit suffit pour ~95 % des cas)
- Upgrader le VPS quand on dépasse les 50 utilisateurs simultanés

**Solution accessible** : carte prépayée (Wise, Revolut, N26) — pas besoin d'un compte bancaire classique, validation en 24-48h, fonctionne sur toutes les APIs.

### 7.2 Pronunciation Assessment "vrai" (phonème par phonème)

- **Azure Speech Pronunciation** : meilleur du marché, payant (~$1/heure d'audio évaluée), CB nécessaire.
- **Notre alternative gratuite** : Deepgram word-confidence + fluency (mots/seconde) + completeness (% mots prononcés vs script). C'est ~70 % aussi bon, suffisant pour un MVP. À durcir en V2 si budget.

### 7.3 LLM premium pour la correction d'écrit

- **Gemini Flash free tier** suffit jusqu'à ~1500 corrections/jour. Au-delà : passer à Gemini Pro ($) ou Claude Sonnet ($).
- Si on vise une qualité de notation "indistinguable d'un examinateur humain certifié", il faudra Claude Sonnet (~$3/M tokens). Pour un MVP éducatif, Gemini Flash est largement bon.

### 7.4 Upgrade VPS

- KVM 1 (1 CPU/4 GB) → KVM 2 (2 CPU/8 GB) sur Hostinger : ~$10/mois.
- Recommandé dès qu'on dépasse 50 utilisateurs actifs simultanés ou si on veut self-host LanguageTool / Whisper.

---

## 8. Métriques produit à tracker dès le sprint 1

Une plateforme qui veut remplacer un prof DOIT mesurer ce qui marche. À mettre en place dans le dashboard admin :

- **Gain par module** = (post-test score) − (pre-test score). Médiane par module.
- **Rétention vocabulaire** = % de mots correctement répondus à 30 jours (FSRS expose cette donnée).
- **Calibration des trophées** = corrélation entre niveau de trophées et score réel au mock exam. Doit être ≥ 0.7.
- **Taux d'abandon par exercice** = % d'utilisateurs qui quittent au milieu d'un exercice. Signal de difficulté mal calibrée.
- **Distribution CEFR estimée vs déclarée** = doit converger après le placement test.
- **NPS post mock exam** = "Recommanderais-tu PrePla à un ami qui prépare ce même examen ?"

---

## 9. Sources & références

- IELTS Writing Band Descriptors (May 2023) — `ielts_writing_band_descriptors.pdf` (sur disque)
- CEFR-J Wordlist & Grammar : https://cefr-j.org/download.html
- OpenLanguageProfiles CEFR-J GitHub : https://github.com/openlanguageprofiles/olp-en-cefrj
- Words-CEFR-Dataset : https://github.com/Maximax67/Words-CEFR-Dataset
- Deepgram pricing & Aura TTS : https://deepgram.com/pricing
- ts-fsrs (FSRS-4 implementation) : https://github.com/open-spaced-repetition/ts-fsrs
- FSRS paper & wiki : https://github.com/open-spaced-repetition/fsrs4anki/wiki
- BBC Learning English : https://www.bbc.co.uk/learningenglish
- VOA Learning English : https://learningenglish.voanews.com
- News in Levels : https://www.newsinlevels.com
- LanguageTool : https://languagetool.org
- Sentry : https://sentry.io
- Gemini API : https://ai.google.dev/gemini-api/docs
- Groq (Llama free tier) : https://groq.com

---

## Annexe — Framework consolidé : Mastery + Comprehensible Input + Spaced Retrieval
*(Ajouté 2026-05-28)*

### Pourquoi cette synthèse

Aucune app existante (Duolingo, Babbel, Busuu, Memrise, Magoosh) ne combine les 3 cadres validés ci-dessous. C'est la valeur unique de PrePla.

### Les 3 piliers

**1. Mastery Learning (Bloom)** — On n'avance pas tant que la maîtrise (≥80%) n'est pas atteinte. Échec ×2 → leçon de consolidation alternative.

**2. L1 → L2 progressif (Krashen + Cummins)**
- A0/A1/A2 : explications 100% en langue maternelle, exemples en cible
- B1/B2 : 70/30
- C1/C2 : 100% en cible (sauf termes rares)

**3. SM-2 sur les erreurs réelles** — Les fautes du student reviennent à J+1, J+3, J+7, J+21… jusqu'à mastery. Différent d'Anki qui spacing-repeat des cartes piochées.

### 4 supports IA

- **Tuteur IA "pourquoi"** : explique le concept, pas juste la réponse correcte
- **Tâches d'examen réelles** : pas de drills génériques
- **Multi-modal** : Deepgram TTS/STT + Mistral writing evaluator
- **Boss-level synthèse** : fin de chapitre = mix de tous les concepts vus

### Boucle d'apprentissage canonique

```
Diagnose → Theory (L1+L2) → Guided practice (3 types variés)
       → Mastery check (≥80%)
              ├── PASS → Advance + XP + errors → SM-2 queue
              └── FAIL → Consolidation alternative
       → End of chapter: synthesis boss-level
```

### Comparaison concurrentielle

| App | Faiblesse vs PrePla |
|-----|----------------------|
| Duolingo | Pas de mastery, pas de théorie, pas exam-aligned |
| Babbel | Cursus fixe, pas d'IA feedback |
| Busuu | Lent (humain), générique |
| Memrise | Vocab uniquement |
| Magoosh / Cambridge | Statique, pas personnalisé |

### Implémentation actuelle (état au 2026-05-28)

- ✅ Mastery threshold dans `CurriculumSkeleton::advanceToNextObjective`
- ✅ Consolidation lessons sur 2 échecs consécutifs (`NextLessonGenerator`)
- ✅ L1 scaffolding tiered (`NextLessonGenerator::generateWithMistral`)
- ✅ Cache lesson par `via_{native_language}`
- ✅ SM-2 sur `UserError` (`ErrorSpacedRepetitionService`)
- ✅ Mistral explainer (`MistralEvaluationService::explainMistake` via chatRaw)
- ✅ Deepgram TTS/STT + Aura-2 pour FR/DE
- ✅ Dashboard refondé : chapitre actif uniquement
- 🔄 Boss-level synthesis : détection ✅, génération AI à câbler
- 🔄 Mode examen immersif : à construire
- 🔄 Pronunciation scoring phonémique : à étudier (MFA self-hosted ?)

### Références académiques

- Bloom, B. S. (1968). *Learning for Mastery*.
- Krashen, S. (1985). *The Input Hypothesis*.
- Cummins, J. (1981). *Bilingualism and Minority-Language Children*.
- Wozniak, P. (1994). *Optimization of repetition spacing* — SM-2.
- Roediger, H. L. & Karpicke, J. D. (2006). *Test-enhanced learning*.
