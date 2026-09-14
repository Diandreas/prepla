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
