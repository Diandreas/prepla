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

/**
 * Les totaux de questions publies par Cambridge, recomposes par la somme des
 * items de chaque partie.
 *
 * Ces totaux ne vivaient que dans les descriptions en prose, ou rien ne pouvait
 * les verifier : le fichier a longtemps annonce « les sept formats du B2 » pour
 * un module qui en compte huit. La somme des parties, elle, se controle.
 *
 * Au A2 Key, « Reading and Writing » est UN seul papier de 32 questions, que la
 * configuration presente en deux modules : on verifie donc leur somme.
 */
test('les totaux de questions de Cambridge sont ceux de l organisme', function () {
    $officiels = [
        // [niveau, modules a additionner, total publie]
        ['A2', ['reading', 'writing'], 32],
        ['A2', ['listening'], 25],
        ['B1', ['reading'], 32],
        ['B1', ['listening'], 25],
        ['B2', ['reading-use-of-english'], 52],
        ['B2', ['listening'], 30],
        ['C1', ['reading-use-of-english'], 56],
        ['C1', ['listening'], 30],
        ['C2', ['reading-use-of-english'], 53],
        ['C2', ['listening'], 30],
    ];

    $parties = collect(partiesDeLaConfig('cambridge'));
    $ecarts = [];

    foreach ($officiels as [$niveau, $modules, $attendu]) {
        $somme = $parties
            ->where('niveau', $niveau)
            ->whereIn('section', $modules)
            ->sum(fn ($p) => is_int($p['items']) ? $p['items'] : 0);

        if ($somme !== $attendu) {
            $ecarts[] = sprintf(
                '%s %s : %d items declares, %d publies',
                $niveau, implode(' + ', $modules), $somme, $attendu
            );
        }
    }

    expect($ecarts)->toBe([]);
});

/**
 * Une epreuve de production n'a pas d'items a compter. Le seeder ne doit pas lui
 * inventer un zero, qui se lirait « module sans question ».
 */
test('une epreuve orale ne declare aucun item', function () {
    $comptees = [];

    foreach (examensStructures() as $slug) {
        foreach (partiesDeLaConfig($slug) as $p) {
            if (str_contains($p['section'], 'speaking') || str_contains($p['section'], 'orale')) {
                if ($p['items'] !== null) {
                    $comptees[] = "{$p['examen']} {$p['niveau']} {$p['nom']} : items = {$p['items']}";
                }
            }
        }
    }

    expect($comptees)->toBe([]);
});

/**
 * Le total de questions doit arriver JUSQU'A la base.
 *
 * La colonne `question_count` lisait une cle qu'aucune configuration ne
 * renseignait : elle est restee vide pour les dix examens depuis sa creation.
 */
test('le seeder inscrit le nombre de questions de chaque module', function () {
    $this->seed(\Database\Seeders\LanguageSeeder::class);
    $this->seed(\Database\Seeders\ExamSeeder::class);

    $cambridge = Exam::where('slug', 'cambridge')->sole();

    $compte = fn (string $section) => ExamSection::where('exam_id', $cambridge->id)
        ->where('slug', $section)->sole()->question_count;

    // Les modules sont partages entre les niveaux : la section porte le total du
    // dernier plan qui l'a decrite. On verifie qu'un total officiel y est inscrit,
    // et non plus rien du tout.
    expect($compte('reading-use-of-english'))->toBeIn([52, 56, 53])
        ->and($compte('listening'))->toBe(30)
        // Une epreuve orale n'a pas d'items a compter : la colonne reste vide,
        // au lieu d'annoncer zero question.
        ->and($compte('speaking'))->toBeNull();
});
