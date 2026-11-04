<?php

/**
 * TEF (TEF Canada, TEF Études) — épreuve « Compréhension écrite ».
 *
 * Écrit à la main d'après les documents officiels du Français des affaires
 * (CCI Paris Île-de-France), lus le 2026-11-04 :
 *  - https://www.lefrancaisdesaffaires.fr/candidat/test-evaluation-francais/tef-canada/presentation/
 *    et .../tef-etudes/presentation/ : « Contenu : 40 questions », « Durée :
 *    1 heure », « Questionnaire à choix multiples (QCM) », bonne réponse =
 *    1 point ;
 *  - https://www.lefrancaisdesaffaires.fr/wp-content/uploads/2024/10/tef-exemples-epreuves-ce.pdf
 *    (exemples officiels) : quatre choix A, B, C, D, et cinq sections dans cet
 *    ordre, reprises ici —
 *      questions 1 à 7   : documents de la vie quotidienne, une question chacun ;
 *      questions 8 à 17  : six phrases puis deux textes à trous (mot manquant) ;
 *      questions 18 à 22 : lecture rapide, quatre séries de quatre textes courts
 *                          puis une série de quatre graphiques ;
 *      questions 23 à 32 : cinq documents administratifs et professionnels,
 *                          deux questions chacun ;
 *      questions 33 à 40 : quatre articles de presse, deux questions chacun.
 *
 * Les documents sont originaux. Ce sont le FORMAT, le nombre d'items, le nombre
 * de choix et l'ordre des sections qui reprennent l'épreuve, pas ses contenus.
 *
 * Une adaptation, dite à l'apprenant : à la question 22, l'épreuve montre
 * quatre graphiques. Notre lecteur n'affiche pas d'image dans ce format ; les
 * quatre courbes sont donc décrites en une ligne chacune.
 *
 * POURQUOI LE DOSSIER « B1 ». Le TEF n'a pas de niveaux : une seule épreuve
 * situe le candidat. L'application range chaque exercice à un niveau et le sert
 * par ce niveau ; la série complète est rangée au B1, le niveau par défaut d'un
 * apprenant. Les questions vont, elles, des documents simples aux articles.
 */

return [
    'exam' => 'tef',
    'level' => 'B1',
    'section' => 'comprehension-ecrite',
    'parts' => [
        [
            'part' => 1,
            'name' => 'Compréhension écrite, partie 1',
            'type' => 'mcq',
            'title' => 'TEF · Compréhension écrite — Série complète de 40 questions',
            'description' => "Les cinq sections de l'épreuve, dans l'ordre officiel : documents du quotidien, textes à trous, lecture rapide, documents professionnels, articles de presse. Quatre choix par question.",
            'content' => [
                'instructions' => "Lisez les documents de chaque section, puis choisissez pour chaque question la seule bonne réponse : A, B, C ou D. Les questions 8 à 13 contiennent leur propre phrase. À l'épreuve, vous avez 1 heure pour les 40 questions, et une bonne réponse vaut 1 point.\n\nÀ noter : à la question 22, l'épreuve montre quatre graphiques ; ici, chaque graphique est décrit en une ligne.",
                'passage' => "━━ SECTION 1 — Documents de la vie quotidienne (questions 1 à 7) ━━\n\n"
                    ."DOCUMENT 1 — Panneau à l'entrée d'un parking\n"
                    ."Parking réservé aux clients du magasin. Stationnement limité à 2 heures. Au-delà, le véhicule sera enlevé aux frais du propriétaire.\n\n"
                    ."DOCUMENT 2 — Carton d'invitation\n"
                    ."Nous fêtons nos dix ans de mariage ! Samedi 14 juin, à partir de 19 h, au restaurant La Fontaine. "
                    ."Merci de nous répondre avant le 1er juin. Pas de cadeau : votre présence nous suffit !\nAïcha et Bruno\n\n"
                    ."DOCUMENT 3 — Au dos d'une carte de transport\n"
                    ."Pour activer votre carte, validez-la une première fois à une borne. Elle est ensuite valable 30 jours à compter de cette "
                    ."première validation. Conservez le reçu : il vous sera demandé en cas de perte.\n\n"
                    ."DOCUMENT 4 — Publicité\n"
                    ."SALLE FORME+ — Un mois offert pour toute inscription d'un an avant le 31 janvier ! Cours collectifs inclus. Accès au sauna en supplément.\n\n"
                    ."DOCUMENT 5 — Avis de la mairie\n"
                    ."En raison de travaux sur les canalisations, l'eau sera coupée le jeudi 9 mai, de 8 h à 12 h, rue des Acacias et rue Pasteur. "
                    ."Nous vous conseillons de prévoir une réserve d'eau. Le service sera rétabli dans l'après-midi au plus tard.\n\n"
                    ."DOCUMENT 6 — Résultats d'une enquête\n"
                    ."62 % des personnes interrogées déclarent lire au moins un livre par mois. Parmi ces lecteurs réguliers, un sur trois lit "
                    ."surtout sur écran (liseuse, tablette ou téléphone).\n\n"
                    ."DOCUMENT 7 — Sur la porte d'un cabinet médical\n"
                    ."Le cabinet sera fermé du 1er au 15 août. Pendant cette période, le docteur Lemoine, 12 rue des Lilas, reçoit les patients "
                    ."du cabinet. En cas d'urgence vitale, composez le 15.\n\n"

                    ."━━ SECTION 2 — Phrases et textes à trous (questions 8 à 17) ━━\n\n"
                    ."Les questions 8 à 13 portent chacune sur une phrase, écrite dans la question.\n\n"
                    ."TEXTE A (questions 14 et 15) — Le covoiturage\n"
                    ."Partager sa voiture pour aller au travail permet de réduire les ______ (14) de transport. De plus en plus de salariés "
                    ."s'y mettent, d'autant que certaines entreprises ______ (15) une prime à ceux qui s'organisent ainsi.\n\n"
                    ."TEXTE B (questions 16 et 17) — Le télétravail\n"
                    ."Si le télétravail offre une grande ______ (16) dans l'organisation de la journée, il peut aussi ______ (17) un sentiment "
                    ."d'isolement chez les salariés qui vivent seuls.\n\n"

                    ."━━ SECTION 3 — Lecture rapide (questions 18 à 22) ━━\n\n"
                    ."SÉRIE 1 (question 18) — Offres d'emploi\n"
                    ."Offre 1 : Cabinet comptable recherche assistant(e), 5 ans d'expérience minimum, maîtrise des logiciels de paie.\n"
                    ."Offre 2 : Librairie cherche vendeur(se) pour l'été. Première expérience bienvenue mais non exigée : nous vous formons.\n"
                    ."Offre 3 : Hôpital recrute infirmier(ère) diplômé(e), expérience en service d'urgence souhaitée.\n"
                    ."Offre 4 : Agence de voyages recherche conseiller(ère) confirmé(e), trilingue, ayant déjà travaillé dans le tourisme.\n\n"
                    ."SÉRIE 2 (question 19) — Hôtels\n"
                    ."Hôtel A : Au cœur de la vieille ville, chambres climatisées, petit-déjeuner compris. Animaux non admis.\n"
                    ."Hôtel B : Face à la mer, piscine chauffée, parking privé. Nos amis à quatre pattes ne sont pas acceptés.\n"
                    ."Hôtel C : À la campagne, grand jardin, chiens et chats bienvenus (supplément de 8 € par nuit).\n"
                    ."Hôtel D : Près de la gare, idéal pour les voyageurs d'affaires, salle de réunion. Établissement non-fumeur, sans animaux.\n\n"
                    ."SÉRIE 3 (question 20) — Formations\n"
                    ."Formation 1 : Anglais des affaires — 10 samedis en salle, à Lyon.\n"
                    ."Formation 2 : Gestion de projet — 3 jours en entreprise, puis un suivi par téléphone.\n"
                    ."Formation 3 : Comptabilité — 100 % en ligne, à votre rythme, sans aucun déplacement.\n"
                    ."Formation 4 : Prise de parole en public — cours du soir en petit groupe, dans nos locaux.\n\n"
                    ."SÉRIE 4 (question 21) — Restaurants\n"
                    ."Le Comptoir : ouvert du mardi au samedi, midi et soir.\n"
                    ."La Table d'Hugo : ouvert tous les jours à midi ; le soir du jeudi au dimanche.\n"
                    ."Chez Nina : ouvert du lundi au vendredi, uniquement le midi.\n"
                    ."L'Escale : ouvert le soir du lundi au samedi ; fermé le dimanche.\n\n"
                    ."SÉRIE 5 (question 22) — Graphiques : ventes de vélos d'une enseigne, de 2019 à 2025\n"
                    ."Graphique 1 : hausse régulière d'année en année.\n"
                    ."Graphique 2 : baisse de 2019 à 2023, puis légère remontée en 2024 et 2025.\n"
                    ."Graphique 3 : chiffres stables sur toute la période.\n"
                    ."Graphique 4 : forte hausse jusqu'en 2022, puis chute brutale.\n\n"

                    ."━━ SECTION 4 — Documents administratifs et professionnels (questions 23 à 32) ━━\n\n"
                    ."DOCUMENT A (questions 23 et 24) — Courriel\n"
                    ."Madame Kessler,\nNous vous confirmons la réservation de la salle Opale pour votre séminaire du 18 au 19 novembre (20 personnes). "
                    ."Le vidéoprojecteur et le café d'accueil sont inclus. Le déjeuner est à commander séparément, au plus tard 7 jours avant. "
                    ."Toute annulation intervenant moins de 15 jours avant la date entraînera la facturation de 50 % du montant total.\n"
                    ."Le service des réservations\n\n"
                    ."DOCUMENT B (questions 25 et 26) — Note de service\n"
                    ."À compter du 1er mars, les frais de déplacement seront remboursés sur présentation des justificatifs originaux, déposés dans "
                    ."l'application interne dans un délai de 30 jours. Les trajets en train restent à privilégier ; l'avion n'est autorisé que pour "
                    ."les trajets de plus de quatre heures en train. Les notes de frais incomplètes seront renvoyées à leur auteur, sans remboursement "
                    ."jusqu'à leur régularisation.\nLa direction financière\n\n"
                    ."DOCUMENT C (questions 27 et 28) — Lettre d'un organisme public\n"
                    ."Monsieur,\nNous avons bien reçu votre demande d'aide au logement. Votre dossier ne peut cependant pas être examiné : il manque "
                    ."votre dernier avis d'imposition. Nous vous remercions de nous le faire parvenir dans un délai d'un mois. Passé ce délai, votre "
                    ."demande sera classée sans suite et vous devrez en déposer une nouvelle. L'aide, si elle vous est accordée, sera versée à compter "
                    ."du mois de dépôt de votre dossier complet.\n\n"
                    ."DOCUMENT D (questions 29 et 30) — Extrait d'un accord d'entreprise\n"
                    ."Article 4 — Le télétravail est ouvert aux salariés ayant au moins six mois d'ancienneté, dans la limite de deux jours par semaine. "
                    ."Il fait l'objet d'une demande écrite au responsable, qui dispose de quinze jours pour répondre ; tout refus doit être motivé. "
                    ."Le salarié reste joignable aux horaires habituels de son service.\n\n"
                    ."DOCUMENT E (questions 31 et 32) — Compte rendu de réunion\n"
                    ."Présents : M. Ortiz, Mme Achour, Mme Leclerc. Excusé : M. Varga.\n"
                    ."1. Le lancement du nouveau site, prévu en avril, est repoussé à juin : les tests de paiement ne sont pas terminés.\n"
                    ."2. Mme Achour présentera un budget révisé lors de la prochaine réunion.\n"
                    ."3. La formation des vendeurs au nouvel outil est maintenue en mai, comme prévu.\n"
                    ."Prochaine réunion : le 12 mars.\n\n"

                    ."━━ SECTION 5 — Articles de presse (questions 33 à 40) ━━\n\n"
                    ."ARTICLE 1 (questions 33 et 34) — Les abeilles sauvages, grandes oubliées\n"
                    ."Quand on parle de déclin des pollinisateurs, on pense à l'abeille domestique et à ses ruches. Pourtant, la France compte près "
                    ."d'un millier d'espèces d'abeilles sauvages, dont beaucoup vivent seules et nichent dans le sol. Ce sont elles qui assurent une "
                    ."grande partie de la pollinisation des cultures. Paradoxe relevé par plusieurs chercheurs : l'installation massive de ruches en "
                    ."ville, présentée comme un geste écologique, pourrait leur nuire, car les abeilles domestiques, très nombreuses, accaparent les "
                    ."fleurs disponibles. Mieux vaudrait, disent-ils, planter des fleurs locales et laisser des coins de terre nue.\n\n"
                    ."ARTICLE 2 (questions 35 et 36) — Le ticket de caisse papier en voie de disparition\n"
                    ."Depuis que les commerces ne sont plus obligés d'imprimer le ticket de caisse, la plupart demandent désormais au client s'il le "
                    ."souhaite. L'objectif affiché est de réduire le gaspillage de papier, souvent traité avec des produits chimiques. Les associations "
                    ."de consommateurs restent vigilantes : sans ticket, il devient plus difficile de vérifier une erreur de prix ou de prouver un achat "
                    ."pour faire jouer la garantie. Elles conseillent de le demander pour tout achat important, ou d'accepter sa version électronique.\n\n"
                    ."ARTICLE 3 (questions 37 et 38) — Réparer plutôt que jeter\n"
                    ."Depuis quelques années, un indice noté sur dix accompagne la vente des téléphones, ordinateurs et lave-linge : il indique si "
                    ."l'appareil se répare facilement. Les fabricants les mieux notés en ont fait un argument de vente. Le bilan reste mitigé : la note "
                    ."est calculée à partir des informations fournies par les fabricants eux-mêmes, et une pièce disponible n'est pas forcément une "
                    ."pièce abordable. Un écran de téléphone remplacé coûte parfois la moitié du prix d'un appareil neuf, ce qui décourage la réparation.\n\n"
                    ."ARTICLE 4 (questions 39 et 40) — Musées gratuits : une fausse bonne idée ?\n"
                    ."Rendre les musées gratuits, voilà une mesure qui semble faire l'unanimité. Les expériences menées ici et là invitent pourtant à "
                    ."la prudence. La fréquentation augmente, certes, mais ce sont surtout les habitués qui viennent plus souvent ; ceux qui ne "
                    ."franchissaient jamais la porte continuent de rester dehors. Le prix n'était donc pas le principal obstacle. Ce qui éloigne une "
                    ."partie du public, c'est le sentiment que ces lieux « ne sont pas pour eux ». Contre cela, la gratuité ne peut rien ; la médiation, "
                    ."les ateliers et les horaires du soir, peut-être davantage.",
            ],
            'questions' => [
                // ── Section 1 : documents de la vie quotidienne ──
                [
                    'id' => 'q1', 'type' => 'mcq',
                    'text' => "Document 1 — Ce panneau informe que :",
                    'options' => [
                        "le parking est gratuit pour tout le monde.",
                        "le magasin est fermé.",
                        "le stationnement est limité dans le temps.",
                        "le parking est réservé aux livraisons.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. « Stationnement limité à 2 heures » : au-delà, le véhicule est enlevé. Le parking est réservé « aux clients du magasin », donc pas à tout le monde (A), et rien ne parle de livraisons.",
                ],
                [
                    'id' => 'q2', 'type' => 'mcq',
                    'text' => "Document 2 — Les invités doivent :",
                    'options' => [
                        "répondre avant le 1er juin.",
                        "apporter un cadeau.",
                        "arriver à midi.",
                        "réserver leur table au restaurant.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. « Merci de nous répondre avant le 1er juin ». Le cadeau est explicitement exclu (« Pas de cadeau »), et la fête commence « à partir de 19 h ».",
                ],
                [
                    'id' => 'q3', 'type' => 'mcq',
                    'text' => "Document 3 — La carte de transport est valable :",
                    'options' => [
                        "30 jours à partir de l'achat.",
                        "un an.",
                        "seulement si l'on garde le reçu.",
                        "30 jours à partir de la première validation.",
                    ],
                    'correct_answer' => 'D',
                    'explanation' => "D. « valable 30 jours à compter de cette première validation ». Le point de départ n'est pas l'achat mais la première validation. Le reçu sert « en cas de perte », il ne conditionne pas la validité.",
                ],
                [
                    'id' => 'q4', 'type' => 'mcq',
                    'text' => "Document 4 — Cette publicité propose :",
                    'options' => [
                        "l'accès gratuit au sauna.",
                        "un mois gratuit pour un abonnement d'un an.",
                        "des cours collectifs payants.",
                        "une réduction valable toute l'année.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « Un mois offert pour toute inscription d'un an ». Le sauna est « en supplément », donc payant, et les cours collectifs sont « inclus ». L'offre est limitée dans le temps : « avant le 31 janvier ».",
                ],
                [
                    'id' => 'q5', 'type' => 'mcq',
                    'text' => "Document 5 — Cet avis sert à :",
                    'options' => [
                        "annoncer la fermeture d'une rue aux voitures.",
                        "demander aux habitants de payer des travaux.",
                        "informer d'une hausse du prix de l'eau.",
                        "prévenir d'une coupure d'eau temporaire.",
                    ],
                    'correct_answer' => 'D',
                    'explanation' => "D. « l'eau sera coupée le jeudi 9 mai, de 8 h à 12 h » et « le service sera rétabli dans l'après-midi au plus tard » : la coupure est limitée dans le temps. L'avis ne parle ni de circulation ni de prix.",
                ],
                [
                    'id' => 'q6', 'type' => 'mcq',
                    'text' => "Document 6 — D'après cette enquête :",
                    'options' => [
                        "la majorité des personnes lit sur écran.",
                        "62 % des personnes lisent sur écran.",
                        "un lecteur régulier sur trois lit surtout sur écran.",
                        "les gens lisent moins qu'avant.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. « Parmi ces lecteurs réguliers, un sur trois lit surtout sur écran ». Le chiffre de 62 % concerne ceux qui lisent un livre par mois, pas la lecture sur écran (B). L'enquête ne compare pas avec le passé (D).",
                ],
                [
                    'id' => 'q7', 'type' => 'mcq',
                    'text' => "Document 7 — Pendant la fermeture du cabinet, les patients :",
                    'options' => [
                        "peuvent consulter le docteur Lemoine.",
                        "ne peuvent voir aucun médecin.",
                        "doivent appeler le 15 pour tout problème.",
                        "doivent attendre le 15 août.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. « le docteur Lemoine […] reçoit les patients du cabinet ». Le 15 n'est à composer qu'« en cas d'urgence vitale », pas pour tout problème (C).",
                ],
                // ── Section 2 : phrases et textes à trous ──
                [
                    'id' => 'q8', 'type' => 'mcq',
                    'text' => "Pour ouvrir un compte bancaire, il faut présenter une ______ d'identité.",
                    'options' => [
                        "feuille",
                        "carte",
                        "page",
                        "case",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. La « carte d'identité » est le document officiel. On peut aussi dire « une pièce d'identité », mais « feuille », « page » et « case » d'identité n'existent pas.",
                ],
                [
                    'id' => 'q9', 'type' => 'mcq',
                    'text' => "À cause de la pluie, le concert a été ______ au mois prochain.",
                    'options' => [
                        "reporté",
                        "annulé",
                        "retardé",
                        "supprimé",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. « Reporter à » une date, c'est déplacer un événement à plus tard : « reporté au mois prochain ». « Annulé » et « supprimé » signifient qu'il n'aura pas lieu, ce qui contredit « au mois prochain ». « Retardé » se dit d'un léger décalage (le concert a commencé avec trente minutes de retard), pas d'un renvoi à un autre mois.",
                ],
                [
                    'id' => 'q10', 'type' => 'mcq',
                    'text' => "Les candidats doivent ______ leur dossier avant le 15 mars.",
                    'options' => [
                        "poser",
                        "apposer",
                        "reposer",
                        "déposer",
                    ],
                    'correct_answer' => 'D',
                    'explanation' => "D. On « dépose » un dossier, une demande, une candidature : on le remet officiellement. « Apposer » se dit d'une signature ou d'un tampon, « reposer » signifie remettre en place ou se détendre.",
                ],
                [
                    'id' => 'q11', 'type' => 'mcq',
                    'text' => "Malgré la crise, l'entreprise a ______ ses bénéfices de 10 %.",
                    'options' => [
                        "accroché",
                        "accru",
                        "accordé",
                        "accouru",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « Accroître » veut dire augmenter ; son participe passé est « accru ». « Accroché » (suspendu), « accordé » (donné, permis) et « accouru » (venu en courant) se ressemblent à l'oreille mais n'ont pas ce sens.",
                ],
                [
                    'id' => 'q12', 'type' => 'mcq',
                    'text' => "Ce médicament doit être pris à jeun, c'est-à-dire ______.",
                    'options' => [
                        "après le repas",
                        "avec un verre de lait",
                        "sans avoir mangé",
                        "uniquement le soir",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. « À jeun » signifie l'estomac vide, sans avoir rien mangé. « C'est-à-dire » annonce une reformulation : seule C dit la même chose ; A en est le contraire.",
                ],
                [
                    'id' => 'q13', 'type' => 'mcq',
                    'text' => "Le directeur a ______ la proposition : il ne veut plus en entendre parler.",
                    'options' => [
                        "adopté",
                        "retenu",
                        "rejeté",
                        "approuvé",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. La suite, « il ne veut plus en entendre parler », exige un refus : « rejeter ». « Adopter », « retenir » et « approuver » expriment au contraire l'accord.",
                ],
                [
                    'id' => 'q14', 'type' => 'mcq',
                    'text' => "Texte A, trou (14) : « réduire les ______ de transport »",
                    'options' => [
                        "frais",
                        "factures",
                        "monnaies",
                        "payes",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. « Les frais de transport » désigne ce que coûtent les déplacements. « Factures » ne se construit pas ainsi (on parle de la facture d'un transporteur), et « monnaies » et « payes » n'ont pas ce sens.",
                ],
                [
                    'id' => 'q15', 'type' => 'mcq',
                    'text' => "Texte A, trou (15) : « certaines entreprises ______ une prime »",
                    'options' => [
                        "touchent",
                        "gagnent",
                        "reçoivent",
                        "versent",
                    ],
                    'correct_answer' => 'D',
                    'explanation' => "D. C'est l'entreprise qui donne la prime : elle la « verse ». « Toucher », « gagner » et « recevoir » une prime désignent ce que fait le salarié qui la perçoit — le sujet ne convient pas.",
                ],
                [
                    'id' => 'q16', 'type' => 'mcq',
                    'text' => "Texte B, trou (16) : « une grande ______ dans l'organisation de la journée »",
                    'options' => [
                        "raideur",
                        "souplesse",
                        "lourdeur",
                        "dureté",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. Le télétravail « offre » un avantage : la « souplesse », c'est-à-dire la flexibilité. « Si… il peut aussi » oppose cet avantage à un inconvénient qui vient ensuite ; « raideur », « lourdeur » et « dureté » seraient déjà des inconvénients.",
                ],
                [
                    'id' => 'q17', 'type' => 'mcq',
                    'text' => "Texte B, trou (17) : « il peut aussi ______ un sentiment d'isolement »",
                    'options' => [
                        "empêcher",
                        "éviter",
                        "supprimer",
                        "engendrer",
                    ],
                    'correct_answer' => 'D',
                    'explanation' => "D. Après l'avantage, le texte annonce un inconvénient : le télétravail peut « engendrer », c'est-à-dire provoquer, l'isolement. « Empêcher », « éviter » et « supprimer » en feraient un avantage, ce qui contredit le « Si… aussi ».",
                ],
                // ── Section 3 : lecture rapide ──
                [
                    'id' => 'q18', 'type' => 'mcq',
                    'text' => "Série 1 — Quelle offre convient à une personne sans aucune expérience ?",
                    'options' => ["Offre 1", "Offre 2", "Offre 3", "Offre 4"],
                    'correct_answer' => 'B',
                    'explanation' => "B. L'offre 2 dit « Première expérience bienvenue mais non exigée : nous vous formons ». Les trois autres demandent de l'expérience (« 5 ans d'expérience minimum », « expérience […] souhaitée », « confirmé(e) »).",
                ],
                [
                    'id' => 'q19', 'type' => 'mcq',
                    'text' => "Série 2 — Quel hôtel accepte les chiens ?",
                    'options' => ["Hôtel A", "Hôtel B", "Hôtel C", "Hôtel D"],
                    'correct_answer' => 'C',
                    'explanation' => "C. « chiens et chats bienvenus », avec un supplément. L'hôtel B emploie une périphrase à repérer en lecture rapide : « Nos amis à quatre pattes ne sont pas acceptés » veut dire que les animaux sont refusés.",
                ],
                [
                    'id' => 'q20', 'type' => 'mcq',
                    'text' => "Série 3 — Quelle formation peut se suivre sans se déplacer ?",
                    'options' => ["Formation 1", "Formation 2", "Formation 3", "Formation 4"],
                    'correct_answer' => 'C',
                    'explanation' => "C. « 100 % en ligne, à votre rythme, sans aucun déplacement ». La formation 2 n'a qu'un suivi à distance, après « 3 jours en entreprise ».",
                ],
                [
                    'id' => 'q21', 'type' => 'mcq',
                    'text' => "Série 4 — Quel restaurant est ouvert le dimanche soir ?",
                    'options' => ["Le Comptoir", "La Table d'Hugo", "Chez Nina", "L'Escale"],
                    'correct_answer' => 'B',
                    'explanation' => "B. La Table d'Hugo est ouverte « le soir du jeudi au dimanche ». Le Comptoir ferme après le samedi, Chez Nina n'ouvre que le midi en semaine, et L'Escale est « fermé le dimanche ».",
                ],
                [
                    'id' => 'q22', 'type' => 'mcq',
                    'text' => "Série 5 — Quel graphique correspond au commentaire : « Après plusieurs années de recul, les ventes repartent timidement à la hausse » ?",
                    'options' => ["Graphique 1", "Graphique 2", "Graphique 3", "Graphique 4"],
                    'correct_answer' => 'B',
                    'explanation' => "B. « plusieurs années de recul » correspond à la baisse de 2019 à 2023, et « repartent timidement » à la « légère remontée » de 2024-2025. Le graphique 4 montre l'inverse : une hausse, puis une chute.",
                ],
                // ── Section 4 : documents administratifs et professionnels ──
                [
                    'id' => 'q23', 'type' => 'mcq',
                    'text' => "Document A — Qu'est-ce qui est compris dans la réservation ?",
                    'options' => [
                        "Le déjeuner des participants.",
                        "L'hébergement des participants.",
                        "Le vidéoprojecteur et le café d'accueil.",
                        "Le transport depuis la gare.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. « Le vidéoprojecteur et le café d'accueil sont inclus ». Le déjeuner, lui, « est à commander séparément ».",
                ],
                [
                    'id' => 'q24', 'type' => 'mcq',
                    'text' => "Document A — Si Mme Kessler annule le 10 novembre :",
                    'options' => [
                        "elle paiera la moitié du prix.",
                        "elle ne paiera rien.",
                        "elle paiera la totalité du prix.",
                        "elle devra choisir une autre date.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. Le séminaire commence le 18 novembre : le 10, c'est moins de 15 jours avant. Or « toute annulation intervenant moins de 15 jours avant la date entraînera la facturation de 50 % », soit la moitié.",
                ],
                [
                    'id' => 'q25', 'type' => 'mcq',
                    'text' => "Document B — Pour être remboursé, un salarié doit :",
                    'options' => [
                        "envoyer des photocopies de ses justificatifs par courrier.",
                        "déposer ses justificatifs originaux dans l'application dans les 30 jours.",
                        "demander l'accord de la direction avant chaque déplacement.",
                        "voyager uniquement en avion.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. Les frais sont remboursés « sur présentation des justificatifs originaux, déposés dans l'application interne dans un délai de 30 jours ». Le train est « à privilégier », l'avion n'étant autorisé que pour les longs trajets (contre D).",
                ],
                [
                    'id' => 'q26', 'type' => 'mcq',
                    'text' => "Document B — Que se passe-t-il si une note de frais est incomplète ?",
                    'options' => [
                        "Elle est renvoyée au salarié et n'est remboursée qu'une fois complétée.",
                        "Elle est refusée définitivement.",
                        "Elle est remboursée à moitié.",
                        "Elle est complétée par la direction financière.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. Elle est « renvoyée à [son] auteur, sans remboursement jusqu'à [sa] régularisation » : le remboursement est suspendu, pas perdu. « jusqu'à » montre que le refus n'est pas définitif (contre B).",
                ],
                [
                    'id' => 'q27', 'type' => 'mcq',
                    'text' => "Document C — Pourquoi le dossier ne peut-il pas être examiné ?",
                    'options' => [
                        "Il a été envoyé trop tard.",
                        "Le demandeur n'a pas droit à l'aide.",
                        "Le demandeur n'a pas signé sa demande.",
                        "Un document manque.",
                    ],
                    'correct_answer' => 'D',
                    'explanation' => "D. « il manque votre dernier avis d'imposition ». La lettre ne se prononce pas encore sur le droit à l'aide (B) : elle dit « si elle vous est accordée ».",
                ],
                [
                    'id' => 'q28', 'type' => 'mcq',
                    'text' => "Document C — Si le demandeur envoie le document après plus d'un mois :",
                    'options' => [
                        "son dossier sera quand même examiné.",
                        "il devra déposer une nouvelle demande.",
                        "l'aide sera versée avec retard.",
                        "il paiera une pénalité.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « Passé ce délai, votre demande sera classée sans suite et vous devrez en déposer une nouvelle ». « Classée sans suite » signifie qu'on arrête de la traiter : elle ne sera pas examinée (contre A).",
                ],
                [
                    'id' => 'q29', 'type' => 'mcq',
                    'text' => "Document D — Qui peut demander à télétravailler ?",
                    'options' => [
                        "Tous les salariés, dès leur arrivée.",
                        "Seulement les responsables de service.",
                        "Les salariés présents depuis au moins six mois.",
                        "Les salariés qui habitent loin de l'entreprise.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. Le télétravail est « ouvert aux salariés ayant au moins six mois d'ancienneté ». L'ancienneté, c'est le temps passé dans l'entreprise : un nouvel arrivant n'y a pas droit (contre A).",
                ],
                [
                    'id' => 'q30', 'type' => 'mcq',
                    'text' => "Document D — Que doit faire le responsable qui refuse une demande ?",
                    'options' => [
                        "Rien de particulier.",
                        "Proposer trois jours de télétravail à la place.",
                        "Expliquer les raisons de son refus.",
                        "Transmettre la demande à la direction générale.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. « tout refus doit être motivé » : le responsable doit donner ses raisons. « Motiver » une décision, dans la langue administrative, c'est la justifier.",
                ],
                [
                    'id' => 'q31', 'type' => 'mcq',
                    'text' => "Document E — Pourquoi le lancement du site est-il repoussé ?",
                    'options' => [
                        "Les tests de paiement ne sont pas terminés.",
                        "Le budget a été dépassé.",
                        "Les vendeurs ne sont pas encore formés.",
                        "M. Varga était absent.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. « repoussé à juin : les tests de paiement ne sont pas terminés ». Les deux-points introduisent la cause. La formation des vendeurs est « maintenue en mai, comme prévu » : elle ne retarde rien.",
                ],
                [
                    'id' => 'q32', 'type' => 'mcq',
                    'text' => "Document E — Qu'est-ce qui a été décidé pour la prochaine réunion ?",
                    'options' => [
                        "M. Varga présentera le nouveau site.",
                        "La formation des vendeurs sera annulée.",
                        "Le site sera lancé en avril.",
                        "Mme Achour présentera un nouveau budget.",
                    ],
                    'correct_answer' => 'D',
                    'explanation' => "D. « Mme Achour présentera un budget révisé lors de la prochaine réunion ». « Révisé » signifie modifié, donc nouveau. Le site est repoussé à juin, et la formation est maintenue.",
                ],
                // ── Section 5 : articles de presse ──
                [
                    'id' => 'q33', 'type' => 'mcq',
                    'text' => "Article 1 — Selon l'article, les abeilles sauvages :",
                    'options' => [
                        "vivent toutes dans des ruches.",
                        "jouent un rôle important dans la pollinisation des cultures.",
                        "sont moins nombreuses que les espèces domestiques.",
                        "sont protégées par les ruches installées en ville.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « Ce sont elles qui assurent une grande partie de la pollinisation des cultures ». Beaucoup « vivent seules et nichent dans le sol », pas dans des ruches (A), et les ruches urbaines pourraient au contraire leur nuire (D).",
                ],
                [
                    'id' => 'q34', 'type' => 'mcq',
                    'text' => "Article 1 — Quel paradoxe les chercheurs soulignent-ils ?",
                    'options' => [
                        "Les fleurs locales nuisent aux abeilles domestiques.",
                        "Les villes sont plus polluées que les campagnes, mais les abeilles y vivent mieux.",
                        "Les abeilles domestiques ont disparu des villes.",
                        "Installer des ruches en ville, geste présenté comme écologique, peut nuire aux abeilles sauvages.",
                    ],
                    'correct_answer' => 'D',
                    'explanation' => "D. Les ruches urbaines, « présentée[s] comme un geste écologique, pourrai[ent] leur nuire », car les abeilles domestiques « accaparent les fleurs disponibles ». Le paradoxe tient à cet écart entre l'intention et l'effet. Les fleurs locales sont la solution proposée, pas le problème (A).",
                ],
                [
                    'id' => 'q35', 'type' => 'mcq',
                    'text' => "Article 2 — Pourquoi les commerces n'impriment-ils plus systématiquement le ticket de caisse ?",
                    'options' => [
                        "Pour éviter les erreurs de prix.",
                        "Parce que les clients le refusent toujours.",
                        "Pour réduire le gaspillage de papier.",
                        "Pour vendre davantage de garanties.",
                    ],
                    'correct_answer' => 'C',
                    'explanation' => "C. « L'objectif affiché est de réduire le gaspillage de papier ». Les erreurs de prix (A) sont au contraire un risque que soulignent les associations : sans ticket, on les vérifie plus difficilement.",
                ],
                [
                    'id' => 'q36', 'type' => 'mcq',
                    'text' => "Article 2 — Que conseillent les associations de consommateurs ?",
                    'options' => [
                        "De demander le ticket pour les achats importants, ou d'accepter sa version électronique.",
                        "De refuser systématiquement le ticket.",
                        "De payer uniquement en espèces.",
                        "De porter plainte contre les commerces.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. Elles « conseillent de le demander pour tout achat important, ou d'accepter sa version électronique », pour garder une preuve d'achat utile en cas de garantie. Leur position est de rester « vigilantes », pas de refuser le ticket ni d'attaquer les commerces.",
                ],
                [
                    'id' => 'q37', 'type' => 'mcq',
                    'text' => "Article 3 — À quoi sert l'indice noté sur dix ?",
                    'options' => [
                        "À indiquer la consommation d'énergie de l'appareil.",
                        "À indiquer si l'appareil est facile à réparer.",
                        "À fixer le prix maximal d'une réparation.",
                        "À noter la qualité du service après-vente.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. Il « indique si l'appareil se répare facilement ». L'article montre justement qu'il ne dit rien du prix des pièces (contre C) : « une pièce disponible n'est pas forcément une pièce abordable ».",
                ],
                [
                    'id' => 'q38', 'type' => 'mcq',
                    'text' => "Article 3 — Pourquoi le bilan de l'indice est-il jugé « mitigé » ?",
                    'options' => [
                        "Parce qu'il repose sur les données des fabricants et ignore le coût des pièces.",
                        "Parce que les consommateurs ne le comprennent pas.",
                        "Parce que les fabricants refusent de l'afficher.",
                        "Parce qu'il ne concerne que les téléphones.",
                    ],
                    'correct_answer' => 'A',
                    'explanation' => "A. Deux limites : la note est calculée « à partir des informations fournies par les fabricants eux-mêmes », et une pièce disponible peut rester trop chère. L'indice concerne aussi les ordinateurs et les lave-linge (contre D), et les fabricants bien notés l'utilisent volontiers (contre C).",
                ],
                [
                    'id' => 'q39', 'type' => 'mcq',
                    'text' => "Article 4 — Selon l'article, la gratuité des musées :",
                    'options' => [
                        "attire surtout un nouveau public.",
                        "fait baisser la fréquentation.",
                        "a été rejetée par tous les musées.",
                        "fait surtout venir plus souvent ceux qui y allaient déjà.",
                    ],
                    'correct_answer' => 'D',
                    'explanation' => "D. « ce sont surtout les habitués qui viennent plus souvent ; ceux qui ne franchissaient jamais la porte continuent de rester dehors ». La fréquentation augmente bien (« certes »), mais sans nouveau public (contre A et B).",
                ],
                [
                    'id' => 'q40', 'type' => 'mcq',
                    'text' => "Article 4 — Selon l'auteur, qu'est-ce qui éloigne une partie du public des musées ?",
                    'options' => [
                        "Le prix des billets.",
                        "Le sentiment que ces lieux ne leur sont pas destinés.",
                        "Le manque d'expositions nouvelles.",
                        "Les horaires du soir.",
                    ],
                    'correct_answer' => 'B',
                    'explanation' => "B. « Ce qui éloigne une partie du public, c'est le sentiment que ces lieux “ne sont pas pour eux” ». Le prix est écarté : « Le prix n'était donc pas le principal obstacle ». Les horaires du soir sont une solution proposée, pas une cause.",
                ],
            ],
        ],
    ],
];
