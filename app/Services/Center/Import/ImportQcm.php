<?php

namespace App\Services\Center\Import;

use App\Models\Exam;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Services\Content\ExerciseSchemaRegistry;
use Illuminate\Support\Facades\DB;

/**
 * Import de QCM dans la banque du centre, depuis un CSV ou un JSON.
 *
 * Une ligne = un exercice d'une question, cree en BROUILLON : le centre le
 * relit avant de le valider pour ses devoirs. Champs : question, options
 * (« A | B | C » en CSV, liste en JSON, ou colonnes option_a … option_f),
 * bonne_reponse (lettre ou texte de l'option), competence, niveau, examen
 * (slug ou nom), explication facultative.
 *
 * L'examen doit etre propose par le centre, et avoir un type QCM pour la
 * competence : l'exercice y est rattache comme s'il etait cree a la main.
 */
class ImportQcm
{
    public const MAX_LIGNES = 200;

    public const NIVEAUX = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

    public const LETTRES = ['A', 'B', 'C', 'D', 'E', 'F'];

    /** Les noms de competence acceptes, vers le skill_type des types d'exercice. */
    private const COMPETENCES = [
        'reading' => 'reading', 'lecture' => 'reading', 'comprehension_ecrite' => 'reading', 'ce' => 'reading', 'lesen' => 'reading',
        'listening' => 'listening', 'ecoute' => 'listening', 'comprehension_orale' => 'listening', 'co' => 'listening', 'horen' => 'listening', 'hoeren' => 'listening',
        'grammar' => 'grammar', 'grammaire' => 'grammar', 'structures' => 'grammar',
        'vocabulary' => 'vocabulary', 'vocabulaire' => 'vocabulary',
        'writing' => 'writing', 'speaking' => 'speaking',
    ];

    /**
     * @param  list<array<string, mixed>>  $lignes
     * @return list<array{ligne:int, question:string, options:list<string>, bonne_reponse:?string, competence:string, niveau:string, examen:string, statut:string, erreurs:list<string>}>
     */
    public function analyser(LanguageCenter $centre, array $lignes): array
    {
        $examens = Exam::all();
        $resultat = [];

        foreach ($lignes as $l) {
            $erreurs = [];
            $question = $this->texte($l['question'] ?? null);
            $options = $this->options($l);
            $reponse = $this->texte($l['bonne_reponse'] ?? $l['reponse'] ?? $l['correct_answer'] ?? null);
            $competenceBrute = $this->texte($l['competence'] ?? $l['skill'] ?? null);
            $niveau = strtoupper($this->texte($l['niveau'] ?? $l['level'] ?? null));
            $examenBrut = $this->texte($l['examen'] ?? $l['exam'] ?? null);
            $explication = $this->texte($l['explication'] ?? $l['explanation'] ?? null);

            if ($question === '') {
                $erreurs[] = 'La question est requise.';
            } elseif (mb_strlen($question) > 2000) {
                $erreurs[] = 'La question dépasse 2000 caractères.';
            }
            if ($options === null) {
                $erreurs[] = 'Les options doivent être du texte.';
                $options = [];
            } elseif (count($options) < 2 || count($options) > 6) {
                $erreurs[] = 'Il faut entre 2 et 6 options.';
            } elseif (collect($options)->contains(fn ($o) => mb_strlen($o) > 500)) {
                $erreurs[] = 'Une option dépasse 500 caractères.';
            } elseif (count(array_unique(array_map('mb_strtolower', $options))) !== count($options)) {
                $erreurs[] = 'Deux options sont identiques.';
            }

            $lettre = $this->lettre($reponse, $options);
            if ($reponse === '') {
                $erreurs[] = 'La bonne réponse est requise.';
            } elseif ($lettre === null && $options !== []) {
                $erreurs[] = "La bonne réponse « {$reponse} » ne correspond à aucune option.";
            }
            if (mb_strlen($explication) > 2000) {
                $erreurs[] = "L'explication dépasse 2000 caractères.";
            }
            if (! in_array($niveau, self::NIVEAUX, true)) {
                $erreurs[] = 'Le niveau doit être A1, A2, B1, B2, C1 ou C2.';
            }

            $competence = self::COMPETENCES[LecteurFichier::cle($competenceBrute)] ?? null;
            if ($competence === null) {
                $erreurs[] = "La compétence « {$competenceBrute} » est inconnue (reading, listening, grammar, vocabulary).";
            }

            $examen = $examenBrut === '' ? null : $examens->first(fn (Exam $e) => mb_strtolower($e->slug) === mb_strtolower($examenBrut)
                || mb_strtolower($e->name) === mb_strtolower($examenBrut));
            // Un examen que le centre ne propose pas est traite comme inconnu :
            // l'import ne dit rien de l'offre des autres.
            if (! $examen || ! $centre->proposeExamen($examen->id)) {
                $erreurs[] = $examenBrut === '' ? "L'examen est requis." : "L'examen « {$examenBrut} » n'est pas proposé par votre centre.";
                $examen = null;
            }

            $type = null;
            if ($examen && $competence) {
                $type = $this->typeQcm($examen, $competence);
                if (! $type) {
                    $erreurs[] = "L'examen « {$examen->name} » n'a pas de QCM pour cette compétence.";
                }
            }

            $resultat[] = [
                'ligne' => (int) $l['_ligne'],
                'question' => $question,
                'options' => $options,
                'bonne_reponse' => $lettre,
                'explication' => $explication !== '' ? $explication : null,
                'competence' => $competence ?? $competenceBrute,
                'niveau' => $niveau,
                'examen' => $examen?->name ?? $examenBrut,
                'exam_id' => $examen?->id,
                'exercise_type_id' => $type?->id,
                'statut' => $erreurs === [] ? 'valide' : 'erreur',
                'erreurs' => $erreurs,
            ];
        }

        return $resultat;
    }

    /** @return array{crees:int, erreurs:int} */
    public function importer(LanguageCenter $centre, User $auteur, array $analyse): array
    {
        $bilan = ['crees' => 0, 'erreurs' => 0];

        DB::transaction(function () use ($centre, $auteur, $analyse, &$bilan) {
            foreach ($analyse as $l) {
                if ($l['statut'] !== 'valide') {
                    $bilan['erreurs']++;

                    continue;
                }

                [$ok, , $questions] = ExerciseSchemaRegistry::validateQuestions('mcq', [[
                    'id' => 'q1',
                    'text' => $l['question'],
                    'options' => $l['options'],
                    'correct_answer' => $l['bonne_reponse'],
                    'explanation' => $l['explication'],
                ]]);
                if (! $ok) {
                    $bilan['erreurs']++;

                    continue;
                }

                Exercise::create([
                    'exercise_type_id' => $l['exercise_type_id'],
                    'exam_id' => $l['exam_id'],
                    'center_id' => $centre->id,
                    'creator_id' => $auteur->id,
                    'content' => [],
                    'questions' => $questions,
                    'difficulty' => $l['niveau'],
                    'is_ai_generated' => false,
                    'center_status' => Exercise::STATUT_BROUILLON,
                ]);
                $bilan['crees']++;
            }
        });

        return $bilan;
    }

    private function typeQcm(Exam $examen, string $competence): ?ExerciseType
    {
        return ExerciseType::where('component_key', 'mcq')
            ->where('skill_type', $competence)
            ->whereHas('section', fn ($q) => $q->where('exam_id', $examen->id))
            ->orderBy('id')->first();
    }

    private function texte(mixed $v): string
    {
        return is_scalar($v) ? trim((string) $v) : '';
    }

    /** @return list<string>|null null si une option n'est pas du texte. */
    private function options(array $l): ?array
    {
        $brut = $l['options'] ?? null;
        if (is_array($brut)) {
            if (! array_is_list($brut) || collect($brut)->contains(fn ($o) => ! is_scalar($o))) {
                return null;
            }
            $liste = $brut;
        } elseif (is_string($brut) && $brut !== '') {
            $liste = explode('|', $brut);
        } else {
            $liste = [];
            foreach (self::LETTRES as $lettre) {
                $liste[] = $l['option_'.strtolower($lettre)] ?? '';
            }
        }

        return array_values(array_filter(array_map(fn ($o) => trim((string) $o), $liste), fn ($o) => $o !== ''));
    }

    /** « B », « b » ou le texte exact d'une option -> sa lettre. */
    private function lettre(string $reponse, array $options): ?string
    {
        if ($reponse === '' || $options === []) {
            return null;
        }
        $maj = strtoupper($reponse);
        $i = array_search($maj, self::LETTRES, true);
        if (strlen($reponse) === 1 && $i !== false && $i < count($options)) {
            return $maj;
        }
        foreach ($options as $k => $o) {
            if (mb_strtolower($o) === mb_strtolower($reponse)) {
                return self::LETTRES[$k];
            }
        }

        return null;
    }
}
