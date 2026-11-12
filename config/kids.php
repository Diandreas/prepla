<?php

/*
 * PrePla Kids : le contenu des themes, pour les enfants de 6 a 12 ans.
 *
 * Chaque mot a une image (emoji, lisible par tous et sans droits), et sa forme
 * dans les trois langues : en anglais le mot seul, en francais et en allemand
 * avec son article, parce qu'un enfant apprend « der Hund » et non « Hund ».
 *
 * L'ordre des themes est l'ordre de la carte : un theme se debloque quand le
 * precedent a recu assez d'etoiles. Les GRATUITS premiers sont ouverts a tous,
 * la suite demande l'acces PrePla Plus du parent.
 */

return [
    'gratuits' => 3,

    // Etoiles qu'il faut sur un theme (sur 12) pour ouvrir le suivant.
    'etoiles_pour_debloquer' => 4,

    'langues' => [
        'english' => ['nom' => 'Anglais', 'nom_en' => 'English', 'drapeau' => '🇬🇧', 'tts' => 'en'],
        'french' => ['nom' => 'Français', 'nom_en' => 'French', 'drapeau' => '🇫🇷', 'tts' => 'fr'],
        'german' => ['nom' => 'Allemand', 'nom_en' => 'German', 'drapeau' => '🇩🇪', 'tts' => 'de'],
    ],

    'avatars' => ['🦊', '🐼', '🐯', '🐸', '🐵', '🦁', '🐰', '🐨', '🦄', '🐧', '🐶', '🐱'],

    'themes' => [
        'animaux' => [
            'fr' => 'Les animaux', 'en' => 'Animals', 'icone' => '🐶', 'couleur' => '#F59E0B',
            'mots' => [
                ['🐶', 'dog', 'le chien', 'der Hund'],
                ['🐱', 'cat', 'le chat', 'die Katze'],
                ['🐦', 'bird', "l'oiseau", 'der Vogel'],
                ['🐟', 'fish', 'le poisson', 'der Fisch'],
                ['🐴', 'horse', 'le cheval', 'das Pferd'],
                ['🐮', 'cow', 'la vache', 'die Kuh'],
                ['🦁', 'lion', 'le lion', 'der Löwe'],
                ['🐘', 'elephant', "l'éléphant", 'der Elefant'],
            ],
        ],
        'couleurs' => [
            'fr' => 'Les couleurs', 'en' => 'Colours', 'icone' => '🎨', 'couleur' => '#EF4444',
            'mots' => [
                ['🔴', 'red', 'rouge', 'rot'],
                ['🔵', 'blue', 'bleu', 'blau'],
                ['🟢', 'green', 'vert', 'grün'],
                ['🟡', 'yellow', 'jaune', 'gelb'],
                ['🟠', 'orange', 'orange', 'orange'],
                ['🟣', 'purple', 'violet', 'lila'],
                ['⚫', 'black', 'noir', 'schwarz'],
                ['⚪', 'white', 'blanc', 'weiß'],
            ],
        ],
        'nombres' => [
            'fr' => 'Les nombres', 'en' => 'Numbers', 'icone' => '🔢', 'couleur' => '#3B82F6',
            'mots' => [
                ['1️⃣', 'one', 'un', 'eins'],
                ['2️⃣', 'two', 'deux', 'zwei'],
                ['3️⃣', 'three', 'trois', 'drei'],
                ['4️⃣', 'four', 'quatre', 'vier'],
                ['5️⃣', 'five', 'cinq', 'fünf'],
                ['6️⃣', 'six', 'six', 'sechs'],
                ['7️⃣', 'seven', 'sept', 'sieben'],
                ['🔟', 'ten', 'dix', 'zehn'],
            ],
        ],
        'famille' => [
            'fr' => 'La famille', 'en' => 'Family', 'icone' => '👨‍👩‍👧', 'couleur' => '#EC4899',
            'mots' => [
                ['👩', 'mother', 'la mère', 'die Mutter'],
                ['👨', 'father', 'le père', 'der Vater'],
                ['👶', 'baby', 'le bébé', 'das Baby'],
                ['👧', 'sister', 'la sœur', 'die Schwester'],
                ['👦', 'brother', 'le frère', 'der Bruder'],
                ['👵', 'grandmother', 'la grand-mère', 'die Großmutter'],
                ['👴', 'grandfather', 'le grand-père', 'der Großvater'],
                ['👨‍👩‍👧‍👦', 'family', 'la famille', 'die Familie'],
            ],
        ],
        'fruits' => [
            'fr' => 'Les fruits', 'en' => 'Fruit', 'icone' => '🍎', 'couleur' => '#22C55E',
            'mots' => [
                ['🍎', 'apple', 'la pomme', 'der Apfel'],
                ['🍌', 'banana', 'la banane', 'die Banane'],
                ['🍊', 'orange', "l'orange", 'die Orange'],
                ['🍍', 'pineapple', "l'ananas", 'die Ananas'],
                ['🍓', 'strawberry', 'la fraise', 'die Erdbeere'],
                ['🥭', 'mango', 'la mangue', 'die Mango'],
                ['🍉', 'watermelon', 'la pastèque', 'die Wassermelone'],
                ['🍇', 'grapes', 'le raisin', 'die Trauben'],
            ],
        ],
        'corps' => [
            'fr' => 'Le corps', 'en' => 'My body', 'icone' => '✋', 'couleur' => '#A855F7',
            'mots' => [
                ['👁️', 'eye', "l'œil", 'das Auge'],
                ['👂', 'ear', "l'oreille", 'das Ohr'],
                ['👃', 'nose', 'le nez', 'die Nase'],
                ['👄', 'mouth', 'la bouche', 'der Mund'],
                ['✋', 'hand', 'la main', 'die Hand'],
                ['🦶', 'foot', 'le pied', 'der Fuß'],
                ['🦵', 'leg', 'la jambe', 'das Bein'],
                ['🦷', 'tooth', 'la dent', 'der Zahn'],
            ],
        ],
        'ecole' => [
            'fr' => "L'école", 'en' => 'School', 'icone' => '🎒', 'couleur' => '#0EA5E9',
            'mots' => [
                ['🏫', 'school', "l'école", 'die Schule'],
                ['📚', 'book', 'le livre', 'das Buch'],
                ['✏️', 'pencil', 'le crayon', 'der Bleistift'],
                ['🎒', 'school bag', 'le cartable', 'die Schultasche'],
                ['👩‍🏫', 'teacher', 'la maîtresse', 'die Lehrerin'],
                ['✂️', 'scissors', 'les ciseaux', 'die Schere'],
                ['📏', 'ruler', 'la règle', 'das Lineal'],
                ['💻', 'computer', "l'ordinateur", 'der Computer'],
            ],
        ],
        'nourriture' => [
            'fr' => 'À table !', 'en' => 'Food', 'icone' => '🍞', 'couleur' => '#F97316',
            'mots' => [
                ['🍞', 'bread', 'le pain', 'das Brot'],
                ['🥛', 'milk', 'le lait', 'die Milch'],
                ['💧', 'water', "l'eau", 'das Wasser'],
                ['🍚', 'rice', 'le riz', 'der Reis'],
                ['🥚', 'egg', "l'œuf", 'das Ei'],
                ['🧀', 'cheese', 'le fromage', 'der Käse'],
                ['🍰', 'cake', 'le gâteau', 'der Kuchen'],
                ['🍗', 'chicken', 'le poulet', 'das Hähnchen'],
            ],
        ],
        'vetements' => [
            'fr' => 'Les vêtements', 'en' => 'Clothes', 'icone' => '👕', 'couleur' => '#14B8A6',
            'mots' => [
                ['👕', 'T-shirt', 'le t-shirt', 'das T-Shirt'],
                ['👖', 'trousers', 'le pantalon', 'die Hose'],
                ['👗', 'dress', 'la robe', 'das Kleid'],
                ['👟', 'shoes', 'les chaussures', 'die Schuhe'],
                ['👒', 'hat', 'le chapeau', 'der Hut'],
                ['🧦', 'socks', 'les chaussettes', 'die Socken'],
                ['🧥', 'coat', 'le manteau', 'der Mantel'],
                ['👓', 'glasses', 'les lunettes', 'die Brille'],
            ],
        ],
        'meteo' => [
            'fr' => 'Le ciel et la météo', 'en' => 'Weather', 'icone' => '🌈', 'couleur' => '#6366F1',
            'mots' => [
                ['☀️', 'sun', 'le soleil', 'die Sonne'],
                ['🌧️', 'rain', 'la pluie', 'der Regen'],
                ['☁️', 'cloud', 'le nuage', 'die Wolke'],
                ['🌬️', 'wind', 'le vent', 'der Wind'],
                ['❄️', 'snow', 'la neige', 'der Schnee'],
                ['🌈', 'rainbow', "l'arc-en-ciel", 'der Regenbogen'],
                ['🌙', 'moon', 'la lune', 'der Mond'],
                ['⭐', 'star', "l'étoile", 'der Stern'],
            ],
        ],
        'maison' => [
            'fr' => 'La maison', 'en' => 'My house', 'icone' => '🏠', 'couleur' => '#84CC16',
            'mots' => [
                ['🏠', 'house', 'la maison', 'das Haus'],
                ['🚪', 'door', 'la porte', 'die Tür'],
                ['🪟', 'window', 'la fenêtre', 'das Fenster'],
                ['🛏️', 'bed', 'le lit', 'das Bett'],
                ['🪑', 'chair', 'la chaise', 'der Stuhl'],
                ['🛋️', 'sofa', 'le canapé', 'das Sofa'],
                ['🔑', 'key', 'la clé', 'der Schlüssel'],
                ['🛁', 'bath', 'la baignoire', 'die Badewanne'],
            ],
        ],
        'transports' => [
            'fr' => 'Les transports', 'en' => 'Transport', 'icone' => '🚗', 'couleur' => '#DC2626',
            'mots' => [
                ['🚗', 'car', 'la voiture', 'das Auto'],
                ['🚌', 'bus', 'le bus', 'der Bus'],
                ['✈️', 'plane', "l'avion", 'das Flugzeug'],
                ['🚲', 'bike', 'le vélo', 'das Fahrrad'],
                ['⛵', 'boat', 'le bateau', 'das Boot'],
                ['🚂', 'train', 'le train', 'der Zug'],
                ['🚕', 'taxi', 'le taxi', 'das Taxi'],
                ['🏍️', 'motorbike', 'la moto', 'das Motorrad'],
            ],
        ],
    ],

    // Les jeux d'un theme, dans l'ordre. « lis » demande de savoir lire : il
    // vient apres les deux jeux d'ecoute ; « dis », le plus difficile, en dernier.
    'activites' => [
        'decouvrir' => ['fr' => 'Découvre', 'en' => 'Discover', 'icone' => '👀'],
        'ecoute' => ['fr' => 'Écoute et touche', 'en' => 'Listen and tap', 'icone' => '👂'],
        'lis' => ['fr' => 'Lis et trouve', 'en' => 'Read and find', 'icone' => '📖'],
        'paires' => ['fr' => 'Memory', 'en' => 'Memory', 'icone' => '🃏'],
        // L'enfant dit le mot au micro ; la transcription est comparee au mot attendu.
        'dis' => ['fr' => 'Dis-le !', 'en' => 'Say it!', 'icone' => '🎤'],
    ],
];
