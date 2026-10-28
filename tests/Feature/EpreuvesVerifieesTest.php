<?php

use App\Models\Exam;
use App\Services\Content\BibliothequeEpreuvesVerifiees;
use App\Services\Content\ExerciseSchemaRegistry;

/**
 * Les epreuves ecrites a la main doivent rester jouables ET fideles.
 *
 * Elles servent de metre : c'est a elles qu'on compare ce que produit l'IA. Un
 * metre faux est pire qu'une absence de metre, d'ou ces tests. Ils tiennent
 * sans base de donnees pour la lecture des fichiers, et s'appuient sur la base
 * seulement pour verifier que les types existent vraiment.
 */
function modulesEcritsALaMain(): array
{
    return app(BibliothequeEpreuvesVerifiees::class)->modules();
}

/** La partie de la config d'examen qui porte ce nom, a ce niveau. */
function partieDeLaConfigExamen(string $examen, string $niveau, string $section, string $nom): ?array
{
    $config = require base_path("config/exams/{$examen}.php");
    $sections = $config['levels'][$niveau]['sections'] ?? $config['sections'] ?? [];

    foreach ($sections as $s) {
        if (($s['slug'] ?? null) !== $section) {
            continue;
        }
        foreach ($s['parts'] ?? [] as $p) {
            if (($p['name'] ?? null) === $nom) {
                return $p;
            }
        }
    }

    return null;
}

test('chaque partie ecrite a la main se valide contre le schema de son composant', function () {
    // Le seul test qui a besoin de la base : il verifie que le type existe
    // VRAIMENT dans le module, tel que le seeder le cree — pas seulement qu'il
    // est ecrit dans la config.
    $this->seed(\Database\Seeders\LanguageSeeder::class);
    $this->seed(\Database\Seeders\ExamSeeder::class);

    $erreurs = [];

    foreach (modulesEcritsALaMain() as $cle => $module) {
        $exam = Exam::where('slug', $module['exam'])->first();
        expect($exam)->not->toBeNull("examen {$module['exam']} absent");

        foreach ($module['parts'] as $partie) {
            $type = app(BibliothequeEpreuvesVerifiees::class)
                ->typePour($exam, $module['section'], $partie['type']);

            if (! $type) {
                $erreurs[] = "{$cle} {$partie['name']} : type « {$partie['type']} » absent du module";

                continue;
            }

            [$valide, $erreur] = ExerciseSchemaRegistry::validateQuestions(
                $type->component_key,
                $partie['questions'],
            );
            if (! $valide) {
                $erreurs[] = "{$cle} {$partie['name']} : {$erreur}";
            }
        }
    }

    expect($erreurs)->toBe([]);
});

test('une partie ecrite a la main porte le type et le nombre d items de la config d examen', function () {
    // C'est tout l'interet de les ecrire a la main : si la config dit cinq items
    // et que le fichier en donne quatre, l'apprenant ne travaille pas son epreuve.
    $ecarts = [];

    foreach (modulesEcritsALaMain() as $cle => $module) {
        foreach ($module['parts'] as $partie) {
            $officielle = partieDeLaConfigExamen(
                $module['exam'], $module['level'], $module['section'], $partie['name'],
            );

            if (! $officielle) {
                $ecarts[] = "{$cle} : « {$partie['name']} » n'existe pas dans la config d'examen";

                continue;
            }
            if (($officielle['type'] ?? null) !== $partie['type']) {
                $ecarts[] = "{$cle} {$partie['name']} : type « {$partie['type']} », la config dit « ".($officielle['type'] ?? '-').' »';
            }
            if (isset($officielle['items']) && count($partie['questions']) !== $officielle['items']) {
                $ecarts[] = "{$cle} {$partie['name']} : ".count($partie['questions'])
                    .' question(s), la config en annonce '.$officielle['items'];
            }
        }
    }

    expect($ecarts)->toBe([]);
});

test('un vrai-faux a deux choix les declare, sinon le lecteur en montre trois', function () {
    // Le composant affiche Vrai / Faux / Non mentionne par defaut. Les Ja/Nein et
    // les Richtig/Falsch n'offrent que deux reponses : sans `answer_options`, on
    // entraine l'apprenant devant un bouton qui n'existe pas le jour de l'examen.
    $muettes = [];

    foreach (modulesEcritsALaMain() as $cle => $module) {
        foreach ($module['parts'] as $partie) {
            if ($partie['type'] !== 'true-false') {
                continue;
            }
            foreach ($partie['questions'] as $q) {
                $choix = $q['answer_options'] ?? null;
                if (! is_array($choix) || count($choix) !== 2) {
                    $muettes[] = "{$cle} {$partie['name']} / {$q['id']}";

                    continue;
                }
                if (! in_array($q['correct_answer'], $choix, true)) {
                    $muettes[] = "{$cle} {$partie['name']} / {$q['id']} : la bonne reponse n'est pas dans les choix";
                }
            }
        }
    }

    expect($muettes)->toBe([]);
});

test('chaque question ecrite a la main explique sa reponse', function () {
    // Un « faux » sans raison n'apprend rien. L'explication cite le mot allemand
    // qui tranche : c'est la difference entre corriger et enseigner.
    $sansExplication = [];

    foreach (modulesEcritsALaMain() as $cle => $module) {
        foreach ($module['parts'] as $partie) {
            // Les productions ecrites et orales sont corrigees par l'IA, point par point.
            if (in_array($partie['type'], ['essay', 'short-writing', 'letter-writing',
                'letter-email-writing', 'integrated-writing'], true)) {
                continue;
            }
            foreach ($partie['questions'] as $q) {
                if (trim((string) ($q['explanation'] ?? '')) === '') {
                    $sansExplication[] = "{$cle} {$partie['name']} / ".($q['id'] ?? '?');
                }
            }
        }
    }

    expect($sansExplication)->toBe([]);
});

test('une partie de comprehension orale porte le texte de son enregistrement', function () {
    // Sans `audio_text`, `prepla:fabriquer-medias` n'a rien a dire : l'exercice
    // arriverait muet, et l'apprenant devrait lire ce qu'il devait entendre.
    $muets = [];

    foreach (modulesEcritsALaMain() as $cle => $module) {
        if (! in_array($module['section'], ['hoeren', 'hoeren-schreiben'], true)) {
            continue;
        }
        foreach ($module['parts'] as $partie) {
            $aUnTexte = trim((string) ($partie['content']['audio_text'] ?? '')) !== ''
                || collect($partie['questions'])->every(
                    fn ($q) => trim((string) ($q['audio_text'] ?? '')) !== '',
                );
            if (! $aUnTexte) {
                $muets[] = "{$cle} {$partie['name']}";
            }
        }
    }

    expect($muets)->toBe([]);
});

test('reensemencer n efface pas le son deja fabrique', function () {
    // Le fichier de contenu ne porte que le TEXTE de l'enregistrement ; la piste
    // est fabriquee ensuite, une fois, et inscrite dans la question. Sans ce
    // report, corriger une consigne rendait muettes toutes les parties d'ecoute
    // — sans rien dire, et en obligeant a repayer la synthese.
    $this->seed(\Database\Seeders\LanguageSeeder::class);
    $this->seed(\Database\Seeders\ExamSeeder::class);

    $bibliotheque = app(\App\Services\Content\BibliothequeEpreuvesVerifiees::class);
    $module = collect($bibliotheque->modules())->firstWhere('section', 'hoeren');
    expect($module)->not->toBeNull('aucun module d ecoute ecrit a la main');

    $bibliotheque->installe($module);

    $exercice = \App\Models\Exercise::where('catalog_key', 'like', 'verifie-v1:%:hoeren:%')->firstOrFail();
    $questions = $exercice->questions;
    $questions[0]['audio_url'] = '/storage/tts/deja-fabrique.mp3';
    $exercice->questions = $questions;
    $exercice->save();

    $bibliotheque->installe($module);

    expect($exercice->fresh()->questions[0]['audio_url'])->toBe('/storage/tts/deja-fabrique.mp3');
});

test('un enregistrement dont le texte a change perd son ancienne piste', function () {
    // Le son dit le texte : si le texte change, le son ment. Mieux vaut aucune
    // piste — la commande en refera une — qu'une qui raconte autre chose.
    $this->seed(\Database\Seeders\LanguageSeeder::class);
    $this->seed(\Database\Seeders\ExamSeeder::class);

    $bibliotheque = app(\App\Services\Content\BibliothequeEpreuvesVerifiees::class);
    $module = collect($bibliotheque->modules())->firstWhere('section', 'hoeren');
    $bibliotheque->installe($module);

    $exercice = \App\Models\Exercise::where('catalog_key', 'like', 'verifie-v1:%:hoeren:%')->firstOrFail();
    $questions = $exercice->questions;
    $questions[0]['audio_url'] = '/storage/tts/ancienne.mp3';
    $questions[0]['audio_text'] = 'Un texte qui n est plus celui du fichier.';
    $exercice->questions = $questions;
    $exercice->save();

    $bibliotheque->installe($module);

    expect($exercice->fresh()->questions[0]['audio_url'] ?? null)->toBeNull();
});
