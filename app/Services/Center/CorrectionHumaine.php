<?php

namespace App\Services\Center;

use App\Models\Classroom;
use App\Models\CorrectionEnseignant;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Models\UserExerciseAttempt;
use App\Services\AI\GrilleOfficielleEcrit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Correction humaine par le centre : quelles productions (redactions, prises
 * de parole) un membre du personnel peut relire, et la note qu'il donne.
 *
 * Perimetre : le responsable voit les eleves de toutes les classes de SON
 * centre ; un enseignant, ceux des classes qu'il anime. Jamais un eleve d'un
 * autre centre, meme si le membre appartient aux deux.
 */
class CorrectionHumaine
{
    public const COMPETENCES = ['writing', 'speaking'];

    public const DIMENSIONS = ['task_achievement', 'coherence_cohesion', 'lexical_resource', 'grammar_accuracy'];

    public const DISQUE = 'local';

    /**
     * Conserve les audios d'une reponse (prise de parole) sur le disque prive.
     * Une panne de stockage ne doit jamais faire echouer la correction.
     *
     * @return array<string, string>|null question_id => chemin
     */
    public static function conserverAudios(int $userId, array $answers): ?array
    {
        $chemins = [];
        foreach ($answers as $questionId => $reponse) {
            if (! $reponse instanceof UploadedFile) {
                continue;
            }
            try {
                $chemin = $reponse->store("productions/{$userId}", self::DISQUE);
                if (is_string($chemin) && $chemin !== '') {
                    $chemins[(string) $questionId] = $chemin;
                }
            } catch (\Throwable $e) {
                Log::warning('Audio de production non conserve', ['user' => $userId, 'erreur' => $e->getMessage()]);
            }
        }

        return $chemins === [] ? null : $chemins;
    }

    /** Les classes (non archivees) du centre que ce membre peut relire. */
    public function classes(LanguageCenter $centre, User $membre): Collection
    {
        $requete = Classroom::where('center_id', $centre->id)->whereNull('archived_at')->orderBy('name');
        if (! $membre->centerRoleIs('center_admin')) {
            $requete->whereHas('teachers', fn ($q) => $q->whereKey($membre->id));
        }

        return $requete->get(['id', 'name']);
    }

    /** Les eleves que ce membre peut relire, eventuellement dans une seule classe. */
    private function eleves(LanguageCenter $centre, User $membre, ?int $classeId = null): Collection
    {
        $classes = $this->classes($centre, $membre)->pluck('id');
        if ($classeId !== null) {
            $classes = $classes->intersect([$classeId]);
        }
        if ($classes->isEmpty()) {
            return collect();
        }

        $eleves = \Illuminate\Support\Facades\DB::table('classroom_user')
            ->whereIn('classroom_id', $classes)
            ->where('role_in_class', 'student')
            ->pluck('user_id')->unique();

        // Encore eleve de CE centre : un eleve parti n'est plus relu ici.
        return \Illuminate\Support\Facades\DB::table('center_user')
            ->where('center_id', $centre->id)
            ->where('role', 'student')
            ->whereIn('user_id', $eleves)
            ->pluck('user_id')->unique()->values();
    }

    /** Tentatives de redaction ou de prise de parole. */
    private function productions(Builder $requete): Builder
    {
        return $requete->whereHas('exercise.exerciseType', function ($q) {
            $q->whereIn('skill_type', self::COMPETENCES)
                ->orWhereHas('section', fn ($s) => $s->whereIn('skill_type', self::COMPETENCES));
        });
    }

    /**
     * La file : productions non revues par ce centre (ou deja revues, au choix).
     *
     * @return list<array<string, mixed>>
     */
    public function file(LanguageCenter $centre, User $membre, ?int $classeId, bool $revues): array
    {
        $eleves = $this->eleves($centre, $membre, $classeId);
        if ($eleves->isEmpty()) {
            return [];
        }

        $requete = $this->productions(UserExerciseAttempt::query()->whereIn('user_id', $eleves));
        $requete = $revues
            ? $requete->whereHas('correctionEnseignant', fn ($q) => $q->where('center_id', $centre->id))
            : $requete->whereDoesntHave('correctionEnseignant');

        return $requete
            ->with(['user:id,name', 'exercise.exerciseType.section', 'exercise.exam:id,name', 'correctionEnseignant'])
            ->latest('id')
            ->limit(100)
            ->get()
            ->map(fn (UserExerciseAttempt $t) => [
                'id' => $t->id,
                'eleve' => $t->user?->name,
                'competence' => $this->competence($t),
                'examen' => $t->exercise?->exam?->name,
                'note_ia' => round((float) $t->accuracy_percent),
                'note_enseignant' => $t->correctionEnseignant?->grille['affichage'] ?? null,
                'date' => $t->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    /** Ce membre peut-il relire cette tentative au nom de ce centre ? */
    public function peutRelire(LanguageCenter $centre, User $membre, UserExerciseAttempt $tentative): bool
    {
        if (! $this->eleves($centre, $membre)->contains($tentative->user_id)) {
            return false;
        }

        return $this->productions(UserExerciseAttempt::whereKey($tentative->id))->exists();
    }

    public function competence(UserExerciseAttempt $t): string
    {
        $type = $t->exercise?->exerciseType;
        $skill = $type?->section?->skill_type ?? $type?->skill_type;

        return $skill === 'speaking' || $type?->skill_type === 'speaking' ? 'speaking' : 'writing';
    }

    /** Le detail d'une production, pour la page de correction. */
    public function detail(UserExerciseAttempt $t): array
    {
        $t->loadMissing(['user:id,name', 'exercise.exerciseType.section', 'exercise.exam:id,name', 'correctionEnseignant.teacher:id,name']);
        $questions = collect($t->exercise?->questions ?? [])
            ->mapWithKeys(fn ($q, $i) => [(string) ($q['id'] ?? $i) => $q]);
        $reponses = $t->answers ?? [];
        $audios = $t->audios ?? [];

        $items = collect($t->feedback ?? [])->map(function ($f) use ($questions, $reponses, $audios, $t) {
            $id = (string) ($f['question_id'] ?? '');
            $q = $questions->get($id, []);
            $reponse = $reponses[$id] ?? null;

            return [
                'question_id' => $id,
                'consigne' => $q['prompt'] ?? $q['text'] ?? null,
                'texte' => is_string($reponse) ? $reponse : null,
                'transcription' => $f['transcription'] ?? null,
                'audio' => isset($audios[$id]) ? route('center.corrections.audio', ['attempt' => $t->id, 'question' => $id]) : null,
                'ia' => [
                    'pourcentage' => isset($f['accuracy']) ? round((float) $f['accuracy']) : null,
                    'sous_notes' => $f['sub_scores'] ?? null,
                    'grille' => $f['grille_officielle'] ?? null,
                    'explication' => $f['explanation'] ?? null,
                ],
            ];
        })->values()->all();

        $correction = $t->correctionEnseignant;

        return [
            'id' => $t->id,
            'eleve' => $t->user?->name,
            'competence' => $this->competence($t),
            'examen' => $t->exercise?->exam?->name,
            'date' => $t->created_at?->toIso8601String(),
            'note_ia' => round((float) $t->accuracy_percent),
            'items' => $items,
            'correction' => $correction ? [
                'sous_notes' => $correction->sous_notes,
                'grille' => $correction->grille,
                'commentaire' => $correction->commentaire,
                'enseignant' => $correction->teacher?->name,
            ] : null,
        ];
    }

    /**
     * Enregistre la note de l'enseignant : quatre dimensions sur 9, traduites
     * dans l'echelle de l'examen par la meme grille que la correction IA.
     */
    public function noter(LanguageCenter $centre, User $membre, UserExerciseAttempt $t, array $sousNotes, ?string $commentaire): CorrectionEnseignant
    {
        $dims = [];
        foreach (self::DIMENSIONS as $cle) {
            $dims[$cle] = round((float) $sousNotes[$cle] * 2) / 2;
        }
        $note = round(array_sum($dims) / count($dims), 2);
        $examen = (string) ($t->exercise?->exam?->name ?? '');
        $grille = GrilleOfficielleEcrit::calculer($examen, $note, $dims);
        if ($grille !== null) {
            // La note vient d'un enseignant, pas d'une estimation automatique.
            unset($grille['mention']);
        } else {
            $sur100 = (int) round($note / 9 * 100);
            $grille = ['examen' => $examen, 'note' => $sur100, 'note_max' => 100, 'affichage' => "{$sur100} / 100", 'commentaire' => null, 'criteres' => []];
        }

        return CorrectionEnseignant::updateOrCreate(
            ['attempt_id' => $t->id],
            [
                'center_id' => $centre->id,
                'teacher_id' => $membre->id,
                'sous_notes' => $dims,
                'note' => $note,
                'grille' => $grille,
                'commentaire' => $commentaire !== null && trim($commentaire) !== '' ? trim($commentaire) : null,
            ]
        );
    }
}
