<?php

/**
 * TCF tout public — épreuve « Compréhension orale ».
 *
 * Écrit à la main d'après la page officielle du TCF tout public
 * (https://www.france-education-international.fr/test/tcf-tout-public,
 * copie lue le 2026-11-04 sur
 * https://web.archive.org/web/20260711113141/https://www.france-education-international.fr/test/tcf-tout-public) :
 *  - 29 questions à choix multiple, 4 choix de réponse (A, B, C, D), une seule
 *    réponse correcte, 25 minutes ;
 *  - « Chaque enregistrement n'est diffusé qu'une seule fois. La question est
 *    posée après l'écoute du document sonore » ;
 *  - difficulté progressive : mots familiers et échanges courants, puis
 *    messages et annonces, émissions de radio, et pour finir des exposés sur
 *    des sujets abstraits (C1-C2).
 *
 * Les enregistrements sont originaux. Ce sont le FORMAT, le nombre d'items, le
 * nombre de choix et la progression qui reprennent l'épreuve, pas ses contenus.
 *
 * Trois choix à garder si ce fichier est recopié :
 *  - aucun texte d'enregistrement dans `content.passage` : il vit dans
 *    `audio_text`, question par question, et `prepla:fabriquer-medias` en tire
 *    une piste. Affiché, il transformerait l'écoute en lecture ;
 *  - un enregistrement par question, comme au TCF : chaque document est court
 *    et ne sert qu'une fois ;
 *  - l'explication cite les mots entendus qui tranchent, entre guillemets.
 *
 * POURQUOI LE DOSSIER « B1 » : voir maitrise-structures.php.
 */

return [
    'exam' => 'tcf',
    'level' => 'B1',
    'section' => 'comprehension-orale',
    'parts' => [
        [
            'part' => 1,
            'name' => 'Compréhension orale, partie 1',
            'type' => 'mcq',
            'title' => 'TCF · Compréhension orale — Série complète, du A1 au C2',
            'description' => "29 enregistrements, une seule écoute chacun, et une question à quatre choix après chaque écoute. Format officiel du TCF tout public.",
            'content' => [
                'instructions' => "Vous allez entendre 29 enregistrements, de plus en plus difficiles. Chaque enregistrement n'est diffusé qu'UNE SEULE FOIS, comme au TCF. Écoutez, puis choisissez la seule bonne réponse : A, B, C ou D. À l'épreuve, l'ensemble dure 25 minutes.",
            ],
            'questions' => [
                // ── A1 ──
                [
                    'id' => 'q1', 'type' => 'mcq',
                    'text' => "Où sont ces deux personnes ?",
                    'audio_text' => "FEMME : Bonjour, une baguette et deux croissants, s'il vous plaît.\nHOMME : Voilà, madame. Ça fait trois euros vingt.\nFEMME : Tenez. Merci, au revoir !",
                    'options' => [
                        "Dans une pharmacie.",
                        "À la poste.",
                        "Dans une boulangerie.",
                        "Dans un restaurant.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. La cliente demande « une baguette et deux croissants » : on achète du pain et des viennoiseries à la boulangerie. Il n'y a ni repas servi (restaurant) ni médicament.",
                ],
                [
                    'id' => 'q2', 'type' => 'mcq',
                    'text' => "À quelle heure part le train ?",
                    'audio_text' => "HOMME : Excusez-moi, le train pour Lyon part à quelle heure ?\nFEMME : À dix heures quinze, quai numéro quatre.\nHOMME : Merci beaucoup.",
                    'options' => [
                        "À 10 h 15.",
                        "À 4 h.",
                        "À 10 h.",
                        "À 15 h.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. La réponse est « À dix heures quinze ». Le chiffre quatre est le numéro du quai, pas l'heure : c'est le piège.",
                ],
                [
                    'id' => 'q3', 'type' => 'mcq',
                    'text' => "Que va faire Sophie samedi ?",
                    'audio_text' => "HOMME : Sophie, tu viens au cinéma avec nous samedi ?\nFEMME : Désolée, samedi je ne peux pas : je vais chez ma grand-mère, à la campagne. Dimanche, si tu veux !",
                    'options' => [
                        "Aller au cinéma.",
                        "Rester chez elle.",
                        "Travailler.",
                        "Rendre visite à sa grand-mère.",
                    ],
                    'correct_answer' => 'D',
                    'explanation' => "D. Sophie refuse le cinéma : « samedi je ne peux pas : je vais chez ma grand-mère ». Le cinéma est la proposition qu'elle décline ; elle propose le dimanche à la place.",
                ],
                [
                    'id' => 'q4', 'type' => 'mcq',
                    'text' => "Qu'est-ce que la cliente veut acheter ?",
                    'audio_text' => "VENDEUR : Bonjour madame, je peux vous aider ?\nCLIENTE : Oui, je cherche un pull bleu, en taille 38.\nVENDEUR : En bleu, nous avons ce modèle-ci. Vous voulez l'essayer ?",
                    'options' => [
                        "Une robe bleue.",
                        "Un pull bleu.",
                        "Un pantalon noir.",
                        "Des chaussures en taille 38.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « je cherche un pull bleu, en taille 38 ». La taille 38 est bien celle du pull, pas d'une paire de chaussures.",
                ],
                // ── A2 ──
                [
                    'id' => 'q5', 'type' => 'mcq',
                    'text' => "Pourquoi Julien téléphone-t-il ?",
                    'audio_text' => "Allô, Marion ? C'est Julien. Je suis désolé, je vais avoir trente minutes de retard : mon bus est en panne. Commence le repas sans moi, j'arrive vers huit heures et demie. À tout à l'heure !",
                    'options' => [
                        "Pour annuler le repas.",
                        "Pour demander l'adresse.",
                        "Pour inviter Marion au restaurant.",
                        "Pour prévenir qu'il sera en retard.",
                    ],
                    'correct_answer' => 'D',
                    'explanation' => "D. « je vais avoir trente minutes de retard : mon bus est en panne ». Il n'annule pas : il demande de commencer sans lui et annonce qu'il arrive « vers huit heures et demie ».",
                ],
                [
                    'id' => 'q6', 'type' => 'mcq',
                    'text' => "Que demande-t-on aux voyageurs ?",
                    'audio_text' => "Mesdames et messieurs, en raison de travaux, le train à destination de Bordeaux partira exceptionnellement du quai numéro sept, et non du quai numéro deux. Nous vous remercions de votre compréhension.",
                    'options' => [
                        "De descendre du train.",
                        "D'attendre le prochain train.",
                        "D'aller au quai sept.",
                        "D'acheter un autre billet.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. Le train partira « du quai numéro sept, et non du quai numéro deux » : il faut changer de quai. Le train n'est ni annulé ni retardé.",
                ],
                [
                    'id' => 'q7', 'type' => 'mcq',
                    'text' => "Que doit faire Madame Benali ?",
                    'audio_text' => "Bonjour Madame Benali, ici le cabinet du docteur Roussel. Le docteur est malade demain : votre rendez-vous de dix heures est annulé. Merci de nous rappeler pour choisir une autre date. Au revoir, madame.",
                    'options' => [
                        "Rappeler le cabinet pour un autre rendez-vous.",
                        "Aller chez le docteur demain à 10 h.",
                        "Prendre des médicaments.",
                        "Attendre que le docteur la rappelle.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. « Merci de nous rappeler pour choisir une autre date » : c'est à elle de rappeler. Le rendez-vous de demain est « annulé », donc B est faux, et personne ne va la rappeler (D).",
                ],
                [
                    'id' => 'q8', 'type' => 'mcq',
                    'text' => "Qu'est-ce que l'homme a perdu ?",
                    'audio_text' => "HOMME : Bonjour, j'ai oublié un sac dans le bus de la ligne douze, ce matin.\nEMPLOYÉE : Comment est-il, votre sac ?\nHOMME : C'est un petit sac à dos noir, avec mon ordinateur dedans. Les clés, je les ai dans ma poche, heureusement.\nEMPLOYÉE : Attendez, je regarde… Oui, nous l'avons !",
                    'options' => [
                        "Ses clés.",
                        "Un sac à dos avec un ordinateur.",
                        "Son portefeuille.",
                        "Son billet de bus.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « un petit sac à dos noir, avec mon ordinateur dedans ». Les clés sont le piège : il les a « dans [sa] poche, heureusement ».",
                ],
                // ── B1 ──
                [
                    'id' => 'q9', 'type' => 'mcq',
                    'text' => "Quelle est la nouveauté annoncée par la mairie ?",
                    'audio_text' => "Bonne nouvelle pour les familles : à partir du mois de septembre, la cantine des écoles de la ville proposera chaque jour un menu végétarien, en plus du menu habituel. Les parents pourront choisir en ligne, une semaine à l'avance. Le prix du repas, lui, ne change pas.",
                    'options' => [
                        "Un menu sans viande sera proposé tous les jours.",
                        "Le prix des repas va baisser.",
                        "La cantine ne servira plus de viande.",
                        "Les parents devront payer en ligne.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. « un menu végétarien, en plus du menu habituel », « chaque jour ». C est le contresens : le menu habituel reste. Le prix « ne change pas », et ce qui se fait en ligne, c'est le choix du menu, pas le paiement.",
                ],
                [
                    'id' => 'q10', 'type' => 'mcq',
                    'text' => "Pourquoi Clara a-t-elle quitté son travail à la banque ?",
                    'audio_text' => "JOURNALISTE : Clara, vous avez travaillé dix ans dans une banque, et aujourd'hui vous êtes boulangère. Pourquoi ce changement ?\nCLARA : Je ne gagnais pas mal ma vie, ce n'est pas la question. Mais je passais mes journées devant un écran sans jamais voir le résultat de mon travail. Aujourd'hui, je fais quelque chose avec mes mains, et le soir, je sais ce que j'ai produit.",
                    'options' => [
                        "Elle ne gagnait pas assez d'argent.",
                        "Elle a été licenciée.",
                        "Elle avait des problèmes avec ses collègues.",
                        "Elle voulait voir concrètement le résultat de son travail.",
                    ],
                    'correct_answer' => 'D',
                    'explanation' => "D. Elle regrettait de « ne jamais voir le résultat de [son] travail » et apprécie maintenant de savoir « ce que [elle a] produit ». L'argent est écarté par elle-même : « Je ne gagnais pas mal ma vie, ce n'est pas la question ».",
                ],
                [
                    'id' => 'q11', 'type' => 'mcq',
                    'text' => "Que conseille le présentateur aux automobilistes ?",
                    'audio_text' => "Point circulation : ce matin, un accident bloque deux voies sur l'autoroute A6 en direction de Paris, à hauteur d'Évry. Il faut compter quarante-cinq minutes de bouchon. Si vous le pouvez, prenez la nationale 7, beaucoup plus fluide, ou reportez votre départ d'une heure.",
                    'options' => [
                        "De prendre l'autoroute A6.",
                        "D'éviter l'autoroute ou de partir plus tard.",
                        "De rouler plus vite.",
                        "D'appeler les secours.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « prenez la nationale 7, beaucoup plus fluide, ou reportez votre départ d'une heure » : deux façons d'éviter le bouchon de l'A6, qui est justement la route à éviter.",
                ],
                [
                    'id' => 'q12', 'type' => 'mcq',
                    'text' => "Que pense l'homme du nouvel appartement ?",
                    'audio_text' => "FEMME : Alors, cet appartement, il te plaît ?\nHOMME : Il est lumineux, c'est vrai, et le quartier est agréable. Mais franchement, pour le prix, il est trop petit : on n'aura même pas de place pour un bureau. Je préfère continuer à chercher.",
                    'options' => [
                        "Il veut le louer tout de suite.",
                        "Il le trouve trop sombre.",
                        "Il le trouve agréable, mais trop petit pour son prix.",
                        "Il n'aime pas le quartier.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. Il reconnaît des qualités (« lumineux », « le quartier est agréable ») mais conclut « pour le prix, il est trop petit » et veut « continuer à chercher ». « lumineux » exclut B, et il aime le quartier.",
                ],
                [
                    'id' => 'q13', 'type' => 'mcq',
                    'text' => "Quel est le but de ce message ?",
                    'audio_text' => "Chers clients, notre magasin fermera exceptionnellement à dix-huit heures ce soir, en raison de l'inventaire annuel. Demain, nous vous accueillerons aux horaires habituels, de neuf heures à vingt heures. Merci de vous diriger dès maintenant vers les caisses si vous avez terminé vos achats.",
                    'options' => [
                        "Annoncer des réductions.",
                        "Annoncer la fermeture définitive du magasin.",
                        "Prévenir d'une fermeture plus tôt que d'habitude.",
                        "Présenter de nouveaux horaires pour toute l'année.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. Le magasin « fermera exceptionnellement à dix-huit heures ce soir » pour l'inventaire. « Exceptionnellement » et « Demain […] aux horaires habituels » montrent que ce n'est ni définitif ni un changement durable.",
                ],
                [
                    'id' => 'q14', 'type' => 'mcq',
                    'text' => "Qu'apprend-on sur le festival de cette année ?",
                    'audio_text' => "Le festival de musique de Carnac revient du douze au quinze juillet. Nouveauté cette année : tous les concerts de l'après-midi seront gratuits ; seuls ceux du soir resteront payants. Les organisateurs attendent vingt mille personnes, soit cinq mille de plus que l'an dernier.",
                    'options' => [
                        "Les concerts de l'après-midi seront gratuits.",
                        "Tous les concerts seront gratuits.",
                        "Le festival attend moins de monde que l'an dernier.",
                        "Le festival durera une semaine.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. « tous les concerts de l'après-midi seront gratuits ; seuls ceux du soir resteront payants ». B généralise à tort. On attend « cinq mille de plus », et le festival dure quatre jours, du 12 au 15.",
                ],
                // ── B2 ──
                [
                    'id' => 'q15', 'type' => 'mcq',
                    'text' => "Selon la chercheuse, pourquoi les gens jettent-ils moins de nourriture ?",
                    'audio_text' => "JOURNALISTE : Les Français jettent moins de nourriture qu'il y a dix ans. Est-ce grâce aux campagnes de sensibilisation ?\nCHERCHEUSE : En partie, mais je serais prudente. Ce qui a le plus changé, c'est le budget : avec la hausse des prix, on fait plus attention à ce qu'on achète. Les campagnes ont surtout aidé ceux qui étaient déjà convaincus.",
                    'options' => [
                        "Surtout grâce aux campagnes de sensibilisation.",
                        "Parce que les supermarchés vendent moins.",
                        "Parce que les familles cuisinent davantage.",
                        "Surtout parce que la nourriture coûte plus cher.",
                    ],
                    'correct_answer' => 'D',
                    'explanation' => "D. « Ce qui a le plus changé, c'est le budget : avec la hausse des prix, on fait plus attention ». Elle relativise les campagnes (« En partie, mais je serais prudente ») : elles n'ont aidé que « ceux qui étaient déjà convaincus ».",
                ],
                [
                    'id' => 'q16', 'type' => 'mcq',
                    'text' => "Quelle est l'attitude de l'architecte face au projet de tour ?",
                    'audio_text' => "On nous présente cette tour comme un symbole de modernité. Je n'ai rien contre les tours en soi ; j'en ai construit. Mais celle-ci tournera le dos au quartier : pas de commerces au rez-de-chaussée, pas de passage public, une entrée gardée. Une tour peut faire vivre une rue ; celle-là va la vider.",
                    'options' => [
                        "Il est opposé à toutes les tours.",
                        "Il critique ce projet précis, qui isolerait le bâtiment du quartier.",
                        "Il soutient le projet sans réserve.",
                        "Il regrette que la tour ne soit pas plus haute.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. Il précise « Je n'ai rien contre les tours en soi ; j'en ai construit », ce qui écarte A. Sa critique vise « celle-ci », qui « tournera le dos au quartier » : sans commerces ni passage public, elle « va [vider] » la rue.",
                ],
                [
                    'id' => 'q17', 'type' => 'mcq',
                    'text' => "Que propose le syndicat ?",
                    'audio_text' => "Le syndicat ne demande pas l'abandon du travail le dimanche, contrairement à ce qui a été dit. Nous demandons qu'il reste fondé sur le volontariat, qu'il soit payé double, et qu'aucun salarié ne puisse être sanctionné pour l'avoir refusé. Sur ces trois points, nous ne céderons pas.",
                    'options' => [
                        "L'interdiction du travail le dimanche.",
                        "Le travail obligatoire un dimanche sur deux.",
                        "Une négociation sur le salaire uniquement.",
                        "Un travail du dimanche volontaire, mieux payé et sans sanction en cas de refus.",
                    ],
                    'correct_answer' => 'D',
                    'explanation' => "D. Les trois conditions sont énumérées : « volontariat », « payé double », et aucune sanction pour un refus. A est justement ce que le syndicat dément : « ne demande pas l'abandon du travail le dimanche, contrairement à ce qui a été dit ».",
                ],
                [
                    'id' => 'q18', 'type' => 'mcq',
                    'text' => "Qu'a montré l'étude présentée ?",
                    'audio_text' => "Une étude menée auprès de deux mille lycéens montre que ceux qui dorment moins de sept heures par nuit n'ont pas de moins bonnes notes en mathématiques. En revanche, leurs résultats baissent nettement dans les exercices qui demandent de la mémoire à long terme, comme l'histoire ou les langues.",
                    'options' => [
                        "Le manque de sommeil fait baisser toutes les notes.",
                        "Les lycéens dorment de plus en plus.",
                        "Le manque de sommeil touche surtout la mémoire à long terme.",
                        "Le sommeil n'a aucun effet sur les résultats.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. Les notes de mathématiques ne bougent pas, mais les résultats « baissent nettement » dans ce qui demande « de la mémoire à long terme ». L'effet existe donc (contre D) mais il n'est pas général (contre A) : « En revanche » marque cette distinction.",
                ],
                [
                    'id' => 'q19', 'type' => 'mcq',
                    'text' => "Pourquoi l'entreprise a-t-elle renoncé à la voiture électrique pour ses livreurs ?",
                    'audio_text' => "JOURNALISTE : Vous aviez annoncé des voitures électriques pour tous vos livreurs. Où en êtes-vous ?\nDIRECTRICE : Nous avons changé d'avis, et pas pour des raisons de prix. En centre-ville, une voiture, même électrique, reste coincée dans les embouteillages. Nos livreurs à vélo cargo font deux fois plus de livraisons par jour. Le choix s'est imposé de lui-même.",
                    'options' => [
                        "Parce que les vélos cargos sont plus efficaces en ville.",
                        "Parce que les voitures électriques coûtaient trop cher.",
                        "Parce que les livreurs refusaient de conduire.",
                        "Parce que la mairie a interdit les voitures.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. Les livreurs à vélo cargo « font deux fois plus de livraisons par jour ». La directrice écarte le prix dès le départ : « pas pour des raisons de prix ». C'est l'efficacité en ville qui a décidé.",
                ],
                [
                    'id' => 'q20', 'type' => 'mcq',
                    'text' => "Que reproche l'auditrice à l'émission ?",
                    'audio_text' => "J'écoute votre émission tous les matins et je l'apprécie. Mais hier, vous avez présenté le cas d'une seule famille comme s'il résumait la situation de tous les agriculteurs. Un témoignage, c'est précieux ; ce n'est pas une statistique. J'aurais aimé que vous le précisiez.",
                    'options' => [
                        "D'avoir invité des agriculteurs.",
                        "D'avoir généralisé à partir d'un seul témoignage.",
                        "D'être diffusée trop tôt le matin.",
                        "D'avoir donné trop de chiffres.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. Elle critique le fait d'avoir présenté « une seule famille comme si [elle] résumait la situation de tous les agriculteurs » : « Un témoignage […] n'est pas une statistique ». Elle n'a rien contre le témoignage lui-même (« c'est précieux »).",
                ],
                // ── C1 ──
                [
                    'id' => 'q21', 'type' => 'mcq',
                    'text' => "Quelle est l'idée principale de l'intervenant ?",
                    'audio_text' => "On répète que les réseaux sociaux enferment chacun dans une bulle d'opinions identiques aux siennes. Les données disent presque l'inverse : on y croise davantage d'avis contraires que dans la vie réelle. Le problème n'est pas l'absence de contradiction, c'est la forme qu'elle prend — brève, brutale, sans visage. On ne sort pas de sa bulle ; on en sort pour se faire insulter, et l'on y retourne plus convaincu qu'avant.",
                    'options' => [
                        "On y rencontre des avis contraires, mais sous une forme qui renforce ses propres convictions.",
                        "Les réseaux sociaux empêchent de rencontrer des opinions différentes.",
                        "Les réseaux sociaux favorisent le dialogue entre opinions opposées.",
                        "Les gens ne s'intéressent pas aux opinions des autres.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. L'intervenant réfute l'idée de la bulle (B) — « on y croise davantage d'avis contraires » — mais montre que la contradiction, « brève, brutale, sans visage », fait qu'on revient « plus convaincu qu'avant ». C serait la conclusion naïve qu'il rejette aussi.",
                ],
                [
                    'id' => 'q22', 'type' => 'mcq',
                    'text' => "Selon l'économiste, que montre l'exemple des autoroutes ?",
                    'audio_text' => "Chaque fois qu'on élargit une autoroute pour réduire les bouchons, le trafic augmente jusqu'à ce que les bouchons reviennent. Ce n'est pas un échec technique : c'est un effet bien connu, qu'on appelle le trafic induit. Une route plus fluide attire des automobilistes qui, jusque-là, prenaient le train ou ne se déplaçaient pas. L'offre crée sa propre demande.",
                    'options' => [
                        "Que les travaux d'élargissement sont mal réalisés.",
                        "Que les automobilistes préfèrent le train.",
                        "Que les bouchons disparaissent après l'élargissement.",
                        "Qu'une offre de transport plus grande attire de nouveaux usagers.",
                    ],
                    'correct_answer' => 'D',
                    'explanation' => "D. C'est le « trafic induit » : une route plus fluide « attire des automobilistes qui […] prenaient le train ou ne se déplaçaient pas ». La formule finale le résume, « L'offre crée sa propre demande ». L'économiste écarte A : « Ce n'est pas un échec technique ».",
                ],
                [
                    'id' => 'q23', 'type' => 'mcq',
                    'text' => "Que veut dire la metteuse en scène à propos des classiques ?",
                    'audio_text' => "On me demande souvent pourquoi je ne « modernise » pas Molière, pourquoi mes comédiens ne jouent pas en jean avec un téléphone à la main. Mais Molière n'a pas besoin qu'on le rende actuel : il l'est. Ce qui vieillit, ce sont les costumes qu'on lui met. Quand on le déguise en contemporain, on rassure le public au lieu de le confronter à un texte qui, lui, n'a pas pris une ride.",
                    'options' => [
                        "Il faut moderniser les classiques pour attirer les jeunes.",
                        "La force actuelle du texte suffit ; le « moderniser » l'affaiblit.",
                        "Les costumes d'époque sont trop chers.",
                        "Molière ne parle plus au public d'aujourd'hui.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « Molière n'a pas besoin qu'on le rende actuel : il l'est ». La mise au goût du jour « rassure le public au lieu de le confronter » au texte. A est la position qu'elle conteste, et D contredit « un texte qui […] n'a pas pris une ride ».",
                ],
                [
                    'id' => 'q24', 'type' => 'mcq',
                    'text' => "Quelle nuance la juriste apporte-t-elle ?",
                    'audio_text' => "JOURNALISTE : Cette loi interdit donc la publicité pour les produits trop sucrés ?\nJURISTE : Pas tout à fait, et la nuance compte. Elle ne l'interdit qu'aux heures où les enfants regardent la télévision, et seulement sur les chaînes traditionnelles. Sur les plateformes en ligne, où les jeunes passent pourtant le plus de temps, rien ne change. On peut donc douter de son efficacité.",
                    'options' => [
                        "La loi interdit totalement cette publicité.",
                        "La loi vise surtout les plateformes en ligne.",
                        "L'interdiction est limitée et ne touche pas les plateformes en ligne.",
                        "La loi n'a pas encore été votée.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. « Pas tout à fait » : l'interdiction vaut seulement « aux heures où les enfants regardent la télévision » et « sur les chaînes traditionnelles ». « Sur les plateformes en ligne […] rien ne change », d'où son doute sur l'efficacité.",
                ],
                [
                    'id' => 'q25', 'type' => 'mcq',
                    'text' => "Pourquoi l'historien se méfie-t-il de l'expression « âge d'or » ?",
                    'audio_text' => "Chaque époque s'invente un âge d'or derrière elle. Les Grecs le plaçaient dans un passé mythique ; nos grands-parents, dans les années de leur jeunesse. L'expression nous renseigne moins sur la période qu'elle désigne que sur l'inquiétude de ceux qui l'emploient. Quand on dit « c'était mieux avant », on parle surtout de ce qui nous fait peur aujourd'hui.",
                    'options' => [
                        "Parce que le passé était en réalité plus difficile que le présent.",
                        "Parce que les Grecs l'ont inventée.",
                        "Parce qu'elle révèle surtout les craintes de ceux qui l'utilisent.",
                        "Parce qu'elle est réservée aux historiens.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. L'expression « nous renseigne moins sur la période qu'elle désigne que sur l'inquiétude de ceux qui l'emploient ». A est tentant mais l'historien ne juge pas le passé lui-même : il analyse ce que l'expression dit de nous.",
                ],
                // ── C2 ──
                [
                    'id' => 'q26', 'type' => 'mcq',
                    'text' => "Quelle est la thèse de la philosophe ?",
                    'audio_text' => "On oppose volontiers l'émotion à la raison, comme si l'une troublait ce que l'autre éclaire. C'est méconnaître le rôle de l'émotion dans le jugement lui-même. Un patient dont les zones émotionnelles du cerveau sont lésées ne devient pas un calculateur parfait : il devient incapable de décider, parce que plus rien ne lui importe. Sans émotion, la raison tourne à vide ; elle sait comparer, elle ne sait plus préférer.",
                    'options' => [
                        "L'émotion est une condition de la décision rationnelle.",
                        "L'émotion perturbe le raisonnement et doit être maîtrisée.",
                        "La raison est inutile pour prendre une décision.",
                        "Les patients cérébrolésés raisonnent mieux que les autres.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. Sans émotion, le patient devient « incapable de décider » : « elle sait comparer, elle ne sait plus préférer ». L'émotion est donc nécessaire au jugement. B est l'opposition traditionnelle que la philosophe réfute, et D inverse l'exemple : le patient n'est pas « un calculateur parfait ».",
                ],
                [
                    'id' => 'q27', 'type' => 'mcq',
                    'text' => "Que reproche le critique au roman dont il parle ?",
                    'audio_text' => "C'est un livre qui a tout pour plaire, et c'est précisément ce qui me gêne. Chaque chapitre se termine sur la petite surprise attendue, chaque personnage évolue exactement comme il faut, chaque émotion est annoncée avant d'être ressentie. On referme le roman sans y avoir rien laissé de soi. L'auteur nous a si bien pris par la main qu'il ne nous a jamais laissé marcher.",
                    'options' => [
                        "D'être trop difficile à lire.",
                        "D'avoir des personnages invraisemblables.",
                        "D'avoir une fin trop surprenante.",
                        "D'être si calculé qu'il ne laisse aucune place au lecteur.",
                    ],
                    'correct_answer' => 'D',
                    'explanation' => "D. Tout est prévu — « la petite surprise attendue », l'émotion « annoncée avant d'être ressentie » — et l'image finale le dit : l'auteur « nous a si bien pris par la main qu'il ne nous a jamais laissé marcher ». « a tout pour plaire » est ici une critique, pas un éloge.",
                ],
                [
                    'id' => 'q28', 'type' => 'mcq',
                    'text' => "Quel paradoxe l'intervenant souligne-t-il ?",
                    'audio_text' => "Jamais nous n'avons eu autant d'outils pour gagner du temps : machines, logiciels, transports rapides. Et jamais le sentiment de manquer de temps n'a été aussi répandu. C'est que chaque minute gagnée est aussitôt réinvestie : le courriel, qui devait remplacer la lettre, a surtout multiplié les messages. Nous n'avons pas accéléré nos tâches ; nous avons accéléré le rythme auquel nous en acceptons de nouvelles.",
                    'options' => [
                        "Les outils modernes font perdre du temps parce qu'ils fonctionnent mal.",
                        "Le temps gagné grâce aux outils est aussitôt rempli par de nouvelles tâches.",
                        "Les gens ont moins de travail qu'avant mais se plaignent davantage.",
                        "Le courriel a fait disparaître le manque de temps.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « chaque minute gagnée est aussitôt réinvestie » ; l'exemple du courriel, qui « a surtout multiplié les messages », le montre. Les outils fonctionnent (contre A) : c'est notre usage qui annule le gain.",
                ],
                [
                    'id' => 'q29', 'type' => 'mcq',
                    'text' => "Quelle conception de la traduction défend la traductrice ?",
                    'audio_text' => "On attend du traducteur qu'il soit invisible, qu'on oublie, en le lisant, que le texte a d'abord été écrit dans une autre langue. Je crois l'inverse souhaitable, au moins par moments. Une traduction qui gomme toute étrangeté trahit doublement : elle prive le lecteur de ce qui fait la singularité de l'original, et elle lui laisse croire que le monde entier pense comme lui. Il faut parfois que le français résiste un peu, qu'il porte la trace de l'autre langue.",
                    'options' => [
                        "Une bonne traduction doit se lire comme un texte écrit directement en français.",
                        "Le traducteur doit simplifier les textes pour le lecteur.",
                        "Il vaut mieux lire les œuvres dans leur langue d'origine.",
                        "Une traduction doit parfois garder la trace de l'étrangeté de l'original.",
                    ],
                    'correct_answer' => 'D',
                    'explanation' => "D. « Il faut parfois que le français résiste un peu, qu'il porte la trace de l'autre langue ». A est l'attente commune qu'elle conteste (« Je crois l'inverse souhaitable »), et la nuance « au moins par moments » montre qu'elle ne rejette pas la traduction elle-même (contre C).",
                ],
            ],
        ],
    ],
];
