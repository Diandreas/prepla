# Kit Google Play — PrePla

Ce dossier regroupe les éléments prêts à importer dans la fiche Play Store française.

## Éléments prêts

- `text/app-name.txt` : nom de l'application (6/30 caractères).
- `text/short-description.txt` : description courte (73/80 caractères).
- `text/full-description.txt` : description complète, sous la limite de 4 000 caractères.
- `graphics/icon-512.png` : icône 512 × 512 px.
- `graphics/feature-graphic-1024x500.png` : image de présentation 1 024 × 500 px.
- `screenshots/phone/` : six captures téléphone 1 080 × 1 920 px.
- `review-notes-fr.md` : texte et parcours à fournir dans « Accès à l'application ».
- `data-safety-draft-fr.md` : brouillon de la déclaration Sécurité des données.
- `publication-checklist-fr.md` : étapes et blocages à lever avant production.
- `assetlinks.template.json` : modèle de liaison TWA, à compléter avec le certificat Play.
- `video-script-fr.md` : scénario facultatif pour une vidéo YouTube de 30 secondes.

## Ordre conseillé des captures

1. Accueil et promesse de l'application.
2. Choix de la langue et de l'examen.
3. Configuration d'une simulation.
4. Exercice chronométré.
5. Connexion et reprise du parcours.
6. Générateur d'exercices assisté par IA.

Les captures sont composées à partir d'écrans réels de PrePla, avec un habillage éditorial
localisé. Elles n'inventent aucune fonctionnalité.

## À ne pas importer tel quel

- Le dossier `source/` contient les sources de fabrication, pas des éléments de fiche.
- `assetlinks.template.json` contient une empreinte factice : il doit être complété puis
  publié sous `https://prepla.mirlab.cloud/.well-known/assetlinks.json`.
- Le brouillon « Sécurité des données » doit être confirmé selon l'hébergement réel, les
  durées de conservation et le comportement exact des prestataires en production.

Pour régénérer les visuels :

```powershell
powershell -ExecutionPolicy Bypass -File scripts/build-play-store-assets.ps1
```

Spécifications officielles des visuels :
[Google Play Console — ajouter des éléments d'aperçu](https://support.google.com/googleplay/android-developer/answer/9866151?hl=fr).
