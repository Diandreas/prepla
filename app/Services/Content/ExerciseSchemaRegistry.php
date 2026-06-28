<?php

namespace App\Services\Content;

/**
 * Single source of truth describing how each exercise type is authored & scored.
 *
 * Each component_key maps to:
 *  - family : the correction family handled by ExerciseScoringService
 *             (exact-match | multi-field | order | ai-writing | ai-speaking)
 *  - media  : which media a question may carry (image, audio)
 *  - label  : human label for the builder UI
 *
 * This registry is intentionally declarative so adding a new (even custom)
 * type = adding one entry here + its mirror in resources/js/lib/exercise-schemas.ts.
 * The frontend reads the same shape to render the right form fields, and the
 * builder controller validates submitted questions against the family.
 */
class ExerciseSchemaRegistry
{
    public const FAMILY_EXACT = 'exact-match';
    public const FAMILY_MULTI = 'multi-field';
    public const FAMILY_ORDER = 'order';
    public const FAMILY_AI_WRITING = 'ai-writing';
    public const FAMILY_AI_SPEAKING = 'ai-speaking';

    /**
     * @return array<string, array{label:string, family:string, media:array<string>}>
     */
    public static function all(): array
    {
        return [
            // ── exact-match ──────────────────────────────────────────────
            'mcq' => ['label' => 'QCM', 'family' => self::FAMILY_EXACT, 'media' => ['audio']],
            'true-false-ng' => ['label' => 'Vrai / Faux / Non mentionné', 'family' => self::FAMILY_EXACT, 'media' => ['audio']],
            'gap-fill' => ['label' => 'Texte à trous', 'family' => self::FAMILY_EXACT, 'media' => ['audio']],
            'matching' => ['label' => 'Association', 'family' => self::FAMILY_EXACT, 'media' => ['audio']],
            'sentence-completion' => ['label' => 'Compléter la phrase', 'family' => self::FAMILY_EXACT, 'media' => ['audio']],
            'short-answer' => ['label' => 'Réponse courte', 'family' => self::FAMILY_EXACT, 'media' => ['audio']],
            'word-formation' => ['label' => 'Formation de mots', 'family' => self::FAMILY_EXACT, 'media' => []],
            'key-word-transformation' => ['label' => 'Transformation', 'family' => self::FAMILY_EXACT, 'media' => []],
            'insert-text' => ['label' => 'Insérer une phrase', 'family' => self::FAMILY_EXACT, 'media' => []],
            'dictation' => ['label' => 'Dictée', 'family' => self::FAMILY_EXACT, 'media' => ['audio']],

            // ── multi-field (map clé→réponse, seuil 70%) ─────────────────
            'open-cloze' => ['label' => 'Texte à trous numérotés', 'family' => self::FAMILY_MULTI, 'media' => []],
            'note-completion' => ['label' => 'Compléter des notes', 'family' => self::FAMILY_MULTI, 'media' => ['audio']],
            'form-completion' => ['label' => 'Compléter un formulaire', 'family' => self::FAMILY_MULTI, 'media' => ['audio']],
            'table-completion' => ['label' => 'Compléter un tableau', 'family' => self::FAMILY_MULTI, 'media' => ['audio']],
            'summary-completion' => ['label' => 'Compléter un résumé', 'family' => self::FAMILY_MULTI, 'media' => []],
            'flow-chart-completion' => ['label' => 'Diagramme de flux', 'family' => self::FAMILY_MULTI, 'media' => []],
            'multiple-matching' => ['label' => 'Association multiple', 'family' => self::FAMILY_MULTI, 'media' => []],
            'diagram-labeling' => ['label' => 'Annoter un schéma', 'family' => self::FAMILY_MULTI, 'media' => ['image']],

            // ── order (séquence) ─────────────────────────────────────────
            'ordering' => ['label' => 'Remettre dans l\'ordre', 'family' => self::FAMILY_ORDER, 'media' => []],
            'gapped-text' => ['label' => 'Texte lacunaire (paragraphes)', 'family' => self::FAMILY_ORDER, 'media' => []],

            // ── IA — écrit ───────────────────────────────────────────────
            'short-writing' => ['label' => 'Rédaction courte', 'family' => self::FAMILY_AI_WRITING, 'media' => ['image']],
            'essay-editor' => ['label' => 'Rédaction / essai', 'family' => self::FAMILY_AI_WRITING, 'media' => []],
            'synthesis' => ['label' => 'Synthèse de documents', 'family' => self::FAMILY_AI_WRITING, 'media' => []],
            'academic-discussion' => ['label' => 'Discussion académique', 'family' => self::FAMILY_AI_WRITING, 'media' => []],
            'graph-description' => ['label' => 'Décrire un graphique', 'family' => self::FAMILY_AI_WRITING, 'media' => ['image']],
            'integrated-task' => ['label' => 'Tâche intégrée', 'family' => self::FAMILY_AI_WRITING, 'media' => ['audio']],

            // ── IA — oral ────────────────────────────────────────────────
            'speaking-recorder' => ['label' => 'Expression orale', 'family' => self::FAMILY_AI_SPEAKING, 'media' => ['image']],
            'role-play' => ['label' => 'Jeu de rôle', 'family' => self::FAMILY_AI_SPEAKING, 'media' => []],
        ];
    }

    public static function known(string $componentKey): bool
    {
        return array_key_exists($componentKey, self::all());
    }

    public static function familyFor(string $componentKey): ?string
    {
        return self::all()[$componentKey]['family'] ?? null;
    }

    /**
     * Validate (and lightly normalise) the authored questions for a given type.
     * Returns [ok(bool), error(?string), questions(array)]. Mirrors the
     * guard-rails the AI generator implicitly enforces, so a teacher cannot
     * save a malformed exercise that would crash the player.
     *
     * @return array{0:bool,1:?string,2:array}
     */
    public static function validateQuestions(string $componentKey, array $questions): array
    {
        if (! self::known($componentKey)) {
            return [false, "Type d'exercice inconnu.", []];
        }
        if (empty($questions)) {
            return [false, 'Au moins une question est requise.', []];
        }

        $family = self::familyFor($componentKey);

        foreach ($questions as $i => &$q) {
            $n = $i + 1;
            $q['id'] = $q['id'] ?? 'q' . $n;
            $q['type'] = $componentKey;

            switch ($family) {
                case self::FAMILY_EXACT:
                    if (trim((string)($q['text'] ?? '')) === '') {
                        return [false, "Question {$n} : le texte est requis.", []];
                    }
                    if (! isset($q['correct_answer']) || trim((string)$q['correct_answer']) === '') {
                        return [false, "Question {$n} : la bonne réponse est requise.", []];
                    }
                    break;

                case self::FAMILY_MULTI:
                    if (empty($q['correct_answers']) || ! is_array($q['correct_answers'])) {
                        return [false, "Question {$n} : la grille de réponses (clé → valeur) est requise.", []];
                    }
                    break;

                case self::FAMILY_ORDER:
                    if (empty($q['correct_order']) || ! is_array($q['correct_order'])) {
                        return [false, "Question {$n} : l'ordre correct est requis.", []];
                    }
                    break;

                case self::FAMILY_AI_WRITING:
                    if (trim((string)($q['text'] ?? $q['writing_prompt'] ?? '')) === '') {
                        return [false, "Question {$n} : la consigne de rédaction est requise.", []];
                    }
                    $q['correct_answer'] = null; // scored by AI
                    break;

                case self::FAMILY_AI_SPEAKING:
                    if (trim((string)($q['text'] ?? $q['prompt'] ?? '')) === '') {
                        return [false, "Question {$n} : la consigne orale est requise.", []];
                    }
                    if (empty($q['expected_points']) || ! is_array($q['expected_points'])) {
                        return [false, "Question {$n} : indiquez au moins un point attendu pour la correction.", []];
                    }
                    $q['correct_answer'] = null; // scored by AI on covered points
                    break;
            }
        }
        unset($q);

        return [true, null, array_values($questions)];
    }
}
