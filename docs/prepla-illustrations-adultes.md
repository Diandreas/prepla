# PrePla (adultes et centres) : les images à générer avec ChatGPT

Même méthode que pour PrePla Kids. Tant qu'une image manque, l'application garde son affichage actuel : vous pouvez avancer par lots.

## Comment faire

1. Une seule conversation ChatGPT pour toutes les images. Envoyez d'abord l'image du renard `public/illustrations/prepla-guide/welcome.png` en disant : « Garde exactement ce personnage. »
2. Collez le **style commun** ci-dessous, puis demandez les images une par une : `Image suivante : <sujet>`.
3. PNG à **fond transparent**, carré 1024 × 1024, enregistré sous le **nom exact** indiqué, dans `E:\project\prepla\public\illustrations\<dossier>\`.
4. Dites-moi « images prêtes » : je les convertis en WebP légers et je les mets en ligne.

## Style commun (à coller une fois)

```
Tu vas créer une série d'illustrations pour PrePla, une application qui prépare des adultes (étudiants, professionnels) aux examens de langue (TCF, DELF, IELTS, Goethe...) au Cameroun. Toutes les images doivent avoir exactement le même style que la mascotte jointe, un renard orange avec une écharpe bleue et un livre bleu marine :
- 3D doux, formes arrondies et mates, ombrage léger, aspect premium, amical mais pas enfantin ;
- couleurs : bleu nuit #1A2B48, bleu ciel #4A90E2, ambre #F5A623, crème ;
- UN sujet centré, entier, large marge, FOND TRANSPARENT, sans décor ni sol ni cadre ;
- AUCUN texte, aucune lettre, aucun chiffre, aucun logo, aucun drapeau dessiné avec du texte ;
- les personnes éventuelles sont noires africaines, adultes, habillées simplement ;
- lisible même petit (120 px). Format carré 1024 × 1024.
Réponds seulement par l'image. J'enverrai chaque sujet un par un.
```

## 1. Le renard qui réagit (le plus important) — dossier `prepla-guide/`

| Fichier | Sujet |
|---|---|
| `bravo.png` | the same fox jumping with joy, both arms raised, amber confetti around |
| `encourage.png` | the same fox with a warm smile, one fist raised, "let's try again" attitude |
| `reflechit.png` | the same fox thinking, paw under its chin, a small glowing light bulb above its head |
| `ecoute.png` | the same fox leaning in with a paw behind its ear, soft sound waves |
| `lecon-finie.png` | the same fox closing its blue book proudly, a small golden star above |
| `serie.png` | the same fox holding a bright warm flame in its paws |
| `serie-perdue.png` | the same fox gently lighting a match to rekindle a small extinguished flame |
| `merci.png` | the same fox holding a gift card with a big smile, amber confetti |

## 2. Pages d'erreur — dossier `etats/`

| Fichier | Sujet |
|---|---|
| `404-perdu.png` | the same fox looking puzzled, holding a map upside down, a question mark bubble without text |
| `500-repare.png` | the same fox wearing a yellow hard hat, holding a wrench next to an unplugged cable |

## 3. Une scène par compétence — dossier `competences/`

| Fichier | Sujet |
|---|---|
| `ecoute.png` | the same fox wearing blue headphones, sound waves around |
| `lecture.png` | the same fox reading an open newspaper |
| `ecrit.png` | the same fox writing on a sheet of paper with a big feather pen |
| `oral.png` | the same fox speaking into a vintage microphone, an empty speech bubble |

## 4. États vides — dossier `etats/`

| Fichier | Sujet |
|---|---|
| `premiere-lecon.png` | the same fox opening a glowing door with a star on it |
| `carnet-vide.png` | an open empty notebook with the same fox holding a pencil |
| `zero-erreur.png` | the same fox proudly holding a shield with a check mark |
| `classement-vide.png` | the same fox alone on the starting line of a running track |

## 5. Parcours de l'apprenant — dossier `onboarding/`

| Fichier | Sujet |
|---|---|
| `bonjour.png` | the same fox waving hello, three empty speech bubbles in blue, amber and white |
| `objectif.png` | the same fox aiming a dart at a target |
| `niveau-revele.png` | the same fox unveiling a big blank medal badge from under a cloth |

## 6. Abonnement — dossier `abonnement/`

| Fichier | Sujet |
|---|---|
| `premium.png` | the same fox wearing a small amber cape, standing on a pile of books with a rolled diploma |

## 7. Espace centre — dossier `centre/`

| Fichier | Sujet |
|---|---|
| `premiere-classe.png` | the same fox as a teacher in front of a green chalkboard with empty desks |
| `inviter-eleves.png` | the same fox holding out an invitation card with a QR-like square pattern (no text) |
| `parcours-vide.png` | a dotted path with small empty flags along the way, the same fox at the start |
| `tout-va-bien.png` | the same fox giving a thumbs-up next to a rising chart arrow |
| `examen-blanc.png` | the same fox holding a stopwatch next to a stack of exam papers |

## 8. Badges — dossier `badges/`

Médailles 3D rondes avec le renard gravé dessus, chacune avec un ruban d'une couleur différente.

| Fichier | Sujet |
|---|---|
| `premier-pas.png` | round 3D medal, fox engraved, sky blue ribbon |
| `serie-7j.png` | round 3D medal, fox engraved with a small flame, amber ribbon |
| `serie-30j.png` | round 3D gold medal, fox engraved with a big flame, red ribbon |
| `examen-blanc.png` | round 3D medal, fox engraved with a stopwatch, navy ribbon |
| `niveau-gagne.png` | round 3D gold medal, fox engraved with an upward arrow, green ribbon |

**Total : 33 images.** Si vous commencez petit : les 8 poses du renard, puis les 2 pages d'erreur et les 4 compétences.
