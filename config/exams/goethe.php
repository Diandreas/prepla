<?php

/**
 * Goethe-Zertifikat - Goethe-Institut
 * Source: goethe.de
 *
 * Level-based: A1 (Start Deutsch 1) through C2 (Großes Deutsches Sprachdiplom)
 * CEFR: Direct mapping A1–C2
 *
 * Reussite : la regle des 60 % par module ne vaut qu'a partir de B1, ou les
 * quatre modules se passent et se repassent separement. Elle est fausse en bas
 * de l'echelle, et le `pass_mark` ci-dessous reste une approximation :
 *  - A1 : 60 points sur 100 au total, sans minimum par module ;
 *  - A2 : au moins 45 points sur 75 pour Lesen + Horen + Schreiben reunis,
 *    ET au moins 15 sur 25 pour Sprechen.
 */

return [
    'slug' => 'goethe',
    'name' => 'Goethe-Zertifikat',
    'language' => 'german',

    'scoring' => [
        'type' => 'level',
        'total' => 100,
        'pass_mark' => 60,
        'section_max' => 25,
        'overall_calculation' => 'sum',
    ],

    'variants' => null,

    // ─── Structure par niveau ───
    'levels' => [
        // ━━━━━━━━━━ A1: Start Deutsch 1 ━━━━━━━━━━
        'A1' => [
            'name' => 'Goethe-Zertifikat A1: Start Deutsch 1',
            'total_duration' => 80,
            'sections' => [
                [
                    'slug' => 'lesen',
                    // Nombre de taches a l'examen reel (source : goethe.de).
                    'task_count' => 3,
                    'name' => 'Lesen (Reading)',
                    'skill_type' => 'reading',
                    'time_limit' => 25,
                    'scoring_weight' => 25,
                    'description' => 'Lire et comprendre des textes très courts : annonces, panneaux, formulaires simples.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Lesen, Teil 1', 'name_fr' => 'Messages courts',
                                "task" => "Deux courriels ou messages courts : dire pour chacune des cinq affirmations si elle est juste ou fausse.",
                                'type' => 'true-false', 'items' => 5, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Lesen, Teil 2', 'name_fr' => 'Petites annonces',
                                "task" => "Cinq petites annonces ou pages web présentées par paires : choisir laquelle des deux, a ou b, répond à la situation décrite.",
                                'type' => 'mcq', 'items' => 5, 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Lesen, Teil 3', 'name_fr' => 'Panneaux et consignes',
                                "task" => "Cinq panneaux, écriteaux et notes de la vie courante : dire pour chacun si l'affirmation proposée est juste ou fausse.",
                                'type' => 'true-false', 'items' => 5, 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['mcq', 'true-false-not-given', 'matching', 'true-false', 'true-false'],
                ],
                [
                    'slug' => 'hoeren',
                    // Nombre de taches a l'examen reel (source : goethe.de).
                    'task_count' => 3,
                    'name' => 'Hören (Listening)',
                    'skill_type' => 'listening',
                    'time_limit' => 20,
                    'scoring_weight' => 25,
                    'description' => 'Comprendre des dialogues et annonces courtes de la vie quotidienne.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Hören, Teil 1', 'name_fr' => 'Courts échanges',
                                "task" => "Six courts enregistrements du quotidien, écoutés une seule fois : choisir pour chacun la bonne réponse parmi trois, aidé par une image.",
                                'type' => 'picture-mcq', 'items' => 6, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Hören, Teil 2', 'name_fr' => 'Annonces publiques',
                                "task" => "Quatre annonces publiques (gare, aéroport, magasin), écoutées une seule fois : dire si l'affirmation proposée est juste ou fausse.",
                                'type' => 'true-false', 'items' => 4, 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Hören, Teil 3', 'name_fr' => 'Messages téléphoniques',
                                "task" => "Cinq messages téléphoniques ou annonces, écoutés deux fois : choisir la bonne réponse parmi trois.",
                                'type' => 'mcq', 'items' => 5, 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['mcq', 'true-false-not-given', 'picture-mcq', 'true-false'],
                ],
                [
                    'slug' => 'schreiben',
                    // Nombre de taches a l'examen reel (source : goethe.de).
                    'task_count' => 2,
                    'name' => 'Schreiben (Writing)',
                    'skill_type' => 'writing',
                    'time_limit' => 20,
                    'scoring_weight' => 25,
                    'description' => 'Remplir un formulaire et écrire un court message personnel.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Schreiben, Teil 1', 'name_fr' => 'Remplir un formulaire',
                                "task" => "Compléter cinq champs manquants d'un formulaire d'inscription à partir d'un texte de situation.",
                                'type' => 'form-completion', 'items' => 5, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Schreiben, Teil 2', 'name_fr' => 'Message court',
                                "task" => "Écrire un message court d'environ 30 mots répondant aux trois points imposés par la consigne.",
                                'type' => 'short-writing', 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['form-completion', 'short-writing'],
                ],
                [
                    'slug' => 'sprechen',
                    // Nombre de taches a l'examen reel (source : goethe.de).
                    'task_count' => 3,
                    'name' => 'Sprechen (Speaking)',
                    'skill_type' => 'speaking',
                    'time_limit' => 15,
                    'scoring_weight' => 25,
                    'description' => 'Se présenter, poser des questions simples et y répondre.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Sprechen, Teil 1', 'name_fr' => 'Se présenter',
                                "task" => "Se présenter à partir de mots-clés : nom, âge, pays, langues.",
                                'type' => 'speaking-response', 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Sprechen, Teil 2', 'name_fr' => 'Demander une information',
                                "task" => "Poser une question et y répondre, à partir d'une carte de mots.",
                                'type' => 'role-play', 'source' => 'tache'],
                        ['part' => 3, 'name' => 'Sprechen, Teil 3', 'name_fr' => 'Formuler une demande',
                                "task" => "Formuler une demande à partir d'une carte-image, et réagir à celle du partenaire.",
                                'type' => 'role-play', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['speaking-response', 'role-play'],
                ],
            ],
        ],

        // ━━━━━━━━━━ A2 ━━━━━━━━━━
        'A2' => [
            'name' => 'Goethe-Zertifikat A2',
            'total_duration' => 105,
            'sections' => [
                [
                    'slug' => 'lesen',
                    // Nombre de taches a l'examen reel (source : goethe.de).
                    'task_count' => 4,
                    'name' => 'Lesen (Reading)',
                    'skill_type' => 'reading',
                    'time_limit' => 30,
                    'scoring_weight' => 25,
                    'description' => 'Comprendre des textes courts : annonces, emails, instructions simples.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Lesen, Teil 1', 'name_fr' => 'Courriel ou message',
                                "task" => "Un courriel ou un article court : choisir pour chacune des cinq questions la bonne réponse parmi trois.",
                                'type' => 'mcq', 'items' => 5, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Lesen, Teil 2', 'name_fr' => 'Petites annonces',
                                "task" => "Cinq situations à résoudre à l'aide de tableaux d'information (horaires, programmes, panneaux) : choisir la bonne réponse parmi trois.",
                                'type' => 'mcq', 'items' => 5, 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Lesen, Teil 3', 'name_fr' => 'Texte informatif',
                                "task" => "Correspondance de la vie courante — courriels, lettres, messages : choisir pour chacune des cinq questions la bonne réponse parmi trois.",
                                'type' => 'mcq', 'items' => 5, 'source' => 'officiel'],
                        ['part' => 4, 'name' => 'Lesen, Teil 4', 'name_fr' => 'Opinions',
                                "task" => "Cinq personnes cherchent quelque chose : associer chacune à l'une des petites annonces proposées. Toutes ne trouvent pas.",
                                'type' => 'matching', 'items' => 5, 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['mcq', 'true-false-not-given', 'matching', 'gap-fill', 'true-false'],
                ],
                [
                    'slug' => 'hoeren',
                    // Nombre de taches a l'examen reel (source : goethe.de).
                    'task_count' => 4,
                    'name' => 'Hören (Listening)',
                    'skill_type' => 'listening',
                    'time_limit' => 30,
                    'scoring_weight' => 25,
                    'description' => 'Comprendre des annonces, conversations téléphoniques et dialogues courants.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Hören, Teil 1', 'name_fr' => 'Messages courts',
                                "task" => "Cinq courts enregistrements du quotidien, écoutés deux fois : choisir la bonne réponse parmi trois.",
                                'type' => 'mcq', 'items' => 5, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Hören, Teil 2', 'name_fr' => 'Conversation',
                                "task" => "Un seul enregistrement, écouté une fois : associer les cinq éléments entendus aux images correspondantes.",
                                'type' => 'matching', 'items' => 5, 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Hören, Teil 3', 'name_fr' => 'Échanges brefs',
                                "task" => "Cinq conversations brèves, écoutées une seule fois : choisir la bonne réponse parmi trois.",
                                'type' => 'mcq', 'items' => 5, 'source' => 'officiel'],
                        ['part' => 4, 'name' => 'Hören, Teil 4', 'name_fr' => 'Entretien',
                                "task" => "Un entretien radiophonique, écouté deux fois : répondre par oui ou non à chacune des cinq affirmations.",
                                'type' => 'true-false', 'items' => 5, 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['mcq', 'true-false-not-given', 'picture-mcq', 'multiple-matching', 'true-false', 'matching', 'true-false'],
                ],
                [
                    'slug' => 'schreiben',
                    // Nombre de taches a l'examen reel (source : goethe.de).
                    'task_count' => 2,
                    'name' => 'Schreiben (Writing)',
                    'skill_type' => 'writing',
                    'time_limit' => 30,
                    'scoring_weight' => 25,
                    'description' => 'Écrire un message court (SMS, email, note) en réponse à une situation.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Schreiben, Teil 1', 'name_fr' => 'Message bref',
                                "task" => "Rédiger un message court en traitant les points imposés.",
                                'type' => 'short-writing', 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Schreiben, Teil 2', 'name_fr' => 'Courriel',
                                "task" => "Rédiger un courriel simple adapté à la situation.",
                                'type' => 'letter-email-writing', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['short-writing', 'essay', 'letter-email-writing'],
                ],
                [
                    'slug' => 'sprechen',
                    // Nombre de taches a l'examen reel (source : goethe.de).
                    'task_count' => 3,
                    'name' => 'Sprechen (Speaking)',
                    'skill_type' => 'speaking',
                    'time_limit' => 15,
                    'scoring_weight' => 25,
                    'description' => 'Se présenter, parler de sa vie quotidienne et planifier quelque chose ensemble.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Sprechen, Teil 1', 'name_fr' => 'Poser des questions',
                                "task" => "Poser des questions sur son partenaire et répondre aux siennes.",
                                'type' => 'speaking-elicitation', 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Sprechen, Teil 2', 'name_fr' => 'Parler de soi',
                                "task" => "Parler de son quotidien à partir d'une carte-thème.",
                                'type' => 'speaking-response', 'source' => 'tache'],
                        ['part' => 3, 'name' => 'Sprechen, Teil 3', 'name_fr' => 'Organiser ensemble',
                                "task" => "Se mettre d'accord avec son partenaire sur une activité commune.",
                                'type' => 'negotiation', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['speaking-response', 'negotiation', 'speaking-discussion', 'speaking-elicitation'],
                ],
            ],
        ],

        // ━━━━━━━━━━ B1 ━━━━━━━━━━
        'B1' => [
            'name' => 'Goethe-Zertifikat B1',
            'total_duration' => 180,
            'sections' => [
                [
                    'slug' => 'lesen',
                    // Nombre de taches a l'examen reel (cf. description).
                    'task_count' => 5,
                    'name' => 'Lesen (Reading)',
                    'skill_type' => 'reading',
                    'time_limit' => 65,
                    'scoring_weight' => 25,
                    'description' => '5 parties : blog, article de presse, petites annonces, commentaires de lecteurs, règlement.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Lesen, Teil 1', 'name_fr' => 'Blog ou courriel',
                                "task" => "Un texte personnel : juger des affirmations vraies ou fausses.",
                                'type' => 'true-false', 'items' => 6, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Lesen, Teil 2', 'name_fr' => 'Article de presse',
                                "task" => "Un article, puis des questions à choix multiple.",
                                'type' => 'mcq', 'items' => 6, 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Lesen, Teil 3', 'name_fr' => 'Annonces',
                                "task" => "Associer chaque situation à l'annonce qui y répond.",
                                'type' => 'matching', 'items' => 7, 'source' => 'officiel'],
                        ['part' => 4, 'name' => 'Lesen, Teil 4', 'name_fr' => 'Opinions de lecteurs',
                                // Ja/Nein, pas un appariement : le Modellsatz officiel demande,
                                // pour chacun des sept courriers de lecteurs, si la personne est
                                // pour ou contre. On entrainait les apprenants au mauvais format.
                                "task" => "Sept courriers de lecteurs sur un même sujet : dire pour chacun si la personne est pour ou contre.",
                                'type' => 'true-false', 'items' => 7, 'source' => 'officiel'],
                        ['part' => 5, 'name' => 'Lesen, Teil 5', 'name_fr' => 'Règlement',
                                "task" => "Un règlement ou une consigne écrite, puis des questions à choix multiple.",
                                'type' => 'mcq', 'items' => 4, 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['mcq', 'true-false-not-given', 'matching', 'matching-headings', 'gap-fill', 'true-false'],
                ],
                [
                    'slug' => 'hoeren',
                    // Nombre de taches a l'examen reel (cf. description).
                    'task_count' => 4,
                    'name' => 'Hören (Listening)',
                    'skill_type' => 'listening',
                    'time_limit' => 40,
                    'question_count' => 30,
                    'scoring_weight' => 25,
                    'description' => '4 parties : annonces (matching 10 items), MCQ (5 items), vrai/faux (7 items), vrai/faux (8 items).',
                    'parts' => [
                        ['part' => 1, 'name' => 'Hören, Teil 1', 'name_fr' => 'Messages et annonces',
                                "task" => "De courts messages : juger des affirmations et choisir la bonne réponse.",
                                'type' => 'true-false', 'items' => 10, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Hören, Teil 2', 'name_fr' => 'Exposé',
                                "task" => "Une présentation suivie, puis des questions à choix multiple.",
                                'type' => 'mcq', 'items' => 5, 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Hören, Teil 3', 'name_fr' => 'Conversation',
                                "task" => "Une conversation du quotidien : juger des affirmations vraies ou fausses.",
                                'type' => 'true-false', 'items' => 7, 'source' => 'officiel'],
                        ['part' => 4, 'name' => 'Hören, Teil 4', 'name_fr' => 'Débat radio',
                                "task" => "Une discussion entre plusieurs personnes : dire qui exprime quoi.",
                                'type' => 'matching', 'items' => 8, 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['mcq', 'true-false-not-given', 'matching', 'note-completion', 'true-false', 'true-false'],
                ],
                [
                    'slug' => 'schreiben',
                    // Nombre de taches a l'examen reel (cf. description).
                    'task_count' => 3,
                    'name' => 'Schreiben (Writing)',
                    'skill_type' => 'writing',
                    'time_limit' => 60,
                    'scoring_weight' => 25,
                    'description' => '3 tâches : email/lettre semi-formelle (~80 mots), prise de position (~80 mots), email personnel.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Schreiben, Teil 1', 'name_fr' => 'Courriel personnel',
                                "task" => "Rédiger un courriel à un proche en traitant les points imposés.",
                                'type' => 'letter-email-writing', 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Schreiben, Teil 2', 'name_fr' => 'Prise de position',
                                "task" => "Écrire une contribution à une discussion en ligne.",
                                'type' => 'short-writing', 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Schreiben, Teil 3', 'name_fr' => 'Courriel formel',
                                "task" => "Rédiger un message poli et formel, par exemple une excuse ou une demande.",
                                'type' => 'letter-writing', 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['essay', 'letter-writing', 'letter-email-writing', 'short-writing'],
                ],
                [
                    'slug' => 'sprechen',
                    // Source : goethe.de, Durchfuehrungsbestimmungen officiels (2026-10-10).
                    'task_count' => 3,
                    'name' => 'Sprechen (Speaking)',
                    'skill_type' => 'speaking',
                    'time_limit' => 15,
                    'scoring_weight' => 25,
                    'description' => '3 parties : planifier ensemble, présenter un thème, discuter et négocier.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Sprechen, Teil 1', 'name_fr' => 'Planifier ensemble',
                                "task" => "Organiser une activité commune avec son partenaire et se mettre d'accord.",
                                'type' => 'negotiation', 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Sprechen, Teil 2', 'name_fr' => 'Présentation',
                                "task" => "Présenter un thème préparé, de manière structurée.",
                                'type' => 'speaking-long-turn', 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Sprechen, Teil 3', 'name_fr' => 'Réagir à la présentation',
                                "task" => "Réagir à l'exposé du partenaire et répondre à ses questions.",
                                'type' => 'speaking-discussion', 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['speaking-response', 'speaking-long-turn', 'negotiation', 'speaking-discussion'],
                ],
            ],
        ],

        // ━━━━━━━━━━ B2 ━━━━━━━━━━
        'B2' => [
            'name' => 'Goethe-Zertifikat B2',
            'total_duration' => 195,
            'sections' => [
                [
                    'slug' => 'lesen',
                    // Nombre de taches a l'examen reel (cf. description).
                    'task_count' => 5,
                    'name' => 'Lesen (Reading)',
                    'skill_type' => 'reading',
                    'time_limit' => 65,
                    'scoring_weight' => 25,
                    // Source : goethe.de, Modellsatz Erwachsene B2 (Vs1.5), tableau « im Ueberblick » + Antwortbogen (30 items).
                    'description' => '5 parties, 30 items : forum (9), phrases à replacer (6), article à choix multiple (6), opinions et titres (6), règlement (3).',
                    'parts' => [
                        ['part' => 1, 'name' => 'Lesen, Teil 1', 'name_fr' => 'Forum à quatre voix',
                                "task" => "Quatre contributions de forum : attribuer chacune des 9 affirmations à son auteur.",
                                'type' => 'matching', 'items' => 9, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Lesen, Teil 2', 'name_fr' => 'Phrases à replacer',
                                "task" => "Un article de magazine à trous : replacer 6 phrases, deux propositions sont en trop.",
                                'type' => 'gapped-text', 'items' => 6, 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Lesen, Teil 3', 'name_fr' => 'Article de presse',
                                "task" => "Un article de journal, puis 6 questions à choix multiple à trois options.",
                                'type' => 'mcq', 'items' => 6, 'source' => 'officiel'],
                        ['part' => 4, 'name' => 'Lesen, Teil 4', 'name_fr' => 'Opinions et titres',
                                "task" => "Six avis sur un même sujet : associer à chacun le titre qui lui convient.",
                                'type' => 'matching-headings', 'items' => 6, 'source' => 'officiel'],
                        ['part' => 5, 'name' => 'Lesen, Teil 5', 'name_fr' => 'Texte réglementaire',
                                "task" => "Un règlement d'études : associer les intitulés du sommaire aux 3 paragraphes.",
                                'type' => 'matching', 'items' => 3, 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['mcq', 'true-false-not-given', 'matching', 'matching-headings', 'gap-fill', 'sentence-completion', 'true-false', 'gapped-text'],
                ],
                [
                    'slug' => 'hoeren',
                    // Nombre de taches a l'examen reel (cf. description).
                    'task_count' => 4,
                    'name' => 'Hören (Listening)',
                    'skill_type' => 'listening',
                    'time_limit' => 40,
                    'scoring_weight' => 25,
                    // Source : goethe.de, Modellsatz Erwachsene B2 (Vs1.5), tableau « im Ueberblick » + Antwortbogen (30 items).
                    'description' => '4 parties, 30 items : cinq courts dialogues (10), interview radio (6), discussion à plusieurs (6), exposé (8).',
                    'parts' => [
                        ['part' => 1, 'name' => 'Hören, Teil 1', 'name_fr' => 'Cinq courts dialogues',
                                "task" => "Cinq extraits entendus une fois : pour chacun, une affirmation vrai/faux puis une question à choix multiple (10 items).",
                                'type' => 'true-false', 'items' => 10, 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Hören, Teil 2', 'name_fr' => 'Interview radio',
                                "task" => "Une interview entendue deux fois, puis 6 questions à choix multiple.",
                                'type' => 'mcq', 'items' => 6, 'source' => 'officiel'],
                        ['part' => 3, 'name' => 'Hören, Teil 3', 'name_fr' => 'Discussion à plusieurs',
                                "task" => "Une discussion radio entendue une fois : pour chacune des 6 affirmations, dire qui la dit.",
                                'type' => 'matching', 'items' => 6, 'source' => 'officiel'],
                        ['part' => 4, 'name' => 'Hören, Teil 4', 'name_fr' => 'Exposé',
                                "task" => "Un court exposé entendu deux fois, puis 8 questions à choix multiple.",
                                'type' => 'mcq', 'items' => 8, 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['mcq', 'true-false-not-given', 'matching', 'note-completion', 'true-false'],
                ],
                [
                    'slug' => 'schreiben',
                    // Nombre de taches a l'examen reel (cf. description).
                    'task_count' => 2,
                    'name' => 'Schreiben (Writing)',
                    'skill_type' => 'writing',
                    'time_limit' => 75,
                    'scoring_weight' => 25,
                    // Source : goethe.de, Modellsatz Erwachsene B2 (Vs1.5) : Teil 1 50 min / 150 mots, Teil 2 25 min / 100 mots.
                    'description' => '2 tâches : contribution de forum argumentée (circa 150 mots, 50 min) + message à son supérieur (circa 100 mots, 25 min).',
                    'parts' => [
                        ['part' => 1, 'name' => 'Schreiben, Teil 1', 'name_fr' => 'Contribution de forum',
                                "task" => "Écrire une contribution de forum d'environ 150 mots : donner son avis, expliquer, proposer, peser le pour et le contre.",
                                'type' => 'essay', 'source' => 'officiel'],
                        ['part' => 2, 'name' => 'Schreiben, Teil 2', 'name_fr' => 'Message professionnel',
                                "task" => "Rédiger un message d'environ 100 mots à son supérieur : expliquer, décrire, proposer, demander.",
                                'type' => 'letter-email-writing', 'source' => 'officiel'],
                    ],
                    'exercise_types' => ['essay', 'letter-writing', 'letter-email-writing'],
                    // Source : goethe.de, Modellsatz B2 « Bewertungskriterien Schreiben » (4 criteres).
                    'rubric' => [
                        'criteria' => [
                            ['name' => 'Aufgabenerfüllung', 'slug' => 'task-fulfilment', 'max' => 25],
                            ['name' => 'Kohärenz', 'slug' => 'coherence', 'max' => 25],
                            ['name' => 'Wortschatz', 'slug' => 'vocabulary', 'max' => 25],
                            ['name' => 'Strukturen', 'slug' => 'structures', 'max' => 25],
                        ],
                    ],
                ],
                [
                    'slug' => 'sprechen',
                    // Source : goethe.de, Durchfuehrungsbestimmungen officiels (2026-10-10).
                    'task_count' => 2,
                    'name' => 'Sprechen (Speaking)',
                    'skill_type' => 'speaking',
                    'time_limit' => 15,
                    'scoring_weight' => 25,
                    'description' => '2 parties : présentation structurée + discussion et négociation.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Sprechen, Teil 1', 'name_fr' => 'Exposé',
                                "task" => "Présenter un thème de manière structurée, puis répondre aux questions.",
                                'type' => 'speaking-long-turn', 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Sprechen, Teil 2', 'name_fr' => 'Discussion',
                                "task" => "Discuter du thème avec son partenaire et défendre son point de vue.",
                                'type' => 'speaking-discussion', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['speaking-long-turn', 'speaking-discussion'],
                ],
            ],
        ],

        // ━━━━━━━━━━ C1 ━━━━━━━━━━
        'C1' => [
            'name' => 'Goethe-Zertifikat C1',
            'total_duration' => 200,
            'sections' => [
                [
                    'slug' => 'lesen',
                    // Nombre de taches a l'examen reel (source : goethe.de).
                    'task_count' => 4,
                    'name' => 'Lesen (Reading)',
                    'skill_type' => 'reading',
                    'time_limit' => 65,
                    'scoring_weight' => 25,
                    'description' => 'Textes complexes : articles scientifiques, commentaires, textes littéraires, résumés.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Lesen, Teil 1', 'name_fr' => 'Texte lacunaire',
                                "task" => "Replacer dans un texte les passages qui en ont été retirés.",
                                'type' => 'gapped-text', 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Lesen, Teil 2', 'name_fr' => 'Article de fond',
                                "task" => "Un texte long et exigeant, puis des questions à choix multiple.",
                                'type' => 'mcq', 'source' => 'tache'],
                        ['part' => 3, 'name' => 'Lesen, Teil 3', 'name_fr' => 'Opinions croisées',
                                "task" => "Associer des affirmations aux textes qui les portent.",
                                'type' => 'matching', 'source' => 'tache'],
                        ['part' => 4, 'name' => 'Lesen, Teil 4', 'name_fr' => 'Texte à compléter',
                                "task" => "Compléter un texte sans proposition : un mot par trou.",
                                'type' => 'open-cloze', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['mcq', 'true-false-not-given', 'matching', 'matching-headings', 'gap-fill', 'sentence-completion', 'short-answer', 'gapped-text', 'open-cloze'],
                ],
                [
                    'slug' => 'hoeren',
                    // Nombre de taches a l'examen reel (source : goethe.de).
                    'task_count' => 4,
                    'name' => 'Hören (Listening)',
                    'skill_type' => 'listening',
                    'time_limit' => 40,
                    'scoring_weight' => 25,
                    'description' => 'Conférences, débats, interviews radio et conversations complexes.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Hören, Teil 1', 'name_fr' => 'Conversation',
                                "task" => "Une conversation entre deux personnes : compléter des notes.",
                                'type' => 'note-completion', 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Hören, Teil 2', 'name_fr' => 'Entretien',
                                "task" => "Un entretien long, puis des questions à choix multiple.",
                                'type' => 'mcq', 'source' => 'tache'],
                        ['part' => 3, 'name' => 'Hören, Teil 3', 'name_fr' => 'Exposé',
                                "task" => "Un exposé universitaire : compléter des notes.",
                                'type' => 'note-completion', 'source' => 'tache'],
                        ['part' => 4, 'name' => 'Hören, Teil 4', 'name_fr' => 'Débat',
                                "task" => "Une discussion à plusieurs voix : dire qui exprime quoi.",
                                'type' => 'matching', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['mcq', 'true-false-not-given', 'matching', 'note-completion', 'sentence-completion'],
                ],
                [
                    'slug' => 'schreiben',
                    // Nombre de taches a l'examen reel (cf. description).
                    'task_count' => 2,
                    'name' => 'Schreiben (Writing)',
                    'skill_type' => 'writing',
                    'time_limit' => 75,
                    'scoring_weight' => 25,
                    'description' => '2 tâches : rédaction structurée (essai argumentatif) + lettre formelle.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Schreiben, Teil 1', 'name_fr' => 'Texte argumenté',
                                "task" => "Rédiger un texte structuré à partir d'un document et de données chiffrées.",
                                'type' => 'integrated-writing', 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Schreiben, Teil 2', 'name_fr' => 'Réécriture formelle',
                                "task" => "Réécrire un texte dans un registre formel adapté au destinataire.",
                                'type' => 'guided-rewrite', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['essay', 'letter-writing', 'integrated-writing', 'guided-rewrite'],
                    'rubric' => [
                        'criteria' => [
                            ['name' => 'Inhalt (Content)', 'slug' => 'content', 'max' => 25],
                            ['name' => 'Textaufbau (Structure)', 'slug' => 'text-structure', 'max' => 25],
                            ['name' => 'Ausdruck (Expression)', 'slug' => 'expression', 'max' => 25],
                            ['name' => 'Korrektheit (Accuracy)', 'slug' => 'accuracy', 'max' => 25],
                        ],
                    ],
                ],
                [
                    'slug' => 'sprechen',
                    // Nombre de taches a l'examen reel (source : goethe.de).
                    'task_count' => 2,
                    'name' => 'Sprechen (Speaking)',
                    'skill_type' => 'speaking',
                    'time_limit' => 20,
                    'scoring_weight' => 25,
                    'description' => '2 parties : présentation argumentée + discussion.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Sprechen, Teil 1', 'name_fr' => 'Exposé',
                                "task" => "Présenter un sujet de manière structurée, à partir d'un support.",
                                'type' => 'speaking-long-turn', 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Sprechen, Teil 2', 'name_fr' => 'Discussion',
                                "task" => "Discuter avec son partenaire et aboutir à une position commune.",
                                'type' => 'speaking-discussion', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['speaking-long-turn', 'speaking-discussion'],
                ],
            ],
        ],

        // ━━━━━━━━━━ C2: Großes Deutsches Sprachdiplom ━━━━━━━━━━
        'C2' => [
            'name' => 'Goethe-Zertifikat C2: GDS',
            'total_duration' => 210,
            'sections' => [
                [
                    'slug' => 'lesen',
                    // Nombre de taches a l'examen reel (source : goethe.de).
                    'task_count' => 4,
                    'name' => 'Lesen (Reading)',
                    'skill_type' => 'reading',
                    'time_limit' => 80,
                    'scoring_weight' => 25,
                    'description' => 'Textes longs et complexes : littérature, science, philosophie. Analyse fine et inférence.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Lesen, Teil 1', 'name_fr' => 'Opinions croisées',
                                "task" => "Associer des affirmations aux textes qui les portent.",
                                'type' => 'matching', 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Lesen, Teil 2', 'name_fr' => 'Texte littéraire ou essai',
                                "task" => "Un texte exigeant, puis des questions à choix multiple.",
                                'type' => 'mcq', 'source' => 'tache'],
                        ['part' => 3, 'name' => 'Lesen, Teil 3', 'name_fr' => 'Texte lacunaire',
                                "task" => "Replacer dans un texte les passages qui en ont été retirés.",
                                'type' => 'gapped-text', 'source' => 'tache'],
                        ['part' => 4, 'name' => 'Lesen, Teil 4', 'name_fr' => 'Texte à compléter',
                                "task" => "Compléter un texte sans proposition : un mot par trou.",
                                'type' => 'open-cloze', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['mcq', 'true-false-not-given', 'matching', 'matching-headings', 'gap-fill', 'sentence-completion', 'short-answer', 'gapped-text', 'open-cloze'],
                ],
                [
                    'slug' => 'hoeren',
                    // Nombre de taches a l'examen reel (source : goethe.de).
                    'task_count' => 3,
                    'name' => 'Hören (Listening)',
                    'skill_type' => 'listening',
                    'time_limit' => 35,
                    'scoring_weight' => 25,
                    'description' => 'Discours académiques, débats radiophoniques, communications authentiques complexes.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Hören, Teil 1', 'name_fr' => 'Entretien',
                                "task" => "Un entretien exigeant : compléter des notes.",
                                'type' => 'note-completion', 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Hören, Teil 2', 'name_fr' => 'Exposé',
                                "task" => "Un exposé long, puis des questions à choix multiple.",
                                'type' => 'mcq', 'source' => 'tache'],
                        ['part' => 3, 'name' => 'Hören, Teil 3', 'name_fr' => 'Débat',
                                "task" => "Une discussion à plusieurs voix : dire qui exprime quoi.",
                                'type' => 'matching', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['mcq', 'true-false-not-given', 'note-completion', 'short-answer', 'matching'],
                ],
                [
                    'slug' => 'schreiben',
                    // Nombre de taches a l'examen reel (cf. description).
                    'task_count' => 2,
                    'name' => 'Schreiben (Writing)',
                    'skill_type' => 'writing',
                    'time_limit' => 80,
                    'scoring_weight' => 25,
                    'description' => '2 tâches : texte argumentatif complexe + réécriture/correction de texte.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Schreiben, Teil 1', 'name_fr' => 'Texte argumenté',
                                "task" => "Rédiger un texte long, structuré et nuancé, à partir d'un document source.",
                                'type' => 'integrated-writing', 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Schreiben, Teil 2', 'name_fr' => 'Réécriture',
                                "task" => "Réécrire un texte en adaptant registre et destinataire.",
                                'type' => 'guided-rewrite', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['essay', 'summary-writing', 'guided-rewrite', 'integrated-writing'],
                    'rubric' => [
                        'criteria' => [
                            ['name' => 'Inhalt (Content)', 'slug' => 'content', 'max' => 25],
                            ['name' => 'Textaufbau (Structure)', 'slug' => 'text-structure', 'max' => 25],
                            ['name' => 'Ausdruck (Expression)', 'slug' => 'expression', 'max' => 25],
                            ['name' => 'Korrektheit (Accuracy)', 'slug' => 'accuracy', 'max' => 25],
                        ],
                    ],
                ],
                [
                    'slug' => 'sprechen',
                    // Nombre de taches a l'examen reel (source : goethe.de).
                    'task_count' => 2,
                    'name' => 'Sprechen (Speaking)',
                    'skill_type' => 'speaking',
                    'time_limit' => 15,
                    'scoring_weight' => 25,
                    'description' => '2 parties : présentation élaborée + débat et négociation.',
                    'parts' => [
                        ['part' => 1, 'name' => 'Sprechen, Teil 1', 'name_fr' => 'Exposé',
                                "task" => "Présenter un sujet exigeant de manière structurée et nuancée.",
                                'type' => 'speaking-long-turn', 'source' => 'tache'],
                        ['part' => 2, 'name' => 'Sprechen, Teil 2', 'name_fr' => 'Débat',
                                "task" => "Défendre un point de vue face à son interlocuteur.",
                                'type' => 'oral-debate', 'source' => 'tache'],
                    ],
                    'exercise_types' => ['speaking-long-turn', 'speaking-discussion', 'oral-debate'],
                ],
            ],
        ],
    ],
];
