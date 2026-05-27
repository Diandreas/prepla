# Ressources English (IELTS, TOEFL, Cambridge)

> Toutes les ressources listées ici sont **gratuites** ou utilisables sous une licence permettant un usage éducatif/commercial avec attribution.
> Fichiers téléchargés localement dans [`docs/resources/en/`](resources/en/).

---

## A. Vocabulaire CEFR ✅ (2 jeux de données)

### A.1 CEFR-J Vocabulary Profile 1.5 — **téléchargé**

- **Fichier** : [`docs/resources/en/cefrj_vocab.csv`](resources/en/cefrj_vocab.csv) — 233 KB, **7 798 mots**
- **Format** : CSV — `headword, pos, CEFR, CoreInventory 1, CoreInventory 2, Threshold`
- **Source** : https://github.com/openlanguageprofiles/olp-en-cefrj
- **Licence** : Free for research/education/commercial **avec citation**
- **Exemple** :
  ```
  abandon,verb,B1
  abandoned,adjective,B2
  ```
- **Usage** : table `cefr_lexicon` côté DB, source de vérité pour le niveau d'un mot.

### A.2 EFLLex — **téléchargé**

- **Fichier** : [`docs/resources/en/efllex_english.tsv`](resources/en/efllex_english.tsv) — 7.2 MB, **15 281 mots**
- **Format** : TSV — `word, tag, freq_a1..c1, total_freq, occurrences par manuel`
- **Source** : https://cental.uclouvain.be/cefrlex/efllex/
- **Licence** : Creative Commons (attribution)
- **Différence vs CEFR-J** : EFLLex donne la **fréquence par niveau** dans des manuels FFL réels (Touchstone, Headway, etc.), pas juste le niveau d'apparition. Plus fin pour estimer si un mot est "fréquent en B2" vs "rare en B2".
- **Usage** : croiser avec CEFR-J pour calibrer la difficulté des exercices générés.

---

## B. Grammaire CEFR

### B.1 CEFR-J Grammar Profile

- **Source** : https://cefr-j.org/download.html
- **Format** : Excel + scripts Perl (versions 2020 & 2025)
- **Licence** : Free avec citation
- **Contenu** : structures grammaticales taggées A1-C2 (présent simple A1, conditionnel hypothétique B2, inversion stylistique C1, etc.)
- **À télécharger ultérieurement** (formulaire de contact requis sur le site, pas direct).

---

## C. Audio + Transcripts (Listening authentique)

> WebFetch bloqué sur certains domaines BBC, mais les RSS sont publics et accessibles via curl/PHP.

### C.1 BBC Learning English — recommandé

| Programme | Niveau | URL RSS / Hub |
|---|---|---|
| 6 Minute English | B1-B2 | https://www.bbc.co.uk/learningenglish/english/features/6-minute-english |
| Learning English Stories | A2-B1 | https://podcasts.files.bbci.co.uk/p02pc9s1.rss |
| Learning English Conversations | A2-B2 | https://podcasts.files.bbci.co.uk/p02pc9zn.rss |
| News Review | B2-C1 | https://www.bbc.co.uk/learningenglish/english/features/news-review |
| English at Work | B1-B2 | https://www.bbc.co.uk/learningenglish/english/features/english-at-work |

**Licence** : usage éducatif autorisé avec attribution. Préférer le RSS (≠ scraping pages).

### C.2 VOA Learning English

- **URL** : https://learningenglish.voanews.com
- **Niveaux** : A2-B2
- **Format** : audio MP3 + transcript HTML, RSS
- **Licence** : "Materials in the public domain. Translations not copyrighted" (US gov)

### C.3 News in Levels

- **URL** : https://www.newsinlevels.com
- **Format** : chaque article réécrit en 3 niveaux (level 1 ≈ A2, level 2 ≈ B1, level 3 ≈ B2)
- **Licence** : free for educational use with attribution

---

## D. Rubriques officielles

### D.1 IELTS Writing — **déjà sur disque**

- **Fichier** : [`ielts_writing_band_descriptors.pdf`](../ielts_writing_band_descriptors.pdf) — racine du projet
- **Contenu intégré dans** [`docs/pedagogy.md`](pedagogy.md#5-rubrique-ielts-writing--implémentation-prompt) (prompt Gemini prêt à l'emploi)

### D.2 IELTS Speaking — **téléchargé**

- **Fichier** : [`docs/resources/en/ielts_speaking_band.pdf`](resources/en/ielts_speaking_band.pdf) — 192 KB (source ielts.org)
- **Backup** : [`docs/resources/en/ielts_speaking_cam.pdf`](resources/en/ielts_speaking_cam.pdf) — 29 KB (Cambridge public version)
- **4 critères** (chacun 0-9) : Fluency & Coherence, Lexical Resource, Grammatical Range & Accuracy, **Pronunciation**.
- **Source officielle** : https://www.ielts.org/-/media/pdfs/speaking-band-descriptors.ashx

### D.3 TOEFL iBT Speaking — **téléchargé**

- **Fichier** : [`docs/resources/en/toefl_speaking_rubrics.pdf`](resources/en/toefl_speaking_rubrics.pdf) — 78 KB
- **Source** : https://www.ets.org/pdfs/toefl/toefl-ibt-speaking-rubrics.pdf
- **Échelle** : 0-4 par tâche, 0-30 total (4 tâches indépendantes + intégrées)

### D.4 TOEFL iBT Writing — **téléchargé**

- **Fichier** : [`docs/resources/en/toefl_writing_rubrics.pdf`](resources/en/toefl_writing_rubrics.pdf) — 71 KB
- **Source** : https://www.ets.org/pdfs/toefl/toefl-ibt-writing-rubrics.pdf
- **Tâches** : Integrated Writing (synthèse) + Academic Discussion (depuis 2023, remplace l'Independent Essay)

### D.5 Cambridge English (A2 Key, B1 Preliminary, B2 First, C1 Advanced, C2 Proficiency)

- **Source** : handbooks officiels Cambridge avec rubrics par paper
- **À récupérer** : `https://www.cambridgeenglish.org/images/504505-a2-key-handbook-2020.pdf` (le DL a timeout, à retenter)
- **Notation** : scale 80-230 sur Cambridge English Scale (équiv. CEFR A1-C2)

---

## E. Sample papers / Practice tests

### E.1 Cambridge

- **A2 Key handbook 2020** (sample papers inclus) : https://www.cambridgeenglish.org/images/504505-a2-key-handbook-2020.pdf
- **Young Learners sample papers vol.1** : https://www.cambridgeenglish.org/Images/young-learners-sample-papers-2018-vol1.pdf

### E.2 IELTS

- **British Council prep tests** : https://takeielts.britishcouncil.org/prepare-test/free-ielts-practice-tests
- **IDP tests** : https://ielts.idp.com/prepare/free-practice-tests
- **Cambridge IELTS books** (1-18) : payants, mais souvent disponibles via bibliothèques universitaires

### E.3 TOEFL

- **ETS Free TOEFL Tests** : https://www.ets.org/toefl/test-takers/ibt/prepare/tests/free.html
- **TOEFL iBT Test Prep Planner** (PDF officiel) : 7 semaines de prep gratuite

---

## F. Stratégies d'examen

### F.1 IELTS Liz — **catalogué intégralement**

Le site `ieltsliz.com` propose 300+ pages gratuites. Pages clés :

| Section | URL |
|---|---|
| Listening | https://ieltsliz.com/ielts-listening/ |
| Reading | https://ieltsliz.com/ielts-reading-lessons-information-and-tips/ |
| Writing Task 1 | https://ieltsliz.com/ielts-writing-task-1-lessons-and-tips/ |
| Writing Task 2 | https://ieltsliz.com/ielts-writing-task-2/ |
| Speaking | https://ieltsliz.com/ielts-speaking-free-lessons-essential-tips/ |
| Exam day tips | https://ieltsliz.com/ielts-exam-tips-on-the-day/ |
| 100 essay questions | https://ieltsliz.com/100-ielts-essay-questions/ |

**Usage app** : module "Stratégies" par section IELTS, 5-10 min de lecture résumée par sous-thème.

### F.2 British Council Teach IELTS

- https://www.teachingenglish.org.uk/ielts (matériel ressources prof, exploitable côté élève)

### F.3 ETS TOEFL Prep

- **Test Prep Planner** : https://www.ets.org/toefl/test-takers/ibt/prepare/free.html

---

## Inventaire fichiers locaux

```
docs/resources/en/
├── cefrj_vocab.csv              (233 KB, 7798 mots) ✅
├── efllex_english.tsv           (7.2 MB, 15281 mots) ✅
├── ielts_speaking_band.pdf      (192 KB, ielts.org) ✅
├── ielts_speaking_cam.pdf       (29 KB, Cambridge) ✅
├── toefl_speaking_rubrics.pdf   (78 KB) ✅
└── toefl_writing_rubrics.pdf    (71 KB) ✅

ielts_writing_band_descriptors.pdf  (167 KB, racine projet) ✅
```

**À récupérer ultérieurement** (timeout ou paywall) :
- Cambridge handbooks A2/B1/B2/C1/C2 (URLs publiques, retry curl avec retry-max-time)
- CEFR-J Grammar Profile (formulaire de contact)
- BBC/VOA audio + transcripts (script `php artisan content:fetch-bbc-rss`)
