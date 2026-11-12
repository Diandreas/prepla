# PrePla Kids : les images à générer avec GPT

Tant qu'une image manque, l'application montre un émoji à la place : vous pouvez donc générer et m'envoyer les images **par lots**, dans l'ordre que vous voulez. Chaque image envoyée remplace son émoji dès que je la mets en ligne.

## Comment faire

1. Dans ChatGPT, ouvrez **une seule conversation** pour toutes les images, et collez d'abord le **style commun** ci-dessous. Gardez la même conversation du début à la fin : c'est ce qui garde toutes les images dans le même style.
2. Pour chaque image, écrivez : `Image suivante : <ce que l'image montre>` en reprenant la colonne de droite des tableaux.
3. Envoyez l'image du **renard qui salue** (`public/illustrations/prepla-guide/welcome.png`) au début de la conversation, en disant : « Garde exactement ce personnage pour les poses du renard. »
4. Téléchargez chaque image en **PNG à fond transparent**, et donnez-lui **exactement** le nom de la colonne « Fichier ».
5. Enregistrez-les dans **`E:\project\prepla\public\illustrations\prepla-kids\`**, avec les sous-dossiers `renard\`, `themes\` et `mots\<thème>\` (par exemple `motsnimaux\dog.png`).
6. Lancez `python scripts/optimiser-images-kids.py` depuis `E:\project\prepla` : il fabrique à côté de chaque PNG une version WebP légère (512 px, environ 40 Ko), la seule que l'application envoie aux téléphones. Les PNG restent chez vous et ne vont pas dans git.

**Vérifiez chaque image avant de l'envoyer.** Il ne doit y avoir **aucun texte, lettre ni chiffre écrit** dedans. Pour les nombres, comptez les objets. L'objet doit être seul, bien centré, et entier, sans bord coupé.

## Style commun (à coller une fois au début)

```
Tu vas créer une série d'images pour une application qui apprend l'anglais, le français et l'allemand aux enfants de 6 à 12 ans. Toutes les images doivent avoir exactement le même style :
- style 3D doux, formes arrondies et mates, ombrage léger, couleurs vives et gaies, aspect de jouet de qualité, comme la mascotte jointe (un renard orange avec une écharpe bleue) ;
- UN seul sujet par image, centré, entier, avec une large marge autour ;
- FOND TRANSPARENT, sans décor, sans sol, sans cadre ;
- AUCUN texte, aucune lettre, aucun chiffre écrit, aucun logo, aucun filigrane ;
- format carré 1024 × 1024 ;
- les personnes sont noires africaines, souriantes, habillées simplement, pour des enfants du Cameroun ;
- lisible même tout petit (sur un téléphone, à 80 pixels de large).
Réponds seulement par l'image, sans commentaire. J'enverrai chaque sujet un par un.
```

## 1. Le renard, 4 poses — dossier `renard/`

| Fichier | Ce que l'image montre |
|---|---|
| `bravo.png` | the same fox mascot jumping with joy, both arms raised, big smile, celebrating |
| `encore.png` | the same fox mascot giving a kind thumbs-up, encouraging smile |
| `ecoute.png` | the same fox mascot with a paw behind its ear, listening carefully |
| `carte.png` | the same fox mascot holding a small open treasure map and pointing forward |

## 2. Les 12 thèmes, pour la carte — dossier `themes/`

Pour chacun : *a small group of 2 or 3 objects from the topic, arranged as a round badge-like composition* (pas de cercle ni de cadre dessiné).

| Fichier | Thème |
|---|---|
| `themes/animaux.png` | Les animaux |
| `themes/couleurs.png` | Les couleurs |
| `themes/nombres.png` | Les nombres |
| `themes/famille.png` | La famille |
| `themes/fruits.png` | Les fruits |
| `themes/corps.png` | Le corps |
| `themes/ecole.png` | L'école |
| `themes/nourriture.png` | À table ! |
| `themes/vetements.png` | Les vêtements |
| `themes/meteo.png` | Le ciel et la météo |
| `themes/maison.png` | La maison |
| `themes/transports.png` | Les transports |

## 3. Les 96 mots — dossier `mots/<thème>/`

### Les animaux  —  dossier `mots/animaux/`

| Fichier | Mot | Ce que l'image montre (à coller dans le prompt) |
|---|---|---|
| `dog.png` | le chien | a dog |
| `cat.png` | le chat | a cat |
| `bird.png` | l'oiseau | a bird |
| `fish.png` | le poisson | a fish |
| `horse.png` | le cheval | a horse |
| `cow.png` | la vache | a cow |
| `lion.png` | le lion | a lion |
| `elephant.png` | l'éléphant | an elephant |

### Les couleurs  —  dossier `mots/couleurs/`

| Fichier | Mot | Ce que l'image montre (à coller dans le prompt) |
|---|---|---|
| `red.png` | rouge | a big glossy red paint splash blob |
| `blue.png` | bleu | a big glossy blue paint splash blob |
| `green.png` | vert | a big glossy green paint splash blob |
| `yellow.png` | jaune | a big glossy yellow paint splash blob |
| `orange.png` | orange | a big glossy orange paint splash blob |
| `purple.png` | violet | a big glossy purple paint splash blob |
| `black.png` | noir | a big glossy black paint splash blob |
| `white.png` | blanc | a big glossy white paint splash blob with a soft grey outline so it shows on white |

### Les nombres  —  dossier `mots/nombres/`

| Fichier | Mot | Ce que l'image montre (à coller dans le prompt) |
|---|---|---|
| `one.png` | un | one red toy ball (exactly 1) |
| `two.png` | deux | two red toy balls (exactly 2) |
| `three.png` | trois | three red toy balls (exactly 3) |
| `four.png` | quatre | four red toy balls (exactly 4) |
| `five.png` | cinq | five red toy balls (exactly 5) |
| `six.png` | six | six red toy balls (exactly 6) |
| `seven.png` | sept | seven red toy balls (exactly 7) |
| `ten.png` | dix | ten red toy balls in two rows of five (exactly 10) |

### La famille  —  dossier `mots/famille/`

| Fichier | Mot | Ce que l'image montre (à coller dans le prompt) |
|---|---|---|
| `mother.png` | la mère | a smiling young Black African mother, waist-up |
| `father.png` | le père | a smiling young Black African father, waist-up |
| `baby.png` | le bébé | a smiling Black African baby sitting |
| `sister.png` | la sœur | a smiling Black African girl about 8 years old, waist-up |
| `brother.png` | le frère | a smiling Black African boy about 8 years old, waist-up |
| `grandmother.png` | la grand-mère | a smiling Black African grandmother with grey hair, waist-up |
| `grandfather.png` | le grand-père | a smiling Black African grandfather with grey hair, waist-up |
| `family.png` | la famille | a happy Black African family of four: mother, father, girl and boy, together |

### Les fruits  —  dossier `mots/fruits/`

| Fichier | Mot | Ce que l'image montre (à coller dans le prompt) |
|---|---|---|
| `apple.png` | la pomme | an apple |
| `banana.png` | la banane | a banana |
| `orange.png` | l'orange | an orange |
| `pineapple.png` | l'ananas | a pineapple |
| `strawberry.png` | la fraise | a strawberry |
| `mango.png` | la mangue | a mango |
| `watermelon.png` | la pastèque | a watermelon |
| `grapes.png` | le raisin | some grapes |

### Le corps  —  dossier `mots/corps/`

| Fichier | Mot | Ce que l'image montre (à coller dans le prompt) |
|---|---|---|
| `eye.png` | l'œil | one friendly cartoon eye |
| `ear.png` | l'oreille | one cartoon human ear |
| `nose.png` | le nez | one cartoon human nose |
| `mouth.png` | la bouche | a smiling cartoon mouth with lips |
| `hand.png` | la main | one open cartoon hand, palm facing us, brown skin |
| `foot.png` | le pied | one cartoon bare foot, brown skin |
| `leg.png` | la jambe | one cartoon leg in shorts, brown skin |
| `tooth.png` | la dent | one shiny white cartoon tooth |

### L'école  —  dossier `mots/ecole/`

| Fichier | Mot | Ce que l'image montre (à coller dans le prompt) |
|---|---|---|
| `school.png` | l'école | a small colourful primary school building |
| `book.png` | le livre | a book |
| `pencil.png` | le crayon | a pencil |
| `school-bag.png` | le cartable | a colourful children's school backpack |
| `teacher.png` | la maîtresse | a smiling Black African female primary-school teacher holding a book |
| `scissors.png` | les ciseaux | some scissors |
| `ruler.png` | la règle | a ruler |
| `computer.png` | l'ordinateur | a computer |

### À table !  —  dossier `mots/nourriture/`

| Fichier | Mot | Ce que l'image montre (à coller dans le prompt) |
|---|---|---|
| `bread.png` | le pain | a bread |
| `milk.png` | le lait | a milk |
| `water.png` | l'eau | a clear glass of water |
| `rice.png` | le riz | a bowl of white rice |
| `egg.png` | l'œuf | an egg |
| `cheese.png` | le fromage | a cheese |
| `cake.png` | le gâteau | a slice of birthday cake |
| `chicken.png` | le poulet | a roasted chicken drumstick |

### Les vêtements  —  dossier `mots/vetements/`

| Fichier | Mot | Ce que l'image montre (à coller dans le prompt) |
|---|---|---|
| `t-shirt.png` | le t-shirt | a T-shirt |
| `trousers.png` | le pantalon | a pair of blue trousers |
| `dress.png` | la robe | a dress |
| `shoes.png` | les chaussures | a pair of sneakers |
| `hat.png` | le chapeau | a hat |
| `socks.png` | les chaussettes | a pair of striped socks |
| `coat.png` | le manteau | a coat |
| `glasses.png` | les lunettes | a pair of round glasses |

### Le ciel et la météo  —  dossier `mots/meteo/`

| Fichier | Mot | Ce que l'image montre (à coller dans le prompt) |
|---|---|---|
| `sun.png` | le soleil | a sun |
| `rain.png` | la pluie | a cloud with raindrops falling |
| `cloud.png` | le nuage | a cloud |
| `wind.png` | le vent | a swirl of wind with a few leaves blowing |
| `snow.png` | la neige | a snowflake with a little pile of snow |
| `rainbow.png` | l'arc-en-ciel | a rainbow |
| `moon.png` | la lune | a crescent moon |
| `star.png` | l'étoile | one golden star shape |

### La maison  —  dossier `mots/maison/`

| Fichier | Mot | Ce que l'image montre (à coller dans le prompt) |
|---|---|---|
| `house.png` | la maison | a house |
| `door.png` | la porte | a door |
| `window.png` | la fenêtre | a window |
| `bed.png` | le lit | a bed |
| `chair.png` | la chaise | a chair |
| `sofa.png` | le canapé | a sofa |
| `key.png` | la clé | a key |
| `bath.png` | la baignoire | a white bathtub with bubbles |

### Les transports  —  dossier `mots/transports/`

| Fichier | Mot | Ce que l'image montre (à coller dans le prompt) |
|---|---|---|
| `car.png` | la voiture | a car |
| `bus.png` | le bus | a bus |
| `plane.png` | l'avion | a plane |
| `bike.png` | le vélo | a bike |
| `boat.png` | le bateau | a boat |
| `train.png` | le train | a train |
| `taxi.png` | le taxi | a taxi |
| `motorbike.png` | la moto | a motorbike |

---

**Total : 4 poses, 12 thèmes et 96 mots, soit 112 images.** Si vous voulez commencer petit, faites d'abord les 4 poses du renard, puis les 3 thèmes gratuits : animaux, couleurs et nombres (28 images). Ce sont ceux que tous les enfants voient en premier.
