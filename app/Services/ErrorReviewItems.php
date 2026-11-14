<?php

namespace App\Services;

use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\User;
use App\Models\UserError;

/**
 * Ce que la revision des erreurs pose a l'apprenant, et comment elle le corrige.
 *
 * Deux defauts corriges ici :
 *  - la bonne reponse partait au navigateur AVANT la reponse : il suffisait
 *    d'ouvrir les outils du navigateur, et le serveur croyait le « juste/faux »
 *    envoye par la page. La reponse attendue reste donc cote serveur, et c'est lui
 *    qui corrige ;
 *  - la meme question revenait mot pour mot, choix dans le meme ordre : on
 *    retenait la position du bon choix, pas la regle. On melange les choix, et
 *    quand le vivier a une autre question du meme type d'exercice, on pose celle-la.
 */
class ErrorReviewItems
{
    /** Cle de session qui retient la question posee pour chaque erreur. */
    public const SESSION = 'error_review_items';

    /**
     * La question a poser pour cette erreur, SANS la reponse attendue.
     *
     * @return array{prompt: string, options: list<string>, variant: bool}
     */
    public function pose(UserError $error, User $user): array
    {
        $variante = $this->varianteDuVivier($error, $user);
        $source = $variante ?? $this->questionDOrigine($error);

        if ($source) {
            [$exercise, $question] = $source;
            // On retient ce qui a ete pose : la correction se fera contre CETTE
            // question, pas contre l'erreur d'origine.
            session()->put(self::SESSION . '.' . $error->id, [
                'exercise_id' => $exercise->id,
                'question_id' => (string) ($question['id'] ?? ''),
            ]);

            $options = Exercise::optionList($question['options'] ?? []);
            shuffle($options);

            return [
                'prompt' => (string) ($question['text'] ?? $question['prompt'] ?? $question['statement'] ?? $error->question_text),
                'options' => array_values($options),
                'variant' => $variante !== null,
            ];
        }

        // Rien dans le vivier : on repose l'erreur elle-meme, mais on note quand
        // meme qu'elle a ete posee, pour n'accepter qu'une correction.
        session()->put(self::SESSION . '.' . $error->id, ['exercise_id' => null, 'question_id' => '']);

        return ['prompt' => (string) $error->question_text, 'options' => [], 'variant' => false];
    }

    /**
     * La reponse attendue pour ce qui a ete pose, avec son explication.
     *
     * @return array{expected: string, explanation: ?string}
     */
    public function attendu(UserError $error, ?array $pose = null): array
    {
        $pose ??= session()->get(self::SESSION . '.' . $error->id);
        if (is_array($pose)) {
            $exercise = Exercise::find($pose['exercise_id'] ?? 0);
            $question = $exercise ? $this->trouverQuestion($exercise, (string) ($pose['question_id'] ?? '')) : null;
            if ($question) {
                $attendu = $this->texteAttendu($question);
                if ($attendu !== '') {
                    $explication = $question['explanation'] ?? null;

                    return [
                        'expected' => $attendu,
                        'explanation' => is_string($explication) && $explication !== '' ? $explication : $error->explanation,
                    ];
                }
            }
        }

        return ['expected' => (string) $error->correct_answer, 'explanation' => $error->explanation];
    }

    /**
     * Consomme la question posee pour cette erreur : une seule correction par
     * question posee. Null si rien n'a ete pose (rejeu, autre onglet, session expiree).
     */
    public function consommer(UserError $error): ?array
    {
        $pose = session()->pull(self::SESSION . '.' . $error->id);

        return is_array($pose) ? $pose : null;
    }

    /**
     * Meme regle que l'ancienne recallEstJuste cote page : la chaine complete, la meme sans
     * son prefixe de lettre « C) », et la lettre seule pour les anciennes erreurs.
     */
    public static function estJuste(string $saisie, string $attendu): bool
    {
        $voulu = trim($attendu);
        $donne = self::normaliser($saisie);
        if ($donne === '' || $voulu === '') {
            return false;
        }

        $formes = [self::normaliser($voulu)];
        if (preg_match('/^([A-Za-z])\)\s*(.+)$/u', $voulu, $m)) {
            $formes[] = self::normaliser($m[2]);
            $formes[] = self::normaliser($m[1]);
        }

        return in_array($donne, $formes, true);
    }

    /** Comme normalizeAnswer de resources/js/lib/scoring.js. */
    public static function normaliser(string $texte): string
    {
        return str_replace(['’', '`'], "'", mb_strtolower(trim($texte)));
    }

    /** Le texte du bon choix (et non sa lettre), ou la reponse ecrite. */
    private function texteAttendu(array $question): string
    {
        $brut = $question['correct_answer'] ?? null;
        if (!is_scalar($brut)) {
            return '';
        }
        $brut = trim((string) $brut);
        $options = Exercise::optionList($question['options'] ?? []);

        if ($options !== [] && preg_match('/^[A-Za-z]$/', $brut)) {
            $index = ord(strtoupper($brut)) - 65;

            return $options[$index] ?? '';
        }

        return $brut;
    }

    /** @return array{0: Exercise, 1: array}|null */
    private function questionDOrigine(UserError $error): ?array
    {
        if (!$error->exercise_id) {
            return null;
        }
        $exercise = Exercise::find($error->exercise_id);
        $question = $exercise ? $this->trouverQuestion($exercise, (string) $error->question_id) : null;

        return $question && $this->texteAttendu($question) !== '' ? [$exercise, $question] : null;
    }

    /**
     * Une autre question du meme type d'exercice, dans l'examen de l'apprenant.
     * Seulement des questions fermees (choix ou reponse courte) : une redaction ne
     * se corrige pas par comparaison.
     *
     * @return array{0: Exercise, 1: array}|null
     */
    private function varianteDuVivier(UserError $error, User $user): ?array
    {
        if (!$error->exercise_type_slug) {
            return null;
        }

        $origine = $error->exercise_id ? Exercise::find($error->exercise_id) : null;
        $examId = $origine?->exam_id ?? $user->profile?->target_exam_id;
        if (!$examId) {
            return null;
        }

        $niveau = $origine?->difficulty;

        $typeIds = ExerciseType::where('slug', $error->exercise_type_slug)->pluck('id');
        if ($typeIds->isEmpty()) {
            return null;
        }

        $candidats = [];
        Exercise::where('exam_id', $examId)
            ->whereIn('exercise_type_id', $typeIds)
            ->whereNull('center_id')
            ->when($niveau, fn ($q) => $q->where('difficulty', $niveau))
            ->inRandomOrder()
            ->limit(20)
            ->get()
            ->each(function (Exercise $exercise) use ($error, &$candidats) {
                foreach ((array) $exercise->questions as $question) {
                    if (!is_array($question)) {
                        continue;
                    }
                    $meme = $exercise->id === $error->exercise_id
                        && (string) ($question['id'] ?? '') === (string) $error->question_id;
                    $texte = (string) ($question['text'] ?? $question['prompt'] ?? '');
                    $attendu = $this->texteAttendu($question);
                    if ($meme || $texte === '' || $texte === $error->question_text || $attendu === '') {
                        continue;
                    }
                    // Une reponse longue est une production libre, pas un mot a retrouver.
                    if (count(preg_split('/\s+/u', trim($attendu), -1, PREG_SPLIT_NO_EMPTY)) > 4 && Exercise::optionList($question['options'] ?? []) === []) {
                        continue;
                    }
                    if (!isset($question['id'])) {
                        continue;
                    }
                    $candidats[] = [$exercise, $question];
                }
            });

        return $candidats === [] ? null : $candidats[array_rand($candidats)];
    }

    private function trouverQuestion(Exercise $exercise, string $questionId): ?array
    {
        foreach ((array) $exercise->questions as $question) {
            if (is_array($question) && (string) ($question['id'] ?? '') === $questionId) {
                return $question;
            }
        }

        return null;
    }
}
