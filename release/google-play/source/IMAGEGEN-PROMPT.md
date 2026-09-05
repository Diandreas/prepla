# Source ImageGen de l'image de présentation

Mode utilisé : génération intégrée `imagegen`, avec l'icône PrePla existante comme
référence visuelle. Le texte et le logo visibles dans le fichier final ont ensuite été
ajoutés de façon déterministe par `scripts/build-play-store-assets.ps1`.

Prompt exact :

> Create a premium, wide Google Play feature-graphic background for PrepLa, an AI-powered language exam preparation mobile app. Match the attached PrepLa icon’s visual identity: deep midnight navy, luminous cobalt and azure blues, small warm amber accents, crisp faceted geometric forms, sophisticated and modern. Composition: 2:1 panoramic banner, keep the entire left 48% calm and dark with very low detail for later French headline and logo overlay; on the right, create an elegant abstract learning scene made from floating language cards, a subtle audio waveform, progress arcs, and a confident upward path, all integrated as polished translucent geometric shapes. No people, no flags, no device mockups, no letters, no words, no numbers, no logos, no UI screenshots, no watermarks. High-end product marketing art, excellent contrast, clean edges, generous negative space, subtle depth, professional educational technology aesthetic.

Fichiers :

- `feature-graphic-ai-source.png` : sortie bitmap originale d'ImageGen.
- `../graphics/feature-graphic-1024x500.png` : fichier final à importer dans Play Console.
