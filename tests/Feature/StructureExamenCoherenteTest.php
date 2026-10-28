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
                $out[] = [
                    'examen' => $slug,
                    'niveau' => $niveau,
                    'section' => $section['slug'],
                    'nom' => $partie['name'] ?? '?',
                    'type' => $partie['type'] ?? null,
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

test('une partie officielle annonce toujours son type', function () {
    $manquants = [];

    foreach (examensStructures() as $slug) {
        foreach (partiesDeLaConfig($slug) as $p) {
            if ($p['source'] === 'officiel' && ! $p['type']) {
                $manquants[] = "{$p['examen']} {$p['niveau']} {$p['nom']}";
            }
        }
    }

    expect($manquants)->toBe([]);
});

test('le type d une partie existe dans les formats de sa section', function () {
    // Un type absent de `exercise_types` ne recoit aucun ExerciseType au seeding :
    // la partie s'affiche puis ne s'ouvre pas, sans message.
    $orphelins = [];

    foreach (examensStructures() as $slug) {
        foreach (partiesDeLaConfig($slug) as $p) {
            if (! $p['type'] || $p['formats'] === []) {
                continue;
            }
            if (! in_array($p['type'], $p['formats'], true)) {
                $orphelins[] = "{$p['examen']} {$p['niveau']} {$p['nom']} : type « {$p['type']} » absent de sa section";
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
            if ($p['source'] !== 'officiel' || in_array($p['type'], $sansItems, true)) {
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
