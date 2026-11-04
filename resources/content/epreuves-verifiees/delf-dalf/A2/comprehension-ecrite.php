<?php

/**
 * DELF A2 tout public — épreuve « Compréhension des écrits », les quatre exercices.
 *
 * Écrit à la main d'après les documents de France Éducation international
 * (lus le 2026-11-04 dans leurs copies archivées, le site opposant un contrôle
 * anti-robot) :
 *  - manuel du candidat DELF A2, § 3.4 : 30 minutes, 4 exercices — des
 *    affiches, menus ou annonces pour chercher une information, un courriel ou
 *    une lettre personnelle, des instructions (règlement intérieur, recette),
 *    un article ; « trois choix » par question à choix multiple
 *    https://www.france-education-international.fr/document/manuel-candidat-delf-a2
 *  - le corrigé du sujet d'exemple au format actualisé, dont on reprend la
 *    forme : 6 documents à rapprocher de 8 personnes (deux restent sans
 *    document), puis 6, 6 et 5 questions à choix multiple
 *    https://www.france-education-international.fr/document/delf-a2-tp-correcteur-coll-exemple1
 *
 * L'autre sujet d'exemple officiel compte 5, 5, 5 et 6 questions : le nombre
 * d'items n'est donc pas fixé par l'épreuve, et la configuration n'en annonce
 * aucun. À l'examen, une question de l'exercice 4 peut demander « vrai ou
 * faux » avec une justification recopiée ; notre format n'a pas de question
 * mixte, toutes sont donc à choix multiple, et la consigne le dit.
 *
 * Les documents sont originaux. TROIS options par question, comme au DELF.
 */

$personnes = [
    'A — Amira cherche un cours pour apprendre à cuisiner.',
    'B — Bruno veut vendre des meubles avant son déménagement.',
    'C — Chloé cherche quelqu\'un pour garder son chat en août.',
    'D — David veut faire du sport le soir, après le travail.',
    'E — Élise cherche une chambre près de l\'université.',
    'F — Fabien veut apprendre à jouer de la guitare.',
    'G — Gabriel cherche un travail pour l\'été.',
    'H — Hélène veut faire une randonnée le week-end.',
];

return [
    'exam' => 'delf-dalf',
    'level' => 'A2',
    'section' => 'comprehension-ecrite',
    'parts' => [

        // ━━━━━━━━━━ Exercice 1 : six annonces, huit personnes ━━━━━━━━━━
        [
            'part' => 1,
            'name' => 'Compréhension des écrits, exercice 1',
            'type' => 'matching',
            'title' => 'DELF A2 · Compréhension des écrits, exercice 1 — Les petites annonces',
            'description' => "Six annonces et huit personnes : à qui correspond chaque annonce ? Deux personnes restent sans annonce. Format actualisé du DELF A2.",
            'content' => [
                'instructions' => "Lisez les six annonces et la liste des huit personnes. Pour chaque annonce, choisissez la personne qui est intéressée. Attention : deux personnes ne correspondent à aucune annonce.",
                'passage' => "LES PERSONNES\n".implode("\n", $personnes)."\n\n"
                    ."LES ANNONCES\n\n"
                    ."Annonce 1 — Club Énergie : salle de sport ouverte jusqu'à 22 h du lundi au vendredi. Premier mois à moitié prix.\n\n"
                    ."Annonce 2 — Brocante Chez Max achète vos meubles d'occasion : canapés, tables, armoires. Nous venons les chercher chez vous gratuitement. Tél. 06 45 78 12 03.\n\n"
                    ."Annonce 3 — Le restaurant Le Bon Goût propose des ateliers le samedi matin : préparez avec notre chef un repas complet en trois heures !\n\n"
                    ."Annonce 4 — Particulier loue chambre meublée de 15 m², à cinq minutes à pied du campus. Libre en septembre.\n\n"
                    ."Annonce 5 — Camping des Pins recrute des serveurs pour juillet et août. Logement possible sur place.\n\n"
                    ."Annonce 6 — Vous partez en vacances ? Étudiante sérieuse s'occupe de vos animaux chez vous. Disponible tout l'été.",
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'matching',
                    'text' => "Annonce 1 — Club Énergie : salle de sport ouverte jusqu'à 22 h.",
                    'options' => $personnes,
                    'correct_answer' => 'D',
                    'explanation' => "D — David. Il veut faire du sport « le soir, après le travail », et la salle est « ouverte jusqu'à 22 h du lundi au vendredi ».",
                ],
                [
                    'id' => 'q2', 'type' => 'matching',
                    'text' => "Annonce 2 — La brocante Chez Max achète des meubles d'occasion.",
                    'options' => $personnes,
                    'correct_answer' => 'B',
                    'explanation' => "B — Bruno. Il « veut vendre des meubles avant son déménagement », et la brocante « achète vos meubles d'occasion ». Le verbe compte : l'annonce achète, Bruno vend — ils sont faits pour s'entendre.",
                ],
                [
                    'id' => 'q3', 'type' => 'matching',
                    'text' => "Annonce 3 — Ateliers du samedi avec le chef du restaurant Le Bon Goût.",
                    'options' => $personnes,
                    'correct_answer' => 'A',
                    'explanation' => "A — Amira. Elle cherche « un cours pour apprendre à cuisiner », et l'atelier propose de préparer « avec notre chef un repas complet ».",
                ],
                [
                    'id' => 'q4', 'type' => 'matching',
                    'text' => "Annonce 4 — Chambre meublée à cinq minutes du campus.",
                    'options' => $personnes,
                    'correct_answer' => 'E',
                    'explanation' => "E — Élise. Elle cherche « une chambre près de l'université », et la chambre est « à cinq minutes à pied du campus » ; le campus, c'est l'université.",
                ],
                [
                    'id' => 'q5', 'type' => 'matching',
                    'text' => "Annonce 5 — Le camping recrute des serveurs pour juillet et août.",
                    'options' => $personnes,
                    'correct_answer' => 'G',
                    'explanation' => "G — Gabriel. Il cherche « un travail pour l'été », et le camping « recrute », c'est-à-dire embauche, pour juillet et août.",
                ],
                [
                    'id' => 'q6', 'type' => 'matching',
                    'text' => "Annonce 6 — Une étudiante s'occupe de vos animaux pendant les vacances.",
                    'options' => $personnes,
                    'correct_answer' => 'C',
                    'explanation' => "C — Chloé. Elle cherche quelqu'un pour garder son chat en août, et l'étudiante « s'occupe de vos animaux chez vous », « tout l'été ». Fabien (la guitare) et Hélène (la randonnée) ne trouvent aucune annonce.",
                ],
            ],
        ],

        // ━━━━━━━━━━ Exercice 2 : un courriel, 6 questions ━━━━━━━━━━
        [
            'part' => 2,
            'name' => 'Compréhension des écrits, exercice 2',
            'type' => 'mcq',
            'title' => 'DELF A2 · Compréhension des écrits, exercice 2 — Des nouvelles de Montréal',
            'description' => "Un courriel personnel et six questions à trois choix. Format actualisé du DELF A2.",
            'content' => [
                'instructions' => "Lisez le courriel, puis répondez aux questions : choisissez A, B ou C.",
                'passage' => "Objet : Des nouvelles de Montréal !\n\n"
                    ."Salut Thomas,\n\n"
                    ."Ça fait déjà deux mois que je suis à Montréal pour mon stage. Je travaille dans une agence de voyages, "
                    ."au centre-ville. Je réponds aux clients au téléphone et je prépare des programmes de visite : j'adore ça !\n\n"
                    ."Au début, c'était difficile : je ne comprenais pas bien l'accent québécois, et il faisait très froid "
                    ."(moins vingt degrés en janvier !). Maintenant, ça va beaucoup mieux.\n\n"
                    ."J'habite avec deux étudiantes canadiennes, dans un appartement près du parc du Mont-Royal. "
                    ."Le week-end, nous faisons du patin à glace sur le lac du parc.\n\n"
                    ."Mon stage finit le 30 avril, mais je vais rester encore deux semaines pour visiter Québec avec ma sœur. "
                    ."Elle arrive de Lyon le 2 mai.\n\n"
                    ."Et toi, tu as trouvé un appartement à Paris ? Écris-moi vite !\n\n"
                    ."Lucas",
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'mcq',
                    'text' => "Pourquoi Lucas est-il à Montréal ?",
                    'options' => [
                        "Pour étudier à l'université.",
                        "Pour faire un stage.",
                        "Pour des vacances.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « je suis à Montréal pour mon stage ». Il habite avec des étudiantes, mais lui fait un stage dans une agence de voyages.",
                ],
                [
                    'id' => 'q2', 'type' => 'mcq',
                    'text' => "Qu'est-ce que Lucas fait dans son travail ?",
                    'options' => [
                        "Il accompagne les touristes en ville.",
                        "Il vend des billets d'avion à l'aéroport.",
                        "Il prépare des programmes de visite.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. « Je réponds aux clients au téléphone et je prépare des programmes de visite ». Il travaille au bureau, « au centre-ville », pas avec les touristes dans la rue.",
                ],
                [
                    'id' => 'q3', 'type' => 'mcq',
                    'text' => "Qu'est-ce qui a été difficile pour Lucas au début ?",
                    'options' => [
                        "Comprendre l'accent et supporter le froid.",
                        "Trouver un appartement.",
                        "Se faire des amis.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. « je ne comprenais pas bien l'accent québécois, et il faisait très froid ». « Au début » montre que c'est fini : « Maintenant, ça va beaucoup mieux ».",
                ],
                [
                    'id' => 'q4', 'type' => 'mcq',
                    'text' => "Que fait Lucas le week-end ?",
                    'options' => [
                        "Du ski.",
                        "Des randonnées en montagne.",
                        "Du patin à glace.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. « Le week-end, nous faisons du patin à glace sur le lac du parc ». Le Mont-Royal est un parc, pas une montagne pour la randonnée.",
                ],
                [
                    'id' => 'q5', 'type' => 'mcq',
                    'text' => "Après son stage, Lucas va…",
                    'options' => [
                        "visiter Québec avec sa sœur.",
                        "rentrer tout de suite en France.",
                        "travailler à Lyon.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. « je vais rester encore deux semaines pour visiter Québec avec ma sœur ». Lyon, c'est la ville d'où arrive sa sœur.",
                ],
                [
                    'id' => 'q6', 'type' => 'mcq',
                    'text' => "Qu'est-ce que Lucas demande à Thomas ?",
                    'options' => [
                        "S'il veut venir à Montréal.",
                        "S'il a trouvé un logement.",
                        "S'il connaît Québec.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « tu as trouvé un appartement à Paris ? ». Un appartement est un logement.",
                ],
            ],
        ],

        // ━━━━━━━━━━ Exercice 3 : des instructions, 6 questions ━━━━━━━━━━
        [
            'part' => 3,
            'name' => 'Compréhension des écrits, exercice 3',
            'type' => 'mcq',
            'title' => 'DELF A2 · Compréhension des écrits, exercice 3 — Le règlement de l\'auberge',
            'description' => "Un règlement intérieur et six questions à trois choix. Format actualisé du DELF A2.",
            'content' => [
                'instructions' => "Lisez le règlement de l'auberge de jeunesse, puis répondez aux questions : choisissez A, B ou C.",
                'passage' => "AUBERGE DE JEUNESSE LES MOUETTES — RÈGLEMENT\n\n"
                    ."• Arrivée à partir de 15 h. Départ avant 11 h. Vous pouvez laisser vos bagages à l'accueil le jour du départ.\n"
                    ."• Les draps sont fournis. Les serviettes ne sont pas fournies : vous pouvez en louer une à l'accueil (2 €).\n"
                    ."• La cuisine est ouverte de 7 h à 22 h. Lavez votre vaisselle après chaque repas.\n"
                    ."• Écrivez votre nom et la date sur vos aliments dans le réfrigérateur. Le réfrigérateur est vidé chaque lundi.\n"
                    ."• Silence dans les chambres après 22 h 30.\n"
                    ."• Il est interdit de fumer dans le bâtiment. Un espace est prévu dans le jardin.",
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'mcq',
                    'text' => "Vous partez à 18 heures le dernier jour. Que pouvez-vous faire ?",
                    'options' => [
                        "Laisser vos bagages à l'accueil.",
                        "Rester dans votre chambre jusqu'à 18 heures.",
                        "Payer une nuit en plus.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. Le départ est « avant 11 h », mais « Vous pouvez laisser vos bagages à l'accueil le jour du départ ». Vous ne pouvez donc pas garder la chambre jusqu'à 18 heures.",
                ],
                [
                    'id' => 'q2', 'type' => 'mcq',
                    'text' => "Qu'est-ce que vous devez apporter ou louer ?",
                    'options' => [
                        "Des draps.",
                        "Une serviette.",
                        "Des assiettes.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « Les serviettes ne sont pas fournies : vous pouvez en louer une à l'accueil ». Les draps, eux, « sont fournis » : l'auberge les donne.",
                ],
                [
                    'id' => 'q3', 'type' => 'mcq',
                    'text' => "Dans la cuisine, vous devez…",
                    'options' => [
                        "cuisiner avant 7 heures.",
                        "demander l'autorisation à l'accueil.",
                        "laver votre vaisselle.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. « Lavez votre vaisselle après chaque repas ». La cuisine ouvre à 7 h : on ne peut pas cuisiner avant.",
                ],
                [
                    'id' => 'q4', 'type' => 'mcq',
                    'text' => "Qu'est-ce que vous devez écrire sur vos aliments ?",
                    'options' => [
                        "Le prix.",
                        "Votre nom et la date.",
                        "Votre numéro de chambre.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « Écrivez votre nom et la date sur vos aliments dans le réfrigérateur ».",
                ],
                [
                    'id' => 'q5', 'type' => 'mcq',
                    'text' => "Que se passe-t-il chaque lundi ?",
                    'options' => [
                        "La cuisine est fermée.",
                        "Les draps sont changés.",
                        "Le réfrigérateur est vidé.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. « Le réfrigérateur est vidé chaque lundi » : tout ce qui est dedans est enlevé. D'où l'intérêt d'écrire son nom et la date.",
                ],
                [
                    'id' => 'q6', 'type' => 'mcq',
                    'text' => "Où est-il possible de fumer ?",
                    'options' => [
                        "Dans le jardin.",
                        "Dans sa chambre.",
                        "Dans la cuisine.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. « Il est interdit de fumer dans le bâtiment. Un espace est prévu dans le jardin ». La chambre et la cuisine sont dans le bâtiment.",
                ],
            ],
        ],

        // ━━━━━━━━━━ Exercice 4 : un article, 5 questions ━━━━━━━━━━
        [
            'part' => 4,
            'name' => 'Compréhension des écrits, exercice 4',
            'type' => 'mcq',
            'title' => 'DELF A2 · Compréhension des écrits, exercice 4 — Un musée dans le train',
            'description' => "Un article de journal et cinq questions à trois choix. Format actualisé du DELF A2.",
            'content' => [
                'instructions' => "Lisez l'article, puis répondez aux questions : choisissez A, B ou C.\n\nÀ noter : le jour de l'examen, une question de cet exercice peut demander « vrai ou faux » avec une phrase du texte à recopier. Ici, toutes les questions sont à choix multiple.",
                'passage' => "UN MUSÉE QUI VOYAGE EN TRAIN\n\n"
                    ."Depuis le mois de mars, un train pas comme les autres s'arrête dans les petites gares de la région : "
                    ."ses trois wagons sont devenus un musée. On y découvre l'histoire du chemin de fer, avec des photos, "
                    ."des uniformes anciens et une vieille locomotive en miniature.\n\n"
                    ."L'idée vient de Marc Dubois, un ancien conducteur de train. « Beaucoup d'habitants des villages ne vont "
                    ."jamais au musée, parce que c'est trop loin. Alors, c'est le musée qui vient à eux ! », explique-t-il.\n\n"
                    ."Le train reste trois jours dans chaque gare. La visite est gratuite et dure environ quarante-cinq minutes. "
                    ."Le jeudi, le musée est réservé aux écoles : les élèves peuvent rencontrer d'anciens conducteurs qui "
                    ."racontent leur métier.\n\n"
                    ."Le succès est grand : plus de 8 000 personnes ont déjà visité le train. Il va continuer son voyage jusqu'en décembre.",
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'mcq',
                    'text' => "De quoi parle cet article ?",
                    'options' => [
                        "D'une nouvelle ligne de train.",
                        "D'un musée installé dans un train.",
                        "D'une gare transformée en musée.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « ses trois wagons sont devenus un musée » : c'est le train lui-même qui est le musée, et il se déplace de gare en gare. Les gares ne sont pas transformées.",
                ],
                [
                    'id' => 'q2', 'type' => 'mcq',
                    'text' => "Qui a eu l'idée de ce musée ?",
                    'options' => [
                        "Le maire d'un village.",
                        "Un professeur d'histoire.",
                        "Un ancien conducteur de train.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. « L'idée vient de Marc Dubois, un ancien conducteur de train ».",
                ],
                [
                    'id' => 'q3', 'type' => 'mcq',
                    'text' => "Pourquoi Marc Dubois a-t-il créé ce musée ?",
                    'options' => [
                        "Parce que les habitants des villages vont rarement au musée.",
                        "Parce que les musées des villes sont trop chers.",
                        "Parce qu'il voulait vendre ses photos.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. « Beaucoup d'habitants des villages ne vont jamais au musée, parce que c'est trop loin. Alors, c'est le musée qui vient à eux ! ». Le problème est la distance, pas le prix.",
                ],
                [
                    'id' => 'q4', 'type' => 'mcq',
                    'text' => "Qui peut visiter le musée le jeudi ?",
                    'options' => [
                        "Tout le monde.",
                        "Seulement les anciens conducteurs.",
                        "Seulement les écoles.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. « Le jeudi, le musée est réservé aux écoles ». « Réservé » veut dire que les autres visiteurs ne peuvent pas entrer ce jour-là. Les anciens conducteurs viennent parler aux élèves.",
                ],
                [
                    'id' => 'q5', 'type' => 'mcq',
                    'text' => "Combien de personnes ont déjà visité le train ?",
                    'options' => [
                        "Plus de 8 000.",
                        "Moins de 3 000.",
                        "Environ 4 500.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. « plus de 8 000 personnes ont déjà visité le train ». Attention aux autres nombres du texte : trois (les wagons, les jours dans chaque gare) et quarante-cinq (les minutes de visite) ne sont pas des visiteurs.",
                ],
            ],
        ],
    ],
];
