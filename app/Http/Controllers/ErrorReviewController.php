<?php

namespace App\Http\Controllers;

use App\Models\ExerciseType;
use App\Models\UserError;
use App\Services\AI\ExerciseGeneratorService;
use App\Services\ErrorSpacedRepetitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ErrorReviewController extends Controller
{
    protected ErrorSpacedRepetitionService $sm2;

    public function __construct(ErrorSpacedRepetitionService $sm2)
    {
        $this->sm2 = $sm2;
    }

    // GET /errors - list unmastered errors
    public function index(Request $request)
    {
        $user = $request->user();

        // Only CONCEPT errors are listed here (grammar/vocab/writing…) — they're the
        // ones the learner can actually re-practise. Comprehension errors (reading/
        // listening on a one-off passage) can't be re-posed, so they feed the
        // diagnostic stats below but never appear as practisable items.
        $errors = UserError::concept($user->id)
            ->orderByDesc('created_at')
            ->paginate(20);
        // Normalise field names for the frontend (reviewed_count → review_count,
        // question_text → prompt) and expose the pedagogical family.
        $errors->getCollection()->transform(fn ($e) => [
            'id' => $e->id,
            'skill_type' => $e->skill_type,
            'prompt' => $e->question_text,
            'user_answer' => $e->user_answer,
            'correct_answer' => $e->correct_answer,
            'explanation' => $e->explanation,
            'mastered' => $e->mastered,
            'review_count' => $e->reviewed_count ?? 0,
            'next_review_at' => $e->next_review_at,
            'created_at' => $e->created_at,
            'family' => UserError::classifyFamily($e->exercise_type_slug, $e->skill_type),
        ]);

        $errorsBySkill = UserError::where('user_id', $user->id)
            ->where('mastered', false)
            ->selectRaw('skill_type, count(*) as count')
            ->groupBy('skill_type')
            ->pluck('count', 'skill_type');

        // Pilier 4: Error category stats
        $errorsByCategory = UserError::categoryStats($user->id);

        // Pilier 3: Due errors count
        $dueForReviewCount = UserError::dueForReview($user->id)->count();

        return Inertia::render('errors/index', [
            'errors' => $errors,
            'errorsBySkill' => $errorsBySkill,
            'errorsByCategory' => $errorsByCategory,
            'dueForReviewCount' => $dueForReviewCount,
        ]);
    }

    // GET /errors/practice - personalized error review session.
    // Only CONCEPT errors are re-practised here (grammar/vocab/writing…): re-testing
    // a comprehension question would just make the learner memorise that one passage.
    public function practice(Request $request)
    {
        $user = $request->user();
        $skillType = $request->query('skill');

        // Pilier 3: SM-2 due errors first, then recent unmastered — concept only.
        // Comprehension errors (reading/listening on a passage) are excluded: they
        // can't be re-posed without the original text.
        $dueErrors = UserError::dueForReview($user->id)
            ->whereNotIn('skill_type', ['reading', 'listening'])
            ->where(function ($q) {
                $q->whereIn('exercise_type_slug', UserError::CONCEPT_SLUGS)
                  ->orWhereIn('skill_type', ['grammar', 'vocabulary', 'use-of-english', 'writing']);
            })
            ->when($skillType, fn($q) => $q->where('skill_type', $skillType))
            ->limit(10)
            ->get();

        if ($dueErrors->isEmpty()) {
            $dueErrors = UserError::concept($user->id)
                ->when($skillType, fn($q) => $q->where('skill_type', $skillType))
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();
        }

        return Inertia::render('errors/practice', [
            'errors' => $dueErrors->map(fn ($e) => [
                'id' => $e->id,
                'skill_type' => $e->skill_type,
                'prompt' => $e->question_text,
                'user_answer' => $e->user_answer,
                'correct_answer' => $e->correct_answer,
                'explanation' => $e->explanation,
                'mastered' => $e->mastered,
                'review_count' => $e->reviewed_count,
                'created_at' => $e->created_at,
            ])->values(),
        ]);
    }

    /**
     * POST /errors/{error}/similar — un exercice NEUF sur le concept raté.
     *
     * La révision reposait la question mot pour mot : l'apprenant réapprenait une
     * phrase, pas une règle. Il pouvait la refaire juste sans avoir compris, et rater
     * la même difficulté ailleurs. On génère donc un exercice du même type sur le même
     * concept, pour qu'il rencontre la difficulté autrement.
     */
    public function similar(Request $request, UserError $error, ExerciseGeneratorService $generator)
    {
        abort_unless($error->user_id === $request->user()->id, 403);

        $profile = $request->user()->profile;
        $exam = $profile?->targetExam;

        if (!$exam) {
            return response()->json(['message' => "Choisis d'abord l'examen que tu prépares."], 422);
        }

        // Même type d'exercice que l'erreur d'origine ; à défaut, un texte à trou, qui
        // convient à presque tous les concepts de grammaire et de vocabulaire.
        $type = ExerciseType::where('slug', $error->exercise_type_slug)->first()
            ?? ExerciseType::where('component_key', 'gap-fill')->first()
            ?? ExerciseType::where('component_key', 'mcq')->first();

        if (!$type) {
            return response()->json(['message' => "Aucun type d'exercice n'est disponible."], 422);
        }

        $concept = $error->error_category ?: ($error->skill_type ?: 'grammar');

        // Une génération par erreur et par heure : cliquer en boucle ne doit pas vider
        // le quota du fournisseur.
        $cacheKey = "error-similar:{$error->id}:" . now()->format('YmdH');

        try {
            $question = Cache::remember($cacheKey, now()->addHour(), function () use ($generator, $type, $exam, $profile, $error, $concept) {
                $exercise = $generator->generate($type, $exam, $profile->current_level ?? 'A1', [
                    'title' => $concept,
                    'concept' => $concept,
                    'native_language' => $profile->native_language ?? 'Français',
                    // On donne la question ratée pour que la nouvelle porte sur la même
                    // difficulté sans être la même phrase.
                    'previous_mistake' => $error->question_text,
                ]);

                $first = collect($exercise->questions ?? [])->first();
                if (!is_array($first)) {
                    return null;
                }

                return [
                    'exercise_id' => $exercise->id,
                    'type' => $first['type'] ?? $type->component_key,
                    'prompt' => $first['text'] ?? $first['prompt'] ?? $first['statement'] ?? '',
                    'options' => array_values(array_filter((array) ($first['options'] ?? []), fn ($o) => is_scalar($o))),
                    'correct_answer' => is_scalar($first['correct_answer'] ?? null) ? (string) $first['correct_answer'] : '',
                    'explanation' => is_string($first['explanation'] ?? null) ? $first['explanation'] : '',
                ];
            });
        } catch (\Throwable $e) {
            Log::warning('Exercice similaire : génération impossible', ['error_id' => $error->id, 'message' => $e->getMessage()]);
            $question = null;
        }

        if (!$question || $question['prompt'] === '' || $question['correct_answer'] === '') {
            Cache::forget($cacheKey); // un échec ne doit pas être resservi une heure durant

            return response()->json([
                'message' => "Aucun exercice n'a pu être écrit pour l'instant. Réessaie dans quelques minutes.",
            ], 503);
        }

        return response()->json(['question' => $question]);
    }

    // POST /errors/{error}/review - mark as reviewed with SM-2
    public function submitReview(Request $request, UserError $error)
    {
        // Sans cette garde, n'importe quel compte pouvait déclarer révisée l'erreur
        // d'un autre apprenant, en énumérant les identifiants : l'algorithme repoussait
        // la prochaine révision, voire marquait l'erreur acquise, et elle cessait de
        // revenir chez la personne concernée. Les pages de lecture des résultats
        // vérifiaient déjà le propriétaire ; ce point d'écriture ne le faisait pas.
        abort_unless($error->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'correct' => 'required|boolean',
        ]);

        // Pilier 3: Use SM-2 scheduling instead of simple counter
        $this->sm2->schedule($error, $validated['correct']);

        return response()->json([
            'success' => true,
            'mastered' => $error->mastered,
            'next_review_at' => $error->next_review_at?->format('Y-m-d H:i'),
            'interval_days' => $error->interval_days,
        ]);
    }
}
