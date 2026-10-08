# Content studio — Remotion + ElevenLabs (contenu réseaux sociaux)

Pipeline de génération vidéo pour la série éducative "Réussirais-tu cette question ?"
(voir le plan de contenu pour le détail des séries par langue). Le projet vit dans
`/remotion` (indépendant de l'app Laravel, son propre `package.json`).

## Voix premium sélectionnées (compte ElevenLabs Prepla)

| Langue | Voix | ID | Registre |
|---|---|---|---|
| 🇫🇷 Français | Sophie – Publicité et Réseaux sociaux | `BewlJwjEWiFLWoXrbGMf` | pro, ton pub/réseaux sociaux |
| 🇬🇧 Anglais | Liam – Energetic, Social Media Creator | `TX3LPaxmHKxFdv7VOQHJ` | énergique, confiant |
| 🇩🇪 Allemand | Anna – Excited & Journalistic | `DEZHhPbmb8LVZmWufkCh` | énergique, ajoutée depuis la bibliothèque partagée ElevenLabs |

Ces IDs sont codés dans `remotion/scripts/generate-voiceover.mjs`.

## Statut du pilote (épisode `would-you-pass-ielts-001`)

- Composition Remotion (`WouldYouPassIELTS001`) : **faite et vérifiée visuellement**
  (badge exam, hook, carte question, options animées, reveal vert, CTA outro).
- Rendu bout en bout : **fait**, avec un audio placeholder silencieux
  (`public/audio/would-you-pass-ielts-001-placeholder.wav`) le temps de débloquer
  ElevenLabs.
- Voix off réelle : **bloquée** — le compte ElevenLabs a une facture impayée
  (`payment_issue` renvoyé par l'API). Une fois régularisé :
  1. `cd remotion`
  2. `npm run voiceover -- would-you-pass-ielts-001.json liam would-you-pass-ielts-001.mp3`
  3. Dans `src/Root.tsx`, repasser `audioSrc` de
     `audio/would-you-pass-ielts-001-placeholder.wav` à
     `audio/would-you-pass-ielts-001.mp3` (la durée de la vidéo s'ajuste
     automatiquement à la durée réelle de la voix via `calculateMetadata`).
  4. Supprimer le fichier placeholder.

## Commandes utiles

```bash
cd remotion
npm install                 # une seule fois
npm run start                # Remotion Studio (prévisualisation, hot-reload)
npm run voiceover -- <episode.json> <liam|sophie|anna> <sortie.mp3>
npm run render -- <CompositionId> out/<nom>.mp4
```

## Ajouter un nouvel épisode

1. Créer `src/data/<id>.json` sur le modèle de `would-you-pass-ielts-001.json`
   (`exam`, `flag`, `hook`, `question`, `options`, `correctIndex`, `cta`, `voice`,
   `narration`).
2. Générer la voix off avec `npm run voiceover`.
3. Ajouter une entrée dans le tableau `episodes` de `src/Root.tsx`.

## Sécurité

- La clé API ElevenLabs vit uniquement dans `remotion/.env` (ignoré par git). Ne
  jamais la committer ni la coller en clair ailleurs.

## Vidéo de présentation PrePla (`PreplaPromo`)

Vidéo de présentation en motion design, verticale 9:16 (1080×1920, 30 i/s, 60 s), pour
attirer les candidats aux examens de langue. Elle suit la vidéo n°1 du plan vidéo
(« Le stress devient un plan ») et se termine sur un appel à l'action unique :
« Fais ton diagnostic gratuit » + `prepla.mirlab.cloud`.

| Temps | Scène | Message |
|---|---|---|
| 0–4 s | Accroche | « Ton [IELTS / TCF / DELF / Goethe…] examen approche ? » sur un chrono qui se vide |
| 4–8 s | Problème | « Tu révises au hasard ? » — les questions fusent, puis tout est aspiré |
| 8–12 s | Logo | Le « P » facetté s'assemble — « Ton examen. Ton niveau. Ton parcours. » |
| 12–18 s | Étape 1 | Choix de la langue, de l'examen (TCF) et de l'objectif — 3 langues · 8 examens |
| 18–26 s | Étape 2 | « Test de placement » : sections A (grammaire), B (lecture), C (rédaction), « Analyse de ton niveau… » → « Parfait point de départ ! » |
| 26–34 s | Étape 3 | Le parcours se dessine ; carte « Ta prochaine mission » avec le renard ; une révision ciblée s'insère |
| 34–42 s | Correction IA | Faute corrigée et expliquée, puis « 30+ formats d'exercices » |
| 42–46 s | Mode examen | Vrais écrans de l'app dans un téléphone ; le chrono « MODE EXAMEN » en sort |
| 46–52 s | Progrès | Compétences, série de jours, objectif B2, erreurs à revoir |
| 52–60 s | Final | « Ne révise plus au hasard. Prépare-toi avec méthode. » + logo + carte mission « Créer mon parcours » + site |

La vidéo est illustrée avec les éléments réels de l'application (copiés dans
`public/promo/app/`) : icônes illustrées sur leurs tuiles (`ArtIcon`), renard-guide, animations
(flamme de série, étoile, trophée, chargement), écrans du mode examen (captures du kit Play Store)
et sons d'interface. Les autres écrans sont redessinés d'après le code et ne montrent que des
fonctions présentes : « Test de placement » en trois sections, carte « Ta prochaine mission »,
formats d'exercices de `resources/js/lib/exercise-schemas.ts`, correction IA, simulations, XP et
séries. La mention « plateforme indépendante, non affiliée aux organismes certificateurs » figure
à la fin.

### Fichiers

- `src/promo/` : composition (`PreplaPromo.tsx`), scènes (`scenes/S1Hook.tsx` … `S10Cta.tsx`),
  composants (logo vectoriel, typographie animée, cartes d'interface) et `theme.ts`
  (couleurs de la landing, courbes d'animation, minutage des scènes sur une grille à 120 BPM).
- `src/promo/content.ts` : textes des scènes partagées, par version (`FR`, `DE`).
- `public/promo/app/` : éléments de l'application (icônes, renard, GIF, deux écrans réels).
- `public/promo/music.mp3` : musique originale synthétisée par `scripts/compose-promo-music.py`
  (aucun échantillon externe, donc libre de droits). `public/promo/sfx/` : bruitages, dont les
  sons d'interface de l'application.
- `src/promo/fontMetrics.ts` : chasses des polices, générées par `scripts/build-font-metrics.py`
  (largeurs de texte calculées sans mesure dans le navigateur).

### Commandes

```bash
cd remotion
npm install
npm run start                          # prévisualiser dans Remotion Studio (composition PreplaPromo)
bash scripts/render-promo.sh           # MP4 final dans out/promo/ (reprend là où il s'est arrêté)
python3 scripts/compose-promo-music.py # régénérer musique et bruitages (numpy, scipy, ffmpeg)
```

Pour changer un texte, modifier `src/promo/content.ts` (scènes partagées) ou la scène
concernée ; si un mot de la machine à sous de l'accroche change, rien d'autre à faire (les
largeurs viennent de `fontMetrics.ts`). Si la police change, relancer
`scripts/build-font-metrics.py` (fonttools, brotli).

### Version allemande (`PreplaPromoDE`)

Même durée, même grille musicale et même montage, pour les francophones qui préparent le
Goethe-Zertifikat ou le TestDaF. Les explications restent en français, l'allemand apparaît dans
les exemples. Textes : `content.ts` (`DE`) ; scènes propres à cette version : `scenes/de/`.

| Temps | Scène | Message |
|---|---|---|
| 0–4 s | Accroche | « [der / die / das] Mädchen ? » : l'article hésite (code couleur des manuels : der bleu, die rouge, das vert), « Tu hésites encore ? », compte à rebours « Goethe B1 · J-30 » |
| 4–8 s | Problème | « Tu révises au hasard ? » — Akkusativ ? Dativ ? Wo steht das Verb ? Goethe ou TestDaF ? |
| 8–12 s | Logo | « Deine Prüfung. Dein Niveau. Dein Weg. » |
| 12–18 s | Étape 1 | Allemand → Goethe-Zertifikat → niveau B1 (A1 à C2) ; objectif « Goethe B1 dans 8 semaines » |
| 18–26 s | Étape 2 | Test de placement en allemand (article, lecture, rédaction) → niveau A2, objectif B1, point fort Lesen, à travailler Sprechen |
| 26–34 s | Étape 3 | Mission « Nebensätze (weil · dass · wenn) », révision ciblée « der, die, das », objectif « Goethe B1 · Modellprüfung » |
| 34–42 s | Correction IA | « …, weil ich will in Deutschland studieren. » : l'IA renvoie le verbe conjugué en fin de phrase et explique la règle ; puis Lesen · Hören · Schreiben · Sprechen |
| 42–46 s | Mode examen | Écran du mode examen recomposé avec le vrai sujet blanc Goethe B1 de l'app (Lesen Teil 1, blog de Lena) ; le chrono Lesen (65 min) sort du téléphone |
| 46–52 s | Progrès | Lesen, Hören, Schreiben, Sprechen ; objectif Goethe B1 ; « 6 mots à revoir : der, die, das » |
| 52–60 s | Final | « Prépare ton Goethe avec méthode. » + carte « Fais ton diagnostic d'allemand gratuit » ; le renard dit « Los geht's! » |

Sources : sujet blanc `database/data/content/mock_exams/goethe/b1_sim1.json`, niveaux et durées
`config/exams/goethe.php`, `config/exams/testdaf.php`. La mention finale précise que PrePla n'est
affiliée ni au Goethe-Institut ni au TestDaF-Institut. Musique : même partition un ton plus haut
(`public/promo/music-de.mp3`).

```bash
COMP=PreplaPromoDE OUT=out/promo-de NAME=prepla-allemand-9x16.mp4 bash scripts/render-promo.sh
python3 scripts/compose-promo-music.py --transpose 2 --out music-de.mp3 --no-sfx   # musique
```
