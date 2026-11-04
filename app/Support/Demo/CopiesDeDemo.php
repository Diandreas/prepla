<?php

namespace App\Support\Demo;

/**
 * Des productions d'apprenants — redactions et prises de parole transcrites —
 * pour les classes de demonstration en allemand, en francais et en anglais,
 * avec la correction que l'IA de l'application en aurait rendue : les fautes
 * (categorie.sous-categorie), le profil (diagnostic) et le bilan.
 *
 * C'est ce que le centre lit sur la fiche d'un eleve : « Grammaire · declinaison
 * (cas) », « le verbe ne reste pas en 2e position »... Sans production corrigee,
 * la fiche affichait « Aucune faiblesse » et aucun profil.
 *
 * Chaque copie a un rang (tier) : 0 faible, 1 moyenne, 2 bonne ; sans rang, sa
 * place dans la liste en tient lieu. Un score bas tire une copie faible. Les
 * fautes sont celles qu'on attend d'un apprenant camerounais : cas et place du
 * verbe en allemand, accords, subjonctif et connecteurs en francais, temps et
 * articles en anglais. Aucune copie ne porte de nom : {prenom} est remplace
 * par celui de l'eleve qui la rend.
 */
final class CopiesDeDemo
{
    /**
     * La copie qui correspond a une langue, une competence, un niveau et un score,
     * signee du prenom de l'eleve. Null quand il n'y en a plus d'assez proche.
     *
     * Une copie est un texte fixe : deux eleves qui la recoivent rendent mot pour
     * mot la meme redaction, et un prospect qui ouvre deux fiches le voit. Une
     * copie deja rendue dans la classe n'est donc jamais reprise (null : l'eleve
     * fera une question fermee a la place) ; parmi les autres, la plus proche du
     * niveau, puis celle que le centre a le moins rendue.
     *
     * @param  list<string>  $dejaEcrites  cles des copies deja rendues par cet eleve
     * @param  array<string, int>  $usagesClasse  cle => fois rendue dans sa classe
     * @param  array<string, int>  $usagesCentre  cle => fois rendue dans le centre
     * @return array{cle: string, texte: string, corrections: list<array<string, string>>, diagnostic: list<array<string, string>>, bilan: string, ecart_vocabulaire: float, ecart_grammaire: float}|null
     */
    public static function copie(string $langue, string $competence, string $niveau, float $precision, string $prenom, array $dejaEcrites = [], array $usagesClasse = [], array $usagesCentre = []): ?array
    {
        $toutes = self::toutes();
        $langue = isset($toutes[$langue]) ? $langue : 'french';
        $parCompetence = $toutes[$langue][$competence] ?? null;
        if (! $parCompetence) {
            return null;
        }

        $bande = in_array($niveau, ['A0', 'A1', 'A2'], true) ? 'A' : 'B';
        if (! isset($parCompetence[$bande])) {
            $bande = $bande === 'A' ? 'B' : 'A';
        }
        $copies = $parCompetence[$bande] ?? [];

        // 0 : copie faible, 1 : moyenne, 2 : bonne.
        $cible = match (true) {
            $precision < 55 => 0,
            $precision < 75 => 1,
            default => 2,
        };

        $candidates = [];
        foreach ($copies as $i => $copie) {
            $cle = "{$langue}|{$competence}|{$bande}|{$i}";
            $ecart = abs(($copie['tier'] ?? min($i, 2)) - $cible);
            // Jamais une copie sans faute pour un eleve a 40 %, ni l'inverse.
            if ($ecart > 1 || in_array($cle, $dejaEcrites, true) || ($usagesClasse[$cle] ?? 0) > 0) {
                continue;
            }
            $candidates[] = [$ecart, $usagesCentre[$cle] ?? 0, $i, $cle];
        }
        if ($candidates === []) {
            return null;
        }
        sort($candidates);
        [, , $i, $cle] = $candidates[0];

        $copie = $copies[$i];
        unset($copie['tier']);
        $copie['texte'] = self::signer($copie['texte'], $prenom);

        return ['cle' => $cle] + $copie + ['ecart_vocabulaire' => 0.0, 'ecart_grammaire' => 0.0];
    }

    /**
     * Le prenom de l'eleve a la place de {prenom} : une copie ne porte jamais le
     * nom d'un autre eleve.
     */
    public static function signer(string $texte, string $prenom): string
    {
        $prenom = trim($prenom) !== '' ? trim($prenom) : '…';

        return str_replace('{prenom}', $prenom, $texte);
    }

    /**
     * Toutes les copies, par langue, competence et tranche de niveau.
     *
     * @return array<string, array<string, array<string, list<array<string, mixed>>>>>
     */
    public static function toutes(): array
    {
        // Les autres copies a la SUITE des premieres : le rang d'une copie, donc
        // sa cle, ne change pas quand on en ajoute.
        return array_merge_recursive(self::COPIES, self::AUTRES_COPIES);
    }

    private const COPIES = [
        'german' => [
            'writing' => [
                'A' => [
                    [
                        'texte' => "Hallo Lisa, ich komme später. Ich habe viel Arbeit in die Büro. Wir kann dann ein Kaffee trinken. Ich bin sehr leid. Bis bald, {prenom}",
                        'corrections' => [
                            ['original' => 'in die Büro', 'corrected' => 'im Büro', 'category' => 'grammar.case', 'explanation' => 'Pour dire où l’on est (Wo?), « in » est suivi du datif : « in dem » devient « im ». « Büro » est neutre.'],
                            ['original' => 'Wir kann', 'corrected' => 'Wir können', 'category' => 'grammar.conjugation', 'explanation' => 'Avec « wir », le verbe de modalité se conjugue « können ».'],
                            ['original' => 'ein Kaffee trinken', 'corrected' => 'einen Kaffee trinken', 'category' => 'grammar.case', 'explanation' => '« Kaffee » est masculin et complément d’objet : accusatif, « einen ».'],
                            ['original' => 'Ich bin sehr leid', 'corrected' => 'Es tut mir sehr leid', 'category' => 'vocabulary.lexical', 'explanation' => 'On s’excuse avec « Es tut mir leid » ; « Ich bin leid » est un calque de l’anglais.'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'grammatical_control', 'observation' => 'Les cas ne sont pas encore distingués : « in die Büro », « ein Kaffee » — l’accusatif et le datif sont remplacés par le nominatif.', 'severity' => 'major'],
                            ['dimension' => 'task_fulfilment', 'observation' => 'Le retard est annoncé, mais la raison reste vague et aucune heure n’est proposée.', 'severity' => 'minor'],
                            ['dimension' => 'lexical_precision', 'observation' => '« Ich bin sehr leid » : la formule d’excuse « Es tut mir leid » n’est pas acquise.', 'severity' => 'minor'],
                        ],
                        'bilan' => 'Message compréhensible, mais les cas (accusatif, datif) sont à reprendre en priorité.',
                        'ecart_vocabulaire' => -0.5, 'ecart_grammaire' => -1.0,
                    ],
                    [
                        'texte' => "Liebe Lisa, ich komme heute 30 Minuten später, weil ich muss noch arbeiten. Im Café sind viele Kunden. Danach wir können zusammen spazieren gehen oder einen Kaffee trinken. Viele Grüße, {prenom}",
                        'corrections' => [
                            ['original' => 'weil ich muss noch arbeiten', 'corrected' => 'weil ich noch arbeiten muss', 'category' => 'grammar.word-order', 'explanation' => 'Après « weil », le verbe conjugué va à la fin de la subordonnée.'],
                            ['original' => 'Danach wir können', 'corrected' => 'Danach können wir', 'category' => 'grammar.word-order', 'explanation' => 'Quand la phrase commence par « Danach », le verbe reste en 2e position : « Danach können wir ».'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'syntactic_complexity', 'observation' => 'Utilise déjà « weil », mais place le verbe comme en français : la règle du verbe final n’est pas automatisée.', 'severity' => 'minor'],
                            ['dimension' => 'task_fulfilment', 'observation' => 'Les trois points de la consigne sont traités : le retard, la raison, une proposition.', 'severity' => 'strength'],
                        ],
                        'bilan' => 'Message complet. La place du verbe (après « weil », après « Danach ») reste à travailler.',
                        'ecart_vocabulaire' => 0.0, 'ecart_grammaire' => -0.5,
                    ],
                    [
                        'texte' => "Liebe Lisa, leider komme ich heute etwa eine halbe Stunde später, weil im Café noch viele Kunden sind. Wollen wir danach zusammen im Park spazieren gehen? Ich bringe dir ein Stück Kuchen mit. Bis gleich! {prenom}",
                        'corrections' => [],
                        'diagnostic' => [
                            ['dimension' => 'grammatical_control', 'observation' => 'Inversion après « leider » et verbe final après « weil » maîtrisés ; verbe séparable « bringe … mit » correct.', 'severity' => 'strength'],
                            ['dimension' => 'task_fulfilment', 'observation' => 'Les trois points sont traités, avec une question qui relance la destinataire.', 'severity' => 'strength'],
                        ],
                        'bilan' => 'Très bon message : clair, poli et correct.',
                        'ecart_vocabulaire' => 0.5, 'ecart_grammaire' => 0.5,
                    ],
                ],
                'B' => [
                    [
                        'texte' => "Sehr geehrte Damen und Herren, ich schreibe Ihnen, weil ich habe ein Problem mit meine Bestellung. Das Paket ist kaputt angekommen und der Handy funktioniert nicht. Ich will das Geld zurück. Ich warte Ihre Antwort. Mit freundlichen Grüßen, {prenom}",
                        'corrections' => [
                            ['original' => 'weil ich habe ein Problem', 'corrected' => 'weil ich ein Problem habe', 'category' => 'grammar.word-order', 'explanation' => 'Après « weil », le verbe conjugué part à la fin de la subordonnée.'],
                            ['original' => 'mit meine Bestellung', 'corrected' => 'mit meiner Bestellung', 'category' => 'grammar.case', 'explanation' => '« mit » est toujours suivi du datif ; « Bestellung » est féminin : « meiner ».'],
                            ['original' => 'der Handy', 'corrected' => 'das Handy', 'category' => 'grammar.article', 'explanation' => '« Handy » est neutre : « das Handy ».'],
                            ['original' => 'Ich warte Ihre Antwort', 'corrected' => 'Ich warte auf Ihre Antwort', 'category' => 'grammar.preposition', 'explanation' => '« warten » se construit avec « auf » + accusatif.'],
                            ['original' => 'Ich will das Geld zurück', 'corrected' => 'Ich möchte mein Geld zurückbekommen', 'category' => 'vocabulary.register', 'explanation' => 'Dans une lettre formelle, « ich möchte » est plus poli que « ich will ».'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'grammatical_control', 'observation' => 'Après « mit », le datif n’est pas fait (« mit meine Bestellung ») et le genre des noms courants hésite (« der Handy »).', 'severity' => 'major'],
                            ['dimension' => 'syntactic_complexity', 'observation' => 'Le verbe reste en 2e position après « weil » : la subordonnée allemande n’est pas maîtrisée.', 'severity' => 'major'],
                            ['dimension' => 'register', 'observation' => '« Ich will » dans une réclamation formelle : le ton est trop direct pour une lettre à une entreprise.', 'severity' => 'minor'],
                        ],
                        'bilan' => 'La réclamation est compréhensible, mais le datif, le genre des noms et la place du verbe font perdre beaucoup de points.',
                        'ecart_vocabulaire' => -0.5, 'ecart_grammaire' => -1.5,
                    ],
                    [
                        'texte' => "Hallo zusammen, ich finde, dass Handys in der Schule verboten sein sollten. Erstens die Schüler sind abgelenkt und lernen weniger. Zweitens gibt es viel Mobbing in sozialen Netzwerken. Aber man kann das Handy auch für die Recherche benutzen. Deshalb ich denke, man braucht klare Regeln. Viele Grüße, {prenom}",
                        'corrections' => [
                            ['original' => 'Erstens die Schüler sind abgelenkt', 'corrected' => 'Erstens sind die Schüler abgelenkt', 'category' => 'grammar.word-order', 'explanation' => '« Erstens » occupe la 1re position : le verbe vient juste après, avant le sujet.'],
                            ['original' => 'Deshalb ich denke', 'corrected' => 'Deshalb denke ich', 'category' => 'grammar.word-order', 'explanation' => 'Après « deshalb » en tête de phrase, le verbe passe avant le sujet.'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'coherence', 'observation' => 'Argumentation bien structurée : « Erstens », « Zweitens », « Aber », « Deshalb ».', 'severity' => 'strength'],
                            ['dimension' => 'grammatical_control', 'observation' => 'Après un connecteur en tête de phrase, le sujet passe avant le verbe (« Deshalb ich denke ») : l’inversion n’est pas automatique.', 'severity' => 'major'],
                            ['dimension' => 'lexical_range', 'observation' => 'Vocabulaire du thème correct mais limité : « Handy » et « Schule » reviennent sans variante.', 'severity' => 'minor'],
                        ],
                        'bilan' => 'Bon plan d’argumentation. L’inversion après les connecteurs est la priorité pour le B1.',
                        'ecart_vocabulaire' => -0.5, 'ecart_grammaire' => -0.5,
                    ],
                    [
                        'texte' => "Liebe Forumsmitglieder, meiner Meinung nach sollte man Handys im Unterricht nicht komplett verbieten. Einerseits lenken sie die Schüler ab, andererseits sind sie ein nützliches Werkzeug, wenn der Lehrer sie gezielt einsetzt. Ich selbst habe mit einer App sehr viel Wortschatz gelernt. Wichtig ist, dass es klare Regeln gibt, an die sich alle halten. Beste Grüße, {prenom}",
                        'corrections' => [
                            ['original' => 'sehr viel Wortschatz gelernt', 'corrected' => 'viele neue Wörter gelernt', 'category' => 'vocabulary.collocation', 'explanation' => '« viele neue Wörter lernen » est plus naturel ; « Wortschatz » s’emploie plutôt avec « erweitern ».'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'syntactic_complexity', 'observation' => 'Relative avec préposition (« an die sich alle halten ») et « einerseits … andererseits » : structures de niveau B2.', 'severity' => 'strength'],
                            ['dimension' => 'coherence', 'observation' => 'Avis nuancé, illustré par une expérience personnelle, et conclusion claire.', 'severity' => 'strength'],
                        ],
                        'bilan' => 'Contribution nuancée et bien construite, au-dessus des attentes du B1.',
                        'ecart_vocabulaire' => 0.5, 'ecart_grammaire' => 0.5,
                    ],
                ],
            ],
            'speaking' => [
                'A' => [
                    [
                        'texte' => "Ich heiße {prenom}. Ich bin aus Kamerun, aus Bafoussam. Ich wohne in Yaoundé seit zwei Jahr. Ich bin Student… äh… ich lerne Informatik. Ich habe zwei Bruder.",
                        'corrections' => [
                            ['original' => 'seit zwei Jahr', 'corrected' => 'seit zwei Jahren', 'category' => 'grammar.case', 'explanation' => 'Après « seit » : datif, et au pluriel « Jahren ».'],
                            ['original' => 'zwei Bruder', 'corrected' => 'zwei Brüder', 'category' => 'vocabulary.word-formation', 'explanation' => 'Le pluriel de « Bruder » prend un Umlaut : « Brüder ».'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'fluency', 'observation' => 'Phrases très courtes, avec une hésitation (« äh ») avant chaque nouvelle information.', 'severity' => 'major'],
                            ['dimension' => 'grammatical_control', 'observation' => 'Les pluriels et le datif après « seit » ne sont pas encore acquis.', 'severity' => 'minor'],
                        ],
                        'bilan' => 'Se présente de façon compréhensible ; il faut enchaîner les phrases et réviser les pluriels.',
                    ],
                    [
                        'texte' => "Also, am Wochenende ich gehe gern auf den Markt mit meiner Mutter. Wir kaufen Gemüse und Fisch. Am Sonntag gehe ich in die Kirche und danach ich spiele Fußball mit meine Freunde.",
                        'corrections' => [
                            ['original' => 'am Wochenende ich gehe', 'corrected' => 'am Wochenende gehe ich', 'category' => 'grammar.word-order', 'explanation' => 'Le verbe reste en 2e position : après « am Wochenende », il passe avant le sujet.'],
                            ['original' => 'danach ich spiele', 'corrected' => 'danach spiele ich', 'category' => 'grammar.word-order', 'explanation' => 'Même règle après « danach » : verbe, puis sujet.'],
                            ['original' => 'mit meine Freunde', 'corrected' => 'mit meinen Freunden', 'category' => 'grammar.case', 'explanation' => '« mit » demande le datif ; au pluriel : « meinen Freunden ».'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'grammatical_control', 'observation' => 'Le verbe ne reste pas en 2e position quand la phrase commence par un complément (« am Wochenende ich gehe », « danach ich spiele »).', 'severity' => 'major'],
                            ['dimension' => 'task_fulfilment', 'observation' => 'Parle de ses habitudes avec des détails concrets (le marché, l’église, le football).', 'severity' => 'strength'],
                        ],
                        'bilan' => 'Bon contenu, réponse développée. La place du verbe est la faute qui revient le plus.',
                    ],
                    [
                        'texte' => "Mein Tagesablauf ist ziemlich voll. Ich stehe um sechs Uhr auf, dann frühstücke ich schnell und fahre mit dem Bus zur Arbeit. Abends lerne ich zwei Stunden Deutsch, weil ich nächstes Jahr in Deutschland eine Ausbildung machen möchte.",
                        'corrections' => [],
                        'diagnostic' => [
                            ['dimension' => 'fluency', 'observation' => 'Discours fluide, enchaîné avec « dann », « abends », « weil ».', 'severity' => 'strength'],
                            ['dimension' => 'syntactic_complexity', 'observation' => 'Verbes séparables (« stehe … auf ») et verbe final après « weil » corrects à l’oral.', 'severity' => 'strength'],
                        ],
                        'bilan' => 'Très bonne prise de parole, au-dessus du niveau A1.',
                    ],
                ],
                'B' => [
                    [
                        'texte' => "Ich denke, die Ausbildung in Deutschland ist gut, weil man kann Geld verdienen. Aber es ist schwer für die Ausländer, weil die Sprache… äh… die Sprache ist schwer. Ich will in der Pflege arbeiten. Das ist mein Traum seit lange.",
                        'corrections' => [
                            ['original' => 'weil man kann Geld verdienen', 'corrected' => 'weil man Geld verdienen kann', 'category' => 'grammar.word-order', 'explanation' => 'Après « weil », le verbe conjugué va à la fin.'],
                            ['original' => 'seit lange', 'corrected' => 'schon lange', 'category' => 'vocabulary.lexical', 'explanation' => 'On dit « schon lange » ou « seit Langem ».'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'fluency', 'observation' => 'Pauses et reprises (« die Sprache… äh… die Sprache ») : l’argument s’arrête dès qu’il faut l’expliquer.', 'severity' => 'major'],
                            ['dimension' => 'lexical_range', 'observation' => '« schwer » revient deux fois et « gut » sert d’unique jugement.', 'severity' => 'minor'],
                            ['dimension' => 'syntactic_complexity', 'observation' => 'Le verbe reste en 2e position après « weil », à chaque fois.', 'severity' => 'minor'],
                        ],
                        'bilan' => 'Le projet est clair, mais l’argumentation reste pauvre et hésitante.',
                    ],
                    [
                        'texte' => "Also, ich bin nicht ganz einverstanden. Natürlich ist das Restaurant bequemer, aber ein Picknick ist billiger und wir können alle zusammen spielen. Vielleicht wir machen das Picknick am Nachmittag und gehen danach ins Restaurant? Was meinst du?",
                        'corrections' => [
                            ['original' => 'Vielleicht wir machen', 'corrected' => 'Vielleicht machen wir', 'category' => 'grammar.word-order', 'explanation' => '« Vielleicht » en tête de phrase : le verbe vient juste après.'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'task_fulfilment', 'observation' => 'Propose un compromis et relance l’interlocuteur (« Was meinst du? ») : c’est ce qu’attend l’épreuve.', 'severity' => 'strength'],
                            ['dimension' => 'grammatical_control', 'observation' => 'Inversion oubliée après « Vielleicht » ; le reste des phrases est correct.', 'severity' => 'minor'],
                        ],
                        'bilan' => 'Bonne interaction, un seul point de grammaire à surveiller.',
                    ],
                    [
                        'texte' => "Ich verstehe deinen Standpunkt, aber lass uns die Vor- und Nachteile vergleichen. Wenn wir im Park feiern, können auch die Kinder der Kollegen mitkommen, und das kostet uns fast nichts. Falls es regnet, könnten wir immer noch kurzfristig einen Tisch im Restaurant reservieren.",
                        'corrections' => [],
                        'diagnostic' => [
                            ['dimension' => 'syntactic_complexity', 'observation' => 'Conditionnelles variées (« Wenn », « Falls » + Konjunktiv II « könnten »).', 'severity' => 'strength'],
                            ['dimension' => 'register', 'observation' => 'Ton adapté à un collègue : propose et nuance, sans imposer.', 'severity' => 'strength'],
                        ],
                        'bilan' => 'Prise de parole convaincante et nuancée.',
                    ],
                ],
            ],
        ],
        'french' => [
            'writing' => [
                'A' => [
                    [
                        'texte' => "Bonjour madame, je vous écris pour dire que je peux pas venir au cours samedi. Mon frère il est malade et je doit rester avec lui. Je suis désolé pour sa. Merci. {prenom}",
                        'corrections' => [
                            ['original' => 'je peux pas', 'corrected' => 'je ne peux pas', 'category' => 'grammar.negation', 'explanation' => 'À l’écrit, la négation a deux parties : « ne… pas ».'],
                            ['original' => 'Mon frère il est malade', 'corrected' => 'Mon frère est malade', 'category' => 'grammar.pronoun', 'explanation' => 'À l’écrit, on ne reprend pas le sujet par « il ».'],
                            ['original' => 'je doit', 'corrected' => 'je dois', 'category' => 'grammar.conjugation', 'explanation' => 'Avec « je », le verbe « devoir » s’écrit « dois ».'],
                            ['original' => 'pour sa', 'corrected' => 'pour cela', 'category' => 'spelling.typo', 'explanation' => '« sa » est un possessif ; on attend ici le pronom « ça » (« cela » à l’écrit).'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'grammatical_control', 'observation' => 'Négation sans « ne » et sujet doublé (« Mon frère il est ») : l’écrit reproduit l’oral.', 'severity' => 'major'],
                            ['dimension' => 'task_fulfilment', 'observation' => 'L’absence est annoncée, mais aucune solution pour rattraper le cours n’est proposée.', 'severity' => 'minor'],
                        ],
                        'bilan' => 'Message compris, mais trop proche de l’oral : négation, sujet et conjugaison à revoir.',
                        'ecart_vocabulaire' => 0.0, 'ecart_grammaire' => -1.0,
                    ],
                    [
                        'texte' => "Bonjour Claire, merci pour ton invitation ! Je viendrai avec plaisir samedi. Est-ce que je peux amener ma cousine ? Elle est arrivé de Douala hier. Je vais préparer des beignets. À samedi ! {prenom}",
                        'corrections' => [
                            ['original' => 'Elle est arrivé', 'corrected' => 'Elle est arrivée', 'category' => 'grammar.agreement', 'explanation' => 'Avec « être », le participe passé s’accorde avec le sujet : « elle est arrivée ».'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'grammatical_control', 'observation' => 'Accord du participe passé avec « être » oublié (« elle est arrivé »).', 'severity' => 'minor'],
                            ['dimension' => 'task_fulfilment', 'observation' => 'Accepte, pose une question et propose d’apporter quelque chose : consigne remplie.', 'severity' => 'strength'],
                        ],
                        'bilan' => 'Bon message, chaleureux et complet. Attention aux accords.',
                        'ecart_vocabulaire' => 0.0, 'ecart_grammaire' => -0.5,
                    ],
                    [
                        'texte' => "Chère Madame Fouda, je vous remercie pour votre message. Malheureusement, je ne pourrai pas assister à la réunion de jeudi, car je passe un examen ce jour-là. Pourriez-vous m'envoyer le compte rendu ? Je vous prie d'agréer mes salutations distinguées.",
                        'corrections' => [
                            ['original' => "Je vous prie d'agréer mes salutations distinguées", 'corrected' => "Je vous prie d'agréer, Madame, mes salutations distinguées", 'category' => 'vocabulary.register', 'explanation' => 'Dans la formule de politesse, on reprend l’appel : « Madame ».'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'register', 'observation' => 'Registre formel tenu du début à la fin (« Pourriez-vous », formule de politesse).', 'severity' => 'strength'],
                            ['dimension' => 'grammatical_control', 'observation' => 'Futur, conditionnel de politesse et négation complète maîtrisés.', 'severity' => 'strength'],
                        ],
                        'bilan' => 'Très bon message formel.',
                        'ecart_vocabulaire' => 0.5, 'ecart_grammaire' => 0.5,
                    ],
                ],
                'B' => [
                    [
                        'texte' => "Aujourd'hui beaucoup de jeunes veulent partir au Canada. Je pense que c'est une bonne chose mais il faut que les jeunes sont préparés. Il y a des avantages, il y a aussi des inconvénients. Par exemple le froid, la famille qui est loin. Donc chacun doit décidé pour lui.",
                        'corrections' => [
                            ['original' => 'il faut que les jeunes sont préparés', 'corrected' => 'il faut que les jeunes soient préparés', 'category' => 'grammar.verb-form', 'explanation' => 'Après « il faut que », le subjonctif est obligatoire : « soient ».'],
                            ['original' => 'doit décidé', 'corrected' => 'doit décider', 'category' => 'grammar.verb-form', 'explanation' => 'Après un verbe conjugué comme « doit », on met l’infinitif : « décider ».'],
                            ['original' => 'Il y a des avantages, il y a aussi des inconvénients', 'corrected' => 'Certes, il y a des avantages, mais il existe aussi des inconvénients', 'category' => 'coherence.connectors', 'explanation' => 'Les deux idées sont juxtaposées : un connecteur d’opposition (« certes… mais ») montre le raisonnement.'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'coherence', 'observation' => 'Les idées sont posées les unes après les autres sans connecteurs logiques : « Il y a des avantages, il y a aussi… ».', 'severity' => 'major'],
                            ['dimension' => 'grammatical_control', 'observation' => 'Subjonctif absent après « il faut que » et infinitif mal écrit (« doit décidé »).', 'severity' => 'major'],
                            ['dimension' => 'lexical_range', 'observation' => '« il y a » revient trois fois en quatre phrases.', 'severity' => 'minor'],
                        ],
                        'bilan' => 'Le sujet est compris, mais le texte ne démontre rien : connecteurs et subjonctif sont les deux priorités.',
                        'ecart_vocabulaire' => -0.5, 'ecart_grammaire' => -1.0,
                    ],
                    [
                        'texte' => "Selon moi, le télétravail présente de nombreux avantages. D'abord, on gagne du temps car on évite les embouteillages de Yaoundé. Ensuite, on peut mieux organiser sa journée. Cependant, il est important que l'employé a un bon accès à internet, ce qui n'est pas toujours le cas. En conclusion, je suis pour le télétravail, à condition que l'entreprise aide ses employés.",
                        'corrections' => [
                            ['original' => "que l'employé a", 'corrected' => "que l'employé ait", 'category' => 'grammar.verb-form', 'explanation' => '« Il est important que » demande le subjonctif : « ait ».'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'coherence', 'observation' => 'Plan clair et connecteurs variés : « D’abord », « Ensuite », « Cependant », « En conclusion ».', 'severity' => 'strength'],
                            ['dimension' => 'grammatical_control', 'observation' => 'Le subjonctif est encore oublié après « il est important que ».', 'severity' => 'minor'],
                            ['dimension' => 'lexical_range', 'observation' => 'Vocabulaire juste mais général (« nombreux avantages », « bon accès ») : peu de termes précis du thème.', 'severity' => 'minor'],
                        ],
                        'bilan' => 'Texte bien organisé. Pour viser plus haut : subjonctif systématique et vocabulaire plus précis.',
                        'ecart_vocabulaire' => -0.5, 'ecart_grammaire' => 0.0,
                    ],
                    [
                        'texte' => "Si l'immigration qualifiée ouvre indéniablement des perspectives, elle soulève aussi la question de la fuite des cerveaux. En effet, lorsqu'un infirmier formé au Cameroun s'installe au Québec, c'est tout un système de santé qui perd son investissement. Il faudrait donc que les pays d'accueil et de départ conçoivent ensemble des programmes de retour, afin que chacun y trouve son compte.",
                        'corrections' => [],
                        'diagnostic' => [
                            ['dimension' => 'syntactic_complexity', 'observation' => 'Concessive (« Si… »), mise en relief et subjonctifs (« conçoivent », « trouve ») bien maîtrisés.', 'severity' => 'strength'],
                            ['dimension' => 'lexical_precision', 'observation' => 'Vocabulaire précis du thème : « fuite des cerveaux », « pays d’accueil ».', 'severity' => 'strength'],
                        ],
                        'bilan' => 'Argumentation nuancée, de très bon niveau.',
                        'ecart_vocabulaire' => 0.5, 'ecart_grammaire' => 0.5,
                    ],
                ],
            ],
            'speaking' => [
                'A' => [
                    [
                        'texte' => "Euh… je m'appelle {prenom}, j'habite à Garoua mais maintenant je suis à Yaoundé pour les études. J'aime le football et… euh… la musique. Le week-end je vais au marché avec mon ami.",
                        'corrections' => [],
                        'diagnostic' => [
                            ['dimension' => 'fluency', 'observation' => 'Hésitations fréquentes (« euh ») et phrases courtes juxtaposées.', 'severity' => 'major'],
                            ['dimension' => 'task_fulfilment', 'observation' => 'Se présente, mais ne développe aucun point (quel football, quelle musique).', 'severity' => 'minor'],
                        ],
                        'bilan' => 'Présentation correcte mais trop courte : il faut développer chaque idée.',
                    ],
                    [
                        'texte' => "Alors, dans mon quartier, il y a beaucoup de commerces, un marché et une école. Ce que j'aime, c'est que les gens se connaissent. Par contre, la route est mauvaise quand il pleut.",
                        'corrections' => [],
                        'diagnostic' => [
                            ['dimension' => 'coherence', 'observation' => 'Oppose clairement les points positifs et négatifs (« Ce que j’aime… Par contre »).', 'severity' => 'strength'],
                            ['dimension' => 'lexical_range', 'observation' => 'Vocabulaire simple et général : « beaucoup de », « mauvaise ».', 'severity' => 'minor'],
                        ],
                        'bilan' => 'Description organisée ; enrichir le vocabulaire.',
                    ],
                    [
                        'texte' => "Je voudrais partir au Canada pour faire un master en génie civil. J'ai déjà contacté deux universités à Montréal et je prépare mon dossier. Ce qui m'inquiète un peu, c'est l'hiver, mais je me dis qu'on s'habitue à tout.",
                        'corrections' => [],
                        'diagnostic' => [
                            ['dimension' => 'fluency', 'observation' => 'Discours fluide et naturel, sans hésitation.', 'severity' => 'strength'],
                            ['dimension' => 'syntactic_complexity', 'observation' => 'Mise en relief (« Ce qui m’inquiète, c’est ») et passé composé maîtrisés.', 'severity' => 'strength'],
                        ],
                        'bilan' => 'Très bonne prise de parole.',
                    ],
                ],
                'B' => [
                    [
                        'texte' => "Moi je pense que les réseaux sociaux c'est pas bien pour les jeunes. Parce que… ils passent tout le temps sur le téléphone. Et aussi il y a les fausses informations. C'est pas bien.",
                        'corrections' => [
                            ['original' => "c'est pas bien", 'corrected' => "ce n'est pas une bonne chose", 'category' => 'vocabulary.register', 'explanation' => 'À l’examen, on évite le registre familier : « ce n’est pas… », et on précise le jugement.'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'lexical_range', 'observation' => '« C’est pas bien » revient deux fois comme seul jugement.', 'severity' => 'major'],
                            ['dimension' => 'coherence', 'observation' => 'Les arguments s’enchaînent par « et aussi » sans être développés ni illustrés.', 'severity' => 'minor'],
                            ['dimension' => 'register', 'observation' => 'Registre familier (« Moi je pense », « c’est pas ») face à un examinateur.', 'severity' => 'minor'],
                        ],
                        'bilan' => 'Avis exprimé, mais sans argument développé ni exemple : la note reste basse.',
                    ],
                    [
                        'texte' => "À mon avis, apprendre une deuxième langue étrangère est indispensable aujourd'hui. Par exemple, au Cameroun, quelqu'un qui parle français et anglais trouve plus facilement du travail. Bien que ce soit difficile, ça vaut la peine. Le problème, c'est que les cours coûtent cher.",
                        'corrections' => [
                            ['original' => 'ça vaut la peine', 'corrected' => 'cela en vaut la peine', 'category' => 'grammar.pronoun', 'explanation' => 'L’expression complète est « en valoir la peine » : le pronom « en » est attendu.'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'syntactic_complexity', 'observation' => 'Emploie « bien que » + subjonctif correctement (« bien que ce soit »).', 'severity' => 'strength'],
                            ['dimension' => 'task_fulfilment', 'observation' => 'Le dernier argument (le coût des cours) est lancé mais pas développé.', 'severity' => 'minor'],
                        ],
                        'bilan' => 'Bonne argumentation illustrée ; aller au bout de chaque idée.',
                    ],
                    [
                        'texte' => "Je nuancerais cette affirmation. Certes, le numérique facilite l'accès au savoir, mais encore faut-il disposer d'une connexion stable, ce qui n'est pas acquis partout. Je proposerais plutôt de combiner cours en présentiel et ressources en ligne, de façon à ce que personne ne soit laissé de côté.",
                        'corrections' => [],
                        'diagnostic' => [
                            ['dimension' => 'coherence', 'observation' => 'Concession puis contre-argument (« Certes… mais encore faut-il ») : raisonnement très clair.', 'severity' => 'strength'],
                            ['dimension' => 'register', 'observation' => 'Registre soutenu tenu jusqu’au bout (« je nuancerais », « encore faut-il »).', 'severity' => 'strength'],
                        ],
                        'bilan' => 'Excellente prise de parole argumentée.',
                    ],
                ],
            ],
        ],
        'english' => [
            'writing' => [
                'B' => [
                    [
                        'texte' => "Nowadays many people is moving to big cities for find a job. In my opinion this is good and bad. The cities becomes very crowded and the life is expensive. In other hand, young people have more opportunity. Government should to build more houses.",
                        'corrections' => [
                            ['original' => 'many people is moving', 'corrected' => 'many people are moving', 'category' => 'grammar.agreement', 'explanation' => '« People » est pluriel : « are ».'],
                            ['original' => 'for find a job', 'corrected' => 'to find a job', 'category' => 'grammar.preposition', 'explanation' => 'Pour exprimer le but : « to » + verbe.'],
                            ['original' => 'the life is expensive', 'corrected' => 'life is expensive', 'category' => 'grammar.article', 'explanation' => 'Pour une idée générale, pas d’article : « life ».'],
                            ['original' => 'In other hand', 'corrected' => 'On the other hand', 'category' => 'coherence.connectors', 'explanation' => 'Le connecteur figé est « On the other hand ».'],
                            ['original' => 'should to build', 'corrected' => 'should build', 'category' => 'grammar.modal', 'explanation' => 'Après un modal, la base verbale sans « to ».'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'grammatical_control', 'observation' => 'Accord sujet-verbe instable (« people is », « cities becomes ») et « should to ».', 'severity' => 'major'],
                            ['dimension' => 'task_fulfilment', 'observation' => 'La position reste floue (« good and bad ») sans argument développé : la note Task Response plafonne vers 5.', 'severity' => 'major'],
                            ['dimension' => 'coherence', 'observation' => 'Connecteur mal formé (« In other hand ») et aucun paragraphe.', 'severity' => 'minor'],
                        ],
                        'bilan' => 'Idées pertinentes mais non développées ; accords et articles à reprendre.',
                        'ecart_vocabulaire' => -0.5, 'ecart_grammaire' => -1.0,
                    ],
                    [
                        'texte' => "In recent years, online learning has become more and more popular. I believe that it has many advantages. Firstly, students can study whenever they want. Secondly, it is cheaper than traditional university. However, I have studied online last year and I found it difficult to stay motivated without a teacher.",
                        'corrections' => [
                            ['original' => 'I have studied online last year', 'corrected' => 'I studied online last year', 'category' => 'grammar.tense', 'explanation' => 'Avec un moment passé précis (« last year »), on emploie le prétérit : « I studied ».'],
                            ['original' => 'than traditional university', 'corrected' => 'than a traditional university', 'category' => 'grammar.article', 'explanation' => 'Nom dénombrable singulier : il faut un article, « a traditional university ».'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'coherence', 'observation' => 'Structure claire : « Firstly », « Secondly », « However ».', 'severity' => 'strength'],
                            ['dimension' => 'grammatical_control', 'observation' => 'Present perfect employé avec un marqueur de temps passé (« have studied … last year »).', 'severity' => 'minor'],
                            ['dimension' => 'lexical_range', 'observation' => '« more and more popular », « many advantages » : expressions courantes, peu de vocabulaire spécifique.', 'severity' => 'minor'],
                        ],
                        'bilan' => 'Réponse organisée, niveau band 6. Varier le vocabulaire et revoir les temps du passé.',
                        'ecart_vocabulaire' => -0.5, 'ecart_grammaire' => 0.0,
                    ],
                    [
                        'texte' => "While some argue that governments should prioritise public transport over road building, I would contend that a balanced approach is more realistic. In cities such as Douala, where congestion costs commuters hours each day, investing in rapid bus lanes would relieve pressure without requiring vast new infrastructure.",
                        'corrections' => [],
                        'diagnostic' => [
                            ['dimension' => 'syntactic_complexity', 'observation' => 'Subordonnées variées (« While… », relative « where… ») sans erreur.', 'severity' => 'strength'],
                            ['dimension' => 'lexical_precision', 'observation' => 'Vocabulaire précis : « congestion », « commuters », « infrastructure ».', 'severity' => 'strength'],
                        ],
                        'bilan' => 'Très bonne copie, niveau band 7,5 à 8.',
                        'ecart_vocabulaire' => 0.5, 'ecart_grammaire' => 0.5,
                    ],
                ],
            ],
            'speaking' => [
                'B' => [
                    [
                        'texte' => "I am living in Bamenda since five years. I like it because the people is friendly. In the weekend I go to church and after I play football with my friends. The weather is… uh… cold sometimes.",
                        'corrections' => [
                            ['original' => 'I am living in Bamenda since five years', 'corrected' => 'I have been living in Bamenda for five years', 'category' => 'grammar.tense', 'explanation' => 'Action commencée dans le passé et toujours vraie : present perfect continu, et « for » + durée.'],
                            ['original' => 'the people is friendly', 'corrected' => 'the people are friendly', 'category' => 'grammar.agreement', 'explanation' => '« People » est pluriel : « are ».'],
                            ['original' => 'In the weekend', 'corrected' => 'At the weekend', 'category' => 'grammar.preposition', 'explanation' => 'On dit « at the weekend » (ou « on the weekend » en anglais américain).'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'grammatical_control', 'observation' => 'Le present perfect n’est pas employé là où il est attendu (« I am living … since »).', 'severity' => 'major'],
                            ['dimension' => 'fluency', 'observation' => 'Réponses courtes, hésitation dès que la phrase s’allonge (« The weather is… uh… »).', 'severity' => 'minor'],
                        ],
                        'bilan' => 'Compréhensible, mais les temps et les accords limitent la note à 5.',
                    ],
                    [
                        'texte' => "Well, I'd say that technology has changed the way we communicate a lot. For example, my grandmother in the village can now see us on video call. But on the other hand, people spend less time talking face to face, which is a bit sad in my opinion.",
                        'corrections' => [],
                        'diagnostic' => [
                            ['dimension' => 'fluency', 'observation' => 'Discours enchaîné avec des marqueurs naturels (« Well, I’d say », « on the other hand »).', 'severity' => 'strength'],
                            ['dimension' => 'lexical_range', 'observation' => 'Vocabulaire juste mais courant : « a lot », « a bit sad ».', 'severity' => 'minor'],
                        ],
                        'bilan' => 'Bonne réponse, band 6,5 ; enrichir le vocabulaire pour passer à 7.',
                    ],
                    [
                        'texte' => "That's an interesting question. I'd argue that the benefits of tourism largely outweigh the drawbacks, provided that local communities actually share in the revenue. Take Limbe, for instance: eco-tourism there has created jobs while encouraging people to protect the coastline.",
                        'corrections' => [],
                        'diagnostic' => [
                            ['dimension' => 'fluency', 'observation' => 'Réponse fluide, argumentée et illustrée par un exemple local.', 'severity' => 'strength'],
                            ['dimension' => 'lexical_range', 'observation' => 'Vocabulaire riche (« outweigh the drawbacks », « share in the revenue »).', 'severity' => 'strength'],
                        ],
                        'bilan' => 'Excellente réponse, niveau band 7,5.',
                    ],
                ],
            ],
        ],
    ];

    /**
     * D'autres copies, a la suite des premieres : une classe de huit eleves ne
     * doit pas rendre trois fois la meme redaction.
     */
    private const AUTRES_COPIES = [
        'german' => [
            'writing' => [
                'A' => [
                    [
                        'tier' => 0,
                        'texte' => "Hallo Tom, am Samstag ich habe Geburtstag. Ich mache eine Party in meine Wohnung. Du kannst dein Freundin mitbringen. Die Party beginnt um 19 Uhr. Bis Samstag! {prenom}",
                        'corrections' => [
                            ['original' => 'am Samstag ich habe', 'corrected' => 'am Samstag habe ich', 'category' => 'grammar.word-order', 'explanation' => 'Quand la phrase commence par « am Samstag », le verbe reste en 2e position, avant le sujet.'],
                            ['original' => 'in meine Wohnung', 'corrected' => 'in meiner Wohnung', 'category' => 'grammar.case', 'explanation' => 'On est dans l’appartement (Wo?) : « in » + datif ; « Wohnung » est féminin : « meiner ».'],
                            ['original' => 'dein Freundin', 'corrected' => 'deine Freundin', 'category' => 'grammar.article', 'explanation' => '« Freundin » est féminin : le possessif prend un -e, « deine ».'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'grammatical_control', 'observation' => 'Le datif après « in » et le genre des noms ne sont pas faits : « in meine Wohnung », « dein Freundin ».', 'severity' => 'major'],
                            ['dimension' => 'syntactic_complexity', 'observation' => 'Après un complément de temps en tête, le sujet passe avant le verbe (« am Samstag ich habe »).', 'severity' => 'minor'],
                            ['dimension' => 'task_fulfilment', 'observation' => 'L’invitation donne le jour, le lieu et l’heure : les informations attendues sont là.', 'severity' => 'strength'],
                        ],
                        'bilan' => 'Invitation complète, mais trois fautes de base (place du verbe, datif, genre) à corriger.',
                        'ecart_vocabulaire' => -0.5, 'ecart_grammaire' => -1.0,
                    ],
                    [
                        'tier' => 0,
                        'texte' => "Sehr geehrte Frau Weber, ich heiße {prenom} und ich möchte ein Deutschkurs machen. Wann der Kurs beginnt? Wie viel kostet der Kurs? Ich habe am Montag keine Zeit. Danke.",
                        'corrections' => [
                            ['original' => 'ein Deutschkurs machen', 'corrected' => 'einen Deutschkurs machen', 'category' => 'grammar.case', 'explanation' => '« Kurs » est masculin et complément d’objet : accusatif, « einen ».'],
                            ['original' => 'Wann der Kurs beginnt?', 'corrected' => 'Wann beginnt der Kurs?', 'category' => 'grammar.word-order', 'explanation' => 'Dans une question avec « wann », le verbe vient juste après le mot interrogatif.'],
                            ['original' => 'Danke.', 'corrected' => 'Mit freundlichen Grüßen', 'category' => 'vocabulary.register', 'explanation' => 'Une lettre formelle se termine par une formule de politesse : « Mit freundlichen Grüßen ».'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'grammatical_control', 'observation' => 'L’accusatif masculin manque (« ein Deutschkurs ») et le verbe de la question est mal placé (« Wann der Kurs beginnt? »).', 'severity' => 'major'],
                            ['dimension' => 'register', 'observation' => 'La lettre commence de façon formelle mais se termine par un simple « Danke » : le registre n’est pas tenu.', 'severity' => 'minor'],
                        ],
                        'bilan' => 'Les questions sont claires ; revoir l’accusatif, la place du verbe dans les questions et la formule finale.',
                        'ecart_vocabulaire' => 0.0, 'ecart_grammaire' => -1.0,
                    ],
                    [
                        'tier' => 1,
                        'texte' => "Liebe Anna, vielen Dank für deine Einladung! Leider kann ich am Freitag nicht kommen, ich muss mit meiner Schwester zum Arzt gehen. Aber ich kann am Sonntag. Hast du Zeit? Wir können zusammen in die Stadt fahren. Liebe Grüße, {prenom}",
                        'corrections' => [
                            ['original' => 'Aber ich kann am Sonntag.', 'corrected' => 'Aber am Sonntag habe ich Zeit.', 'category' => 'vocabulary.lexical', 'explanation' => '« ich kann am Sonntag » sans verbe est un calque ; on dit « am Sonntag habe ich Zeit ».'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'grammatical_control', 'observation' => 'Verbes de modalité avec l’infinitif à la fin (« kann … nicht kommen », « muss … gehen ») bien placés.', 'severity' => 'strength'],
                            ['dimension' => 'lexical_range', 'observation' => 'Phrases justes mais vocabulaire de base : « kommen », « gehen », « fahren ».', 'severity' => 'minor'],
                        ],
                        'bilan' => 'Bonne réponse à l’invitation, claire et polie.',
                        'ecart_vocabulaire' => 0.0, 'ecart_grammaire' => 0.0,
                    ],
                    [
                        'tier' => 2,
                        'texte' => "Hallo Herr Schulz, ich bin neu im Haus und wohne im zweiten Stock. Am Samstagabend feiere ich meinen Geburtstag. Es wird vielleicht ein bisschen laut, dafür entschuldige ich mich schon jetzt. Sie sind herzlich eingeladen! Viele Grüße, {prenom}",
                        'corrections' => [],
                        'diagnostic' => [
                            ['dimension' => 'register', 'observation' => 'Le vouvoiement (« Sie ») est tenu, adapté à un voisin qu’on connaît peu.', 'severity' => 'strength'],
                            ['dimension' => 'syntactic_complexity', 'observation' => 'Inversions correctes après « Am Samstagabend » et « dafür ».', 'severity' => 'strength'],
                        ],
                        'bilan' => 'Message très réussi, au-delà du A1.',
                        'ecart_vocabulaire' => 0.5, 'ecart_grammaire' => 0.5,
                    ],
                ],
                'B' => [
                    [
                        'tier' => 0,
                        'texte' => "Ich wohne jetzt in Douala, aber früher ich habe auf dem Land gewohnt. In der Stadt gibt es mehr Arbeit, aber die Leben ist teuer. Auf dem Land die Leute sind freundlicher. Ich denke, dass ich möchte später wieder auf dem Land wohnen.",
                        'corrections' => [
                            ['original' => 'früher ich habe auf dem Land gewohnt', 'corrected' => 'früher habe ich auf dem Land gewohnt', 'category' => 'grammar.word-order', 'explanation' => '« früher » en tête : le verbe conjugué vient juste après.'],
                            ['original' => 'die Leben', 'corrected' => 'das Leben', 'category' => 'grammar.article', 'explanation' => '« Leben » est neutre : « das Leben ».'],
                            ['original' => 'Auf dem Land die Leute sind', 'corrected' => 'Auf dem Land sind die Leute', 'category' => 'grammar.word-order', 'explanation' => 'Après un complément en tête de phrase : le verbe, puis le sujet.'],
                            ['original' => 'dass ich möchte später wieder auf dem Land wohnen', 'corrected' => 'dass ich später wieder auf dem Land wohnen möchte', 'category' => 'grammar.word-order', 'explanation' => 'Après « dass », le verbe conjugué va à la fin.'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'grammatical_control', 'observation' => 'La règle du verbe en 2e position n’est pas appliquée : trois phrases sur quatre la cassent.', 'severity' => 'major'],
                            ['dimension' => 'syntactic_complexity', 'observation' => 'Après « dass », le verbe reste à sa place du français.', 'severity' => 'major'],
                            ['dimension' => 'coherence', 'observation' => 'Les avantages de la ville et de la campagne sont cités sans être reliés : « aber » est le seul connecteur.', 'severity' => 'minor'],
                        ],
                        'bilan' => 'Avis compréhensible, mais la place du verbe fait perdre la plupart des points de grammaire.',
                        'ecart_vocabulaire' => 0.0, 'ecart_grammaire' => -1.5,
                    ],
                    [
                        'tier' => 0,
                        'texte' => "Sehr geehrter Herr Becker, seit zwei Wochen die Heizung in meiner Wohnung funktioniert nicht. Ich habe Sie schon angerufen, aber niemand hat geantwortet. Es ist sehr kalt und mein Kind ist krank geworden. Können Sie bitte schnell ein Techniker schicken? Mit freundlichen Grüßen, {prenom}",
                        'corrections' => [
                            ['original' => 'seit zwei Wochen die Heizung in meiner Wohnung funktioniert nicht', 'corrected' => 'seit zwei Wochen funktioniert die Heizung in meiner Wohnung nicht', 'category' => 'grammar.word-order', 'explanation' => '« seit zwei Wochen » occupe la 1re place : le verbe suit immédiatement.'],
                            ['original' => 'ein Techniker schicken', 'corrected' => 'einen Techniker schicken', 'category' => 'grammar.case', 'explanation' => 'Complément d’objet masculin : accusatif, « einen ».'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'task_fulfilment', 'observation' => 'Le problème, sa durée et la demande sont clairement posés.', 'severity' => 'strength'],
                            ['dimension' => 'grammatical_control', 'observation' => 'Accusatif masculin oublié (« ein Techniker ») et verbe déplacé après un complément de temps.', 'severity' => 'major'],
                        ],
                        'bilan' => 'Réclamation efficace ; la grammaire de base (cas, place du verbe) reste fragile.',
                        'ecart_vocabulaire' => 0.0, 'ecart_grammaire' => -1.0,
                    ],
                    [
                        'tier' => 1,
                        'texte' => "Liebe Frau Hoffmann, vielen Dank für Ihre Nachricht. Leider kann ich am Montag nicht zur Besprechung kommen, weil mein Zug erst um 11 Uhr in Frankfurt ankommt. Ist es möglich, dass wir verschieben den Termin auf Dienstag? Ich entschuldige mich für die Unannehmlichkeiten und freue mich auf Ihre Antwort. Mit freundlichen Grüßen, {prenom}",
                        'corrections' => [
                            ['original' => 'dass wir verschieben den Termin auf Dienstag', 'corrected' => 'dass wir den Termin auf Dienstag verschieben', 'category' => 'grammar.word-order', 'explanation' => 'Après « dass », le verbe conjugué va à la fin.'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'register', 'observation' => 'Registre formel et formules de politesse adaptées à une collègue.', 'severity' => 'strength'],
                            ['dimension' => 'syntactic_complexity', 'observation' => 'La subordonnée avec « weil » est juste, pas encore celle avec « dass » : la règle n’est pas stable.', 'severity' => 'minor'],
                        ],
                        'bilan' => 'Message professionnel bien tourné ; un seul point de syntaxe à surveiller.',
                        'ecart_vocabulaire' => 0.5, 'ecart_grammaire' => -0.5,
                    ],
                    [
                        'tier' => 1,
                        'texte' => "Ich lerne seit einem Jahr Deutsch, weil ich in Deutschland eine Ausbildung in der Pflege machen möchte. Am Anfang war es schwer, besonders die Artikel. Jetzt lerne ich jeden Tag mit einer App und schaue deutsche Serien. Ich empfehle allen, jeden Tag ein bisschen zu lernen, als einmal pro Woche sehr viel.",
                        'corrections' => [
                            ['original' => 'als einmal pro Woche sehr viel', 'corrected' => 'statt einmal pro Woche sehr viel', 'category' => 'vocabulary.lexical', 'explanation' => 'Pour opposer deux façons de faire, on dit « statt » (« au lieu de ») ; « als » sert à comparer.'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'coherence', 'observation' => 'Le témoignage suit un ordre clair : la raison, les difficultés, la méthode, le conseil.', 'severity' => 'strength'],
                            ['dimension' => 'lexical_precision', 'observation' => '« als » est employé à la place de « statt » : la nuance entre comparer et remplacer n’est pas acquise.', 'severity' => 'minor'],
                        ],
                        'bilan' => 'Contribution claire et personnelle, de bon niveau B1.',
                        'ecart_vocabulaire' => 0.0, 'ecart_grammaire' => 0.0,
                    ],
                    [
                        'tier' => 2,
                        'texte' => "Sehr geehrte Damen und Herren, mit großem Interesse habe ich Ihre Anzeige für einen Ausbildungsplatz als Elektroniker gelesen. Da ich in Yaoundé bereits ein technisches Abitur gemacht habe und seit zwei Jahren Deutsch lerne, glaube ich, dass ich die Voraussetzungen erfülle. Über eine Einladung zu einem Vorstellungsgespräch würde ich mich sehr freuen. Mit freundlichen Grüßen, {prenom}",
                        'corrections' => [],
                        'diagnostic' => [
                            ['dimension' => 'register', 'observation' => 'Lettre de motivation au registre juste, avec les formules attendues (« mit großem Interesse », « würde ich mich freuen »).', 'severity' => 'strength'],
                            ['dimension' => 'syntactic_complexity', 'observation' => 'Subordonnée en tête (« Da ich … lerne ») suivie de l’inversion : structure maîtrisée.', 'severity' => 'strength'],
                        ],
                        'bilan' => 'Excellente lettre, prête pour une vraie candidature.',
                        'ecart_vocabulaire' => 0.5, 'ecart_grammaire' => 0.5,
                    ],
                ],
            ],
            'speaking' => [
                'A' => [
                    [
                        'tier' => 0,
                        'texte' => "Ich habe… äh… eine Familie groß. Mein Vater ist Lehrer und mein Mutter verkauft auf dem Markt. Ich habe drei Schwester. Wir wohnen in Douala.",
                        'corrections' => [
                            ['original' => 'eine Familie groß', 'corrected' => 'eine große Familie', 'category' => 'grammar.word-order', 'explanation' => 'L’adjectif se place avant le nom : « eine große Familie ».'],
                            ['original' => 'mein Mutter', 'corrected' => 'meine Mutter', 'category' => 'grammar.article', 'explanation' => '« Mutter » est féminin : « meine ».'],
                            ['original' => 'drei Schwester', 'corrected' => 'drei Schwestern', 'category' => 'vocabulary.word-formation', 'explanation' => 'Le pluriel de « Schwester » est « Schwestern ».'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'fluency', 'observation' => 'Hésite dès la première phrase et construit l’idée comme en français (« eine Familie groß »).', 'severity' => 'major'],
                            ['dimension' => 'grammatical_control', 'observation' => 'Genre des possessifs et pluriels encore incertains.', 'severity' => 'minor'],
                        ],
                        'bilan' => 'Présentation de la famille compréhensible ; travailler les pluriels et le genre.',
                    ],
                    [
                        'tier' => 1,
                        'texte' => "Ich esse gern Ndolé mit Plantains. Das ist ein Essen aus Kamerun. Meine Mutter kocht es jeden Sonntag. Ich trinke nicht gern Kaffee, ich trinke lieber Tee mit viel Zucker.",
                        'corrections' => [],
                        'diagnostic' => [
                            ['dimension' => 'task_fulfilment', 'observation' => 'Parle de ses goûts avec un exemple personnel (le ndolé du dimanche).', 'severity' => 'strength'],
                            ['dimension' => 'lexical_range', 'observation' => '« gern » et « lieber » bien employés, mais peu de vocabulaire au-delà.', 'severity' => 'minor'],
                        ],
                        'bilan' => 'Bonne réponse, naturelle ; enrichir le vocabulaire.',
                    ],
                    [
                        'tier' => 1,
                        'texte' => "Am Morgen ich fahre mit dem Moto-Taxi zur Arbeit. Die Fahrt dauert zwanzig Minuten. Am Abend ich gehe zu Fuß nach Hause, weil es ist billiger.",
                        'corrections' => [
                            ['original' => 'Am Morgen ich fahre', 'corrected' => 'Am Morgen fahre ich', 'category' => 'grammar.word-order', 'explanation' => 'Après « am Morgen » en tête, le verbe passe avant le sujet.'],
                            ['original' => 'Am Abend ich gehe', 'corrected' => 'Am Abend gehe ich', 'category' => 'grammar.word-order', 'explanation' => 'Même règle après « am Abend » : verbe, puis sujet.'],
                            ['original' => 'weil es ist billiger', 'corrected' => 'weil es billiger ist', 'category' => 'grammar.word-order', 'explanation' => 'Après « weil », le verbe conjugué va à la fin.'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'grammatical_control', 'observation' => 'Le verbe ne reste pas en 2e position après « am Morgen », « am Abend ».', 'severity' => 'major'],
                            ['dimension' => 'task_fulfilment', 'observation' => 'Décrit son trajet avec des détails concrets (le moto-taxi, la durée).', 'severity' => 'strength'],
                        ],
                        'bilan' => 'Contenu clair ; la place du verbe est la faute qui revient.',
                    ],
                    [
                        'tier' => 2,
                        'texte' => "In meiner Freizeit spiele ich Gitarre in einer kleinen Band. Wir proben jeden Samstag bei meinem Freund zu Hause. Nächsten Monat spielen wir zum ersten Mal auf einer Hochzeit, darauf freue ich mich sehr.",
                        'corrections' => [],
                        'diagnostic' => [
                            ['dimension' => 'fluency', 'observation' => 'Parle sans hésiter et relie les idées (« jeden Samstag », « nächsten Monat »).', 'severity' => 'strength'],
                            ['dimension' => 'grammatical_control', 'observation' => 'Datif juste après « in », « bei », « auf » ; verbe en 2e position respecté.', 'severity' => 'strength'],
                        ],
                        'bilan' => 'Très bonne prise de parole, vivante et correcte.',
                    ],
                ],
                'B' => [
                    [
                        'tier' => 0,
                        'texte' => "Ich finde… äh… das Leben in der Stadt ist besser. Es gibt viel Arbeit und viel Krankenhäuser. Aber auf dem Land es ist ruhig. Ich weiß nicht… beide ist gut.",
                        'corrections' => [
                            ['original' => 'viel Krankenhäuser', 'corrected' => 'viele Krankenhäuser', 'category' => 'grammar.agreement', 'explanation' => 'Devant un nom pluriel : « viele ».'],
                            ['original' => 'auf dem Land es ist ruhig', 'corrected' => 'auf dem Land ist es ruhig', 'category' => 'grammar.word-order', 'explanation' => 'Après un complément en tête de phrase, le verbe passe avant le sujet.'],
                            ['original' => 'beide ist gut', 'corrected' => 'beides ist gut', 'category' => 'grammar.pronoun', 'explanation' => 'Pour deux choses prises ensemble : « beides ist gut ».'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'fluency', 'observation' => 'Nombreuses pauses ; la fin (« Ich weiß nicht… ») montre que l’argument n’est pas préparé.', 'severity' => 'major'],
                            ['dimension' => 'task_fulfilment', 'observation' => 'Aucun avis tranché ni exemple : l’épreuve attend une position défendue.', 'severity' => 'major'],
                        ],
                        'bilan' => 'Prise de parole trop courte et hésitante pour le B1.',
                    ],
                    [
                        'tier' => 0,
                        'texte' => "Für mich, Sport ist sehr wichtig. Ich gehe jeden Morgen in Mvog-Mbi joggen. Aber viele Leute haben keine Zeit für Sport, weil sie arbeiten viel. Die Regierung muss mehr Stadien bauen.",
                        'corrections' => [
                            ['original' => 'Für mich, Sport ist sehr wichtig', 'corrected' => 'Für mich ist Sport sehr wichtig', 'category' => 'grammar.word-order', 'explanation' => '« Für mich » occupe la 1re place : le verbe vient ensuite, puis le sujet.'],
                            ['original' => 'weil sie arbeiten viel', 'corrected' => 'weil sie viel arbeiten', 'category' => 'grammar.word-order', 'explanation' => 'Après « weil », le verbe conjugué va à la fin.'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'grammatical_control', 'observation' => 'Verbe en 2e position et verbe final après « weil » oubliés à chaque phrase complexe.', 'severity' => 'major'],
                            ['dimension' => 'coherence', 'observation' => 'Passe de son expérience à la politique sportive sans transition.', 'severity' => 'minor'],
                        ],
                        'bilan' => 'Idées présentes, mais la syntaxe allemande n’est pas encore en place à l’oral.',
                    ],
                    [
                        'tier' => 1,
                        'texte' => "Ich möchte über meine Erfahrung mit dem Online-Unterricht sprechen. Während der Pandemie hatten wir nur Kurse im Internet. Das war praktisch, aber die Verbindung war oft schlecht. Deshalb ich bevorzuge den Unterricht in der Klasse.",
                        'corrections' => [
                            ['original' => 'Deshalb ich bevorzuge', 'corrected' => 'Deshalb bevorzuge ich', 'category' => 'grammar.word-order', 'explanation' => 'Après « deshalb » en tête de phrase, le verbe passe avant le sujet.'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'coherence', 'observation' => 'Exposé structuré : expérience, avantage, inconvénient, conclusion.', 'severity' => 'strength'],
                            ['dimension' => 'grammatical_control', 'observation' => 'Inversion oubliée après « deshalb ».', 'severity' => 'minor'],
                        ],
                        'bilan' => 'Bon exposé ; une faute de syntaxe récurrente à corriger.',
                    ],
                    [
                        'tier' => 1,
                        'texte' => "Also, ich schlage vor, dass wir das Geschenk zusammen kaufen. Jeder gibt zehn Euro, dann können wir eine schöne Uhr kaufen. Was hältst du davon? Oder hast du eine bessere Idee?",
                        'corrections' => [],
                        'diagnostic' => [
                            ['dimension' => 'task_fulfilment', 'observation' => 'Fait une proposition concrète et demande l’avis de l’autre : interaction réussie.', 'severity' => 'strength'],
                            ['dimension' => 'lexical_range', 'observation' => 'Phrases correctes mais simples ; peu d’arguments pour convaincre.', 'severity' => 'minor'],
                        ],
                        'bilan' => 'Interaction efficace ; développer les arguments.',
                    ],
                    [
                        'tier' => 2,
                        'texte' => "Meiner Meinung nach hat das Homeoffice mehr Vorteile als Nachteile, vorausgesetzt, man kann sich gut organisieren. Ich zum Beispiel spare jeden Tag fast zwei Stunden, die ich sonst im Stau verbringen würde. Andererseits fehlt mir manchmal der direkte Kontakt zu den Kollegen.",
                        'corrections' => [],
                        'diagnostic' => [
                            ['dimension' => 'syntactic_complexity', 'observation' => 'Relative, Konjunktiv II (« verbringen würde ») et « vorausgesetzt » : structures de niveau B2.', 'severity' => 'strength'],
                            ['dimension' => 'coherence', 'observation' => 'Avis nuancé, illustré par son propre cas.', 'severity' => 'strength'],
                        ],
                        'bilan' => 'Excellente prise de parole argumentée.',
                    ],
                ],
            ],
        ],
        'french' => [
            'writing' => [
                'A' => [
                    [
                        'tier' => 0,
                        'texte' => "Salut Paul, samedi j'organise une fête pour mon anniversaire. Tu peux venir avec ta sœur. On va mangé du poulet DG et danser. C'est chez moi a Biyem-Assi. {prenom}",
                        'corrections' => [
                            ['original' => 'On va mangé', 'corrected' => 'On va manger', 'category' => 'grammar.verb-form', 'explanation' => 'Après « aller », le verbe est à l’infinitif : « manger ».'],
                            ['original' => 'a Biyem-Assi', 'corrected' => 'à Biyem-Assi', 'category' => 'spelling.accent', 'explanation' => 'La préposition « à » prend un accent ; « a » est le verbe avoir.'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'grammatical_control', 'observation' => 'L’infinitif après « aller » n’est pas acquis (« on va mangé »).', 'severity' => 'major'],
                            ['dimension' => 'task_fulfilment', 'observation' => 'Le jour et le lieu sont donnés, mais pas l’heure de la fête.', 'severity' => 'minor'],
                        ],
                        'bilan' => 'Invitation sympathique ; attention aux infinitifs et aux accents.',
                        'ecart_vocabulaire' => 0.0, 'ecart_grammaire' => -1.0,
                    ],
                    [
                        'tier' => 1,
                        'texte' => "Bonjour Madame Ngo, je vous écris pour réserver une chambre pour deux personne du 12 au 15 décembre. Nous arriverons vers 18 heures. Est-ce que le petit-déjeuner est compris dans le prix ? Je vous remercie d'avance pour votre réponse. Cordialement, {prenom}",
                        'corrections' => [
                            ['original' => 'deux personne', 'corrected' => 'deux personnes', 'category' => 'grammar.agreement', 'explanation' => 'Après « deux », le nom est au pluriel : « personnes ».'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'register', 'observation' => 'Formules de politesse adaptées à une réservation (« Je vous remercie d’avance », « Cordialement »).', 'severity' => 'strength'],
                            ['dimension' => 'grammatical_control', 'observation' => 'Un pluriel oublié (« deux personne »).', 'severity' => 'minor'],
                        ],
                        'bilan' => 'Message clair et complet.',
                        'ecart_vocabulaire' => 0.0, 'ecart_grammaire' => -0.5,
                    ],
                    [
                        'tier' => 2,
                        'texte' => "Chers voisins, je m'appelle {prenom} et je viens d'emménager au troisième étage. Samedi prochain, j'organise un petit repas dans la cour pour faire connaissance. Chacun peut apporter un plat de sa région. Si vous avez des questions, n'hésitez pas à frapper à ma porte !",
                        'corrections' => [],
                        'diagnostic' => [
                            ['dimension' => 'task_fulfilment', 'observation' => 'Se présente, invite et donne les modalités : tous les points sont traités.', 'severity' => 'strength'],
                            ['dimension' => 'coherence', 'observation' => 'Message bien enchaîné, ton chaleureux et adapté.', 'severity' => 'strength'],
                        ],
                        'bilan' => 'Très bon message.',
                        'ecart_vocabulaire' => 0.5, 'ecart_grammaire' => 0.5,
                    ],
                ],
                'B' => [
                    [
                        'tier' => 0,
                        'texte' => "Les réseaux sociaux ont changer notre façon de communiquer. Beaucoup de jeunes passe plusieurs heures par jour sur leur téléphone. Je pense que c'est dangereux. Il faut que les parents contrôle leurs enfants. Mais les réseaux sociaux permet aussi de trouver du travail.",
                        'corrections' => [
                            ['original' => 'ont changer', 'corrected' => 'ont changé', 'category' => 'grammar.verb-form', 'explanation' => 'Après l’auxiliaire « avoir », on met le participe passé : « changé ».'],
                            ['original' => 'Beaucoup de jeunes passe', 'corrected' => 'Beaucoup de jeunes passent', 'category' => 'grammar.agreement', 'explanation' => 'Le sujet est pluriel : « passent ».'],
                            ['original' => 'que les parents contrôle', 'corrected' => 'que les parents contrôlent', 'category' => 'grammar.verb-form', 'explanation' => 'Subjonctif après « il faut que », au pluriel : « contrôlent ».'],
                            ['original' => 'les réseaux sociaux permet', 'corrected' => 'les réseaux sociaux permettent', 'category' => 'grammar.agreement', 'explanation' => 'Le sujet est pluriel : « permettent ».'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'grammatical_control', 'observation' => 'Accords sujet-verbe oubliés au pluriel (« les jeunes passe », « les réseaux permet »).', 'severity' => 'major'],
                            ['dimension' => 'coherence', 'observation' => 'Un avis puis un contre-argument sans transition ni exemple : l’argumentation ne se construit pas.', 'severity' => 'major'],
                        ],
                        'bilan' => 'Sujet compris ; accords et structure de l’argumentation à reprendre.',
                        'ecart_vocabulaire' => -0.5, 'ecart_grammaire' => -1.5,
                    ],
                    [
                        'tier' => 1,
                        'texte' => "Madame la Directrice, je me permets de vous écrire au sujet de la cantine de l'école. Depuis la rentrée, les repas sont souvent froids et les portions ont diminuées, alors que les frais ont augmenté. Je souhaiterais que vous organisiez une réunion avec les parents afin que nous trouvions une solution ensemble. En attendant votre réponse, je vous prie d'agréer, Madame la Directrice, mes salutations respectueuses.",
                        'corrections' => [
                            ['original' => 'ont diminuées', 'corrected' => 'ont diminué', 'category' => 'grammar.agreement', 'explanation' => 'Avec « avoir », le participe passé ne s’accorde pas avec le sujet : « ont diminué ».'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'register', 'observation' => 'Lettre formelle bien tenue : formule d’appel, demande polie, formule finale.', 'severity' => 'strength'],
                            ['dimension' => 'syntactic_complexity', 'observation' => 'Subjonctifs justes après « je souhaiterais que » et « afin que ».', 'severity' => 'strength'],
                            ['dimension' => 'grammatical_control', 'observation' => 'Accord du participe passé avec « avoir » fautif (« ont diminuées »).', 'severity' => 'minor'],
                        ],
                        'bilan' => 'Bonne lettre de réclamation, précise et polie.',
                        'ecart_vocabulaire' => 0.0, 'ecart_grammaire' => 0.0,
                    ],
                    [
                        'tier' => 2,
                        'texte' => "Faut-il interdire la publicité destinée aux enfants ? À première vue, la mesure semble excessive : la publicité finance une partie des programmes jeunesse. Pourtant, les enfants ne disposent pas encore du recul nécessaire pour distinguer l'information de la persuasion. Plutôt qu'une interdiction totale, il serait donc préférable d'encadrer strictement les messages, notamment ceux qui vantent des produits trop sucrés.",
                        'corrections' => [],
                        'diagnostic' => [
                            ['dimension' => 'coherence', 'observation' => 'Question, concession, objection puis proposition : argumentation complète.', 'severity' => 'strength'],
                            ['dimension' => 'lexical_precision', 'observation' => 'Vocabulaire précis (« recul », « persuasion », « encadrer »).', 'severity' => 'strength'],
                        ],
                        'bilan' => 'Excellente argumentation.',
                        'ecart_vocabulaire' => 0.5, 'ecart_grammaire' => 0.5,
                    ],
                ],
            ],
            'speaking' => [
                'A' => [
                    [
                        'tier' => 0,
                        'texte' => "Euh… moi c'est {prenom}. Je suis… euh… élève en terminale. J'ai deux frères et une petite sœur. Mon père il est chauffeur.",
                        'corrections' => [
                            ['original' => 'Mon père il est chauffeur', 'corrected' => 'Mon père est chauffeur', 'category' => 'grammar.pronoun', 'explanation' => 'On ne reprend pas le sujet par « il ».'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'fluency', 'observation' => 'Hésitations à chaque phrase ; réponses très courtes.', 'severity' => 'major'],
                            ['dimension' => 'task_fulfilment', 'observation' => 'Donne les informations de base sans rien développer (goûts, projets).', 'severity' => 'minor'],
                        ],
                        'bilan' => 'Présentation minimale ; il faut parler plus longtemps et plus librement.',
                    ],
                    [
                        'tier' => 1,
                        'texte' => "Le week-end, je vais souvent à Kribi avec ma famille. On se baigne, on mange du poisson braisé sur la plage. J'aime beaucoup parce que c'est calme, pas comme à Yaoundé.",
                        'corrections' => [],
                        'diagnostic' => [
                            ['dimension' => 'task_fulfilment', 'observation' => 'Décrit une habitude avec des détails concrets.', 'severity' => 'strength'],
                            ['dimension' => 'syntactic_complexity', 'observation' => 'Phrases simples juxtaposées, peu de liens logiques.', 'severity' => 'minor'],
                        ],
                        'bilan' => 'Réponse claire et vivante.',
                    ],
                    [
                        'tier' => 2,
                        'texte' => "Plus tard, j'aimerais travailler dans la santé, comme mon oncle qui est infirmier à l'hôpital de Bafoussam. Ce qui me plaît, c'est d'aider les gens. Mais avant, je dois réussir mon bac et trouver une bonne école.",
                        'corrections' => [],
                        'diagnostic' => [
                            ['dimension' => 'fluency', 'observation' => 'Parle de façon fluide et enchaîne les idées.', 'severity' => 'strength'],
                            ['dimension' => 'syntactic_complexity', 'observation' => 'Conditionnel, relative et mise en relief bien employés.', 'severity' => 'strength'],
                        ],
                        'bilan' => 'Très bonne prise de parole.',
                    ],
                ],
                'B' => [
                    [
                        'tier' => 0,
                        'texte' => "Le travail des enfants, euh, c'est pas normal. Les enfants doivent aller à l'école. Mais il y a des familles qui sont pauvres, donc… euh… c'est compliqué.",
                        'corrections' => [
                            ['original' => "c'est pas normal", 'corrected' => "ce n'est pas acceptable", 'category' => 'vocabulary.register', 'explanation' => 'À l’examen, on évite « c’est pas » et on choisit un mot plus précis.'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'coherence', 'observation' => 'L’avis et la nuance sont posés, mais aucun n’est développé ni illustré.', 'severity' => 'major'],
                            ['dimension' => 'fluency', 'observation' => 'Pauses (« euh ») au moment d’argumenter.', 'severity' => 'minor'],
                        ],
                        'bilan' => 'Avis clair mais trop court : il faut développer et illustrer.',
                    ],
                    [
                        'tier' => 1,
                        'texte' => "Je suis plutôt d'accord avec cette idée. Le sport à l'école apprend aux élèves à travailler en équipe. Par exemple, dans mon lycée à Ebolowa, l'équipe de handball a gagné le championnat régional. Par contre, il faudrait que les écoles aient plus de terrains.",
                        'corrections' => [],
                        'diagnostic' => [
                            ['dimension' => 'task_fulfilment', 'observation' => 'Prend position et donne un exemple personnel.', 'severity' => 'strength'],
                            ['dimension' => 'lexical_range', 'observation' => 'Vocabulaire juste mais courant ; « par exemple » et « par contre » sont les seuls liens.', 'severity' => 'minor'],
                        ],
                        'bilan' => 'Bonne réponse argumentée.',
                    ],
                    [
                        'tier' => 2,
                        'texte' => "C'est une question délicate. D'un côté, l'exode rural vide les villages de leurs forces vives ; de l'autre, on ne peut pas reprocher aux jeunes de chercher un avenir meilleur. À mon sens, la solution passe par le développement d'activités en zone rurale, par exemple la transformation des produits agricoles sur place.",
                        'corrections' => [],
                        'diagnostic' => [
                            ['dimension' => 'coherence', 'observation' => 'Pose les deux côtés du débat avant de proposer une solution.', 'severity' => 'strength'],
                            ['dimension' => 'lexical_precision', 'observation' => 'Vocabulaire précis (« exode rural », « forces vives »).', 'severity' => 'strength'],
                        ],
                        'bilan' => 'Excellente prise de parole.',
                    ],
                ],
            ],
        ],
        'english' => [
            'writing' => [
                'B' => [
                    [
                        'tier' => 0,
                        'texte' => "Some people thinks that university should be free for everyone. I agree with this idea because many students in my country cannot pay the fees. If university is free, more young people will study and the country will develop. But the government don't have enough money.",
                        'corrections' => [
                            ['original' => 'Some people thinks', 'corrected' => 'Some people think', 'category' => 'grammar.agreement', 'explanation' => '« People » est pluriel : « think ».'],
                            ['original' => "the government don't have", 'corrected' => "the government doesn't have", 'category' => 'grammar.agreement', 'explanation' => '« Government » est singulier : « doesn’t ».'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'grammatical_control', 'observation' => 'Accord sujet-verbe instable (« people thinks », « government don’t »).', 'severity' => 'major'],
                            ['dimension' => 'task_fulfilment', 'observation' => 'Le contre-argument (le manque d’argent) arrive à la fin sans être discuté : position peu développée.', 'severity' => 'minor'],
                        ],
                        'bilan' => 'Position claire mais peu développée ; revoir les accords. Niveau band 5 à 5,5.',
                        'ecart_vocabulaire' => -0.5, 'ecart_grammaire' => -1.0,
                    ],
                    [
                        'tier' => 1,
                        'texte' => "The chart shows the number of tourists who visited three countries between 2010 and 2020. Overall, the number of visitors increased in all three countries. Kenya had the highest growth, from 1 million to 2.5 millions. In contrast, Ghana's figures stayed relatively stable until 2016 and then rose slightly.",
                        'corrections' => [
                            ['original' => '2.5 millions', 'corrected' => '2.5 million', 'category' => 'grammar.agreement', 'explanation' => 'Après un nombre, « million » reste invariable : « 2.5 million ».'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'coherence', 'observation' => 'Vue d’ensemble (« Overall ») puis comparaison : la structure attendue de la tâche 1.', 'severity' => 'strength'],
                            ['dimension' => 'lexical_range', 'observation' => 'Vocabulaire de l’évolution correct mais répétitif (« increased », « rose »).', 'severity' => 'minor'],
                        ],
                        'bilan' => 'Bonne description, band 6 à 6,5.',
                        'ecart_vocabulaire' => -0.5, 'ecart_grammaire' => 0.0,
                    ],
                    [
                        'tier' => 1,
                        'texte' => "Many people believe that children should learn a foreign language at primary school. I strongly agree, because young children learn languages more easy than adults. In Cameroon, for example, children who grow up speaking French and English have more opportunities later. However, schools need trained teachers, which is not always the case in rural areas.",
                        'corrections' => [
                            ['original' => 'more easy than adults', 'corrected' => 'more easily than adults', 'category' => 'vocabulary.word-formation', 'explanation' => 'Pour qualifier un verbe, on emploie l’adverbe : « more easily ».'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'task_fulfilment', 'observation' => 'Position claire, exemple local pertinent et limite reconnue.', 'severity' => 'strength'],
                            ['dimension' => 'grammatical_control', 'observation' => 'Adjectif employé à la place de l’adverbe (« more easy »).', 'severity' => 'minor'],
                        ],
                        'bilan' => 'Réponse solide, band 6,5.',
                        'ecart_vocabulaire' => 0.0, 'ecart_grammaire' => 0.0,
                    ],
                    [
                        'tier' => 2,
                        'texte' => "It is often argued that working from home benefits employees more than employers. In my view, this claim overlooks the productivity gains that companies themselves report. Lower office costs and reduced staff turnover can offset the challenges of remote supervision, provided that clear objectives are set from the outset.",
                        'corrections' => [],
                        'diagnostic' => [
                            ['dimension' => 'syntactic_complexity', 'observation' => 'Passif, relatives et « provided that » maîtrisés.', 'severity' => 'strength'],
                            ['dimension' => 'lexical_precision', 'observation' => 'Vocabulaire académique précis (« overlooks », « staff turnover », « offset »).', 'severity' => 'strength'],
                        ],
                        'bilan' => 'Excellente copie, band 7,5 à 8.',
                        'ecart_vocabulaire' => 0.5, 'ecart_grammaire' => 0.5,
                    ],
                ],
            ],
            'speaking' => [
                'B' => [
                    [
                        'tier' => 0,
                        'texte' => "My favourite place is… uh… the market in Bamenda. I go there every Saturdays with my mother. There is many things to buy, like vegetables and clothes. It is very noisy but I like.",
                        'corrections' => [
                            ['original' => 'every Saturdays', 'corrected' => 'every Saturday', 'category' => 'grammar.agreement', 'explanation' => 'Après « every », le nom reste au singulier.'],
                            ['original' => 'There is many things', 'corrected' => 'There are many things', 'category' => 'grammar.agreement', 'explanation' => 'Avec un nom pluriel : « there are ».'],
                            ['original' => 'but I like.', 'corrected' => 'but I like it.', 'category' => 'grammar.pronoun', 'explanation' => '« like » demande un complément : « I like it ».'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'grammatical_control', 'observation' => 'Accords et pronoms compléments oubliés (« there is many », « I like »).', 'severity' => 'major'],
                            ['dimension' => 'fluency', 'observation' => 'Hésitation au début et phrases courtes.', 'severity' => 'minor'],
                        ],
                        'bilan' => 'Réponse compréhensible, band 5.',
                    ],
                    [
                        'tier' => 1,
                        'texte' => "I think young people today are more independent than before. For example, my cousin started her own business when she was only twenty-two. She sells clothes online. But on the other hand, it's harder for them to find a stable job.",
                        'corrections' => [],
                        'diagnostic' => [
                            ['dimension' => 'coherence', 'observation' => 'Avis illustré par un exemple concret, puis nuancé.', 'severity' => 'strength'],
                            ['dimension' => 'lexical_range', 'observation' => 'Vocabulaire simple ; peu d’expressions idiomatiques.', 'severity' => 'minor'],
                        ],
                        'bilan' => 'Bonne réponse, band 6.',
                    ],
                    [
                        'tier' => 1,
                        'texte' => "When I was a child, I have lived with my grandparents in Kumbo. Every evening my grandfather told us stories about the history of our village. I didn't realise it at the time, but those stories taught me a lot about respect.",
                        'corrections' => [
                            ['original' => 'I have lived with my grandparents', 'corrected' => 'I lived with my grandparents', 'category' => 'grammar.tense', 'explanation' => 'Pour une période terminée de l’enfance, on emploie le prétérit : « I lived ».'],
                        ],
                        'diagnostic' => [
                            ['dimension' => 'fluency', 'observation' => 'Récit fluide et bien enchaîné.', 'severity' => 'strength'],
                            ['dimension' => 'grammatical_control', 'observation' => 'Present perfect employé pour une période passée terminée.', 'severity' => 'minor'],
                        ],
                        'bilan' => 'Bon récit personnel, band 6,5.',
                    ],
                    [
                        'tier' => 2,
                        'texte' => "Honestly, I think social media is a double-edged sword. On the one hand, it gives small businesses in places like Buea access to customers they could never have reached before. On the other, the constant pressure to compare ourselves with others can take a real toll on young people's mental health.",
                        'corrections' => [],
                        'diagnostic' => [
                            ['dimension' => 'lexical_range', 'observation' => 'Expressions idiomatiques bien employées (« double-edged sword », « take a toll »).', 'severity' => 'strength'],
                            ['dimension' => 'coherence', 'observation' => 'Argumentation équilibrée et illustrée.', 'severity' => 'strength'],
                        ],
                        'bilan' => 'Excellente réponse, band 7,5.',
                    ],
                ],
            ],
        ],
    ];
}
