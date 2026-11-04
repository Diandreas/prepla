<?php

/**
 * DELF B1 tout public — épreuve « Compréhension des écrits », les deux exercices.
 *
 * Écrit à la main d'après les documents de France Éducation international
 * (lus le 2026-11-04 dans leurs copies archivées, le site opposant un contrôle
 * anti-robot) :
 *  - page du DELF B1 : 2 exercices, 45 minutes
 *    https://www.france-education-international.fr/diplome/delf-tout-public/niveau-b1
 *  - manuel du candidat DELF B1, § 3.4 : exercice 1, « repérer des informations
 *    dans une brochure, une annonce, un menu pour réaliser une tâche donnée »,
 *    en mettant « une croix pour chaque critère et pour chaque annonce » ;
 *    exercice 2, « comprendre un article d'intérêt général » ; « trois choix »
 *    par question à choix multiple
 *    https://www.france-education-international.fr/document/manuel-candidat-delf-b1
 *  - corrigé du sujet d'exemple au format en deux exercices : une grille de
 *    4 documents × 5 critères (20 cases oui / non), puis un article de 11
 *    questions — la forme reprise ici
 *    https://www.france-education-international.fr/document/delf-b1-tp-correcteur-coll-exemple2
 *
 * Le format actualisé, lui, compte 3 exercices (une grille de 16 cases, puis
 * deux articles de 7 questions) : le nombre d'items n'est donc pas fixé, et la
 * configuration n'en annonce aucun.
 *
 * Deux adaptations, dites à l'apprenant :
 *  - à l'examen, la grille se termine par « Quel club choisissez-vous ? ».
 *    Le vrai-faux n'a pas de question ouverte : le choix est donné dans
 *    l'explication de la dernière case ;
 *  - à l'examen, certaines questions de l'article demandent « vrai ou faux »
 *    avec une justification, ou une réponse courte. Ici, toutes sont à choix
 *    multiple, avec TROIS options comme au DELF.
 *
 * Les documents sont originaux.
 */

$grille = ['Oui', 'Non'];

return [
    'exam' => 'delf-dalf',
    'level' => 'B1',
    'section' => 'comprehension-ecrite',
    'parts' => [

        // ━━━━━━━━━━ Exercice 1 : grille oui / non, 4 clubs × 5 critères ━━━━━━━━━━
        [
            'part' => 1,
            'name' => 'Compréhension des écrits, exercice 1',
            'type' => 'true-false',
            'title' => 'DELF B1 · Compréhension des écrits, exercice 1 — Choisir un club de sport',
            'description' => "Quatre annonces, cinq critères : pour chaque club, dites si chaque critère est rempli (oui / non). Format du DELF B1 en deux exercices.",
            'content' => [
                'instructions' => "Vous cherchez un club de sport pour vous et votre fille de 12 ans. Vous avez cinq critères :\n"
                    ."1. il y a une piscine ;\n"
                    ."2. il y a des cours pour les adolescents de 12 ans ;\n"
                    ."3. le club est ouvert le dimanche ;\n"
                    ."4. l'abonnement adulte coûte moins de 40 € par mois ;\n"
                    ."5. on peut y aller en transports en commun.\n\n"
                    ."Lisez les quatre annonces. Pour chaque club et pour chaque critère, répondez Oui ou Non. À l'examen, vous indiquez ensuite le club que vous choisissez.",
                'passage' => "CLUB 1 — AQUAFORME\n"
                    ."Notre piscine de 25 mètres et notre salle de musculation vous accueillent du lundi au samedi, de 7 h à 21 h. "
                    ."Cours de natation pour les jeunes à partir de 10 ans. Abonnement : 45 € par mois. "
                    ."Bus 12, arrêt « Aquaforme », devant l'entrée.\n\n"
                    ."CLUB 2 — TONIC GYM\n"
                    ."Salle de 800 m² équipée des dernières machines, cours de fitness et de boxe. Ouvert 7 jours sur 7 ; le dimanche, "
                    ."de 9 h à 13 h. Abonnement : 29 € par mois. Club réservé aux plus de 16 ans. Situé dans la zone d'activités "
                    ."des Peupliers, avec un grand parking gratuit ; aucune ligne de bus ne dessert la zone.\n\n"
                    ."CLUB 3 — CENTRE SPORTIF DES RIVES\n"
                    ."Piscine olympique, salle de sport et terrains de tennis. Une section « Jeunes » propose des cours pour les 12-17 ans. "
                    ."Ouvert tous les jours, dimanche compris, de 8 h à 20 h. Abonnement adulte : 35 € par mois ; demi-tarif pour les "
                    ."moins de 18 ans. À 200 mètres de la station de tramway Les Rives.\n\n"
                    ."CLUB 4 — CLUB VITAL\n"
                    ."Bassin de 25 mètres, sauna et cours collectifs. Activités pour les enfants de 4 à 11 ans le mercredi. "
                    ."Ouvert du lundi au samedi, et le dimanche matin. Abonnement : 39 € par mois. Métro ligne B, station Vital.",
            ],
            'questions' => [
                // Club 1 — Aquaforme
                ['id' => 'q1', 'type' => 'true-false', 'text' => "Club 1 — Aquaforme : il y a une piscine.", 'answer_options' => $grille, 'correct_answer' => 'Oui',
                    'explanation' => "Oui. « Notre piscine de 25 mètres »."],
                ['id' => 'q2', 'type' => 'true-false', 'text' => "Club 1 — Aquaforme : il y a des cours pour les adolescents de 12 ans.", 'answer_options' => $grille, 'correct_answer' => 'Oui',
                    'explanation' => "Oui. « Cours de natation pour les jeunes à partir de 10 ans » : à 12 ans, votre fille peut y participer."],
                ['id' => 'q3', 'type' => 'true-false', 'text' => "Club 1 — Aquaforme : le club est ouvert le dimanche.", 'answer_options' => $grille, 'correct_answer' => 'Non',
                    'explanation' => "Non. Le club est ouvert « du lundi au samedi » : le dimanche n'en fait pas partie."],
                ['id' => 'q4', 'type' => 'true-false', 'text' => "Club 1 — Aquaforme : l'abonnement adulte coûte moins de 40 € par mois.", 'answer_options' => $grille, 'correct_answer' => 'Non',
                    'explanation' => "Non. « Abonnement : 45 € par mois », c'est plus de 40 €."],
                ['id' => 'q5', 'type' => 'true-false', 'text' => "Club 1 — Aquaforme : on peut y aller en transports en commun.", 'answer_options' => $grille, 'correct_answer' => 'Oui',
                    'explanation' => "Oui. « Bus 12, arrêt “Aquaforme”, devant l'entrée »."],
                // Club 2 — Tonic Gym
                ['id' => 'q6', 'type' => 'true-false', 'text' => "Club 2 — Tonic Gym : il y a une piscine.", 'answer_options' => $grille, 'correct_answer' => 'Non',
                    'explanation' => "Non. L'annonce cite une salle, des machines, du fitness et de la boxe, mais aucune piscine. Quand un critère n'est pas mentionné, il n'est pas rempli."],
                ['id' => 'q7', 'type' => 'true-false', 'text' => "Club 2 — Tonic Gym : il y a des cours pour les adolescents de 12 ans.", 'answer_options' => $grille, 'correct_answer' => 'Non',
                    'explanation' => "Non. « Club réservé aux plus de 16 ans » : une adolescente de 12 ans ne peut pas s'inscrire."],
                ['id' => 'q8', 'type' => 'true-false', 'text' => "Club 2 — Tonic Gym : le club est ouvert le dimanche.", 'answer_options' => $grille, 'correct_answer' => 'Oui',
                    'explanation' => "Oui. « Ouvert 7 jours sur 7 ; le dimanche, de 9 h à 13 h »."],
                ['id' => 'q9', 'type' => 'true-false', 'text' => "Club 2 — Tonic Gym : l'abonnement adulte coûte moins de 40 € par mois.", 'answer_options' => $grille, 'correct_answer' => 'Oui',
                    'explanation' => "Oui. « Abonnement : 29 € par mois »."],
                ['id' => 'q10', 'type' => 'true-false', 'text' => "Club 2 — Tonic Gym : on peut y aller en transports en commun.", 'answer_options' => $grille, 'correct_answer' => 'Non',
                    'explanation' => "Non. « aucune ligne de bus ne dessert la zone » : il faut venir en voiture, d'où le « grand parking gratuit ». « Desservir » veut dire passer par un lieu, pour un transport."],
                // Club 3 — Centre sportif des Rives
                ['id' => 'q11', 'type' => 'true-false', 'text' => "Club 3 — Centre sportif des Rives : il y a une piscine.", 'answer_options' => $grille, 'correct_answer' => 'Oui',
                    'explanation' => "Oui. « Piscine olympique »."],
                ['id' => 'q12', 'type' => 'true-false', 'text' => "Club 3 — Centre sportif des Rives : il y a des cours pour les adolescents de 12 ans.", 'answer_options' => $grille, 'correct_answer' => 'Oui',
                    'explanation' => "Oui. « Une section “Jeunes” propose des cours pour les 12-17 ans » : 12 ans est compris."],
                ['id' => 'q13', 'type' => 'true-false', 'text' => "Club 3 — Centre sportif des Rives : le club est ouvert le dimanche.", 'answer_options' => $grille, 'correct_answer' => 'Oui',
                    'explanation' => "Oui. « Ouvert tous les jours, dimanche compris »."],
                ['id' => 'q14', 'type' => 'true-false', 'text' => "Club 3 — Centre sportif des Rives : l'abonnement adulte coûte moins de 40 € par mois.", 'answer_options' => $grille, 'correct_answer' => 'Oui',
                    'explanation' => "Oui. « Abonnement adulte : 35 € par mois », et votre fille paiera la moitié."],
                ['id' => 'q15', 'type' => 'true-false', 'text' => "Club 3 — Centre sportif des Rives : on peut y aller en transports en commun.", 'answer_options' => $grille, 'correct_answer' => 'Oui',
                    'explanation' => "Oui. « À 200 mètres de la station de tramway Les Rives »."],
                // Club 4 — Club Vital
                ['id' => 'q16', 'type' => 'true-false', 'text' => "Club 4 — Club Vital : il y a une piscine.", 'answer_options' => $grille, 'correct_answer' => 'Oui',
                    'explanation' => "Oui. « Bassin de 25 mètres » : un bassin, ici, c'est une piscine."],
                ['id' => 'q17', 'type' => 'true-false', 'text' => "Club 4 — Club Vital : il y a des cours pour les adolescents de 12 ans.", 'answer_options' => $grille, 'correct_answer' => 'Non',
                    'explanation' => "Non. Les activités sont « pour les enfants de 4 à 11 ans » : à 12 ans, votre fille est trop âgée. C'est le critère qui fait perdre ce club, très proche du bon choix."],
                ['id' => 'q18', 'type' => 'true-false', 'text' => "Club 4 — Club Vital : le club est ouvert le dimanche.", 'answer_options' => $grille, 'correct_answer' => 'Oui',
                    'explanation' => "Oui. « et le dimanche matin » : il est ouvert, même si ce n'est qu'une partie de la journée."],
                ['id' => 'q19', 'type' => 'true-false', 'text' => "Club 4 — Club Vital : l'abonnement adulte coûte moins de 40 € par mois.", 'answer_options' => $grille, 'correct_answer' => 'Oui',
                    'explanation' => "Oui. « Abonnement : 39 € par mois », juste sous la limite de 40 €."],
                ['id' => 'q20', 'type' => 'true-false', 'text' => "Club 4 — Club Vital : on peut y aller en transports en commun.", 'answer_options' => $grille, 'correct_answer' => 'Oui',
                    'explanation' => "Oui. « Métro ligne B, station Vital ». Bilan de la grille : seul le club 3, le Centre sportif des Rives, remplit les cinq critères — c'est lui qu'il faut choisir. Le club 4 échoue seulement sur l'âge."],
            ],
        ],

        // ━━━━━━━━━━ Exercice 2 : un article d'intérêt général, 11 questions ━━━━━━━━━━
        [
            'part' => 2,
            'name' => 'Compréhension des écrits, exercice 2',
            'type' => 'mcq',
            'title' => 'DELF B1 · Compréhension des écrits, exercice 2 — Un toit contre un peu de temps',
            'description' => "Un article d'intérêt général et onze questions à trois choix, dans l'ordre du texte. Format du DELF B1 en deux exercices.",
            'content' => [
                'instructions' => "Lisez l'article, puis répondez aux questions : choisissez A, B ou C. Les questions suivent l'ordre du texte.\n\nÀ noter : le jour de l'examen, certaines questions demandent « vrai ou faux » avec une phrase du texte à recopier, ou une réponse courte. Ici, toutes les questions sont à choix multiple.",
                'passage' => "COLOCATION ENTRE GÉNÉRATIONS : UN TOIT CONTRE UN PEU DE TEMPS\n\n"
                    ."Quand Inès, 21 ans, est arrivée à Bordeaux pour ses études d'infirmière, elle a vite compris qu'elle ne pourrait "
                    ."pas payer un studio : les petits logements du centre se louent plus de 600 euros par mois. C'est une affiche, "
                    ."à la faculté, qui l'a mise sur la piste d'une autre solution : vivre chez une personne âgée.\n\n"
                    ."Depuis un an, elle partage donc l'appartement de Germaine, 84 ans, veuve depuis cinq ans. Inès ne paie pas de "
                    ."loyer ; elle participe seulement aux charges, 80 euros par mois. En échange, elle s'engage à être présente le "
                    ."soir, à dîner avec Germaine trois fois par semaine et à l'aider pour les courses. « Ce n'est pas un travail "
                    ."d'aide à domicile, précise-t-elle. Je ne fais pas de soins, je ne fais pas le ménage. Je suis là, c'est tout. »\n\n"
                    ."Ce type de colocation existe en France depuis une vingtaine d'années, mais il se développe surtout depuis que "
                    ."les loyers augmentent dans les grandes villes. Des associations mettent en relation les deux générations : "
                    ."elles rencontrent chaque candidat, vérifient que les attentes sont compatibles et proposent un contrat qui fixe "
                    ."les règles de vie. « Le plus important, c'est l'entretien de départ, explique Paul Mercadier, responsable "
                    ."d'une de ces associations. Une personne qui se couche à 21 heures et un étudiant qui joue de la batterie, ça ne "
                    ."marchera jamais, même s'ils sont tous les deux très sympathiques. »\n\n"
                    ."Pour les personnes âgées, l'avantage n'est pas d'abord financier. Germaine le dit avec humour : « Mes enfants "
                    ."dorment mieux depuis qu'Inès est là ! » Beaucoup de familles hésitent en effet à laisser un parent âgé seul la "
                    ."nuit, sans pour autant vouloir l'installer en maison de retraite. La présence d'un jeune permet souvent de "
                    ."retarder ce départ.\n\n"
                    ."Tout n'est pas toujours simple. Certains étudiants trouvent difficile de rentrer tôt ou de recevoir peu "
                    ."d'amis. Les associations reconnaissent qu'environ une colocation sur cinq s'arrête avant la fin de l'année. "
                    ."Mais elles remarquent aussi que beaucoup de binômes restent en contact bien après. Inès, elle, a déjà décidé de "
                    ."rester une deuxième année. « Au début, je venais pour le prix, raconte-t-elle. Aujourd'hui, je reste pour "
                    ."Germaine. »",
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'mcq',
                    'text' => "Pourquoi Inès a-t-elle cherché une autre solution qu'un studio ?",
                    'options' => [
                        "Elle ne voulait pas vivre seule.",
                        "Les studios du centre étaient trop chers pour elle.",
                        "Sa faculté l'obligeait à vivre chez une personne âgée.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « elle ne pourrait pas payer un studio : les petits logements du centre se louent plus de 600 euros par mois ». La faculté n'oblige à rien : une simple affiche l'a informée.",
                ],
                [
                    'id' => 'q2', 'type' => 'mcq',
                    'text' => "Comment Inès a-t-elle découvert cette solution ?",
                    'options' => [
                        "Grâce à la famille de Germaine.",
                        "Grâce à une amie infirmière.",
                        "Grâce à une affiche à la faculté.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. « C'est une affiche, à la faculté, qui l'a mise sur la piste ». « Mettre sur la piste » veut dire donner l'idée, orienter vers une solution.",
                ],
                [
                    'id' => 'q3', 'type' => 'mcq',
                    'text' => "Combien Inès paie-t-elle chaque mois ?",
                    'options' => [
                        "80 euros de charges.",
                        "Rien du tout.",
                        "Plus de 600 euros.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. « Inès ne paie pas de loyer ; elle participe seulement aux charges, 80 euros par mois ». Pas de loyer ne veut pas dire rien : les charges (eau, chauffage…) sont payées. 600 euros, c'est le prix d'un studio.",
                ],
                [
                    'id' => 'q4', 'type' => 'mcq',
                    'text' => "En échange, Inès s'engage à…",
                    'options' => [
                        "faire le ménage chez Germaine.",
                        "donner des soins à Germaine.",
                        "être présente le soir et dîner avec Germaine plusieurs fois par semaine.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. « elle s'engage à être présente le soir, à dîner avec Germaine trois fois par semaine et à l'aider pour les courses ». Elle le dit elle-même : « Je ne fais pas de soins, je ne fais pas le ménage ».",
                ],
                [
                    'id' => 'q5', 'type' => 'mcq',
                    'text' => "Comment Inès voit-elle son rôle ?",
                    'options' => [
                        "Comme une présence, pas comme un travail.",
                        "Comme celui d'une aide à domicile.",
                        "Comme un stage pour ses études d'infirmière.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. « Ce n'est pas un travail d'aide à domicile […]. Je suis là, c'est tout. » Le lien avec ses études d'infirmière est tentant, mais elle précise justement qu'elle ne fait pas de soins.",
                ],
                [
                    'id' => 'q6', 'type' => 'mcq',
                    'text' => "Pourquoi ce type de colocation se développe-t-il aujourd'hui ?",
                    'options' => [
                        "Parce qu'une nouvelle loi l'encourage.",
                        "Parce que les loyers augmentent dans les grandes villes.",
                        "Parce qu'il vient d'être inventé.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « il se développe surtout depuis que les loyers augmentent dans les grandes villes ». Il « existe en France depuis une vingtaine d'années » : ce n'est pas une nouveauté (contre C).",
                ],
                [
                    'id' => 'q7', 'type' => 'mcq',
                    'text' => "Quel est le rôle des associations ?",
                    'options' => [
                        "Elles mettent en relation les deux personnes et fixent des règles.",
                        "Elles paient une partie du loyer.",
                        "Elles choisissent l'appartement de l'étudiant.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. Elles « mettent en relation les deux générations », vérifient les attentes et « proposent un contrat qui fixe les règles de vie ». L'article ne parle d'aucune aide financière.",
                ],
                [
                    'id' => 'q8', 'type' => 'mcq',
                    'text' => "Selon Paul Mercadier, qu'est-ce qui compte le plus pour qu'une colocation réussisse ?",
                    'options' => [
                        "Que les deux personnes soient sympathiques.",
                        "Que leurs habitudes de vie soient compatibles.",
                        "Que l'étudiant fasse des études de santé.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. Son exemple — se coucher à 21 heures face à un batteur — montre que des rythmes incompatibles font échouer la colocation, « même s'ils sont tous les deux très sympathiques ». La sympathie ne suffit donc pas (contre A).",
                ],
                [
                    'id' => 'q9', 'type' => 'mcq',
                    'text' => "Pour les personnes âgées, quel est le principal avantage ?",
                    'options' => [
                        "Gagner de l'argent grâce au loyer.",
                        "Recevoir de l'aide pour le ménage.",
                        "Rassurer leur famille et rester plus longtemps chez elles.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. « l'avantage n'est pas d'abord financier » : « Mes enfants dorment mieux », et la présence d'un jeune « permet souvent de retarder » le départ en maison de retraite.",
                ],
                [
                    'id' => 'q10', 'type' => 'mcq',
                    'text' => "Que reconnaissent les associations ?",
                    'options' => [
                        "Que toutes les colocations réussissent.",
                        "Qu'environ une colocation sur cinq s'arrête avant la fin de l'année.",
                        "Que la moitié des étudiants abandonnent.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « environ une colocation sur cinq s'arrête avant la fin de l'année », soit 20 %, loin de la moitié. « Tout n'est pas toujours simple » écarte A.",
                ],
                [
                    'id' => 'q11', 'type' => 'mcq',
                    'text' => "Pourquoi Inès reste-t-elle une deuxième année ?",
                    'options' => [
                        "Pour le prix, qui reste très bas.",
                        "Parce qu'elle n'a pas trouvé d'autre logement.",
                        "Pour la relation qu'elle a créée avec Germaine.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. « Au début, je venais pour le prix […]. Aujourd'hui, je reste pour Germaine. » L'opposition « Au début / Aujourd'hui » montre que sa raison a changé.",
                ],
            ],
        ],
    ],
];
