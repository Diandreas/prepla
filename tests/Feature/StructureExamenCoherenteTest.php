<?php

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\ExerciseType;

/**
 * Une structure d'examen ne doit jamais promettre plus qu'elle ne tient.
 *
 * L'application affiche a l'apprenant « tache reconstituee » quand une partie
 * n'est pas verifiee, et annonce son nombre d'items quand elle l'est. Ce contrat
 * ne vaut que si `source: officiel` signifie vraiment : verifie dans le document
 * de l'organisme, avec ses chiffres.
 *
 * Trois choses ont deja casse en vrai, d'ou ces tests :
 *  - une partie `officiel` sans `items`, qui annoncait une precision qu'elle
 *    n'avait pas ;
 *  - un `type` pointant sur un format absent de sa section, qui fermait la partie
 *    en silence ;
 *  - un type faux (Goethe B1 Lesen Teil 4 en appariement au lieu de Ja/Nein),
 *    qui entrainait au mauvais format sans que rien ne le signale.
 */
function partiesDeLaConfig(string $slug): array
{
    $config = require base_path("config/exams/{$slug}.php");
    $out = [];

    foreach ($config['levels'] ?? [] as $niveau => $L) {
        foreach ($L['sections'] ?? [] as $section) {
            foreach ($section['parts'] ?? [] as $partie) {
                // Une partie annonce UN format (`type`), ou PLUSIEURS quand
                // l'epreuve laisse le choix au candidat (`types`) : la partie 2 du
                // Writing de Cambridge offre selon le palier un article, une
                // lettre, une proposition, un rapport, une critique ou une
                // histoire. Chaque examen declare les siens, a chacun de ses
                // niveaux ; on les ramene ici a une liste pour les controler de la
                // meme facon.
                $declares = is_array($partie['types'] ?? null)
                    ? $partie['types']
                    : [$partie['type'] ?? null];

                $out[] = [
                    'examen' => $slug,
                    'niveau' => $niveau,
                    'section' => $section['slug'],
                    'nom' => $partie['name'] ?? '?',
                    'types' => array_values(array_filter(
                        $declares,
                        fn ($format) => is_string($format) && $format !== ''
                    )),
                    'items' => $partie['items'] ?? null,
                    'source' => $partie['source'] ?? null,
                    'formats' => $section['exercise_types'] ?? [],
                ];
            }
        }
    }

    return $out;
}

/** Les examens dont la structure est declaree partie par partie. */
function examensStructures(): array
{
    return collect(glob(base_path('config/exams/*.php')))
        ->map(fn ($f) => basename($f, '.php'))
        ->filter(fn ($s) => ! empty(partiesDeLaConfig($s)))
        ->values()
        ->all();
}

test('une partie officielle annonce toujours son format', function () {
    $manquants = [];

    foreach (examensStructures() as $slug) {
        foreach (partiesDeLaConfig($slug) as $p) {
            if ($p['source'] === 'officiel' && $p['types'] === []) {
                $manquants[] = "{$p['examen']} {$p['niveau']} {$p['nom']}";
            }
        }
    }

    expect($manquants)->toBe([]);
});

test('le type d une partie existe dans les formats de sa section', function () {
    // Un type absent de `exercise_types` ne recoit aucun ExerciseType au seeding :
    // la partie s'affiche puis ne s'ouvre pas, sans message. Pour une partie au
    // choix, CHACUN des formats proposes doit donc etre servable : il suffit d'en
    // oublier un pour que ce choix-la tombe dans le vide une fois sur quatre.
    $orphelins = [];

    foreach (examensStructures() as $slug) {
        foreach (partiesDeLaConfig($slug) as $p) {
            if ($p['types'] === [] || $p['formats'] === []) {
                continue;
            }
            foreach ($p['types'] as $format) {
                if (! in_array($format, $p['formats'], true)) {
                    $orphelins[] = "{$p['examen']} {$p['niveau']} {$p['nom']} : type « {$format} » absent de sa section";
                }
            }
        }
    }

    expect($orphelins)->toBe([]);
});

test('une partie a questions fermees et marquee officielle annonce ses items', function () {
    // Les productions ecrites et orales n'ont pas d'items : on ne les compte pas.
    $sansItems = ['essay', 'letter-writing', 'letter-email-writing', 'short-writing',
        'integrated-writing', 'report-writing', 'guided-writing', 'graph-description',
        'speaking-response', 'speaking-long-turn', 'speaking-discussion', 'negotiation',
        'role-play', 'oral-debate', 'speaking-recorder', 'synthesis', 'academic-discussion',
        'picture-description', 'integrated-speaking'];

    $muettes = [];

    foreach (examensStructures() as $slug) {
        foreach (partiesDeLaConfig($slug) as $p) {
            // Une partie au choix n'a d'items a annoncer que si l'un au moins de
            // ses formats en compte : quatre redactions au choix n'en ont aucun.
            $compteDesItems = array_diff($p['types'], $sansItems) !== [];

            if ($p['source'] !== 'officiel' || ! $compteDesItems) {
                continue;
            }
            if ($p['items'] === null) {
                $muettes[] = "{$p['examen']} {$p['niveau']} {$p['nom']}";
            }
        }
    }

    expect($muettes)->toBe([]);
});

test('un nombre d items reste plausible', function () {
    $absurdes = [];

    foreach (examensStructures() as $slug) {
        foreach (partiesDeLaConfig($slug) as $p) {
            if ($p['items'] === null) {
                continue;
            }
            if (! is_int($p['items']) || $p['items'] < 1 || $p['items'] > 60) {
                $absurdes[] = "{$p['examen']} {$p['niveau']} {$p['nom']} : {$p['items']}";
            }
        }
    }

    expect($absurdes)->toBe([]);
});
