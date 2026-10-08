<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'exercise_type_id',
        'exam_id',
        'center_id',
        'creator_id',
        'lesson_id',
        'mock_exam_id',
        'exam_section_id',
        // node_id/order_in_node absents d'ici = update() silencieusement ignoré →
        // les exercices générés n'étaient jamais rattachés à leur nœud, et chaque
        // ouverture de session relançait la génération IA complète.
        'node_id',
        'order_in_node',
        'content',
        'questions',
        'difficulty',
        'xp_reward',
        'is_ai_generated',
        'catalog_key',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'questions' => 'array',
            'is_ai_generated' => 'boolean',
        ];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Lesson::class);
    }

    public function exerciseType(): BelongsTo
    {
        return $this->belongsTo(ExerciseType::class);
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function mockExam(): BelongsTo
    {
        return $this->belongsTo(MockExam::class);
    }

    public function examSection(): BelongsTo
    {
        return $this->belongsTo(ExamSection::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(UserExerciseAttempt::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(LanguageCenter::class, 'center_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
    /**
     * Types juges par l'IA : une question ouverte n'a pas de reponse attendue, et
     * c'est normal. La meme liste que app/Services/ExerciseScoringService.php et
     * resources/js/lib/scoring.js.
     */
    public const AI_EVALUATED_TYPES = [
        'essay', 'essay-editor', 'speaking', 'writing', 'short-writing',
        'graph-description', 'academic-discussion', 'speaking-recorder',
        'role-play', 'synthesis', 'integrated-task',
        'guided-rewrite', 'text-continuation', 'synthesis-essay',
        'oral-debate', 'negotiation', 'speaking-elicitation', 'listen-repeat',
        // « Ecriture guidee » manquait : la question etait ecartee comme impossible
        // a repondre, donc la redaction etait notee zero quoi que l'eleve ecrive.
        'guided-writing',
    ];

    /**
     * Le texte d'un choix de QCM, quelle que soit la forme rendue par l'IA.
     *
     * Le generateur rend parfois les choix sous forme d'objets
     * (`[{"text": "Ja"}, ...]`) au lieu de chaines. L'ecran savait deja les lire
     * (coerceOption) ; la correction, non : elle passait le choix brut a une
     * comparaison typee `?string` et levait une TypeError — 500 a l'envoi de la
     * seance, tout le travail perdu — et la reponse attendue retombait sur la
     * lettre nue, « Bonne reponse : A ».
     *
     * Regle identique dans resources/js/lib/scoring.js (optionText) : les deux
     * correcteurs doivent lire un choix de la meme facon.
     */
    public static function optionText(mixed $option): string
    {
        if (is_bool($option)) {
            return $option ? '1' : '';
        }

        if (is_scalar($option)) {
            return (string) $option;
        }

        if (is_array($option)) {
            foreach (['text', 'label', 'value'] as $cle) {
                if (is_scalar($option[$cle] ?? null)) {
                    return self::optionText($option[$cle]);
                }
            }

            foreach ($option as $valeur) {
                if (is_scalar($valeur)) {
                    return self::optionText($valeur);
                }
            }
        }

        return '';
    }

    /**
     * Une question a laquelle un apprenant peut reellement repondre.
     *
     * Du contenu casse atteignait les apprenants : une consigne « completez les
     * notes » dont aucune case n'etait vide (donc rien a remplir), une lettre
     * attendue hors de la liste des choix (donc comptee fausse quoi qu'on reponde),
     * des reponses attendues sans aucun champ pour les saisir. Releve sur les
     * donnees reelles : 14 questions sur 1224.
     */
    public static function questionIsAnswerable(array $question, ?string $fallbackType = null): bool
    {
        $type = $question['type'] ?? $fallbackType ?? '';

        // Une question ouverte est jugee par l'IA : pas de reponse attendue a trouver.
        if (in_array($type, self::AI_EVALUATED_TYPES, true)) {
            return true;
        }

        $fields = $question['notes'] ?? $question['fields'] ?? null;
        $map = $question['correct_answers'] ?? null;

        // Les champs ne s'appellent pas « notes » partout : associations (statements
        // + texts), etiquetage de schema (labels) et organigramme (steps) portaient
        // les leurs sous un autre nom. Faute de les reconnaitre, ces trois familles
        // etaient jugees impossibles a repondre A 100 %, et les quatre chemins qui
        // filtrent jetaient l'exercice entier sans un mot.
        foreach (['statements', 'labels', 'steps'] as $forme) {
            if (is_array($question[$forme] ?? null) && $question[$forme] !== []) {
                return true;
            }
        }

        if (is_array($fields)) {
            foreach ($fields as $field) {
                if (is_array($field) && ($field['value'] ?? null) === '') {
                    return true; // au moins une case a remplir
                }
            }

            return false;
        }

        // Une carte de reponses attendues suffit : pour un tableau ou un formulaire,
        // les champs peuvent venir du contenu de l'exercice et non de la question.
        // Verifie sur la batterie de reference : exiger une structure ici rejetait
        // des questions parfaitement jouables.
        if (is_array($map) && $map !== []) {
            return true;
        }

        if (is_array($question['correct_order'] ?? null)) {
            return true;
        }

        $expected = $question['correct_answer'] ?? null;

        // Un ensemble de reponses (choix multiples, associations) est un tableau.
        if (is_array($expected)) {
            return $expected !== [];
        }

        if (! is_scalar($expected) || trim((string) $expected) === '') {
            return false;
        }

        // Une lettre attendue doit designer un choix qui existe.
        $options = $question['options'] ?? null;
        $letter = strtoupper(trim((string) $expected));
        if (is_array($options) && preg_match('/^[A-Z]$/', $letter)) {
            $rank = ord($letter) - ord('A');

            return $rank >= 0 && $rank < count($options);
        }

        return true;
    }

    /** Les questions de cet exercice auxquelles on peut repondre, renumerotees. */
    public function answerableQuestions(): array
    {
        $fallback = $this->exerciseType?->component_key;

        return array_values(array_filter(
            $this->questions ?? [],
            fn ($question) => is_array($question) && self::questionIsAnswerable($question, $fallback)
        ));
    }
}
