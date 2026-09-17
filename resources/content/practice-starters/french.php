<?php

return [
    'A1' => [
        'mcq' => [
            'title' => 'Français A1 · Une nouvelle voisine',
            'description' => 'Entraînement général à la compréhension d’une présentation simple, indépendant de tout examen officiel.',
            'content' => [
                'instructions' => 'Lis la présentation de Nora, puis choisis la bonne réponse.',
                'text' => 'Bonjour ! Je m’appelle Nora. J’ai vingt-quatre ans et j’habite à Lyon avec ma sœur. Je suis étudiante. Le matin, je vais à l’université à vélo. J’aime cuisiner et lire. Le samedi, je joue au tennis avec mon amie Léa.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'mcq',
                    'text' => 'Quel âge a Nora ?',
                    'options' => ['Vingt ans', 'Vingt-quatre ans', 'Trente ans', 'Trente-quatre ans'],
                    'correct_answer' => 'B',
                    'explanation' => 'Nora dit « J’ai vingt-quatre ans ». En français, on utilise le verbe « avoir » pour donner son âge.',
                ],
                [
                    'id' => 'q2', 'type' => 'mcq',
                    'text' => 'Avec qui habite Nora ?',
                    'options' => ['Son frère', 'Ses parents', 'Son amie Léa', 'Sa sœur'],
                    'correct_answer' => 'D',
                    'explanation' => '« J’habite à Lyon avec ma sœur » identifie la personne qui partage son logement.',
                ],
                [
                    'id' => 'q3', 'type' => 'mcq',
                    'text' => 'Que fait Nora ?',
                    'options' => ['Elle est étudiante.', 'Elle est médecin.', 'Elle est vendeuse.', 'Elle est cuisinière.'],
                    'correct_answer' => 'A',
                    'explanation' => 'Le texte indique « Je suis étudiante ». Aimer cuisiner ne signifie pas être cuisinière de métier.',
                ],
                [
                    'id' => 'q4', 'type' => 'mcq',
                    'text' => 'Comment va-t-elle à l’université ?',
                    'options' => ['En voiture', 'En bus', 'À vélo', 'À pied'],
                    'correct_answer' => 'C',
                    'explanation' => 'La phrase « je vais à l’université à vélo » précise son moyen de transport.',
                ],
                [
                    'id' => 'q5', 'type' => 'mcq',
                    'text' => 'Quand joue-t-elle au tennis ?',
                    'options' => ['Le lundi', 'Le samedi', 'Le mercredi', 'Le dimanche'],
                    'correct_answer' => 'B',
                    'explanation' => '« Le samedi » introduit une habitude : Nora joue au tennis ce jour de la semaine.',
                ],
            ],
        ],
        'gap-fill' => [
            'title' => 'Français A1 · Les verbes du quotidien',
            'description' => 'Entraînement général au présent dans de courtes phrases écrites, sans génération IA.',
            'content' => [
                'instructions' => 'Complète chaque phrase avec un seul mot : le verbe entre parenthèses conjugué au présent de l’indicatif.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'gap-fill',
                    'text' => 'Je ___ étudiant. (être)', 'correct_answer' => 'suis',
                    'explanation' => 'Au présent, « être » se conjugue « je suis ». On utilise ce verbe pour présenter sa situation.',
                ],
                [
                    'id' => 'q2', 'type' => 'gap-fill',
                    'text' => 'Tu ___ un frère. (avoir)', 'correct_answer' => 'as',
                    'explanation' => 'Avec « tu », le présent du verbe « avoir » est « as », sans accent.',
                ],
                [
                    'id' => 'q3', 'type' => 'gap-fill',
                    'text' => 'Nous ___ français à la maison. (parler)', 'correct_answer' => 'parlons',
                    'explanation' => 'Pour un verbe régulier en -er, la terminaison avec « nous » est -ons : parl- + -ons.',
                ],
                [
                    'id' => 'q4', 'type' => 'gap-fill',
                    'text' => 'Elle ___ au marché le samedi. (aller)', 'correct_answer' => 'va',
                    'explanation' => '« Aller » est irrégulier. Au présent, avec « elle », on écrit « va ».',
                ],
                [
                    'id' => 'q5', 'type' => 'gap-fill',
                    'text' => 'Vous ___ un film. (regarder)', 'correct_answer' => 'regardez',
                    'explanation' => 'Avec « vous », les verbes réguliers en -er prennent -ez au présent : « vous regardez ».',
                ],
            ],
        ],
        'matching' => [
            'title' => 'Français A1 · À chaque besoin, son lieu',
            'description' => 'Entraînement général pour comprendre des situations simples et reconnaître les lieux utiles.',
            'content' => [
                'instructions' => 'Pour chaque situation, choisis le lieu qui convient parmi les quatre propositions.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'matching',
                    'text' => 'Je veux acheter du pain et des croissants.',
                    'options' => ['La piscine', 'La gare', 'La boulangerie', 'La bibliothèque'],
                    'correct_answer' => 'C',
                    'explanation' => 'Le pain et les croissants s’achètent à la boulangerie. Ce sont les indices utiles dans la phrase.',
                ],
                [
                    'id' => 'q2', 'type' => 'matching',
                    'text' => 'Je veux emprunter un roman.',
                    'options' => ['La bibliothèque', 'La pharmacie', 'La piscine', 'La gare'],
                    'correct_answer' => 'A',
                    'explanation' => '« Emprunter » un livre signifie le prendre pour le rendre ensuite. La bibliothèque permet cet emprunt.',
                ],
                [
                    'id' => 'q3', 'type' => 'matching',
                    'text' => 'Je dois prendre le train.',
                    'options' => ['La boulangerie', 'Le cinéma', 'Le parc', 'La gare'],
                    'correct_answer' => 'D',
                    'explanation' => 'La gare est le lieu de départ et d’arrivée des trains.',
                ],
                [
                    'id' => 'q4', 'type' => 'matching',
                    'text' => 'Je veux nager dans un bassin.',
                    'options' => ['La bibliothèque', 'La piscine', 'La pharmacie', 'La boulangerie'],
                    'correct_answer' => 'B',
                    'explanation' => 'Les mots « nager » et « bassin » permettent d’identifier la piscine.',
                ],
                [
                    'id' => 'q5', 'type' => 'matching',
                    'text' => 'Je veux voir un film sur grand écran.',
                    'options' => ['La gare', 'La pharmacie', 'Le cinéma', 'La bibliothèque'],
                    'correct_answer' => 'C',
                    'explanation' => 'Voir un film « sur grand écran » correspond ici à une sortie au cinéma.',
                ],
            ],
        ],
    ],
    'A2' => [
        'mcq' => [
            'title' => 'Français A2 · Un atelier de cuisine',
            'description' => 'Entraînement général à la lecture d’une annonce pratique, sans lien avec un sujet officiel.',
            'content' => [
                'instructions' => 'Lis l’annonce, puis choisis la réponse correspondant à chaque détail.',
                'text' => 'Le centre de quartier organise un atelier de cuisine le mercredi 18 juin, de 17 h à 19 h. L’atelier est ouvert aux adultes débutants. Le matériel et les ingrédients sont fournis, mais chaque personne doit apporter une boîte pour emporter son repas. La participation coûte 8 euros. Il faut s’inscrire à l’accueil avant le lundi 16 juin. Attention : l’atelier aura lieu dans la grande salle du premier étage, et non dans la cuisine du rez-de-chaussée.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'mcq',
                    'text' => 'À qui s’adresse cet atelier ?',
                    'options' => ['Aux cuisiniers professionnels', 'Aux enfants de moins de dix ans', 'Aux adultes débutants', 'Aux élèves d’une seule école'],
                    'correct_answer' => 'C',
                    'explanation' => '« Ouvert aux adultes débutants » indique le public concerné : aucune expérience en cuisine n’est demandée.',
                ],
                [
                    'id' => 'q2', 'type' => 'mcq',
                    'text' => 'Combien de temps dure l’atelier ?',
                    'options' => ['Deux heures', 'Une heure', 'Trois heures', 'Une demi-journée'],
                    'correct_answer' => 'A',
                    'explanation' => 'L’atelier commence à 17 h et finit à 19 h : sa durée est de deux heures.',
                ],
                [
                    'id' => 'q3', 'type' => 'mcq',
                    'text' => 'Que faut-il apporter ?',
                    'options' => ['Tous les ingrédients', 'Une casserole', 'Un livre de recettes', 'Une boîte pour le repas'],
                    'correct_answer' => 'D',
                    'explanation' => 'Les ingrédients et le matériel sont fournis. Le mot « mais » introduit ce qui reste à apporter : une boîte.',
                ],
                [
                    'id' => 'q4', 'type' => 'mcq',
                    'text' => 'Comment s’inscrire ?',
                    'options' => ['Par téléphone après le 18 juin', 'À l’accueil avant le 16 juin', 'Sur place après l’atelier', 'En envoyant sa recette par courrier'],
                    'correct_answer' => 'B',
                    'explanation' => 'L’annonce donne à la fois le lieu de l’inscription, « à l’accueil », et sa limite, « avant le lundi 16 juin ».',
                ],
                [
                    'id' => 'q5', 'type' => 'mcq',
                    'text' => 'Où se déroule l’atelier ?',
                    'options' => ['Dans le jardin du centre', 'À l’accueil', 'Dans la grande salle du premier étage', 'Dans la cuisine du rez-de-chaussée'],
                    'correct_answer' => 'C',
                    'explanation' => 'La dernière phrase précise le premier étage et exclut la cuisine du rez-de-chaussée avec « et non ».',
                ],
            ],
        ],
        'gap-fill' => [
            'title' => 'Français A2 · Raconter une sortie',
            'description' => 'Entraînement général au passé composé avec l’auxiliaire déjà donné.',
            'content' => [
                'instructions' => 'Complète chaque phrase avec un seul mot : le participe passé du verbe entre parenthèses. L’auxiliaire est déjà écrit.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'gap-fill',
                    'text' => 'Hier, nous avons ___ le musée. (visiter)', 'correct_answer' => 'visité',
                    'explanation' => 'Le participe passé d’un verbe régulier en -er se termine par -é : « visiter » devient « visité ».',
                ],
                [
                    'id' => 'q2', 'type' => 'gap-fill',
                    'text' => 'J’ai ___ le bus à huit heures. (prendre)', 'correct_answer' => 'pris',
                    'explanation' => 'Le participe passé de « prendre » est irrégulier : « pris ». Le passé composé est « j’ai pris ».',
                ],
                [
                    'id' => 'q3', 'type' => 'gap-fill',
                    'text' => 'Elle a ___ une carte postale à sa sœur. (écrire)', 'correct_answer' => 'écrit',
                    'explanation' => '« Écrire » a pour participe passé « écrit ». Le complément « une carte postale » suit le verbe : on n’ajoute pas de -e.',
                ],
                [
                    'id' => 'q4', 'type' => 'gap-fill',
                    'text' => 'Nous avons ___ un film après le dîner. (voir)', 'correct_answer' => 'vu',
                    'explanation' => 'Le participe passé de « voir » est « vu » : « nous avons vu un film ».',
                ],
                [
                    'id' => 'q5', 'type' => 'gap-fill',
                    'text' => 'Ils ont ___ une table pour le déjeuner. (réserver)', 'correct_answer' => 'réservé',
                    'explanation' => '« Réserver » est un verbe en -er : son participe passé est « réservé ». Avec « avoir », il ne s’accorde pas avec le sujet « ils ».',
                ],
            ],
        ],
        'matching' => [
            'title' => 'Français A2 · Comprendre les consignes',
            'description' => 'Entraînement général pour reformuler des messages et annonces du quotidien.',
            'content' => [
                'instructions' => 'Choisis, pour chaque message, la phrase qui en exprime le sens.',
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'matching',
                    'text' => 'Merci de rendre les clés avant midi.',
                    'options' => ['On peut garder les clés toute la journée.', 'Il faut rapporter les clés avant 12 h.', 'Il faut fabriquer de nouvelles clés.', 'Les clés sont disponibles seulement le soir.'],
                    'correct_answer' => 'B',
                    'explanation' => '« Rendre » signifie ici rapporter. « Avant midi » fixe une limite avant 12 h.',
                ],
                [
                    'id' => 'q2', 'type' => 'matching',
                    'text' => 'Ascenseur en panne. Utilisez l’escalier.',
                    'options' => ['L’ascenseur est réservé au personnel.', 'L’escalier est fermé.', 'Il faut attendre dans l’ascenseur.', 'L’ascenseur ne fonctionne pas.'],
                    'correct_answer' => 'D',
                    'explanation' => '« En panne » signifie qu’un appareil ne fonctionne pas. L’escalier est proposé comme solution.',
                ],
                [
                    'id' => 'q3', 'type' => 'matching',
                    'text' => 'Entrée gratuite pour les enfants de moins de six ans.',
                    'options' => ['Un enfant de cinq ans ne paie pas.', 'Tous les adultes entrent gratuitement.', 'Un enfant de huit ans entre gratuitement.', 'Les enfants ne peuvent pas entrer.'],
                    'correct_answer' => 'A',
                    'explanation' => 'Cinq ans est inférieur à six ans. La gratuité annoncée concerne cette tranche d’âge, pas les adultes ni les enfants de huit ans.',
                ],
                [
                    'id' => 'q4', 'type' => 'matching',
                    'text' => 'Réservation obligatoire pour le cours du samedi.',
                    'options' => ['Le cours du samedi est annulé.', 'Le cours a lieu tous les jours.', 'Il faut réserver sa place à l’avance.', 'On peut venir sans prévenir.'],
                    'correct_answer' => 'C',
                    'explanation' => 'Le mot « obligatoire » indique que réserver est nécessaire pour participer au cours.',
                ],
                [
                    'id' => 'q5', 'type' => 'matching',
                    'text' => 'Magasin fermé exceptionnellement ce mardi.',
                    'options' => ['Le magasin ferme tous les mardis.', 'La fermeture de ce mardi n’est pas habituelle.', 'Le magasin ferme définitivement.', 'Le magasin ouvre uniquement le mardi.'],
                    'correct_answer' => 'B',
                    'explanation' => '« Exceptionnellement » signale une situation inhabituelle. L’annonce ne décrit pas une fermeture chaque mardi.',
                ],
            ],
        ],
    ],
];
