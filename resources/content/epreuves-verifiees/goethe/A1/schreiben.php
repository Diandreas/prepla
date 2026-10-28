<?php

/**
 * Goethe-Zertifikat A1 (Start Deutsch 1) — module Schreiben, les deux parties.
 *
 * Ecrit a la main d'apres le format du Modellsatz Erwachsene :
 *  - Teil 1 : un texte de situation, puis un formulaire d'inscription dont CINQ
 *    champs manquent ;
 *  - Teil 2 : un message court d'environ 30 mots, trois points imposes.
 *
 * Les textes sont originaux. Ce sont les FORMATS, les longueurs et les nombres
 * d'items qui reprennent l'epreuve, pas les contenus de l'organisme.
 *
 * Trois choix a garder si ce fichier est recopie :
 *  - Teil 1 compte CINQ questions, une par champ manquant, parce que la config
 *    d'examen annonce cinq items et qu'un item = un point le jour de l'epreuve.
 *    Chaque question porte le formulaire entier dans `fields` : les quatre
 *    autres champs a completer y figurent en pointilles (« ... »), donc ils
 *    n'offrent aucune reponse a l'apprenant, exactement comme sur le papier ;
 *  - les cles de `correct_answers` sont l'INDEX 0 du champ vide dans `fields`
 *    (c'est ce que le composant envoie et ce que la correction attend) ;
 *  - l'explication est en francais et dit POURQUOI, en citant le mot allemand
 *    du texte de situation qui donne la reponse.
 *
 * Les cinq reponses se deduisent toutes du texte de situation. Rien a deviner.
 */

$situation = "Ihre Freundin heißt Marta Kowalski. Sie ist 29 Jahre alt und kommt aus Polen.\n"
    ."Jetzt wohnt sie in der Gartenstraße 12 in 80336 München.\n"
    ."Marta arbeitet im Krankenhaus. Sie ist Krankenschwester.\n"
    ."Am Tag hat sie keine Zeit. Darum möchte sie am Abend Deutsch lernen.\n"
    ."Ihre Telefonnummer ist 01764455221.\n"
    ."Marta bezahlt den Kurs mit Bargeld. Heute ist der 14. September.";

/**
 * Le formulaire, pour une question donnee : le champ `$vide` est le seul a
 * completer, les autres champs a completer de l'epreuve restent en pointilles.
 *
 * @param  int  $vide  index 0 du champ laisse vide dans cette question
 * @return array<int, array{label: string, type: string, value: string}>
 */
$formulaire = function (int $vide): array {
    $lignes = [
        0 => ['label' => 'Kurs', 'valeur' => 'Deutschkurs A1 (Abendkurs)', 'trou' => false],
        1 => ['label' => 'Familienname', 'valeur' => '...', 'trou' => true],
        2 => ['label' => 'Vorname', 'valeur' => 'Marta', 'trou' => false],
        3 => ['label' => 'Alter', 'valeur' => '...', 'trou' => true],
        4 => ['label' => 'Straße und Hausnummer', 'valeur' => '...', 'trou' => true],
        5 => ['label' => 'Postleitzahl und Wohnort', 'valeur' => '80336 München', 'trou' => false],
        6 => ['label' => 'Beruf', 'valeur' => '...', 'trou' => true],
        7 => ['label' => 'Telefon', 'valeur' => '...', 'trou' => true],
    ];

    $champs = [];
    foreach ($lignes as $i => $ligne) {
        $champs[] = [
            'label' => $ligne['label'],
            'type' => 'text',
            // Seul le champ de CETTE question est vide : le composant n'y met un
            // champ de saisie que si la valeur est la chaine vide.
            'value' => ($i === $vide && $ligne['trou']) ? '' : $ligne['valeur'],
        ];
    }

    return $champs;
};

return [
    'exam' => 'goethe',
    'level' => 'A1',
    'section' => 'schreiben',
    'parts' => [

        // ━━━━━━━━━━ Teil 1 : remplir un formulaire, 5 items ━━━━━━━━━━
        [
            'part' => 1,
            'name' => 'Schreiben, Teil 1',
            'type' => 'form-completion',
            'title' => 'Goethe A1 · Schreiben, Teil 1 — Anmeldung zum Deutschkurs',
            'description' => "Un texte de situation, puis cinq champs manquants dans un formulaire d'inscription. Format du Modellsatz officiel.",
            'content' => [
                'instructions' => "Lis la situation, puis complète le champ demandé du formulaire. Toutes les réponses sont dans le texte.",
                'passage' => "SITUATION\n\n".$situation."\n\n"
                    ."Marta möchte sich bei der Volkshochschule Nord anmelden. Sie bittet Sie um Hilfe: "
                    ."Bitte füllen Sie das Anmeldeformular für sie aus.",
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'form-completion',
                    'text' => "Anmeldeformular — Volkshochschule Nord. Ergänzen Sie: Familienname.",
                    'fields' => $formulaire(1),
                    'correct_answers' => ['1' => 'Kowalski'],
                    'explanation' => "Kowalski. Le texte dit « Ihre Freundin heißt Marta Kowalski » : « heißt » donne le nom complet, et « Familienname » est le nom de famille, donc la deuxième partie, Kowalski. « Marta » est le prénom (Vorname), déjà inscrit.",
                ],
                [
                    'id' => 'q2', 'type' => 'form-completion',
                    'text' => "Anmeldeformular — Volkshochschule Nord. Ergänzen Sie: Alter.",
                    'fields' => $formulaire(3),
                    'correct_answers' => ['3' => '29'],
                    'explanation' => "29. Le texte dit « Sie ist 29 Jahre alt » : « Jahre alt » donne l'âge. Attention à ne pas écrire 14 : « der 14. September » est la date du jour, pas l'âge.",
                ],
                [
                    'id' => 'q3', 'type' => 'form-completion',
                    'text' => "Anmeldeformular — Volkshochschule Nord. Ergänzen Sie: Straße und Hausnummer.",
                    'fields' => $formulaire(4),
                    'correct_answers' => ['4' => 'Gartenstraße 12'],
                    'explanation' => "Gartenstraße 12. Le texte dit « Jetzt wohnt sie in der Gartenstraße 12 » : « wohnt in » introduit l'adresse. Le code postal et la ville, « 80336 München », vont dans la ligne suivante du formulaire.",
                ],
                [
                    'id' => 'q4', 'type' => 'form-completion',
                    'text' => "Anmeldeformular — Volkshochschule Nord. Ergänzen Sie: Beruf.",
                    'fields' => $formulaire(6),
                    'correct_answers' => ['6' => 'Krankenschwester'],
                    'explanation' => "Krankenschwester. « Sie ist Krankenschwester » nomme le métier : en allemand, le métier suit « sein » sans article. « Krankenhaus » est le lieu de travail (l'hôpital), pas le métier.",
                ],
                [
                    'id' => 'q5', 'type' => 'form-completion',
                    'text' => "Anmeldeformular — Volkshochschule Nord. Ergänzen Sie: Telefon.",
                    'fields' => $formulaire(7),
                    'correct_answers' => ['7' => '01764455221'],
                    'explanation' => "01764455221. Le texte dit « Ihre Telefonnummer ist 01764455221 ». Ne confonds pas avec « 80336 », qui est annoncé par « Postleitzahl und Wohnort », le code postal.",
                ],
            ],
        ],

        // ━━━━━━━━━━ Teil 2 : message court, corrige par l'IA ━━━━━━━━━━
        // Famille ai-writing : une seule question, pas de bonne reponse. Les trois
        // points imposes figurent dans la consigne allemande ('text'), et c'est
        // elle que l'IA recoit. 'expected_points' est conserve comme
        // documentation de la tache, mais il n'est PAS transmis a la correction
        // pour une redaction ecrite : ExerciseScoringService ne le lit que dans
        // la branche orale.
        [
            'part' => 2,
            'name' => 'Schreiben, Teil 2',
            'type' => 'short-writing',
            'title' => 'Goethe A1 · Schreiben, Teil 2 — Eine kurze E-Mail',
            'description' => "Un message court d'environ 30 mots qui répond aux trois points imposés. Format du Modellsatz officiel.",
            'content' => [
                'instructions' => "Écris environ 30 mots et traite les trois points. Commence par une salutation et termine par une formule de politesse.",
            ],
            'questions' => [
                [
                    'id' => 'q1', 'type' => 'short-writing',
                    'text' => "Sie haben am Samstag Geburtstag und machen ein kleines Fest.\n"
                        ."Schreiben Sie eine E-Mail an Ihre Freundin Nina.\n"
                        ."Schreiben Sie ungefähr 30 Wörter.\n"
                        ."Schreiben Sie zu allen drei Punkten:\n\n"
                        ."– Warum schreiben Sie? (Ihr Geburtstag, das Fest)\n"
                        ."– Wann und wo ist das Fest?\n"
                        ."– Was soll Nina mitbringen?\n\n"
                        ."Vergessen Sie Anrede und Grußformel nicht.",
                    'expected_points' => [
                        "Le motif du message : l'anniversaire et l'invitation à la fête (par exemple « Ich habe am Samstag Geburtstag und mache ein Fest »).",
                        "Le moment et le lieu de la fête : une heure et une adresse ou un lieu (par exemple « am Samstag um 19 Uhr bei mir, Gartenstraße 12 »).",
                        "Ce que Nina doit apporter : un objet précis avec « mitbringen » (par exemple « Bring bitte einen Salat mit »).",
                    ],
                ],
            ],
        ],
    ],
];
