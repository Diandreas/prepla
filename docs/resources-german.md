# Ressources Deutsch (Goethe-Zertifikat, TestDaF)

> Toutes les ressources listées ici sont **gratuites** ou utilisables sous une licence permettant un usage éducatif/commercial avec attribution.
> Fichiers téléchargés localement dans [`docs/resources/de/`](resources/de/).

---

## A. Vocabulaire CEFR

### A.1 Goethe-Zertifikat Wortliste A1 — **téléchargé**

- **Fichier** : [`docs/resources/de/goethe_a1_wortliste.pdf`](resources/de/goethe_a1_wortliste.pdf) — 712 KB
- **Contenu** : ~650 mots (dont ~325 actifs attendus du candidat A1)
- **Source officielle Goethe-Institut** : https://www.goethe.de/pro/relaunch/prf/de/A1_SD1_Wortliste_02.pdf
- **À faire** : extraction texte du PDF → CSV pour ingestion en `cefr_lexicon_de`.

### A.2 Wortlisten A1 / A2 / B1 — autres URLs Goethe

| Niveau | URL |
|---|---|
| A1 Fit in Deutsch 1 | https://www.goethe.de/pro/relaunch/prf/de/Goethe-Zertifikat_A1_Fit1_Wortliste.pdf |
| A2 | (existe, URL à confirmer : pattern `Goethe-Zertifikat_A2_Wortliste.pdf`) |
| B1 | (officielle Goethe, dispo via FU Berlin link ci-dessous) |

### A.3 FU Berlin — CSV consolidé A1 / A2 / B1

- **Hub** : https://www.sprachenzentrum.fu-berlin.de/slz/sprachen-links/deutsch/wortschatz/index.html
- **CSV** : `dictionary_a1a2b1_onlystems.csv` (colonnes : id, level, word stem)
- **À récupérer** : oui — fait gagner du temps vs extraction depuis 3 PDFs séparés.

### A.4 sprach-o-mat (GitHub)

- **Repo** : https://github.com/technologiestiftung/sprach-o-mat
- **Outil de diagnostic complexité de textes allemands** (open source)
- **Contient** : listes de mots taggés A1-C2 + scripts d'analyse
- **Usage app** : peut servir à **classer la difficulté d'un texte allemand** automatiquement (pour la lecture authentique).

### A.5 ⚠️ Pas d'équivalent FLELex pour l'allemand

L'équipe UCLouvain CENTAL n'a publié que EFLLex (EN) et FLELex (FR) — pas de "DELex". Confirmé par 404 sur `cefrlex/sgnllex/`.

**Conséquence** : la base lexicale allemande sera moins riche que EN/FR. Compensation : Goethe Wortlisten officielles A1+A2+B1 + sprach-o-mat couvrent l'essentiel.

---

## B. Grammaire CEFR

### B.1 Profile Deutsch (référence officielle)

- **Description** : équivalent allemand de "English Profile" (Cambridge). Liste de structures grammaticales par niveau.
- **Statut** : **payant** (ouvrage Langenscheidt + CD-ROM). Pas de version gratuite officielle.
- **Alternative gratuite** : extraire les structures depuis les **Modellsätze Goethe A1/A2/B1/B2/C1/C2** (déjà sur disque) — chaque niveau d'examen testant des structures spécifiques.

### B.2 Grammatik in Stichworten (DW)

- **URL** : https://learngerman.dw.com/en/grammar
- **Contenu** : 100+ thèmes grammaticaux taggés par niveau (A1-C1), exemples, exercices
- **Licence** : usage éducatif avec attribution.

---

## C. Audio + Transcripts (Hörverstehen authentique)

> Deutsche Welle est **la** ressource gratuite de référence pour l'allemand. WebFetch bloqué mais `curl` fonctionne, et leurs RSS sont publics.

### C.1 Deutsche Welle — Apprendre l'allemand

Courses identifiés depuis la page hub (téléchargée localement [`docs/resources/de/dw_learn_german.html`](resources/de/dw_learn_german.html)) :

| Programme | Niveau | Format | URL |
|---|---|---|---|
| **Nicos Weg** | A1 → B1 | Vidéos + transcripts + exercices | https://learngerman.dw.com/en/nicos-weg/c-36519687 |
| **Deutschtrainer** | A1 | 100 leçons courtes | https://learngerman.dw.com/en/deutschtrainer |
| **Beginners** (cours débutant) | A1 | Cours structuré | https://learngerman.dw.com/en/beginners |
| **Deine Deutschprüfung** | A2-C1 | Prep examens | https://learngerman.dw.com/en/deine-deutschprüfung |
| **Langsam gesprochene Nachrichten** | A2-B1 | Audio + transcript quotidien | https://learngerman.dw.com/en/langsam-gesprochene-nachrichten |
| **Top-Thema mit Vokabeln** | B1 | Article audio + vocabulaire | https://learngerman.dw.com/en/top-thema |
| **Sprachbar** | B2-C1 | Podcast linguistique | https://learngerman.dw.com/en/sprachbar |
| **Jojo sucht das Glück** | B1 | Série télé (3 saisons) | https://learngerman.dw.com/en/jojo |
| **Wieso nicht?** / **Marktplatz** | B2 | Allemand des affaires | https://learngerman.dw.com/en/wieso-nicht |

**Licence** : utilisation autorisée à des fins éducatives avec citation "© Deutsche Welle".

### C.2 Stratégie d'ingestion

`php artisan content:fetch-dw` qui :
1. Pour chaque programme, scrape la liste des épisodes (ou utilise le RSS quand dispo).
2. Pour chaque épisode : télécharge audio MP3 + extrait le transcript HTML.
3. Stocke en `content_items (source, level, type, audio_url, transcript, ...)`.
4. Re-tague le niveau selon Flesch-Kincaid (texte transcript) pour cohérence interne.

### C.3 Slow German (alternative)

- **URL** : https://slowgerman.com
- **Format** : podcast audio + transcript, débit lent, A2-B2
- **Licence** : usage éducatif libre.

---

## D. Rubriques officielles Goethe & TestDaF ✅

### D.1 Goethe-Zertifikat A1 Modellsatz — **téléchargé**

- **Fichier** : [`docs/resources/de/goethe_a1_modellsatz.pdf`](resources/de/goethe_a1_modellsatz.pdf) — 1.1 MB
- **Source** : https://www.goethe.de/pro/relaunch/prf/materialien/A1_sd1/sd_1_modellsatz.pdf
- **Contenu** : examen blanc complet A1 (Start Deutsch 1) — Hören, Lesen, Schreiben, Sprechen + grilles de notation.

### D.2 Goethe-Zertifikat B1 Modellsatz — **téléchargé**

- **Fichier** : [`docs/resources/de/goethe_b1_modellsatz.pdf`](resources/de/goethe_b1_modellsatz.pdf) — 906 KB
- **Source** : https://www.goethe.de/pro/relaunch/prf/materialien/B1/b1_modellsatz_erwachsene.pdf

### D.3 Goethe-Zertifikat C1 Modellsatz — **téléchargé**

- **Fichier** : [`docs/resources/de/goethe_c1_modellsatz.pdf`](resources/de/goethe_c1_modellsatz.pdf) — 1.1 MB
- **Source** : https://www.goethe.de/pro/relaunch/prf/materialien/C1/c1_modellsatz.pdf

### D.4 Goethe A2 et C2 — à récupérer

URLs confirmées dans les résultats de recherche, à télécharger ultérieurement :
- A2 Erwachsene : recherche `goethe.de pro/relaunch/prf/materialien/A2_erwachsene`
- A2 Fit Jugendliche : https://www.goethe.de/pro/relaunch/prf/materialien/A2_fit/A2_Uebungssatz_Jugendliche.pdf
- B2 : `pattern goethe.de pro/relaunch/prf/materialien/B2/b2_modellsatz.pdf`
- C2 : `pattern goethe.de pro/relaunch/prf/materialien/C2/c2_modellsatz.pdf` (mise à jour mars 2026)

### D.5 TestDaF Modelltests — **téléchargés (partiels)**

- **Modelltest 01 Leseverstehen** : [`docs/resources/de/testdaf_modelltest1_lesen.pdf`](resources/de/testdaf_modelltest1_lesen.pdf) — 830 KB
- **Modelltest 01 Schriftlicher Ausdruck** : [`docs/resources/de/testdaf_modelltest1_schreiben.pdf`](resources/de/testdaf_modelltest1_schreiben.pdf) — 637 KB

**Reste à récupérer pour Modelltest 01** :
- Hörverstehen (audio + livret)
- Mündlicher Ausdruck (livret + audio)
- Solutions (Lösungen) Lesen et autres

URL pattern : `https://www.testdaf.de/fileadmin/testdaf/downloads/Modelltests_papierbasierter_TestDaF/Modelltest_1/{Section}/Modelltest_01_{Suffix}_Heft.pdf`

**Modelltest 02** : pattern identique avec `Modelltest_2`. À ajouter à l'ingestion.

### D.6 Notation TestDaF

- **Échelle** : TDN 3, TDN 4, TDN 5 par compétence (équiv. ≈ B2.1, B2.2, C1).
- **TDN 5** dans toutes les sections = admissible pour universités allemandes.

### D.7 Goethe — Bewertung (notation)

- Échelles spécifiques par niveau, intégrées dans chaque Modellsatz.
- Pour A1-A2 : "Bestanden" / "Nicht bestanden" + percentile.
- Pour B1+ : 4 sections × 100 points = 400 total, ≥ 60% par section requis.

---

## E. Sample papers / Practice tests

### E.1 Goethe-Institut — Übungsmaterialien officiels

- **Hub** : https://www.goethe.de/de/spr/kup/prf.html
- **Modellsätze** par niveau (déjà téléchargés A1/B1/C1, à compléter A2/B2/C2).
- **Übungssätze** (banque d'exercices supplémentaires) par niveau.

### E.2 TestDaF — Modelltests

- **Hub** : https://www.testdaf.de
- **Modelltests 01 + 02** complets disponibles gratuitement (à scrape systématique).

### E.3 telc — Modelltests

- **URL** : https://www.telc.net/pruefungsteilnehmende/modellpruefungen.html
- **Contenu** : examens telc (alternative reconnue, A1-C2), modèles téléchargeables gratuits.

### E.4 ÖSD (Österreichisches Sprachdiplom)

- **URL** : https://www.osd.at
- **Contenu** : équivalent autrichien du Goethe, Modellprüfungen gratuites.
- **Pertinence** : reconnu en Autriche/Allemagne pour visas/études, niveau identique au Goethe.

---

## F. Stratégies d'examen

### F.1 Goethe-Institut — Prüfungstraining

- **Hub** : https://www.goethe.de/de/spr/kup/prf.html
- **Format** : tips officiels par section et par niveau.

### F.2 Deutsche Welle — Deine Deutschprüfung

- **URL** : https://learngerman.dw.com/en/deine-deutschprüfung
- **Contenu** : préparation Goethe et autres examens, exercices interactifs.

### F.3 DW Test (placement test)

- **URL** : https://learngerman.dw.com/en/placementDashboard
- **Usage** : test de positionnement A1-C2 avec correction immédiate.

### F.4 Klett Sprachen — TestDaF Übungstest

- **URL** : https://www.klett-sprachen.de
- **Statut** : payant mais quelques extraits gratuits + audio. Recommandé pour comparer notre format à un test commercial standard.

---

## Inventaire fichiers locaux

```
docs/resources/de/
├── goethe_a1_modellsatz.pdf        (1.1 MB, examen blanc complet A1) ✅
├── goethe_b1_modellsatz.pdf        (906 KB, examen blanc complet B1) ✅
├── goethe_c1_modellsatz.pdf        (1.1 MB, examen blanc complet C1) ✅
├── goethe_a1_wortliste.pdf         (712 KB, ~650 mots A1) ✅
├── testdaf_modelltest1_lesen.pdf   (830 KB, Leseverstehen) ✅
├── testdaf_modelltest1_schreiben.pdf (637 KB, Schriftlicher Ausdruck) ✅
├── dw_learn_german.html            (112 KB, hub courses identifiés) ✅
├── dw_nicos_weg.html               (89 KB, page Nicos Weg) ✅
├── efllex_info.html                (page EFLLex pour reference) ✅
└── sgnllex_info.html               (404 — pas d'équivalent allemand UCLouvain) ❌
```

**À récupérer ultérieurement** :
- Goethe Modellsätze A2 + B2 + C2 (URLs pattern connues)
- Goethe Wortlisten A2 + B1 (Wordlist Berlin CSV consolidée)
- TestDaF Modelltest 01 Hören + Sprechen + Lösungen
- TestDaF Modelltest 02 complet
- Audio DW (Nicos Weg, Langsam gesprochene Nachrichten, Top-Thema) via script `php artisan content:fetch-dw`
- Slow German podcasts via RSS

**Bloqueur identifié** : pas de FLELex équivalent allemand → on devra extraire le vocabulaire CEFR depuis les Wortlisten Goethe officielles + corpus DW. Effort : 1-2 jours d'ingestion + nettoyage.
