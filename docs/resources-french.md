# Ressources Français (DELF/DALF, TCF, TEF)

> Toutes les ressources listées ici sont **gratuites** ou utilisables sous une licence permettant un usage éducatif/commercial avec attribution.
> Fichiers téléchargés localement dans [`docs/resources/fr/`](resources/fr/).

---

## A. Vocabulaire CEFR ✅ (2 jeux de données, ~32 000 entrées)

### A.1 FLELex — version TaggerTraining (TT) — **téléchargé**

- **Fichier** : [`docs/resources/fr/flelex_tt.csv`](resources/fr/flelex_tt.csv) — 774 KB, **14 236 entrées**
- **Format** : TSV — `word, tag, freq_A1, freq_A2, freq_B1, freq_B2, freq_C1, freq_C2, freq_total`
- **Origine** : corpus de 777 000 mots issus de manuels FFL réels
- **Source** : https://cental.uclouvain.be/cefrlex/flelex/
- **Licence** : Creative Commons (attribution UCLouvain CENTAL)
- **Exemple** :
  ```
  abaisser   VER   0.83   0.00   0.43   0.00   0.00   12.83   1.25
  ```
  → "abaisser" apparaît surtout en C2 (12.83), rare partout ailleurs.

### A.2 FLELex — version CRF (Conditional Random Fields tagger) — **téléchargé**

- **Fichier** : [`docs/resources/fr/flelex_crf.csv`](resources/fr/flelex_crf.csv) — 956 KB, **17 871 entrées**
- **Différence** : tagger POS différent → quelques mots/expressions multi-tokens supplémentaires.
- **Recommandation** : utiliser **FLELex TT** comme source primaire ; CRF en backup pour disambiguation.

### A.3 Stratégie d'usage

- Ingérer FLELex TT en table `cefr_lexicon_fr (word, pos, level_first_seen, freq_per_level, freq_total)`.
- **Mapping CEFR** : le niveau d'un mot = premier niveau avec `freq >= seuil` (ex: seuil 5.0 par million).
- Pour la **génération d'exercices**, filtrer le pool de mots à `level ≤ niveau_utilisateur + 1`.
- Pour la **lecture** : surligner en couleur les mots > niveau utilisateur, proposer "+ Lexique".

---

## B. Grammaire CEFR

### B.1 Référentiels DELF/DALF par niveau

- **Source** : France Éducation Internationale — chaque niveau (A1, A2, B1, B2, C1, C2) a un référentiel grammatical officiel.
- **Hub** : https://www.france-education-international.fr/diplome/ressources-dilf-delf-dalf-tcf
- **À récupérer** : grilles compétences grammaticales par niveau (PDF disséminés dans le site, scrape ciblé requis).

### B.2 CECRL Volume Complémentaire 2018 (référence officielle)

- **URL** : https://rm.coe.int/common-european-framework-of-reference-for-languages-learning-teaching/16809ea0d4
- **Licence** : Council of Europe — usage éducatif gratuit
- **Contenu** : descripteurs can-do par niveau et compétence (compréhension orale, expression orale, médiation...) en français + anglais.

---

## C. Audio + Transcripts (Compréhension orale authentique)

### C.1 RFI Savoirs / Journal en français facile

- **URL hub** : https://savoirs.rfi.fr
- **Statut** : ⚠️ Le site refuse le scraping direct (`403` via curl, fetch bloqué). Les podcasts sont par contre **disponibles via les apps de podcast standard** et leur flux RSS public.
- **Programmes clés** :
  - **Journal en français facile** : actualité quotidienne, débit lent, B1-B2, audio + transcript complet
  - **Le mot du jour** : 2 min, A2-B1, étymologie+contexte
  - **Édito carré (économie)** : C1-C2
- **Flux RSS** : https://www.rfi.fr/fr/podcasts/journal-en-fran%C3%A7ais-facile/feed.xml *(à confirmer côté serveur)*
- **Licence** : usage éducatif autorisé, attribution obligatoire (RFI = service public français).

### C.2 TV5 Monde Apprendre

- **URL** : https://apprendre.tv5monde.com
- **Niveaux** : A1-C1
- **Format** : vidéos courtes (1-5 min) + transcripts + activités interactives
- **Licence** : pédagogique gratuit, attribution.

### C.3 7 jours sur la planète (TV5)

- **URL** : https://enseigner.tv5monde.com/fiches-pedagogiques-fle/collection/7-jours-sur-la-planete
- **Niveaux** : B1-C1
- **Contenu** : actualité hebdo avec fiches pédagogiques complètes.

### C.4 Audio Lingua

- **URL** : https://audio-lingua.eu/?lang=fr
- **Format** : enregistrements de natifs taggés par niveau A1-C2, theme, durée
- **Licence** : Creative Commons BY-NC-SA
- **À ingérer** : oui, format idéal pour exercices listening génériques.

---

## D. Rubriques officielles DELF/DALF ✅

### D.1 DELF A1 — Production Orale — **téléchargé**

- **Fichier** : [`docs/resources/fr/delf_a1_po.pdf`](resources/fr/delf_a1_po.pdf) — 112 KB
- **Source** : https://www.france-education-international.fr/document/grille-po-a1
- **Critères** : nouvelle grille 2022 (5-6 critères par niveau, échelle qualitative).

### D.2 DELF B2 — Production Orale — **téléchargé**

- **Fichier** : [`docs/resources/fr/delf_b2_po.pdf`](resources/fr/delf_b2_po.pdf) — 110 KB
- **Source** : https://www.france-education-international.fr/document/grille-po-b2

### D.3 À récupérer pour les autres niveaux

URLs probables (à confirmer) :
- DELF A2 PO : `france-education-international.fr/document/grille-po-a2`
- DELF B1 PO : `france-education-international.fr/document/grille-po-b1`
- DALF C1 PO : `france-education-international.fr/document/grille-po-c1`
- DALF C2 PO : `france-education-international.fr/document/grille-po-c2`
- Idem pour PE (Production Écrite) : remplacer `po` par `pe` dans l'URL

### D.4 Nouveau système (2022)

Les nouvelles grilles **n'utilisent plus le 0.5 point** mais des **descripteurs qualitatifs** (niveau atteint / partiellement atteint / non atteint). Implication app : la notation auto doit s'aligner sur ces 3-4 paliers par critère, pas un score numérique fin.

**Ref article officielle** : https://www.france-education-international.fr/actualites/lettre-fei/2022-10/delf-dalf-de-nouvelles-grilles-pour-une-evaluation-au-plus-pres-des-competences-des-candidats?langue=en

---

## E. Sample papers / Practice tests

### E.1 TCF (Test de Connaissance du Français)

- **Exemples officiels CIEP/FEI** : https://www.france-education-international.fr/test/exemples-epreuves-tcf?langue=en
- **Composantes** : Compréhension orale, écrite, structures, expression orale, écrite.
- **Score** : 100-699 → mapping CEFR (200=A1, 300=A2, 400=B1, 500=B2, 600=C1, 700=C2 max théorique).

### E.2 TEF (Test d'Évaluation de Français)

- **Hub** : https://www.cci-paris-idf.fr/fr/education/formation-continue-valorisation-competences/certification-francais-tef
- **Sample tutorials** : https://www.lefrancaisdesaffaires.fr (oral comprehension + expression)
- **App officielle** : "Français 3.0" (gratuite, exos d'entraînement)

### E.3 DELF/DALF sample subjects

- **DELF junior A2** : https://www.france-education-international.fr/diplome/delf-junior-scolaire/exemples-sujets-a2
- **DELF adulte** : pages similaires par niveau sur `france-education-international.fr/diplome/delf-{niveau}/exemples-sujets`
- **À scraper systématiquement** pour A1, A2, B1, B2 + DALF C1, C2.

---

## F. Stratégies d'examen

### F.1 Bonjour de France

- **URL** : https://www.bonjourdefrance.com
- **Contenu** : cours par niveau, fiches grammaire, exercices, prep DELF gratuit.

### F.2 Le Point du FLE

- **URL** : https://www.lepointdufle.net
- **Contenu** : portail d'exercices FLE classés par niveau et thème (lien externe vers ressources d'enseignants).

### F.3 Français Authentique

- **URL** : https://www.francaisauthentique.com
- **Contenu** : podcasts gratuits francophones natifs, B1-B2, sur la culture et la langue informelle.

### F.4 CIEP DELF Prim / Junior / Adultes

- **Hub** : https://www.france-education-international.fr/diplome/ressources-dilf-delf-dalf-tcf
- **Contenu** : guides examinateurs, exemples corrigés, fiches méthodologiques officielles.

---

## Inventaire fichiers locaux

```
docs/resources/fr/
├── flelex_tt.csv         (774 KB, 14236 entrées CEFR-tagged) ✅
├── flelex_crf.csv        (956 KB, 17871 entrées CEFR-tagged) ✅
├── delf_a1_po.pdf        (112 KB, grille officielle FEI) ✅
├── delf_b2_po.pdf        (110 KB, grille officielle FEI) ✅
├── flelex_info.html      (page download FLELex avec URLs) ✅
└── rfi_apprendre.html    (403 — site refuse curl) ❌
```

**À récupérer ultérieurement** :
- Grilles DELF/DALF restantes (A2, B1, C1, C2 × PO + PE) via scrape FEI
- Référentiels grammaticaux DELF par niveau
- Sample papers TCF/TEF complets
- Audio TV5/RFI via leur RSS public (script `php artisan content:fetch-rfi-rss`)
- Audio Lingua (BY-NC-SA, idéal pour ingestion massive)
