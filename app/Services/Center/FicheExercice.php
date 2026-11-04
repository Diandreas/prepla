<?php

namespace App\Services\Center;

use App\Models\Exercise;

/**
 * Ce qu'une liste montre d'un exercice : un titre lisible, son examen, son
 * niveau, sa competence, son nombre de questions et son origine.
 *
 * Les listes du centre affichaient « Multiple Choice #12 » : le nom technique
 * anglais du type et un numero. Un responsable ne retrouvait pas son exercice
 * sur la sante parmi dix QCM. Le titre vient de l'exercice lui-meme ; le nom
 * francais du type est pose cote navigateur (resources/js/lib/createur-exercices.js).
 *
 * L'appelant charge exerciseType et exam : rien ici ne fait de requete.
 */
class FicheExercice
{
    /** Les relations a charger avant d'appeler ligne(). */
    public const RELATIONS = ['exerciseType:id,name,slug,component_key,skill_type', 'exam:id,name'];

    /** Le titre de l'exercice, son extrait a defaut, ou null : le type prendra le relais. */
    public static function titre(Exercise $exercice): ?string
    {
        $contenu = is_array($exercice->content) ? $exercice->content : [];
        $questions = is_array($exercice->questions) ? $exercice->questions : [];

        foreach ([
            $contenu['title'] ?? null,
            $contenu['passage'] ?? null,
            $contenu['text'] ?? null,
            $questions[0]['text'] ?? null,
        ] as $candidat) {
            if (! is_string($candidat)) {
                continue;
            }
            $propre = trim((string) preg_replace('/\s+/u', ' ', strip_tags($candidat)));
            if ($propre !== '') {
                return mb_strlen($propre) > 80 ? rtrim(mb_substr($propre, 0, 77)).'…' : $propre;
            }
        }

        return null;
    }

    /** @return array<string, mixed> */
    public static function ligne(Exercise $exercice): array
    {
        $type = $exercice->exerciseType;

        return [
            'id' => $exercice->id,
            'titre' => self::titre($exercice),
            'type' => $type?->name,
            'type_slug' => $type?->slug,
            'component_key' => $type?->component_key,
            'skill_type' => $type?->skill_type,
            'difficulty' => $exercice->difficulty,
            'exam_id' => $exercice->exam_id,
            'exam' => $exercice->exam?->name,
            'questions_count' => is_array($exercice->questions) ? count($exercice->questions) : 0,
            'is_ai_generated' => (bool) $exercice->is_ai_generated,
            'source' => $exercice->center_id ? 'centre' : 'prepla',
            'updated_at' => $exercice->updated_at?->toIso8601String(),
        ];
    }
}
