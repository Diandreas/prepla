<?php

/**
 * DELF A1 tout public — épreuve « Compréhension des écrits », les quatre exercices.
 *
 * Écrit à la main d'après les documents de France Éducation international
 * (lus le 2026-11-04 dans leurs copies archivées, le site opposant un contrôle
 * anti-robot) :
 *  - manuel du candidat DELF A1, § 3.4 : 30 minutes, 4 exercices — un message
 *    court (courriel, carte d'invitation), une affiche ou des indications pour
 *    s'orienter dans l'espace, des instructions ou une publicité pour
 *    s'orienter dans le temps, un article court ; § 3.2 : « Je choisis la bonne
 *    réponse entre trois choix »
 *    https://www.france-education-international.fr/document/manuel-candidat-delf-a1
 *  - corrigés des deux sujets d'exemple : 5 questions par exercice dans les
 *    deux formats en circulation, et toutes à choix multiple dans le format
 *    actualisé, que l'on suit ici
 *    https://www.france-education-international.fr/document/delfa1toutpubliccorrecteurexemple2
 *
 * Les documents sont originaux. Ce sont le FORMAT, le nombre d'items et le
 * nombre de choix qui reprennent l'épreuve, pas ses contenus.
 *
 * À garder si ce fichier est recopié : TROIS options par question (le DELF n'en
 * a pas quatre), et une explication en français simple qui cite le mot du
 * document qui donne la réponse. À l'examen, certaines réponses sont des
 * images ; ici, elles sont écrites.
 */

return [
    'exam' => 'delf-dalf',
    'level' => 'A1',
    'section' => 'comprehension-ecrite',
    'parts' => [

        // ━━━━━━━━━━ Exercice 1 : un message court, 5 questions ━━━━━━━━━━
        [
            'part' => 1,
            'name' => 'Compréhension des écrits, exercice 1',
            'type' => 'mcq',
            'title' => 'DELF A1 · Compréhension des écrits, exercice 1 — L\'invitation de Julie',
            'description' => "Un courriel court et cinq questions à trois choix. Format officiel du DELF A1.",
            'content' => [
                'instructions' => "Lisez le courriel, puis répondez aux questions : choisissez A, B ou C.",
                'passage' => "De : julie.martin@courriel.fr\nÀ : sofia.ramos@courriel.fr\nObjet : Samedi !\n\n"
                    ."Bonjour Sofia,\n\n"
                    ."Samedi, c'est mon anniversaire ! Je fais une petite fête chez moi, 8 rue des Écoles, au troisième étage. "
                    ."Tu peux venir à 19 heures ? On va manger une pizza et danser.\n\n"
                    ."Est-ce que tu peux apporter des boissons ? Moi, je prépare le gâteau au chocolat.\n\n"
                    ."Attention : l'ascenseur ne marche pas, prends l'escalier !\n\n"
                    ."Réponds-moi avant jeudi, s'il te plaît.\n\n"
                    ."Bisous,\nJulie",
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'mcq',
                    'text' => "Pourquoi Julie écrit-elle à Sofia ?",
                    'options' => [
                        "Pour lui demander un gâteau.",
                        "Pour l'inviter à une fête.",
                        "Pour annuler un rendez-vous.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « Samedi, c'est mon anniversaire ! Je fais une petite fête chez moi » et « Tu peux venir ? » : c'est une invitation. Le gâteau, c'est Julie qui le prépare.",
                ],
                [
                    'id' => 'q2', 'type' => 'mcq',
                    'text' => "À quelle heure commence la fête ?",
                    'options' => [
                        "À 8 heures.",
                        "À 13 heures.",
                        "À 19 heures.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. « Tu peux venir à 19 heures ? ». Le chiffre 8 est le numéro de la rue : « 8 rue des Écoles ».",
                ],
                [
                    'id' => 'q3', 'type' => 'mcq',
                    'text' => "Qu'est-ce que Sofia doit apporter ?",
                    'options' => [
                        "Des boissons.",
                        "Un gâteau.",
                        "Une pizza.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. « Est-ce que tu peux apporter des boissons ? ». Julie prépare le gâteau (« Moi, je prépare le gâteau »), et la pizza est le repas de la fête.",
                ],
                [
                    'id' => 'q4', 'type' => 'mcq',
                    'text' => "Pour aller chez Julie, Sofia doit…",
                    'options' => [
                        "prendre l'ascenseur.",
                        "sonner au rez-de-chaussée.",
                        "prendre l'escalier.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. « l'ascenseur ne marche pas, prends l'escalier ! ». « Ne marche pas » veut dire qu'il est en panne.",
                ],
                [
                    'id' => 'q5', 'type' => 'mcq',
                    'text' => "Quand Sofia doit-elle répondre ?",
                    'options' => [
                        "Avant jeudi.",
                        "Samedi.",
                        "Après la fête.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. « Réponds-moi avant jeudi, s'il te plaît ». Samedi, c'est le jour de la fête.",
                ],
            ],
        ],

        // ━━━━━━━━━━ Exercice 2 : s'orienter dans l'espace, 5 questions ━━━━━━━━━━
        [
            'part' => 2,
            'name' => 'Compréhension des écrits, exercice 2',
            'type' => 'mcq',
            'title' => 'DELF A1 · Compréhension des écrits, exercice 2 — À la médiathèque',
            'description' => "Une affiche dans un lieu public et cinq questions à trois choix. Format officiel du DELF A1.",
            'content' => [
                'instructions' => "Vous êtes à l'accueil de la médiathèque. Lisez l'affiche, puis répondez aux questions : choisissez A, B ou C.",
                'passage' => "MÉDIATHÈQUE JULES-VERNE — BIENVENUE !\n\n"
                    ."REZ-DE-CHAUSSÉE\n- Accueil et inscriptions\n- Journaux et magazines\n- Cafétéria (à droite de l'accueil)\n\n"
                    ."1er ÉTAGE\n- Livres pour les enfants\n- Salle de jeux\n\n"
                    ."2e ÉTAGE\n- Livres pour les adultes\n- Films (DVD)\n- Salle Internet : 10 ordinateurs\n\n"
                    ."3e ÉTAGE\n- Salle de travail silencieuse\n\n"
                    ."Les toilettes sont au rez-de-chaussée, à gauche de l'accueil.\n"
                    ."L'ascenseur est derrière l'escalier.",
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'mcq',
                    'text' => "Vous voulez lire un magazine. Où allez-vous ?",
                    'options' => [
                        "Au 1er étage.",
                        "Au rez-de-chaussée.",
                        "Au 2e étage.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « Journaux et magazines » sont au « REZ-DE-CHAUSSÉE », le niveau de la rue, avec l'accueil.",
                ],
                [
                    'id' => 'q2', 'type' => 'mcq',
                    'text' => "Votre fils a 6 ans. Il cherche un livre. Où allez-vous ?",
                    'options' => [
                        "Au 1er étage.",
                        "Au rez-de-chaussée.",
                        "Au 3e étage.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. Les « Livres pour les enfants » sont au « 1er ÉTAGE ». Les livres pour les adultes sont au 2e étage.",
                ],
                [
                    'id' => 'q3', 'type' => 'mcq',
                    'text' => "Vous voulez utiliser un ordinateur. Où allez-vous ?",
                    'options' => [
                        "Au 1er étage.",
                        "Au 2e étage.",
                        "Au 3e étage.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. La « Salle Internet : 10 ordinateurs » est au « 2e ÉTAGE ». Au 3e étage, il y a seulement une salle de travail.",
                ],
                [
                    'id' => 'q4', 'type' => 'mcq',
                    'text' => "Vous voulez travailler au calme. Où allez-vous ?",
                    'options' => [
                        "À la cafétéria.",
                        "Dans la salle de jeux.",
                        "Au 3e étage.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. Au « 3e ÉTAGE », il y a la « Salle de travail silencieuse ». « Silencieuse » veut dire sans bruit, au calme.",
                ],
                [
                    'id' => 'q5', 'type' => 'mcq',
                    'text' => "Où sont les toilettes ?",
                    'options' => [
                        "À droite de l'accueil.",
                        "À gauche de l'accueil.",
                        "Derrière l'escalier.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « Les toilettes sont au rez-de-chaussée, à gauche de l'accueil ». À droite, c'est la cafétéria, et derrière l'escalier, c'est l'ascenseur.",
                ],
            ],
        ],

        // ━━━━━━━━━━ Exercice 3 : s'orienter dans le temps, 5 questions ━━━━━━━━━━
        [
            'part' => 3,
            'name' => 'Compréhension des écrits, exercice 3',
            'type' => 'mcq',
            'title' => 'DELF A1 · Compréhension des écrits, exercice 3 — La piscine des Lilas',
            'description' => "Une publicité avec des horaires et cinq questions à trois choix. Format officiel du DELF A1.",
            'content' => [
                'instructions' => "Lisez la publicité, puis répondez aux questions : choisissez A, B ou C.",
                'passage' => "PISCINE DES LILAS — NOUVEAUX HORAIRES\n\n"
                    ."Lundi : fermé\n"
                    ."Mardi, jeudi, vendredi : 12 h – 14 h et 17 h – 21 h\n"
                    ."Mercredi : 10 h – 19 h\n"
                    ."Samedi et dimanche : 9 h – 18 h\n\n"
                    ."COURS DE NATATION POUR ADULTES : le mardi à 19 h\n"
                    ."COURS POUR LES ENFANTS : le mercredi à 14 h\n\n"
                    ."Prix d'une entrée : 4 € (adultes), 2 € (enfants)\n"
                    ."Le bonnet de bain est obligatoire.",
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'mcq',
                    'text' => "Quel jour la piscine est-elle fermée ?",
                    'options' => [
                        "Le mercredi.",
                        "Le dimanche.",
                        "Le lundi.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. « Lundi : fermé ». Le mercredi et le dimanche, la piscine est ouverte.",
                ],
                [
                    'id' => 'q2', 'type' => 'mcq',
                    'text' => "Vous travaillez jusqu'à 18 heures le jeudi. Vous pouvez nager le jeudi…",
                    'options' => [
                        "le soir, jusqu'à 21 heures.",
                        "le matin.",
                        "à 10 heures.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. Le jeudi, la piscine est ouverte « 12 h – 14 h et 17 h – 21 h ». Après 18 heures, vous pouvez nager le soir, jusqu'à 21 heures.",
                ],
                [
                    'id' => 'q3', 'type' => 'mcq',
                    'text' => "Quand a lieu le cours de natation pour adultes ?",
                    'options' => [
                        "Le mercredi à 14 heures.",
                        "Le mardi à 19 heures.",
                        "Le samedi à 9 heures.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « COURS DE NATATION POUR ADULTES : le mardi à 19 h ». Le mercredi à 14 h, c'est le cours pour les enfants.",
                ],
                [
                    'id' => 'q4', 'type' => 'mcq',
                    'text' => "Combien coûte une entrée pour un enfant ?",
                    'options' => [
                        "4 euros.",
                        "Rien : c'est gratuit.",
                        "2 euros.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. « Prix d'une entrée : 4 € (adultes), 2 € (enfants) ». 4 €, c'est le prix pour les adultes.",
                ],
                [
                    'id' => 'q5', 'type' => 'mcq',
                    'text' => "Qu'est-ce qui est obligatoire ?",
                    'options' => [
                        "Un bonnet de bain.",
                        "Une serviette.",
                        "Un cours de natation.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. « Le bonnet de bain est obligatoire ». « Obligatoire » veut dire qu'il faut l'avoir, sinon on ne peut pas nager.",
                ],
            ],
        ],

        // ━━━━━━━━━━ Exercice 4 : un article court, 5 questions ━━━━━━━━━━
        [
            'part' => 4,
            'name' => 'Compréhension des écrits, exercice 4',
            'type' => 'mcq',
            'title' => 'DELF A1 · Compréhension des écrits, exercice 4 — La fête du pain',
            'description' => "Un article court et cinq questions à trois choix. Format officiel du DELF A1.",
            'content' => [
                'instructions' => "Lisez l'article, puis répondez aux questions : choisissez A, B ou C.",
                'passage' => "LA FÊTE DU PAIN REVIENT À SAINT-ANDRÉ\n\n"
                    ."Comme chaque année, la fête du pain a lieu en mai, sur la place du marché de Saint-André. "
                    ."Cette année, elle dure deux jours : le samedi 17 et le dimanche 18 mai.\n\n"
                    ."Les boulangers de la ville montrent comment ils font le pain. Les enfants peuvent aussi faire "
                    ."leur petit pain et le rapporter à la maison.\n\n"
                    ."L'entrée est gratuite. Pour l'atelier des enfants, il faut s'inscrire à l'office de tourisme ou "
                    ."par téléphone au 04 76 21 35 80.",
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'mcq',
                    'text' => "La fête du pain a lieu…",
                    'options' => [
                        "tous les mois.",
                        "pour la première fois.",
                        "une fois par an.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. « Comme chaque année, la fête du pain a lieu en mai » : elle revient tous les ans, une fois par an. « REVIENT » dans le titre dit aussi que ce n'est pas la première fois.",
                ],
                [
                    'id' => 'q2', 'type' => 'mcq',
                    'text' => "Cette année, la fête dure…",
                    'options' => [
                        "deux jours.",
                        "un jour.",
                        "une semaine.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. « elle dure deux jours : le samedi 17 et le dimanche 18 mai ».",
                ],
                [
                    'id' => 'q3', 'type' => 'mcq',
                    'text' => "Qu'est-ce que les enfants peuvent faire ?",
                    'options' => [
                        "Vendre du pain.",
                        "Faire un petit pain.",
                        "Visiter une boulangerie.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « Les enfants peuvent aussi faire leur petit pain et le rapporter à la maison ». Ce sont les boulangers qui montrent leur travail.",
                ],
                [
                    'id' => 'q4', 'type' => 'mcq',
                    'text' => "Combien coûte l'entrée de la fête ?",
                    'options' => [
                        "Rien.",
                        "2 euros.",
                        "5 euros.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. « L'entrée est gratuite » : « gratuit » veut dire qu'on ne paie pas.",
                ],
                [
                    'id' => 'q5', 'type' => 'mcq',
                    'text' => "Pour l'atelier des enfants, il faut…",
                    'options' => [
                        "arriver tôt le samedi.",
                        "s'inscrire avant.",
                        "acheter du pain.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « Pour l'atelier des enfants, il faut s'inscrire à l'office de tourisme ou par téléphone ».",
                ],
            ],
        ],
    ],
];
