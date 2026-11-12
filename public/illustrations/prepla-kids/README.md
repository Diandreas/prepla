# PrePla Kids : illustrations pour integration

Les 112 PNG du brief sont disponibles ici : 4 poses dans `renard/`, 12 vignettes dans `themes/`, 96 mots dans `mots/<theme>/`. Les noms respectent `generation-manifest.json`.

Base URL publique : `/illustrations/prepla-kids/`.
Exemple : `/illustrations/prepla-kids/mots/animaux/cat.png`.

Les 18 illustrations individuelles deja presentes ont ete conservees. Le reste a ete genere en planches puis decoupe, suivant la demande de l'utilisateur. Generation via l'outil imagegen integre ; decoupage avec Pillow. Les fichiers WebP preexistants n'ont pas ete modifies.

Dimensions natives : 18 PNG de 1254 x 1254 pixels et 94 PNG de 418 x 418 pixels. Les decoupes conviennent aux vignettes mobiles (environ 80 pixels CSS) ; elles ne sont pas des originaux 1024 x 1024. Pas d'agrandissement artificiel.

Les planches sources, prompts exacts et correspondances des cases sont dans `output/prepla-kids/` a la racine du projet (`*-plan.json`, `*-sheet.png`). Pour les transports, la source finale est `transports-transparent-sheet.png` : le fond residuel de la premiere planche a ete retire. Le decoupage de la chaise a ete ajuste pour exclure le bord du canape voisin.

Controle : presence des 112 noms, PNG RGBA avec transparence, sujets et ordre des cases verifies visuellement, quantites 1, 2, 3, 4, 5, 6, 7 et 10 verifiees. Apercu global : `output/prepla-kids/preview.jpg`.

Aucune integration des ecrans ni mise en ligne effectuee : l'utilisateur a demande de deposer les fichiers pour Claude. Si le code consomme uniquement les WebP, creer les WebP manquants depuis ces PNG avant integration.
