<?php

return [
    'A1' => [
        'mcq' => [
            'title' => 'Anglais A1 · Un matin au café',
            'description' => 'Entraînement général à la lecture de phrases du quotidien, indépendant de tout examen officiel.',
            'content' => [
                'instructions' => 'Lis le texte, puis choisis la réponse qui correspond à chaque information.',
                'text' => 'My name is Lina. I work in a small café. The café opens at eight in the morning. I walk to work with my friend Ben. For breakfast, I have bread and tea. On Sundays, the café is closed.',
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
                'text' => 'Hi Sam, our walk on Saturday is now on Sunday because heavy rain is expected on Saturday. Meet us outside the library at 10:30, not at the station. Please bring a sandwich and some water. The walk will take about two hours. My sister Eva is coming too. If you cannot come, send me a message before Friday evening. See you soon! Alex',
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
];
