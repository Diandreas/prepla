<?php

return [
    'A1' => [
        'mcq' => [
            'title' => 'Anglais A1 · Un matin au café',
            'description' => 'Entraînement général à la lecture de phrases du quotidien, indépendant de tout examen officiel.',
            'content' => [
                'instructions' => 'Lis le texte, puis choisis la réponse qui correspond à chaque information.',
                'passage' => 'My name is Lina. I work in a small café. The café opens at eight in the morning. I walk to work with my friend Ben. For breakfast, I have bread and tea. On Sundays, the café is closed.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'mcq',
                    'text' => 'Where does Lina work?',
                    'options' => ['In a school', 'In a café', 'In a hospital', 'In a shop'],
                    'correct_answer' => 'B',
                    'explanation' => 'Le texte dit « I work in a small café » : Lina travaille dans un café.',
                ],
                [
                    'id' => 'q2', 'type' => 'mcq',
                    'text' => 'What time does the café open?',
                    'options' => ['At six', 'At seven', 'At eight', 'At nine'],
                    'correct_answer' => 'C',
                    'explanation' => '« Opens at eight in the morning » indique une ouverture à huit heures du matin.',
                ],
                [
                    'id' => 'q3', 'type' => 'mcq',
                    'text' => 'How does Lina go to work?',
                    'options' => ['On foot', 'By bus', 'By bike', 'By car'],
                    'correct_answer' => 'A',
                    'explanation' => '« I walk to work » signifie aller au travail à pied ; « on foot » exprime le même moyen de déplacement.',
                ],
                [
                    'id' => 'q4', 'type' => 'mcq',
                    'text' => 'What does Lina drink for breakfast?',
                    'options' => ['Milk', 'Coffee', 'Juice', 'Tea'],
                    'correct_answer' => 'D',
                    'explanation' => 'Lina prend « bread and tea » au petit-déjeuner. La boisson mentionnée est le thé.',
                ],
                [
                    'id' => 'q5', 'type' => 'mcq',
                    'text' => 'When is the café closed?',
                    'options' => ['On Fridays', 'On Sundays', 'On Mondays', 'On Saturdays'],
                    'correct_answer' => 'B',
                    'explanation' => '« On Sundays, the café is closed » signifie que le café est fermé le dimanche.',
                ],
            ],
        ],
        'gap-fill' => [
            'title' => 'Anglais A1 · Présenter ses habitudes',
            'description' => 'Entraînement général au présent simple dans de courtes phrases écrites, sans génération IA.',
            'content' => [
                'instructions' => 'Complète chaque phrase avec un seul mot : le verbe entre parenthèses conjugué au présent simple, à la forme affirmative.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'gap-fill',
                    'text' => 'I ___ a student. (be)', 'correct_answer' => 'am',
                    'explanation' => 'Au présent, le verbe « be » devient « am » avec le sujet « I » : « I am a student ».',
                ],
                [
                    'id' => 'q2', 'type' => 'gap-fill',
                    'text' => 'She ___ in a small house. (live)', 'correct_answer' => 'lives',
                    'explanation' => 'À la troisième personne du singulier, on ajoute généralement un -s au présent simple : « she lives ».',
                ],
                [
                    'id' => 'q3', 'type' => 'gap-fill',
                    'text' => 'We ___ two cats. (have)', 'correct_answer' => 'have',
                    'explanation' => 'Avec « we », on conserve la forme « have ». La forme « has » est réservée à he, she et it.',
                ],
                [
                    'id' => 'q4', 'type' => 'gap-fill',
                    'text' => 'My brother ___ to school by bus. (go)', 'correct_answer' => 'goes',
                    'explanation' => '« My brother » correspond à « he ». Au présent simple, « go » devient « goes » avec ce sujet.',
                ],
                [
                    'id' => 'q5', 'type' => 'gap-fill',
                    'text' => 'They ___ happy today. (be)', 'correct_answer' => 'are',
                    'explanation' => 'Au présent, on utilise « are » avec « they » : « They are happy today ».',
                ],
            ],
        ],
        'matching' => [
            'title' => 'Anglais A1 · Les lieux du quotidien',
            'description' => 'Entraînement général pour associer une courte description au lieu correspondant.',
            'content' => [
                'instructions' => 'Pour chaque description, choisis le lieu qui correspond. Il y a une seule bonne réponse par question.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'matching',
                    'text' => 'You borrow books here.',
                    'options' => ['A library', 'A swimming pool', 'A bakery', 'A station'],
                    'correct_answer' => 'A',
                    'explanation' => '« Borrow books » signifie emprunter des livres. On le fait dans une bibliothèque : « a library ».',
                ],
                [
                    'id' => 'q2', 'type' => 'matching',
                    'text' => 'You buy bread here.',
                    'options' => ['A park', 'A cinema', 'A bakery', 'A library'],
                    'correct_answer' => 'C',
                    'explanation' => '« A bakery » est une boulangerie, le lieu où on achète du pain (« bread »).',
                ],
                [
                    'id' => 'q3', 'type' => 'matching',
                    'text' => 'You watch films on a big screen here.',
                    'options' => ['A bank', 'A cinema', 'A station', 'A school'],
                    'correct_answer' => 'B',
                    'explanation' => 'Les mots « films » et « big screen » renvoient au cinéma : « a cinema ».',
                ],
                [
                    'id' => 'q4', 'type' => 'matching',
                    'text' => 'You take a train here.',
                    'options' => ['A café', 'A hospital', 'A supermarket', 'A station'],
                    'correct_answer' => 'D',
                    'explanation' => 'Pour prendre un train (« take a train »), on va à la gare : « a station ».',
                ],
                [
                    'id' => 'q5', 'type' => 'matching',
                    'text' => 'You swim in the water here.',
                    'options' => ['A bakery', 'A swimming pool', 'A library', 'A bank'],
                    'correct_answer' => 'B',
                    'explanation' => '« Swim » signifie nager. Parmi ces lieux, « a swimming pool » est la piscine.',
                ],
            ],
        ],
    ],
    'A2' => [
        'mcq' => [
            'title' => 'Anglais A2 · Un changement de programme',
            'description' => 'Entraînement général à la compréhension d’un message pratique, sans lien avec un sujet officiel.',
            'content' => [
                'instructions' => 'Lis le message et repère les informations utiles pour organiser la sortie.',
                'passage' => 'Hi Sam, our walk on Saturday is now on Sunday because heavy rain is expected on Saturday. Meet us outside the library at 10:30, not at the station. Please bring a sandwich and some water. The walk will take about two hours. My sister Eva is coming too. If you cannot come, send me a message before Friday evening. See you soon! Alex',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'mcq',
                    'text' => 'Why has Alex changed the day of the walk?',
                    'options' => ['The library is closed.', 'Sam is working.', 'Rain is expected on Saturday.', 'Eva has lost her bag.'],
                    'correct_answer' => 'C',
                    'explanation' => 'Le mot « because » introduit la raison : de fortes pluies sont prévues le samedi.',
                ],
                [
                    'id' => 'q2', 'type' => 'mcq',
                    'text' => 'Where should Sam meet the group?',
                    'options' => ['Outside the library', 'Inside the station', 'At Alex’s house', 'In a café'],
                    'correct_answer' => 'A',
                    'explanation' => 'Alex précise « outside the library » et exclut explicitement la gare avec « not at the station ».',
                ],
                [
                    'id' => 'q3', 'type' => 'mcq',
                    'text' => 'What should Sam bring?',
                    'options' => ['A book and a pen', 'Tea and cups', 'A tent and a blanket', 'A sandwich and water'],
                    'correct_answer' => 'D',
                    'explanation' => 'La demande « Please bring a sandwich and some water » indique les deux choses à apporter.',
                ],
                [
                    'id' => 'q4', 'type' => 'mcq',
                    'text' => 'How long will the walk take?',
                    'options' => ['About thirty minutes', 'About two hours', 'About four hours', 'All day'],
                    'correct_answer' => 'B',
                    'explanation' => '« Will take about two hours » exprime une durée approximative de deux heures, pas une heure de départ.',
                ],
                [
                    'id' => 'q5', 'type' => 'mcq',
                    'text' => 'What should Sam do if he cannot come?',
                    'options' => ['Call Eva on Sunday', 'Wait outside the station', 'Send Alex a message before Friday evening', 'Leave a note in the library'],
                    'correct_answer' => 'C',
                    'explanation' => 'La condition « If you cannot come » est suivie de la consigne : prévenir Alex par message avant vendredi soir.',
                ],
            ],
        ],
        'gap-fill' => [
            'title' => 'Anglais A2 · Raconter sa journée',
            'description' => 'Entraînement général au prétérit dans des phrases courtes accompagnées d’un indice.',
            'content' => [
                'instructions' => 'Complète chaque phrase avec un seul mot : le verbe entre parenthèses conjugué au prétérit, à la forme affirmative.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'gap-fill',
                    'text' => 'Yesterday, I ___ my friend after work. (visit)', 'correct_answer' => 'visited',
                    'explanation' => '« Visit » est régulier : on ajoute -ed au prétérit. « Yesterday » situe la visite dans le passé.',
                ],
                [
                    'id' => 'q2', 'type' => 'gap-fill',
                    'text' => 'Last Saturday, we ___ to the market. (go)', 'correct_answer' => 'went',
                    'explanation' => 'Le prétérit de « go » est irrégulier : « went ». Il ne se forme pas avec -ed.',
                ],
                [
                    'id' => 'q3', 'type' => 'gap-fill',
                    'text' => 'She ___ a new notebook yesterday. (buy)', 'correct_answer' => 'bought',
                    'explanation' => '« Buy » devient « bought » au prétérit, quelle que soit la personne.',
                ],
                [
                    'id' => 'q4', 'type' => 'gap-fill',
                    'text' => 'They ___ dinner at home last night. (cook)', 'correct_answer' => 'cooked',
                    'explanation' => 'Le verbe régulier « cook » prend -ed au prétérit : « cooked ». « Last night » signifie hier soir.',
                ],
                [
                    'id' => 'q5', 'type' => 'gap-fill',
                    'text' => 'I ___ tired after the walk yesterday. (be)', 'correct_answer' => 'was',
                    'explanation' => 'Au prétérit, « be » devient « was » avec « I ». Avec « you », « we » ou « they », on emploie « were ».',
                ],
            ],
        ],
        'matching' => [
            'title' => 'Anglais A2 · Comprendre les messages affichés',
            'description' => 'Entraînement général pour relier une courte annonce à sa signification pratique.',
            'content' => [
                'instructions' => 'Lis chaque annonce, puis choisis la phrase qui reformule correctement son sens.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'matching',
                    'text' => 'Please return all library books by Friday.',
                    'options' => ['Bring the books back no later than Friday.', 'Keep the books until next month.', 'Buy new books on Friday.', 'Return only one book today.'],
                    'correct_answer' => 'A',
                    'explanation' => '« By Friday » fixe une date limite : les livres doivent être rendus au plus tard vendredi.',
                ],
                [
                    'id' => 'q2', 'type' => 'matching',
                    'text' => 'This lift is out of order. Please use the stairs.',
                    'options' => ['The stairs are closed.', 'The lift is only for staff.', 'Wait inside the lift.', 'The lift is not working.'],
                    'correct_answer' => 'D',
                    'explanation' => '« Out of order » signifie en panne. La demande d’utiliser les escaliers confirme que l’ascenseur ne fonctionne pas.',
                ],
                [
                    'id' => 'q3', 'type' => 'matching',
                    'text' => 'Two tickets left for tonight’s show.',
                    'options' => ['The show starts in two hours.', 'Only two tickets are still available.', 'Every ticket costs two pounds.', 'There are two shows tonight.'],
                    'correct_answer' => 'B',
                    'explanation' => 'Ici, « left » veut dire « restants ». L’annonce porte sur deux billets encore disponibles, pas sur l’horaire.',
                ],
                [
                    'id' => 'q4', 'type' => 'matching',
                    'text' => 'Visitors must sign in at reception.',
                    'options' => ['Visitors should enter through the kitchen.', 'Visitors cannot enter the building.', 'Visitors need to register when they arrive.', 'Visitors must bring their own food.'],
                    'correct_answer' => 'C',
                    'explanation' => '« Sign in » signifie s’enregistrer à l’arrivée. « Must » exprime une obligation.',
                ],
                [
                    'id' => 'q5', 'type' => 'matching',
                    'text' => 'Buy one sandwich and get a second sandwich free.',
                    'options' => ['Every sandwich is free.', 'Buy one sandwich and pay nothing for the second.', 'You must buy three sandwiches.', 'Only drinks are free.'],
                    'correct_answer' => 'B',
                    'explanation' => 'L’offre exige l’achat d’un sandwich ; le deuxième est gratuit. Elle ne rend pas tous les sandwichs gratuits.',
                ],
            ],
        ],
    ],
    'B1' => [
        'mcq' => [
            'title' => 'Anglais B1 · A street given back to pedestrians',
            'description' => 'Entraînement général à la lecture d’un compte rendu municipal, sans lien avec un sujet officiel.',
            'content' => [
                'instructions' => 'Lis le texte, puis choisis la réponse qui correspond à ce qui est écrit, et non à ton opinion.',
                'passage' => 'Since January, Lime Street has been closed to cars between 8 a.m. and 7 p.m. The council presented a first review at the neighbourhood meeting. Shopkeepers, who had feared a drop in footfall, report the opposite on Saturdays: customer numbers are up. On weekdays, however, the figures have stayed much the same. Several residents complain that the traffic has simply moved to the narrower streets nearby. The council accepts that this is a genuine problem and has announced a further study before the scheme is extended anywhere else. No final decision will be taken before September.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'mcq',
                    'text' => 'What had the shopkeepers feared?',
                    'options' => [
                        'A drop in the number of customers',
                        'A rise in their rent',
                        'The permanent closure of their shops',
                        'That residents would move away',
                    ],
                    'correct_answer' => 'A',
                    'explanation' => 'La relative « who had feared a drop in footfall » dit précisément leur crainte. Le plus-que-parfait la situe avant le bilan.',
                ],
                [
                    'id' => 'q2', 'type' => 'mcq',
                    'text' => 'What happens on Saturdays?',
                    'options' => [
                        'Customer numbers fall.',
                        'Customer numbers rise.',
                        'The shops close early.',
                        'Nothing measurable changes.',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => '« The opposite » renverse la crainte énoncée juste avant : le samedi, la fréquentation augmente. La stabilité concerne les jours de semaine.',
                ],
                [
                    'id' => 'q3', 'type' => 'mcq',
                    'text' => 'What do several residents complain about?',
                    'options' => [
                        'Noise from café terraces',
                        'A shortage of paid parking spaces',
                        'Traffic moving to narrower nearby streets',
                        'Shops closing on Saturdays',
                    ],
                    'correct_answer' => 'C',
                    'explanation' => 'Le texte nomme la gêne : la circulation s’est reportée sur des rues plus étroites. Ni bruit ni stationnement ne sont mentionnés.',
                ],
                [
                    'id' => 'q4', 'type' => 'mcq',
                    'text' => 'What is the council’s position?',
                    'options' => [
                        'It denies the problem.',
                        'It extends the scheme at once.',
                        'It cancels the scheme.',
                        'It accepts the problem and orders a further study.',
                    ],
                    'correct_answer' => 'D',
                    'explanation' => 'Deux verbes se suivent : « accepts » puis « has announced a further study ». Ni déni ni décision immédiate.',
                ],
                [
                    'id' => 'q5', 'type' => 'mcq',
                    'text' => 'When will a final decision be taken?',
                    'options' => [
                        'Not before September',
                        'In January',
                        'At the next neighbourhood meeting',
                        'It has already been taken.',
                    ],
                    'correct_answer' => 'A',
                    'explanation' => '« No final decision will be taken before September » fixe une limite basse : rien avant septembre, sans promettre une décision à cette date.',
                ],
            ],
        ],
        'gap-fill' => [
            'title' => 'Anglais B1 · Temps et liens logiques',
            'description' => 'Entraînement général aux temps du récit et aux connecteurs courants.',
            'content' => [
                'instructions' => 'Complète chaque phrase avec un seul mot. Cherche d’abord quel temps ou quel lien logique la phrase exige.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'gap-fill',
                    'text' => 'She stayed at home ___ she was feeling ill.',
                    'correct_answer' => 'because',
                    'explanation' => 'La cause s’introduit par « because » suivi d’une proposition complète. « Because of » demanderait un groupe nominal.',
                ],
                [
                    'id' => 'q2', 'type' => 'gap-fill',
                    'text' => 'I have ___ in Lyon since 2019. (live)',
                    'correct_answer' => 'lived',
                    'explanation' => 'Une situation commencée dans le passé et toujours vraie appelle le present perfect : « have lived », ici renforcé par « since ».',
                ],
                [
                    'id' => 'q3', 'type' => 'gap-fill',
                    'text' => 'I will call you as soon as I ___ the answer. (get)',
                    'correct_answer' => 'get',
                    'explanation' => 'Après « as soon as », l’anglais emploie le présent pour une action future — jamais « will ».',
                ],
                [
                    'id' => 'q4', 'type' => 'gap-fill',
                    'text' => 'If I ___ more time, I would learn an instrument. (have)',
                    'correct_answer' => 'had',
                    'explanation' => 'L’irréel du présent se construit « if + prétérit ... would + infinitif » : « if I had ... I would learn ».',
                ],
                [
                    'id' => 'q5', 'type' => 'gap-fill',
                    'text' => 'This is the report ___ I told you about last week.',
                    'correct_answer' => 'that',
                    'explanation' => 'La relative déterminative avec complément d’objet prend « that » ou « which ». « That » est ici le choix le plus naturel après un nom déterminé.',
                ],
            ],
        ],
        'matching' => [
            'title' => 'Anglais B1 · Comprendre une consigne écrite',
            'description' => 'Entraînement général à saisir l’intention d’un message pratique ou administratif.',
            'content' => [
                'instructions' => 'Lis chaque message et choisis la phrase qui en rend le mieux le sens.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'matching',
                    'text' => 'Registration closes on 15 March. Applications received after that date will not be considered.',
                    'options' => [
                        'You cannot register after 15 March.',
                        'Registration opens on 15 March.',
                        'Late applications cost more.',
                        'The deadline can be extended on request.',
                    ],
                    'correct_answer' => 'A',
                    'explanation' => '« Closes » ferme la période et « will not be considered » exclut tout examen des dossiers tardifs.',
                ],
                [
                    'id' => 'q2', 'type' => 'matching',
                    'text' => 'If you have any queries, please contact your case worker directly rather than the main desk.',
                    'options' => [
                        'The main desk has closed for good.',
                        'Queries are answered in writing only.',
                        'You should ask the person handling your file.',
                        'There is no one left to contact.',
                    ],
                    'correct_answer' => 'C',
                    'explanation' => '« Your case worker » désigne la personne chargée du dossier ; « rather than » écarte l’accueil général.',
                ],
                [
                    'id' => 'q3', 'type' => 'matching',
                    'text' => 'The workshop will run provided that at least eight people sign up.',
                    'options' => [
                        'The workshop is already full.',
                        'The workshop will only run if enough people register.',
                        'Eight people have already registered.',
                        'The workshop has been cancelled.',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => '« Provided that » pose une condition encore ouverte. Rien n’indique que le seuil soit atteint.',
                ],
                [
                    'id' => 'q4', 'type' => 'matching',
                    'text' => 'Please leave the room as you found it.',
                    'options' => [
                        'The room needs redecorating.',
                        'The room is out of bounds.',
                        'You should tidy the room before leaving.',
                        'Using the room costs money.',
                    ],
                    'correct_answer' => 'C',
                    'explanation' => '« As you found it » demande de rendre la salle dans l’état initial : donc rangée.',
                ],
                [
                    'id' => 'q5', 'type' => 'matching',
                    'text' => 'Delivery is likely to be delayed by about a week. We apologise for the inconvenience.',
                    'options' => [
                        'The delivery will probably arrive later than planned.',
                        'The order has been cancelled.',
                        'The delivery has already arrived.',
                        'You must wait a week before ordering.',
                    ],
                    'correct_answer' => 'A',
                    'explanation' => '« Is likely to » et « about » marquent une prévision, non une certitude. Rien n’est annulé.',
                ],
            ],
        ],
    ],
    'B2' => [
        'mcq' => [
            'title' => 'Anglais B2 · The myth of uninterrupted work',
            'description' => 'Entraînement général à la lecture argumentative, sans lien avec un sujet officiel.',
            'content' => [
                'instructions' => 'Lis le texte, puis distingue ce que l’auteur affirme de ce qu’il rapporte ou nuance.',
                'passage' => 'We are often told that people should be able to work without interruption. The idea sounds like common sense; it holds up rather poorly under scrutiny. One team followed staff in two comparable departments for six months. In the first, messages were checked three times a day at fixed hours; in the second, everyone organised their own day. Contrary to what the researchers had expected, measured productivity did not differ to any significant degree. What the interviews revealed is more interesting: staff in the first group reported markedly less tiredness by the end of the day. Should the rule therefore be applied everywhere? That is far from obvious. Imposing a single rhythm on jobs whose demands differ would risk treating as a cause what may only be an effect of feeling in control.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'mcq',
                    'text' => 'Which finding went against the researchers’ expectations?',
                    'options' => [
                        'Tiredness rose in both groups.',
                        'Measured productivity did not differ significantly.',
                        'Staff refused to take part.',
                        'The two departments were not comparable.',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => '« Contrary to what the researchers had expected » annonce le démenti, qui porte sur la productivité, restée stable.',
                ],
                [
                    'id' => 'q2', 'type' => 'mcq',
                    'text' => 'What did the interviews show?',
                    'options' => [
                        'Less tiredness reported in the first group',
                        'Higher productivity in the second group',
                        'Criticism of the method used',
                        'Reluctance to change working habits',
                    ],
                    'correct_answer' => 'A',
                    'explanation' => 'Les entretiens sont opposés aux chiffres : mesures stables d’un côté, moindre fatigue déclarée de l’autre.',
                ],
                [
                    'id' => 'q3', 'type' => 'mcq',
                    'text' => 'How does the writer answer the question about applying the rule everywhere?',
                    'options' => [
                        'They recommend it without reservation.',
                        'They say it is already standard practice.',
                        'They are doubtful about it.',
                        'They do not answer at all.',
                    ],
                    'correct_answer' => 'C',
                    'explanation' => '« That is far from obvious » exprime un doute net. L’auteur se prononce, mais contre la généralisation.',
                ],
                [
                    'id' => 'q4', 'type' => 'mcq',
                    'text' => 'What risk does the last sentence point to?',
                    'options' => [
                        'Mistaking an effect for a cause',
                        'Underestimating the cost of the study',
                        'Overlooking longer-serving staff',
                        'Increasing the number of interruptions',
                    ],
                    'correct_answer' => 'A',
                    'explanation' => 'La fin le dit : « treating as a cause what may only be an effect ». C’est une erreur de raisonnement, non de méthode.',
                ],
                [
                    'id' => 'q5', 'type' => 'mcq',
                    'text' => 'What is the status of the idea in the first sentence?',
                    'options' => [
                        'It is the writer’s own thesis.',
                        'It is a common belief the writer then questions.',
                        'It is a quotation from a member of staff.',
                        'It is the study’s conclusion.',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => '« We are often told » attribue l’idée à l’opinion courante, et « it holds up rather poorly » annonce sa mise en question.',
                ],
            ],
        ],
        'gap-fill' => [
            'title' => 'Anglais B2 · Concession, passif et articulation',
            'description' => 'Entraînement général aux formes attendues à l’écrit soutenu.',
            'content' => [
                'instructions' => 'Complète chaque phrase avec un seul mot, en respectant la construction et le lien logique attendus.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'gap-fill',
                    'text' => '___ he knew all the arguments, he did not change his mind.',
                    'correct_answer' => 'Although',
                    'explanation' => '« Although » introduit une concession suivie d’une proposition complète. « Despite » exigerait un groupe nominal.',
                ],
                [
                    'id' => 'q2', 'type' => 'gap-fill',
                    'text' => 'The building ___ completely renovated last year. (be)',
                    'correct_answer' => 'was',
                    'explanation' => 'Passif au prétérit : « was » plus le participe « renovated ». Le bâtiment subit l’action.',
                ],
                [
                    'id' => 'q3', 'type' => 'gap-fill',
                    'text' => 'The findings are clear; they do not, ___, explain the cause.',
                    'correct_answer' => 'however',
                    'explanation' => '« However », en incise, restreint ce qui vient d’être accordé. « Therefore » inverserait le rapport logique.',
                ],
                [
                    'id' => 'q4', 'type' => 'gap-fill',
                    'text' => 'The more carefully you prepare, ___ calmer you feel in the exam.',
                    'correct_answer' => 'the',
                    'explanation' => 'La corrélation proportionnelle se construit « the more ..., the + comparatif ... ». Le second « the » est obligatoire.',
                ],
                [
                    'id' => 'q5', 'type' => 'gap-fill',
                    'text' => 'These are figures ___ reliability is still debated.',
                    'correct_answer' => 'whose',
                    'explanation' => '« Whose » exprime l’appartenance, y compris pour un inanimé : la fiabilité de ces chiffres.',
                ],
            ],
        ],
        'matching' => [
            'title' => 'Anglais B2 · Ce que la formule laisse entendre',
            'description' => 'Entraînement général à l’interprétation d’énoncés prudents ou indirects.',
            'content' => [
                'instructions' => 'Lis chaque énoncé et choisis ce qu’il veut réellement dire, au-delà des mots.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'matching',
                    'text' => 'Your proposal is well meant, but it does not go far enough.',
                    'options' => [
                        'The proposal is rejected as inadequate.',
                        'The proposal is accepted as it stands.',
                        'The proposal was made in bad faith.',
                        'The proposal arrived too late.',
                    ],
                    'correct_answer' => 'A',
                    'explanation' => 'La concession sur l’intention prépare le rejet du fond : « does not go far enough » porte le jugement.',
                ],
                [
                    'id' => 'q2', 'type' => 'matching',
                    'text' => 'Perhaps we could return to this point at a later stage.',
                    'options' => [
                        'The point has been settled for good.',
                        'The point is not being dealt with now.',
                        'The point was never on the agenda.',
                        'The point must be resolved immediately.',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => 'Renvoyer à plus tard est une manière polie de clore la discussion pour l’instant, sans trancher.',
                ],
                [
                    'id' => 'q3', 'type' => 'matching',
                    'text' => 'It may prove difficult to meet that deadline.',
                    'options' => [
                        'The deadline has already passed.',
                        'The deadline will certainly be met.',
                        'The deadline will probably not be met.',
                        'The deadline has been brought forward.',
                    ],
                    'correct_answer' => 'C',
                    'explanation' => '« May prove difficult » atténue une annonce défavorable : l’échéance est compromise sans être encore manquée.',
                ],
                [
                    'id' => 'q4', 'type' => 'matching',
                    'text' => 'One might reasonably ask whether this expense was justified.',
                    'options' => [
                        'The expense is unanimously approved.',
                        'The expense was never costed.',
                        'The usefulness of the expense is being questioned.',
                        'The expense has been cancelled.',
                    ],
                    'correct_answer' => 'C',
                    'explanation' => '« Might reasonably ask » introduit une critique en la présentant comme légitime : le doute est réel, la forme est prudente.',
                ],
                [
                    'id' => 'q5', 'type' => 'matching',
                    'text' => 'It is still too early to reach a final judgement.',
                    'options' => [
                        'No firm conclusion is possible yet.',
                        'The judgement has already been made.',
                        'No judgement will ever be possible.',
                        'The judgement was unfavourable.',
                    ],
                    'correct_answer' => 'A',
                    'explanation' => '« Too early » diffère la conclusion sans l’exclure : il manque des éléments, rien de plus n’est affirmé.',
                ],
            ],
        ],
    ],
    'C1' => [
        'mcq' => [
            'title' => 'Anglais C1 · Forgetting as a function',
            'description' => 'Entraînement général à la lecture d’un texte de vulgarisation savante, sans lien avec un sujet officiel.',
            'content' => [
                'instructions' => 'Lis le texte, puis repère la thèse, les réserves et ce qui demeure ouvert.',
                'passage' => 'Forgetting enjoys a thoroughly bad name. It is taken to be a failure, the malfunction of a system whose business is to store. Recent work on memory points the other way: forgetting is not a fault but an achievement. A memory that retained everything would not be perfect; it would be unusable, unable to separate what matters from what merely happened. What counts, on this view, is not how much is kept but how reliably the trivial is cleared away. The account has not gone unchallenged. Critics object, not without force, that the usefulness of a process does not by itself establish its function; some forgetting is simply loss. What is no longer in dispute, at least, is that the question cannot sensibly be raised without treating forgetting as a phenomenon in its own right.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'mcq',
                    'text' => 'What does recent work argue?',
                    'options' => [
                        'Forgetting is a defect of memory.',
                        'Forgetting is an achievement in its own right.',
                        'Forgetting can be trained away.',
                        'Forgetting affects only trivial material.',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => '« Not a fault but an achievement » oppose les deux lectures. Le texte renverse explicitement la réputation initiale.',
                ],
                [
                    'id' => 'q2', 'type' => 'mcq',
                    'text' => 'Why would a memory that retained everything be unusable?',
                    'options' => [
                        'It would consume too much energy.',
                        'It would distort recollections.',
                        'It could not separate what matters from what merely happened.',
                        'It would work only in the short term.',
                    ],
                    'correct_answer' => 'C',
                    'explanation' => 'La raison est donnée par « unable to separate ». Le défaut n’est pas de capacité mais de hiérarchie.',
                ],
                [
                    'id' => 'q3', 'type' => 'mcq',
                    'text' => 'On this view, what counts?',
                    'options' => [
                        'The quantity of material kept',
                        'The speed of recall',
                        'The regularity of revision',
                        'How reliably the trivial is cleared away',
                    ],
                    'correct_answer' => 'D',
                    'explanation' => 'La structure « not ... but ... » écarte la quantité au profit du tri. C’est le critère retenu.',
                ],
                [
                    'id' => 'q4', 'type' => 'mcq',
                    'text' => 'What objection do critics raise?',
                    'options' => [
                        'Usefulness alone does not establish function.',
                        'The studies used too few participants.',
                        'Forgetting cannot be measured.',
                        'The work ignores earlier research.',
                    ],
                    'correct_answer' => 'A',
                    'explanation' => 'L’objection est logique — un glissement de l’utile au fonctionnel — et non méthodologique.',
                ],
                [
                    'id' => 'q5', 'type' => 'mcq',
                    'text' => 'What does the final sentence establish?',
                    'options' => [
                        'That one side has won the argument',
                        'A minimal point nobody now disputes',
                        'That the debate is closed',
                        'That the question should be abandoned',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => '« No longer in dispute, at least » n’accorde que le minimum : le sujet mérite d’être traité pour lui-même. Le débat reste ouvert.',
                ],
            ],
        ],
        'gap-fill' => [
            'title' => 'Anglais C1 · Registre écrit et nuance',
            'description' => 'Entraînement général aux tournures de l’anglais écrit soigné.',
            'content' => [
                'instructions' => 'Complète chaque phrase avec un seul mot, en respectant le registre écrit et la nuance attendue.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'gap-fill',
                    'text' => 'The findings remain provisional, ___ the precision claimed for them.',
                    'correct_answer' => 'despite',
                    'explanation' => '« Despite » introduit la concession devant un groupe nominal. « Although » exigerait une proposition complète.',
                ],
                [
                    'id' => 'q2', 'type' => 'gap-fill',
                    'text' => 'This is ___ a preliminary assessment, not a verdict.',
                    'correct_answer' => 'merely',
                    'explanation' => '« Merely » restreint la portée — rien de plus qu’une appréciation — et relève du registre écrit, là où l’oral dirait « just ».',
                ],
                [
                    'id' => 'q3', 'type' => 'gap-fill',
                    'text' => 'The survey was carried out ___ to clarify the legal framework.',
                    'correct_answer' => 'so',
                    'explanation' => 'Le but se marque par « so as to + infinitif », plus soutenu que « to » seul dans ce registre.',
                ],
                [
                    'id' => 'q4', 'type' => 'gap-fill',
                    'text' => 'Not ___ can the data be transferred to other regions without caution.',
                    'correct_answer' => 'readily',
                    'explanation' => '« Not readily » atténue : le transfert n’est pas immédiat. L’inversion après la négation en tête est propre à l’écrit soutenu.',
                ],
                [
                    'id' => 'q5', 'type' => 'gap-fill',
                    'text' => 'The author takes it ___ granted that the method is familiar.',
                    'correct_answer' => 'for',
                    'explanation' => 'La locution figée est « take it for granted that » : tenir pour acquis, sans démontrer.',
                ],
            ],
        ],
        'matching' => [
            'title' => 'Anglais C1 · Distinguer des formulations proches',
            'description' => 'Entraînement général à la discrimination fine entre énoncés voisins.',
            'content' => [
                'instructions' => 'Choisis la reformulation qui conserve exactement la portée de l’énoncé, ni plus ni moins.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'matching',
                    'text' => 'A connection cannot be ruled out.',
                    'options' => [
                        'A connection has been demonstrated.',
                        'A connection is conceivable but unproven.',
                        'A connection has been disproved.',
                        'A connection is probable.',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => 'La double négation ouvre une simple possibilité, sans avancer ni preuve ni probabilité.',
                ],
                [
                    'id' => 'q2', 'type' => 'matching',
                    'text' => 'The measure has not been shown to be effective.',
                    'options' => [
                        'The measure has been shown to be harmful.',
                        'The measure was never implemented.',
                        'The effectiveness of the measure is unproven.',
                        'The measure works only in the long run.',
                    ],
                    'correct_answer' => 'C',
                    'explanation' => 'Absence de preuve d’efficacité ne vaut pas preuve de nocivité. L’énoncé reste au niveau du constat.',
                ],
                [
                    'id' => 'q3', 'type' => 'matching',
                    'text' => 'That reading is at least defensible.',
                    'options' => [
                        'That reading is the only possible one.',
                        'That reading is plainly mistaken.',
                        'That reading is generally accepted.',
                        'That reading can be argued for without being compelling.',
                    ],
                    'correct_answer' => 'D',
                    'explanation' => '« At least defensible » accorde le minimum : soutenable, sans exclusivité ni consensus.',
                ],
                [
                    'id' => 'q4', 'type' => 'matching',
                    'text' => 'The author refrains from offering evidence of their own.',
                    'options' => [
                        'The author chooses not to offer evidence.',
                        'The author was unable to find any.',
                        'The author disputes other people’s evidence.',
                        'The author cites no one but themselves.',
                    ],
                    'correct_answer' => 'A',
                    'explanation' => '« Refrain from » exprime un choix, non une impossibilité. La nuance sépare la décision de l’échec.',
                ],
                [
                    'id' => 'q5', 'type' => 'matching',
                    'text' => 'The criticism holds only up to a point.',
                    'options' => [
                        'The criticism is wholly unfounded.',
                        'The criticism is partly justified.',
                        'The criticism is exactly on target.',
                        'The criticism has been withdrawn.',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => '« Only up to a point » pose une validité limitée : la critique vaut pour une part, pas pour le tout.',
                ],
            ],
        ],
    ],
    'C2' => [
        'mcq' => [
            'title' => 'Anglais C2 · What a number does not say',
            'description' => 'Entraînement général à la lecture critique d’une argumentation dense, sans lien avec un sujet officiel.',
            'content' => [
                'instructions' => 'Lis le texte, puis distingue l’argument de l’illustration, et mesure la portée exacte de chaque affirmation.',
                'passage' => 'Few rhetorical devices appear as unassailable as the number. Whoever produces one seems to step out of the quarrel of opinions and let the matter speak for itself. That is precisely where the difficulty lies. A statistic always answers a question somebody has asked — and the result almost never carries that question along with it. Period of observation, comparison group, choice of indicator: at each of these points decisions were taken that shape the result without leaving any legible trace in it. None of this issues a licence for arbitrariness. To infer from the situatedness of every measurement that anything goes is to confuse criticism of a claim with its abandonment. The conclusion that actually follows is less comfortable: numbers remain indispensable, but they require that the conditions of their making be read alongside them — a demand that can be neither delegated nor abridged.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'mcq',
                    'text' => 'Where does the difficulty with numbers lie?',
                    'options' => [
                        'They are too complex for non-specialists to calculate.',
                        'They rest on a question that no longer appears in the result.',
                        'They are deliberately falsified by specialists.',
                        'They date faster than other kinds of evidence.',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => 'Le texte l’énonce après le tiret : la question posée en amont ne voyage pas avec le résultat. Ni fraude ni obsolescence.',
                ],
                [
                    'id' => 'q2', 'type' => 'mcq',
                    'text' => 'What is the function of the list “period of observation, comparison group, choice of indicator”?',
                    'options' => [
                        'It demonstrates the rigour of statistical method.',
                        'It lists the chapters of a study.',
                        'It sets out the points where invisible decisions shape the result.',
                        'It enumerates mistakes to be avoided.',
                    ],
                    'correct_answer' => 'C',
                    'explanation' => 'L’énumération illustre la thèse : autant de choix « without leaving any legible trace ». Ce ne sont pas des fautes.',
                ],
                [
                    'id' => 'q3', 'type' => 'mcq',
                    'text' => 'Which inference does the text explicitly reject?',
                    'options' => [
                        'That every measurement is situated',
                        'That numbers may be used at all',
                        'That statistics are rarely published',
                        'That situatedness makes all claims equivalent',
                    ],
                    'correct_answer' => 'D',
                    'explanation' => 'Le texte nomme la confusion : critiquer une prétention n’est pas y renoncer. Il admet la situativité, refuse le relativisme.',
                ],
                [
                    'id' => 'q4', 'type' => 'mcq',
                    'text' => 'What does “neither delegated nor abridged” mean here?',
                    'options' => [
                        'The work of reading cannot be passed on or shortened.',
                        'The work is reserved for specialists.',
                        'The work is not worth the effort.',
                        'The work will soon be automated.',
                    ],
                    'correct_answer' => 'A',
                    'explanation' => 'Les deux verbes sont niés ensemble : ni transfert, ni raccourci. L’effort revient au lecteur.',
                ],
                [
                    'id' => 'q5', 'type' => 'mcq',
                    'text' => 'What position does the text take overall?',
                    'options' => [
                        'It dismisses statistics as a means of knowledge.',
                        'It holds them indispensable but in need of contextual reading.',
                        'It regards the quarrel of opinions as settled.',
                        'It recommends leaving numbers to experts.',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => '« Remain indispensable, but they require ... » tient les deux bouts : maintien de l’outil, exigence de lecture.',
                ],
            ],
        ],
        'gap-fill' => [
            'title' => 'Anglais C2 · Charnières de l’argumentation',
            'description' => 'Entraînement général aux articulations qui portent la nuance d’un raisonnement écrit.',
            'content' => [
                'instructions' => 'Complète chaque phrase avec un seul mot. La bonne réponse dépend du rapport logique exact entre les deux propositions.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'gap-fill',
                    'text' => 'The finding is unambiguous; it does not, ___, explain how matters came to this.',
                    'correct_answer' => 'however',
                    'explanation' => '« However », en incise, restreint ce qui vient d’être accordé. « Therefore » inverserait le rapport logique.',
                ],
                [
                    'id' => 'q2', 'type' => 'gap-fill',
                    'text' => 'The evidence is thin, ___ the thesis collapsing altogether.',
                    'correct_answer' => 'without',
                    'explanation' => '« Without + gérondif » nie une conséquence attendue : preuves minces, sans effondrement de la thèse pour autant.',
                ],
                [
                    'id' => 'q3', 'type' => 'gap-fill',
                    'text' => 'The results are ___ interesting than methodologically revealing.',
                    'correct_answer' => 'less',
                    'explanation' => 'Le couple « less ... than ... » déplace l’accent : l’intérêt du résultat compte moins que sa portée de méthode.',
                ],
                [
                    'id' => 'q4', 'type' => 'gap-fill',
                    'text' => 'It remains open ___ the rule applies to earlier cases as well.',
                    'correct_answer' => 'whether',
                    'explanation' => 'L’interrogation indirecte prend « whether ». « That » affirmerait, ce que « remains open » interdit.',
                ],
                [
                    'id' => 'q5', 'type' => 'gap-fill',
                    'text' => 'The criticism would be warranted ___ it addressed the published text.',
                    'correct_answer' => 'if',
                    'explanation' => 'Le système hypothétique « if + prétérit ... would » marque l’irréel : la critique ne vaudrait qu’à cette condition, non remplie.',
                ],
            ],
        ],
        'matching' => [
            'title' => 'Anglais C2 · Ce que la formule concède',
            'description' => 'Entraînement général à la lecture des atténuations et des sous-entendus de l’écrit savant.',
            'content' => [
                'instructions' => 'Choisis la reformulation qui restitue exactement ce que l’énoncé concède — et ce qu’il refuse.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'matching',
                    'text' => 'The study can hardly be accused of carelessness.',
                    'options' => [
                        'The study is criticised for being careless.',
                        'The care taken is not in question — other things may be.',
                        'The study is convincing in every respect.',
                        'The study was never examined.',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => 'La litote concède un point précis — le soin — et suggère, par son insistance, que la critique porte ailleurs.',
                ],
                [
                    'id' => 'q2', 'type' => 'matching',
                    'text' => 'Whether the effort was worth making, readers may judge for themselves.',
                    'options' => [
                        'The writer considers the effort justified.',
                        'The writer withholds judgement, not without scepticism.',
                        'The effort was never costed.',
                        'All those involved were in agreement.',
                    ],
                    'correct_answer' => 'B',
                    'explanation' => 'Renvoyer le lecteur à son propre jugement est une abstention de façade : le doute affleure sans être assumé.',
                ],
                [
                    'id' => 'q3', 'type' => 'matching',
                    'text' => 'The argument is sound, granted its premises.',
                    'options' => [
                        'The argument holds regardless of any premises.',
                        'The argument contains a formal error.',
                        'The argument stands or falls with its premises.',
                        'The premises were never stated.',
                    ],
                    'correct_answer' => 'C',
                    'explanation' => '« Granted its premises » subordonne la validité à une condition. Le désaccord se déplace vers les prémisses.',
                ],
                [
                    'id' => 'q4', 'type' => 'matching',
                    'text' => 'Few would care to deny that a connection exists here.',
                    'options' => [
                        'The connection is treated as barely contestable.',
                        'The connection has been disproved.',
                        'The connection is pure speculation.',
                        'Research is silent on the connection.',
                    ],
                    'correct_answer' => 'A',
                    'explanation' => 'La formule affirme par la négative : contester serait déplacé, donc le lien est tenu pour acquis.',
                ],
                [
                    'id' => 'q5', 'type' => 'matching',
                    'text' => 'The contribution has its merits — on ground it has staked out itself.',
                    'options' => [
                        'The contribution is praised without reservation.',
                        'The contribution addresses an unfamiliar field.',
                        'The praise stands, but the framing of the subject is doubted.',
                        'The contribution has been withdrawn.',
                    ],
                    'correct_answer' => 'C',
                    'explanation' => 'Le tiret introduit la réserve : le mérite est réel, mais sur un terrain choisi par l’auteur — d’où un doute sur la portée.',
                ],
            ],
        ],
    ],
];
