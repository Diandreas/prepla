<?php

/**
 * La configuration doit suivre le document de l'organisme, pas nos souvenirs.
 *
 * Les relevés de `docs/releves-officiels/` portent, palier par palier et partie
 * par partie, ce qui a été lu dans les Modellsätze, Übungstests et
 * Durchführungsbestimmungen — avec la citation à l'appui. Ce test confronte la
 * configuration à ces relevés.
 *
 * Il existe parce qu'une vérification faite une fois ne protège de rien : une
 * retouche de config un mois plus tard peut défaire ce qui avait été établi, et
 * personne ne s'en apercevrait. C'est la repasse, automatisée.
 */
function relevesOfficiels(): array
{
    $out = [];
    foreach (glob(base_path('docs/releves-officiels/*.json')) as $fichier) {
        $releve = json_decode(file_get_contents($fichier), true);
        if (is_array($releve)) {
            $out[basename($fichier)] = $releve;
        }
    }

    return $out;
}

/** Du nom de module du relevé vers le slug de section de la configuration. */
function slugDuModule(string $nom): string
{
    $correspondances = [
        'hörverstehen und schreiben' => 'hoeren-schreiben',
        'hören und schreiben' => 'hoeren-schreiben',
        'sprachbausteine' => 'sprachbausteine',
        'leseverstehen' => 'lesen',
        'hörverstehen' => 'hoeren',
        'schriftlicher ausdruck' => 'schreiben',
        'mündlicher ausdruck' => 'sprechen',
        'lesen' => 'lesen',
        'hören' => 'hoeren',
        // Les relevés écrits sans trémas restent des relevés : « Hoeren »
        // désigne le même module que « Hören ».
        'hoeren' => 'hoeren',
        'hörverstehen' => 'hoeren',
        'schreiben' => 'schreiben',
        'sprechen' => 'sprechen',
    ];

    $minuscule = mb_strtolower($nom);
    // Du plus long au plus court : « Hörverstehen und Schreiben » ne doit pas
    // être pris pour « Hörverstehen » tout court.
    foreach ($correspondances as $cle => $slug) {
        if (str_contains($minuscule, $cle)) {
            return $slug;
        }
    }

    return '?';
}

/** Les parties de la configuration, pour un examen et un palier. */
function partiesConfigurees(string $examen, string $niveau): array
{
    $config = require base_path("config/exams/{$examen}.php");
    $sections = $config['levels'][$niveau]['sections'] ?? $config['sections'] ?? [];

    $out = [];
    foreach ($sections as $section) {
        foreach ($section['parts'] ?? [] as $partie) {
            $out[] = $partie + ['__section' => $section['slug']];
        }
    }

    return $out;
}

test('chaque partie de la configuration porte le nombre d items du document', function () {
    // Un bloc officiel unique que nous présentons en deux modules reste le même
    // bloc : « Lesen und Schreiben » du telc A1 compte ses cinq parties chez
    // nous aussi, réparties sur deux sections.
    $regroupements = [
        'telc/A1/lesen' => ['lesen', 'schreiben'],
        'telc/A1/schreiben' => ['lesen', 'schreiben'],
        'telc/A2/lesen' => ['lesen', 'schreiben'],
        'telc/A2/schreiben' => ['lesen', 'schreiben'],
    ];

    // Remplir la feuille de réponses n'est pas un exercice de langue.
    $horsEntrainement = ['formalitäten'];

    $fichierVersExamen = [
        'testdaf' => 'testdaf', 'telc' => 'telc',
        'goethe-zertifikat' => 'goethe', 'ösd' => 'osd',
    ];

    $ecarts = [];

    foreach (relevesOfficiels() as $nomFichier => $releve) {
        $examen = null;
        foreach ($fichierVersExamen as $motif => $slug) {
            if (str_contains($nomFichier, $motif)) {
                $examen = $slug;
                break;
            }
        }
        if (! $examen) {
            continue;
        }

        // Un relevé décrit soit plusieurs paliers, soit un seul.
        $paliers = $releve['niveaux'] ?? [$releve];

        foreach ($paliers as $palier) {
            $niveau = $palier['niveau'] ?? null;
            if (! $niveau) {
                continue;
            }
            $configurees = partiesConfigurees($examen, $niveau);
            if ($configurees === []) {
                $ecarts[] = "{$examen} {$niveau} : palier absent de la configuration";

                continue;
            }

            foreach ($palier['modules'] ?? [] as $module) {
                if (in_array(mb_strtolower(trim($module['nom'] ?? '')), $horsEntrainement, true)) {
                    continue;
                }
                $slug = slugDuModule($module['nom'] ?? '');
                $sections = $regroupements["{$examen}/{$niveau}/{$slug}"] ?? [$slug];

                $nôtres = array_values(array_filter(
                    $configurees,
                    fn ($p) => in_array($p['__section'], $sections, true),
                ));

                $officielles = array_values(array_filter(
                    $module['parties'] ?? [],
                    fn ($p) => ! empty($p['numero']),
                ));

                if ($nôtres === []) {
                    $ecarts[] = "{$examen} {$niveau} {$slug} : module absent de la configuration";

                    continue;
                }

                if (count($nôtres) !== count($officielles)) {
                    $ecarts[] = "{$examen} {$niveau} {$slug} : ".count($nôtres)
                        .' partie(s) chez nous, '.count($officielles).' dans le document';

                    continue;
                }

                foreach ($officielles as $rang => $officielle) {
                    $notre = $nôtres[$rang];
                    // Une production libre n'a pas d'items : elle se note par
                    // critères. Un « 1 » posé là est une convention de rédaction
                    // du relevé, pas un chiffre de l'organisme.
                    $productions = ['essay', 'letter-writing', 'letter-email-writing', 'short-writing',
                        'integrated-writing', 'report-writing', 'guided-writing', 'graph-description',
                        'speaking-response', 'speaking-long-turn', 'speaking-discussion', 'negotiation',
                        'role-play', 'oral-debate', 'picture-description', 'speaking-elicitation',
                        'synthesis', 'academic-discussion'];

                    $attendus = (int) ($officielle['items'] ?? 0);
                    if ($attendus === 0 || in_array($notre['type'] ?? '', $productions, true)) {
                        continue;
                    }
                    if ((int) ($notre['items'] ?? 0) !== $attendus) {
                        $ecarts[] = "{$examen} {$niveau} {$slug} partie ".($rang + 1)
                            .' ('.($notre['name'] ?? '?').') : '.($notre['items'] ?? 'aucun')
                            ." item(s) chez nous, {$attendus} dans le document";
                    }
                }
            }
        }
    }

    expect($ecarts)->toBe([]);
});

test('chaque releve conserve la citation qui le fonde', function () {
    // Un relevé sans citation n'est plus une preuve, c'est une affirmation de
    // plus — et c'est sur lui qu'on supprime du contenu.
    $muets = [];

    foreach (relevesOfficiels() as $nom => $releve) {
        if (empty($releve['sources'])) {
            $muets[] = "{$nom} : aucune source citée";
        }

        foreach ($releve['niveaux'] ?? [$releve] as $palier) {
            foreach ($palier['modules'] ?? [] as $module) {
                foreach ($module['parties'] ?? [] as $partie) {
                    if (($partie['items'] ?? 0) > 0 && trim((string) ($partie['citation'] ?? '')) === '') {
                        $muets[] = "{$nom} : « ".($partie['nom_officiel'] ?? '?')." » annonce des items sans citation";
                    }
                }
            }
        }
    }

    expect($muets)->toBe([]);
});
