<?php

use App\Services\Content\ExerciseSchemaRegistry;

/**
 * Le catalogue sans IA est le filet de sécurité : il sert quand le fournisseur ne
 * répond plus. Un exercice cassé y est donc pire qu'ailleurs, puisqu'il tombe au pire
 * moment. Ces contrôles valent pour tout contenu ajouté ensuite.
 */
function starterCatalogues(): array
{
    $all = [];
    foreach (['english', 'french', 'german'] as $language) {
        $all[$language] = require resource_path("content/practice-starters/{$language}.php");
    }

    return $all;
}

test('chaque serie du catalogue est complete et jouable', function () {
    $problems = [];

    foreach (starterCatalogues() as $language => $catalogue) {
        foreach ($catalogue as $level => $types) {
            foreach ($types as $component => $template) {
                $where = "{$language}/{$level}/{$component}";

                if (trim($template['title'] ?? '') === '') $problems[] = "{$where} : titre vide.";
                if (trim($template['description'] ?? '') === '') $problems[] = "{$where} : description vide.";
                if (trim($template['content']['instructions'] ?? '') === '') $problems[] = "{$where} : consigne vide.";
                if (count($template['questions'] ?? []) !== 5) {
                    $problems[] = "{$where} : " . count($template['questions'] ?? []) . ' questions au lieu de 5.';
                }

                // Le joueur n'affiche que content.passage : un QCM de compréhension sans
                // cette clé poserait des questions sur un texte jamais montré.
                if ($component === 'mcq' && trim($template['content']['passage'] ?? '') === '') {
                    $problems[] = "{$where} : texte de référence absent (content.passage).";
                }

                $ids = array_column($template['questions'] ?? [], 'id');
                if (count($ids) !== count(array_unique($ids))) $problems[] = "{$where} : identifiants en double.";

                [$valid, $error] = ExerciseSchemaRegistry::validateQuestions($component, $template['questions'] ?? []);
                if (!$valid) $problems[] = "{$where} : {$error}";
            }
        }
    }

    expect($problems)->toBe([]);
});

test('aucune question du catalogue n est insoluble', function () {
    $problems = [];

    foreach (starterCatalogues() as $language => $catalogue) {
        foreach ($catalogue as $level => $types) {
            foreach ($types as $component => $template) {
                foreach ($template['questions'] as $question) {
                    $where = "{$language}/{$level}/{$component}/{$question['id']}";
                    $expected = trim((string) ($question['correct_answer'] ?? ''));

                    if (trim((string) ($question['text'] ?? '')) === '') $problems[] = "{$where} : énoncé vide.";
                    if (trim((string) ($question['explanation'] ?? '')) === '') $problems[] = "{$where} : explication vide.";

                    if (isset($question['options'])) {
                        $options = $question['options'];
                        if (count($options) !== 4) $problems[] = "{$where} : " . count($options) . ' options au lieu de 4.';

                        $normalised = array_map(fn ($o) => mb_strtolower(trim((string) $o)), $options);
                        if (count(array_unique($normalised)) !== count($normalised)) $problems[] = "{$where} : options en double.";
                        if (in_array('', $normalised, true)) $problems[] = "{$where} : option vide.";

                        // Une lettre qui ne désigne aucune option rend la question impossible.
                        if (!preg_match('/^[A-Z]$/', $expected)) {
                            $problems[] = "{$where} : « {$expected} » n'est pas une lettre d'option.";
                        } elseif (ord($expected) - 65 >= count($options)) {
                            $problems[] = "{$where} : « {$expected} » ne désigne aucune option — question insoluble.";
                        }
                    } else {
                        // Un seul trou par phrase : deux trous pour une réponse d'un mot
                        // rendaient l'exercice impossible à gagner.
                        $blanks = preg_match_all('/_{2,}/', (string) $question['text']);
                        if ($blanks !== 1) $problems[] = "{$where} : {$blanks} trou(s) au lieu d'un seul.";
                        if (preg_match('/^[A-Z]$/', $expected)) {
                            $problems[] = "{$where} : réponse réduite à une lettre alors qu'il n'y a pas d'options.";
                        }
                    }
                }
            }
        }
    }

    expect($problems)->toBe([]);
});

test('le catalogue couvre tous les niveaux du cadre europeen dans les trois langues', function () {
    foreach (starterCatalogues() as $language => $catalogue) {
        expect(array_keys($catalogue))->toBe(['A1', 'A2', 'B1', 'B2', 'C1', 'C2'], $language);

        foreach ($catalogue as $level => $types) {
            expect(array_keys($types))->toEqualCanonicalizing(['mcq', 'gap-fill', 'matching'], "{$language}/{$level}");
        }
    }
});
