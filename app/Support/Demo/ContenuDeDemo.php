<?php

namespace App\Support\Demo;

/**
 * Le contenu du centre de demonstration « Institut Linguae » (DELF).
 *
 * La demo fabriquait chaque exercice comme un QCM a une question, toute reponse
 * valant « A » et toute correction etant vide — meme a l'ecrit, ou « Redigez un
 * message » proposait quatre lettres. Un prospect qui ouvrait une copie dans le
 * suivi du centre voyait donc une redaction reduite a « A », sans aucune faute
 * relevee : exactement ce que l'application sait faire de mieux, rendu invisible.
 *
 * Ici, de vrais exercices — un texte a lire, un enregistrement a ecouter, un
 * sujet de redaction —, et pour chaque tentative des reponses COHERENTES avec
 * son score : les bonnes reponses quand elle reussit, de vraies erreurs quand
 * elle echoue, et pour l'ecrit une copie d'apprenant avec ses fautes, leur
 * correction, ses criteres et son profil.
 *
 * Tout est DETERMINISTE : la meme tentative donne toujours la meme copie, pour
 * qu'une reparation relancee ne change rien.
 */
final class ContenuDeDemo
{
    /** Les exercices, par competence puis par niveau. */
    public static function exercice(string $competence, string $niveau): array
    {
        $niveau = in_array($niveau, ['A1', 'B1', 'B2'], true) ? $niveau : 'B1';

        return match ($competence) {
            'writing' => self::REDACTIONS[$niveau]['exercice'],
            'listening' => self::ECOUTES[$niveau],
            default => self::LECTURES[$niveau],
        };
    }

    /**
     * Toutes les fautes des copies d'un niveau : de quoi reecrire une erreur
     * fabriquee par l'ancienne demo avec une vraie faute de redaction.
     *
     * @return list<array{original: string, corrected: string, category: string, explanation: string}>
     */
    public static function fautesDeRedaction(string $niveau): array
    {
        $niveau = in_array($niveau, ['A1', 'B1', 'B2'], true) ? $niveau : 'B1';

        return array_values(array_merge(...array_map(
            fn (array $copie) => $copie['corrections'],
            self::REDACTIONS[$niveau]['copies'],
        )));
    }

    /**
     * Une tentative coherente avec son score : ses reponses, la correction
     * enregistree, et les erreurs qu'elle laisse.
     *
     * @param  array<int, array<string, mixed>>  $questions  les questions de l'exercice
     * @return array{answers: array<string, mixed>, feedback: list<array<string, mixed>>, erreurs: list<array<string, mixed>>, corrections: list<array<string, mixed>>, texte: ?string}
     */
    public static function tentative(string $competence, string $niveau, array $questions, float $precision, int $graine): array
    {
        return $competence === 'writing'
            ? self::copieEcrite($niveau, $precision, $graine)
            : self::reponsesFermees($questions, $precision, $graine);
    }

    /** @param  array<int, array<string, mixed>>  $questions */
    private static function reponsesFermees(array $questions, float $precision, int $graine): array
    {
        $n = count($questions);
        $justes = (int) round($n * max(0, min(100, $precision)) / 100);

        // Quelles questions sont ratees : toujours les memes pour une tentative.
        $ordre = range(0, $n - 1);
        usort($ordre, fn ($a, $b) => crc32($graine.'-'.$a) <=> crc32($graine.'-'.$b));
        $ratees = array_slice($ordre, 0, $n - $justes);

        $answers = [];
        $feedback = [];
        $erreurs = [];

        foreach ($questions as $i => $q) {
            $lettreJuste = (string) $q['correct_answer'];
            $rate = in_array($i, $ratees, true);
            $lettres = array_map(fn ($k) => chr(65 + $k), array_keys($q['options']));
            $fausses = array_values(array_diff($lettres, [$lettreJuste]));
            $donnee = $rate ? $fausses[crc32($graine.'-f-'.$i) % count($fausses)] : $lettreJuste;
            $attendue = $lettreJuste.') '.$q['options'][ord($lettreJuste) - 65];

            $answers[$q['id']] = $donnee;
            $feedback[] = [
                'question_id' => $q['id'],
                'correct' => ! $rate,
                'accuracy' => $rate ? 0 : 100,
                'correct_answer' => $q['options'][ord($lettreJuste) - 65],
                'explanation' => $q['explanation'],
            ];

            if ($rate) {
                $erreurs[] = [
                    'question_id' => $q['id'],
                    'question_text' => $q['text'],
                    'user_answer' => $donnee,
                    'correct_answer' => $attendue,
                    'explanation' => $q['explanation'],
                ];
            }
        }

        return ['answers' => $answers, 'feedback' => $feedback, 'erreurs' => $erreurs, 'corrections' => [], 'texte' => null];
    }

    private static function copieEcrite(string $niveau, float $precision, int $graine): array
    {
        $niveau = in_array($niveau, ['A1', 'B1', 'B2'], true) ? $niveau : 'B1';
        $copies = self::REDACTIONS[$niveau]['copies'];
        // Les copies sont rangees de la plus faible a la meilleure : un score bas
        // tire une copie faible, un score haut une bonne.
        $rang = (int) min(count($copies) - 1, floor(max(0, min(99.9, $precision)) / 100 * count($copies)));
        $copie = $copies[$rang];

        $note = round(max(0, min(100, $precision)) / 100 * 9, 1);
        $ecart = fn (float $d) => max(0, min(9, round($note + $d, 1)));

        return [
            'answers' => ['q1' => $copie['texte']],
            'feedback' => [[
                'question_id' => 'q1',
                'correct' => $precision >= 60,
                'accuracy' => $precision,
                'band_score' => $note,
                'sub_scores' => [
                    'task_achievement' => $ecart(0.5),
                    'coherence_cohesion' => $ecart(0),
                    'lexical_resource' => $ecart($copie['ecart_vocabulaire']),
                    'grammar_accuracy' => $ecart($copie['ecart_grammaire']),
                ],
                'corrections' => $copie['corrections'],
                'diagnostic' => $copie['diagnostic'],
                'explanation' => $copie['bilan'],
            ]],
            'erreurs' => [],
            'corrections' => $copie['corrections'],
            'texte' => $copie['texte'],
        ];
    }

    private const LECTURES = [
        'A1' => [
            'title' => 'Lecture A1 — Le message de Julie',
            'content' => [
                'instructions' => 'Lisez le message, puis répondez aux questions.',
                'passage' => "Salut Karim,\nSamedi, c'est mon anniversaire ! Je fais une petite fête chez moi, à 19 heures. Mon adresse : 12, rue des Lilas, au deuxième étage. Apporte de la musique, s'il te plaît. Mon frère prépare le gâteau. Réponds-moi avant jeudi.\nJulie",
            ],
            'questions' => [
                ['id' => 'q1', 'type' => 'mcq', 'text' => 'Pourquoi Julie écrit-elle à Karim ?', 'options' => ['Pour l’inviter à sa fête', 'Pour lui demander de l’argent', 'Pour annuler un rendez-vous'], 'correct_answer' => 'A', 'explanation' => '« C’est mon anniversaire ! Je fais une petite fête » : Julie invite Karim.'],
                ['id' => 'q2', 'type' => 'mcq', 'text' => 'À quelle heure commence la fête ?', 'options' => ['À 12 heures', 'À 19 heures', 'À 2 heures'], 'correct_answer' => 'B', 'explanation' => 'Le message dit « à 19 heures ». 12 est le numéro de la rue, 2 l’étage.'],
                ['id' => 'q3', 'type' => 'mcq', 'text' => 'Qu’est-ce que Karim doit apporter ?', 'options' => ['Un gâteau', 'Des boissons', 'De la musique'], 'correct_answer' => 'C', 'explanation' => '« Apporte de la musique » ; c’est le frère de Julie qui prépare le gâteau.'],
                ['id' => 'q4', 'type' => 'mcq', 'text' => 'Quand Karim doit-il répondre ?', 'options' => ['Avant jeudi', 'Samedi', 'Après la fête'], 'correct_answer' => 'A', 'explanation' => '« Réponds-moi avant jeudi. »'],
            ],
        ],
        'B1' => [
            'title' => 'Lecture B1 — La bibliothèque change ses horaires',
            'content' => [
                'instructions' => 'Lisez l’article, puis répondez aux questions.',
                'passage' => "À partir du 1er mars, la médiathèque municipale ouvrira le dimanche de 10 h à 17 h. En contrepartie, elle fermera le lundi toute la journée. Cette décision fait suite à une enquête menée auprès de 800 usagers : près de six sur dix réclamaient une ouverture le week-end, surtout les familles et les étudiants. Le personnel, lui, n’a pas été recruté : les agents travailleront le dimanche par roulement, avec une prime. Certains habitués regrettent la fermeture du lundi, jour où la salle d’étude était la plus calme.",
            ],
            'questions' => [
                ['id' => 'q1', 'type' => 'mcq', 'text' => 'Quel changement est annoncé ?', 'options' => ['La médiathèque fermera le dimanche', 'La médiathèque ouvrira le dimanche et fermera le lundi', 'La médiathèque ouvrira tous les jours'], 'correct_answer' => 'B', 'explanation' => '« ouvrira le dimanche… En contrepartie, elle fermera le lundi » : l’un compense l’autre.'],
                ['id' => 'q2', 'type' => 'mcq', 'text' => 'Sur quoi repose cette décision ?', 'options' => ['Sur une enquête auprès des usagers', 'Sur une demande du personnel', 'Sur une loi nationale'], 'correct_answer' => 'A', 'explanation' => '« Cette décision fait suite à une enquête menée auprès de 800 usagers. »'],
                ['id' => 'q3', 'type' => 'mcq', 'text' => 'Comment le dimanche sera-t-il assuré ?', 'options' => ['Par de nouveaux employés', 'Par des bénévoles', 'Par les agents actuels, à tour de rôle'], 'correct_answer' => 'C', 'explanation' => '« Le personnel n’a pas été recruté : les agents travailleront par roulement » — « par roulement » veut dire à tour de rôle.'],
                ['id' => 'q4', 'type' => 'mcq', 'text' => 'Que regrettent certains habitués ?', 'options' => ['Le prix de l’abonnement', 'Le calme de la salle d’étude le lundi', 'La fermeture du samedi'], 'correct_answer' => 'B', 'explanation' => 'Ils regrettent le lundi, « jour où la salle d’étude était la plus calme ».'],
            ],
        ],
        'B2' => [
            'title' => 'Lecture B2 — Le télétravail, une liberté sous condition',
            'content' => [
                'instructions' => 'Lisez le texte, puis répondez aux questions.',
                'passage' => "Le télétravail a longtemps été présenté comme une conquête : moins de transports, plus d’autonomie, un meilleur équilibre entre vie professionnelle et vie privée. Pourtant, plusieurs études récentes nuancent ce tableau. Si la productivité individuelle progresse souvent, la coopération informelle — ces échanges imprévus devant la machine à café — s’étiole, au détriment de l’innovation. Surtout, les salariés les plus jeunes, qui apprennent leur métier en observant leurs aînés, en seraient les premières victimes. Les entreprises qui réussissent leur transition ne sont donc pas celles qui généralisent le télétravail, mais celles qui organisent délibérément le temps passé ensemble.",
            ],
            'questions' => [
                ['id' => 'q1', 'type' => 'mcq', 'text' => 'Quelle est la position de l’auteur sur le télétravail ?', 'options' => ['Il le rejette entièrement', 'Il en reconnaît les avantages mais en souligne les limites', 'Il le présente comme une conquête sans réserve'], 'correct_answer' => 'B', 'explanation' => '« Pourtant, plusieurs études nuancent ce tableau » : il ne rejette pas les avantages, il les nuance.'],
                ['id' => 'q2', 'type' => 'mcq', 'text' => 'Qu’est-ce qui « s’étiole » avec le télétravail ?', 'options' => ['La productivité individuelle', 'Les échanges informels entre collègues', 'Le temps de transport'], 'correct_answer' => 'B', 'explanation' => '« la coopération informelle […] s’étiole » : « s’étioler », c’est s’affaiblir peu à peu.'],
                ['id' => 'q3', 'type' => 'mcq', 'text' => 'Qui en souffrirait le plus ?', 'options' => ['Les cadres expérimentés', 'Les jeunes salariés', 'Les dirigeants'], 'correct_answer' => 'B', 'explanation' => '« les salariés les plus jeunes […] en seraient les premières victimes », parce qu’ils apprennent en observant.'],
                ['id' => 'q4', 'type' => 'mcq', 'text' => 'Selon l’auteur, quelles entreprises réussissent ?', 'options' => ['Celles qui généralisent le télétravail', 'Celles qui l’interdisent', 'Celles qui organisent le temps passé ensemble'], 'correct_answer' => 'C', 'explanation' => 'La dernière phrase oppose « généralisent » à « organisent délibérément le temps passé ensemble ».'],
            ],
        ],
    ];

    private const ECOUTES = [
        'A1' => [
            'title' => 'Écoute A1 — Au téléphone avec la pharmacie',
            'content' => [
                'instructions' => 'Écoutez le dialogue, puis répondez aux questions.',
                'audio_text' => "— Pharmacie du Centre, bonjour.\n— Bonjour madame. Vous êtes ouverts le samedi ?\n— Oui, de 9 heures à midi.\n— D’accord. Et vous avez des masques pour enfants ?\n— Oui, en boîte de dix, à quatre euros.\n— Merci, je passe samedi matin.",
            ],
            'questions' => [
                ['id' => 'q1', 'type' => 'mcq', 'text' => 'Que demande la personne d’abord ?', 'options' => ['Les horaires du samedi', 'Le prix des médicaments', 'L’adresse de la pharmacie'], 'correct_answer' => 'A', 'explanation' => '« Vous êtes ouverts le samedi ? » : elle demande les horaires.'],
                ['id' => 'q2', 'type' => 'mcq', 'text' => 'Le samedi, la pharmacie ferme à…', 'options' => ['9 heures', 'midi', '4 heures'], 'correct_answer' => 'B', 'explanation' => '« de 9 heures à midi ».'],
                ['id' => 'q3', 'type' => 'mcq', 'text' => 'Combien coûte une boîte de masques ?', 'options' => ['Dix euros', 'Deux euros', 'Quatre euros'], 'correct_answer' => 'C', 'explanation' => '« en boîte de dix, à quatre euros » : dix est le nombre de masques.'],
                ['id' => 'q4', 'type' => 'mcq', 'text' => 'Quand la personne va-t-elle passer ?', 'options' => ['Samedi matin', 'Ce soir', 'Lundi'], 'correct_answer' => 'A', 'explanation' => '« je passe samedi matin ».'],
            ],
        ],
        'B1' => [
            'title' => 'Écoute B1 — Annonce à la gare',
            'content' => [
                'instructions' => 'Écoutez l’annonce, puis répondez aux questions.',
                'audio_text' => "Mesdames et messieurs, en raison d’un incident technique sur la voie, le train de 14 h 32 à destination de Lyon partira avec un retard estimé à quarante minutes. Les voyageurs en correspondance pour Grenoble sont invités à se présenter au guichet numéro 3, où un autocar de remplacement leur sera proposé. Nous vous rappelons que les billets restent valables dans les trains suivants de la journée. Nous vous prions de nous excuser pour la gêne occasionnée.",
            ],
            'questions' => [
                ['id' => 'q1', 'type' => 'mcq', 'text' => 'Pourquoi le train est-il en retard ?', 'options' => ['À cause d’un incident technique', 'À cause d’une grève', 'À cause de la météo'], 'correct_answer' => 'A', 'explanation' => '« en raison d’un incident technique sur la voie ».'],
                ['id' => 'q2', 'type' => 'mcq', 'text' => 'Quel est le retard annoncé ?', 'options' => ['Quatorze minutes', 'Quarante minutes', 'Trente-deux minutes'], 'correct_answer' => 'B', 'explanation' => '« un retard estimé à quarante minutes » ; 14 h 32 est l’heure du départ prévu.'],
                ['id' => 'q3', 'type' => 'mcq', 'text' => 'Que doivent faire les voyageurs pour Grenoble ?', 'options' => ['Attendre sur le quai', 'Acheter un nouveau billet', 'Aller au guichet 3'], 'correct_answer' => 'C', 'explanation' => '« sont invités à se présenter au guichet numéro 3 », où un autocar les attend.'],
                ['id' => 'q4', 'type' => 'mcq', 'text' => 'Que dit l’annonce des billets ?', 'options' => ['Ils sont remboursés', 'Ils restent valables dans les trains suivants', 'Ils doivent être échangés'], 'correct_answer' => 'B', 'explanation' => '« les billets restent valables dans les trains suivants de la journée ».'],
            ],
        ],
        'B2' => [
            'title' => 'Écoute B2 — Débat radio : faut-il interdire le téléphone à l’école ?',
            'content' => [
                'instructions' => 'Écoutez l’extrait, puis répondez aux questions.',
                'audio_text' => "— Pour moi, l’interdiction totale est une fausse bonne idée. On prive les élèves d’un outil qu’ils devront apprendre à maîtriser de toute façon.\n— Mais les chiffres sont là : depuis que notre collège a banni les portables, les conflits dans la cour ont diminué d’un tiers.\n— Je ne conteste pas vos chiffres. Je dis seulement qu’interdire, c’est renoncer à éduquer. Il vaudrait mieux des temps d’usage encadrés, en classe, avec l’enseignant.\n— Encore faut-il que les enseignants soient formés, ce qui est loin d’être le cas.",
            ],
            'questions' => [
                ['id' => 'q1', 'type' => 'mcq', 'text' => 'Quelle est la position du premier intervenant ?', 'options' => ['Il soutient l’interdiction totale', 'Il juge l’interdiction contre-productive', 'Il n’a pas d’avis'], 'correct_answer' => 'B', 'explanation' => '« une fausse bonne idée » : il juge l’interdiction mauvaise malgré les apparences.'],
                ['id' => 'q2', 'type' => 'mcq', 'text' => 'Quel effet le second intervenant attribue-t-il à l’interdiction ?', 'options' => ['Moins de conflits dans la cour', 'De meilleures notes', 'Plus de participation en classe'], 'correct_answer' => 'A', 'explanation' => '« les conflits dans la cour ont diminué d’un tiers ».'],
                ['id' => 'q3', 'type' => 'mcq', 'text' => 'Que propose le premier intervenant à la place ?', 'options' => ['Laisser le téléphone libre', 'Des temps d’usage encadrés en classe', 'Une interdiction le matin seulement'], 'correct_answer' => 'B', 'explanation' => '« Il vaudrait mieux des temps d’usage encadrés, en classe, avec l’enseignant. »'],
                ['id' => 'q4', 'type' => 'mcq', 'text' => 'Quelle objection lui oppose-t-on ?', 'options' => ['Le coût des équipements', 'Le refus des parents', 'Le manque de formation des enseignants'], 'correct_answer' => 'C', 'explanation' => '« Encore faut-il que les enseignants soient formés, ce qui est loin d’être le cas. »'],
            ],
        ],
    ];

    /**
     * Les sujets de redaction, et pour chacun trois copies d'apprenant, de la
     * plus faible a la meilleure, avec leurs vraies fautes et leur profil.
     */
    private const REDACTIONS = [
        'A1' => [
            'exercice' => [
                'title' => 'Écrit A1 — Annuler un rendez-vous',
                'content' => ['instructions' => 'Écrivez un message d’environ 40 mots.'],
                'questions' => [['id' => 'q1', 'type' => 'essay', 'text' => 'Vous ne pouvez pas aller à votre rendez-vous chez le dentiste mardi. Écrivez un message pour l’annuler et proposer un autre jour.']],
            ],
            'copies' => [
                [
                    'texte' => "Bonjour, je suis malade mardi, je peux pas venir au rendez-vous. Je veux venir jeudi ou vendredi. Merci beaucoup, au revoir. Paul",
                    'corrections' => [
                        ['original' => 'je suis malade mardi', 'corrected' => 'je serai malade mardi', 'category' => 'grammar.tense', 'explanation' => 'Mardi est dans le futur : il faut le futur, « je serai ».'],
                        ['original' => 'je peux pas', 'corrected' => 'je ne peux pas', 'category' => 'grammar.negation', 'explanation' => 'À l’écrit, la négation a deux parties : « ne… pas ».'],
                        ['original' => 'Je veux venir', 'corrected' => 'Je voudrais venir', 'category' => 'vocabulary.register', 'explanation' => '« Je voudrais » est plus poli que « je veux » pour demander quelque chose.'],
                    ],
                    'diagnostic' => [
                        ['dimension' => 'grammatical_control', 'observation' => 'La négation perd son « ne » (« je peux pas ») : c’est le français oral transposé à l’écrit.', 'severity' => 'major'],
                        ['dimension' => 'register', 'observation' => '« Je veux » pour demander un rendez-vous : la forme polie « je voudrais » n’est pas encore acquise.', 'severity' => 'minor'],
                    ],
                    'bilan' => 'Le message est compris, mais la négation et la politesse sont à travailler.',
                    'ecart_vocabulaire' => 0, 'ecart_grammaire' => -1,
                ],
                [
                    'texte' => "Bonjour Docteur, je ne peux pas venir mardi parce que je travaille. Est-ce que c'est possible jeudi à 10 heures ? Merci et bonne journée. Amina",
                    'corrections' => [
                        ['original' => 'Bonjour Docteur', 'corrected' => 'Bonjour docteur', 'category' => 'spelling.capitalisation', 'explanation' => 'En français, « docteur » prend une minuscule après « Bonjour ».'],
                    ],
                    'diagnostic' => [
                        ['dimension' => 'task_fulfilment', 'observation' => 'L’annulation, la raison et une nouvelle date sont toutes là, en peu de mots.', 'severity' => 'strength'],
                        ['dimension' => 'syntactic_complexity', 'observation' => 'Utilise déjà « parce que » et une question avec « est-ce que » : bon niveau A1.', 'severity' => 'strength'],
                    ],
                    'bilan' => 'Message complet et poli. Quelques détails de présentation.',
                    'ecart_vocabulaire' => 0, 'ecart_grammaire' => 0.5,
                ],
                [
                    'texte' => "Bonjour Madame, je suis désolée mais je ne pourrai pas venir à mon rendez-vous mardi. Je suis en voyage. Est-ce que je peux venir jeudi ou vendredi matin ? Merci beaucoup. Fatou",
                    'corrections' => [],
                    'diagnostic' => [
                        ['dimension' => 'grammatical_control', 'observation' => 'Futur (« je ne pourrai pas ») et accord du féminin (« désolée ») maîtrisés.', 'severity' => 'strength'],
                        ['dimension' => 'task_fulfilment', 'observation' => 'Toutes les consignes sont respectées, avec une formule d’excuse adaptée.', 'severity' => 'strength'],
                    ],
                    'bilan' => 'Très bon message : clair, poli et sans faute.',
                    'ecart_vocabulaire' => 0.5, 'ecart_grammaire' => 0.5,
                ],
            ],
        ],
        'B1' => [
            'exercice' => [
                'title' => 'Écrit B1 — Courriel d’annulation',
                'content' => ['instructions' => 'Écrivez un courriel d’environ 160 mots.'],
                'questions' => [['id' => 'q1', 'type' => 'essay', 'text' => 'Vous devez annuler un rendez-vous avec votre conseiller à la mairie. Écrivez-lui un courriel pour vous excuser, expliquer la raison et proposer une solution.']],
            ],
            'copies' => [
                [
                    'texte' => "Monsieur, je vous écris pour dire que je peux pas venir au rendez-vous de mercredi. Mon fils est malade et je dois rester avec lui. C'est très important pour moi ce rendez-vous parce que j'ai besoin de faire mon dossier. Je suis vraiment désolé. Est-ce que on peut faire le rendez-vous la semaine prochaine ? Je suis libre lundi, mardi et jeudi. Si c'est pas possible je peux envoyer les documents par mail. Merci pour votre compréhension. Cordialement.",
                    'corrections' => [
                        ['original' => 'je peux pas venir', 'corrected' => 'je ne peux pas venir', 'category' => 'grammar.negation', 'explanation' => 'À l’écrit, on garde le « ne » de la négation.'],
                        ['original' => 'Est-ce que on peut', 'corrected' => 'Est-ce qu’on peut', 'category' => 'spelling.typo', 'explanation' => '« que » s’élide devant une voyelle : « qu’on ».'],
                        ['original' => 'faire le rendez-vous', 'corrected' => 'fixer un nouveau rendez-vous', 'category' => 'vocabulary.collocation', 'explanation' => 'On « fixe » ou « reporte » un rendez-vous, on ne le « fait » pas.'],
                        ['original' => "Si c'est pas possible", 'corrected' => "Si ce n'est pas possible", 'category' => 'grammar.negation', 'explanation' => 'Même règle : « ne » obligatoire à l’écrit.'],
                    ],
                    'diagnostic' => [
                        ['dimension' => 'grammatical_control', 'observation' => 'Le « ne » de la négation tombe deux fois sur deux (« je peux pas », « c’est pas ») : habitude de l’oral.', 'severity' => 'major'],
                        ['dimension' => 'lexical_precision', 'observation' => '« faire » remplace des verbes précis (« fixer », « reporter », « constituer mon dossier ») : vocabulaire général.', 'severity' => 'minor'],
                        ['dimension' => 'task_fulfilment', 'observation' => 'Excuse, raison et deux solutions proposées : la tâche est bien remplie.', 'severity' => 'strength'],
                    ],
                    'bilan' => 'La tâche est réussie, mais la négation orale et un vocabulaire trop général abaissent la note.',
                    'ecart_vocabulaire' => -1, 'ecart_grammaire' => -1,
                ],
                [
                    'texte' => "Bonjour Monsieur Ndiaye, je me permets de vous écrire car je ne pourrai pas me présenter à notre rendez-vous de mercredi 14 h. En effet, mon employeur m'a demandé de remplacer un collègue ce jour-là. Je suis vraiment désolée de ce contretemps. Serait-il possible de reporter notre rencontre à vendredi matin ? Sinon, je peux vous faire parvenir les pièces de mon dossier par courriel dès demain. Je vous remercie de votre compréhension et vous prie d'agréer mes salutations distinguées.",
                    'corrections' => [
                        ['original' => 'mercredi 14 h', 'corrected' => 'mercredi à 14 h', 'category' => 'grammar.preposition', 'explanation' => 'On annonce une heure avec la préposition « à ».'],
                    ],
                    'diagnostic' => [
                        ['dimension' => 'register', 'observation' => 'Formules du courriel formel maîtrisées (« je me permets », « serait-il possible », formule finale complète).', 'severity' => 'strength'],
                        ['dimension' => 'coherence', 'observation' => '« En effet » et « Sinon » articulent bien la raison et l’alternative.', 'severity' => 'strength'],
                    ],
                    'bilan' => 'Courriel formel très réussi : registre juste, idées bien articulées.',
                    'ecart_vocabulaire' => 0.5, 'ecart_grammaire' => 0,
                ],
                [
                    'texte' => "Monsieur, je suis au regret de devoir annuler notre rendez-vous prévu mercredi à 14 h : une urgence familiale m'oblige à me rendre à Douala. Conscient que ce report retarde l'instruction de mon dossier, je vous propose deux solutions : soit un rendez-vous la semaine prochaine, à votre convenance, soit l'envoi immédiat des pièces justificatives par courriel. Je reste joignable au 06 12 34 56 78. En vous remerciant par avance de votre compréhension, je vous prie d'agréer, Monsieur, l'expression de mes salutations distinguées.",
                    'corrections' => [],
                    'diagnostic' => [
                        ['dimension' => 'syntactic_complexity', 'observation' => 'Participiale (« Conscient que… »), double alternative « soit… soit… » : syntaxe déjà de niveau B2.', 'severity' => 'strength'],
                        ['dimension' => 'lexical_precision', 'observation' => 'Vocabulaire administratif exact : « instruction de mon dossier », « pièces justificatives ».', 'severity' => 'strength'],
                    ],
                    'bilan' => 'Excellent courriel : précis, structuré, sans faute. Prêt pour viser le B2.',
                    'ecart_vocabulaire' => 0.5, 'ecart_grammaire' => 0.5,
                ],
            ],
        ],
        'B2' => [
            'exercice' => [
                'title' => 'Écrit B2 — Lettre formelle de désistement',
                'content' => ['instructions' => 'Rédigez une lettre formelle d’environ 250 mots.'],
                'questions' => [['id' => 'q1', 'type' => 'essay', 'text' => 'Vous annulez votre participation à un séminaire professionnel payé par votre entreprise. Écrivez à l’organisateur : excusez-vous, justifiez votre décision et demandez le remboursement.']],
            ],
            'copies' => [
                [
                    'texte' => "Madame, Monsieur, je vous écris pour annuler ma participation au séminaire du 12 mai. Ma entreprise a changé les priorités et je dois rester au bureau pour un projet important. Je suis très désolé pour ça. Le séminaire était très intéressant et je voulais beaucoup y participer. Mais c'est impossible maintenant. Je voudrais avoir le remboursement de l'inscription. L'inscription a été payée le 3 avril. Je vous envoie la facture avec cette lettre. Merci beaucoup pour votre aide et j'attends votre réponse. Cordialement.",
                    'corrections' => [
                        ['original' => 'Ma entreprise', 'corrected' => 'Mon entreprise', 'category' => 'grammar.agreement', 'explanation' => 'Devant une voyelle, « ma » devient « mon », même au féminin.'],
                        ['original' => 'désolé pour ça', 'corrected' => 'désolé de ce contretemps', 'category' => 'vocabulary.register', 'explanation' => '« ça » est familier : impropre dans une lettre formelle.'],
                        ['original' => 'Mais c\'est impossible maintenant.', 'corrected' => 'Malheureusement, cela m\'est désormais impossible.', 'category' => 'coherence.connectors', 'explanation' => 'Commencer une phrase par « Mais » est maladroit à l’écrit soutenu.'],
                        ['original' => 'je voulais beaucoup y participer', 'corrected' => 'je tenais beaucoup à y participer', 'category' => 'vocabulary.collocation', 'explanation' => '« tenir à » exprime mieux l’attachement que « vouloir beaucoup ».'],
                    ],
                    'diagnostic' => [
                        ['dimension' => 'syntactic_complexity', 'observation' => 'Uniquement des phrases simples, juxtaposées : aucune subordonnée relative ou concessive, attendue au B2.', 'severity' => 'major'],
                        ['dimension' => 'register', 'observation' => 'Glissements familiers (« pour ça », « Mais c’est impossible ») dans une lettre censée être formelle.', 'severity' => 'major'],
                        ['dimension' => 'lexical_range', 'observation' => '« très » revient trois fois ; peu de lexique professionnel (« contretemps », « désistement » absents).', 'severity' => 'minor'],
                    ],
                    'bilan' => 'Les éléments demandés sont présents, mais la lettre reste au niveau B1 : phrases simples et registre instable.',
                    'ecart_vocabulaire' => -1, 'ecart_grammaire' => -0.5,
                ],
                [
                    'texte' => "Madame, Monsieur, c'est avec regret que je vous informe de mon désistement du séminaire « Manager à distance » prévu le 12 mai. En effet, une réorganisation interne, survenue la semaine dernière, exige ma présence au siège à cette date. Bien que je mesure la gêne que peut occasionner une annulation tardive, je me permets de solliciter le remboursement des frais d'inscription, réglés le 3 avril par mon employeur. Vous trouverez ci-joint la facture correspondante. Je vous saurais gré de bien vouloir m'informer des modalités de ce remboursement. Je vous prie d'agréer, Madame, Monsieur, mes salutations distinguées.",
                    'corrections' => [
                        ['original' => 'réglés le 3 avril', 'corrected' => 'réglés le 3 avril dernier', 'category' => 'coherence.reference', 'explanation' => 'Préciser « dernier » évite toute ambiguïté sur l’année.'],
                    ],
                    'diagnostic' => [
                        ['dimension' => 'syntactic_complexity', 'observation' => 'Concessive (« Bien que je mesure… »), relative et participiale : syntaxe variée de niveau B2.', 'severity' => 'strength'],
                        ['dimension' => 'register', 'observation' => 'Registre soutenu tenu de bout en bout (« Je vous saurais gré », « solliciter »).', 'severity' => 'strength'],
                    ],
                    'bilan' => 'Lettre formelle maîtrisée : argumentation claire, registre et syntaxe de niveau B2.',
                    'ecart_vocabulaire' => 0.5, 'ecart_grammaire' => 0,
                ],
                [
                    'texte' => "Madame, Monsieur, je me vois contraint de renoncer à ma participation au séminaire « Manager à distance » du 12 mai, auquel mon entreprise m'avait inscrit. La fusion annoncée de nos deux filiales mobilise en effet l'ensemble de l'encadrement à cette période, si bien qu'aucune absence ne saurait être accordée. Je mesure combien un désistement à trois semaines de l'événement complique votre organisation, et vous prie de bien vouloir m'en excuser. Conformément à l'article 6 de vos conditions générales, qui prévoit un remboursement intégral pour toute annulation signalée plus de quinze jours à l'avance, je vous saurais gré de procéder au remboursement des frais réglés le 3 avril dernier. Je reste naturellement à votre disposition pour tout justificatif complémentaire.",
                    'corrections' => [],
                    'diagnostic' => [
                        ['dimension' => 'coherence', 'observation' => 'Argumentation appuyée sur une référence précise (« l’article 6 de vos conditions générales ») : stratégie convaincante.', 'severity' => 'strength'],
                        ['dimension' => 'lexical_range', 'observation' => 'Lexique riche et exact (« fusion », « encadrement », « désistement »), sans répétition.', 'severity' => 'strength'],
                    ],
                    'bilan' => 'Copie remarquable, au niveau du C1 : précise, argumentée et sans faute.',
                    'ecart_vocabulaire' => 0.5, 'ecart_grammaire' => 0.5,
                ],
            ],
        ],
    ];
}
