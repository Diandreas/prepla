<?php

namespace App\Http\Controllers;

use App\Models\ExerciseType;
use App\Models\UserError;
use App\Services\AI\ExerciseGeneratorService;
use App\Services\ErrorReviewItems;
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
        // Les erreurs maitrisees restent listees (apres les autres) : c'est la
        // seulement que leur bonne reponse est reaffichee.
        $errors = UserError::concept($user->id, true)
            ->orderBy('mastered')
            ->orderByDesc('created_at')
            ->paginate(20);
        // Normalise field names for the frontend (reviewed_count → review_count,
        // question_text → prompt) and expose the pedagogical family.
        $errors->getCollection()->transform(fn ($e) => [
            'id' => $e->id,
            'skill_type' => $e->skill_type,
            'prompt' => $e->question_text,
            'user_answer' => $e->user_answer,
            // Plus de reponse attendue ici : la page de revision la reposerait avec
            // la solution deja dans le navigateur. La correction vient apres la reponse.
            'explanation' => $e->explanation,
            // Une erreur maitrisee n'a plus de rappel a gacher : on remontre la solution.
            'correct_answer' => $e->mastered ? $e->correct_answer : null,
            'mastered' => $e->mastered,
            'review_count' => $e->reviewed_count ?? 0,
            'next_review_at' => $e->next_review_at,
            'created_at' => $e->created_at,
            'family' => UserError::classifyFamily($e->exercise_type_slug, $e->skill_type),
        ]);

        // Les tuiles cliquables ne comptent que ce qui est REELLEMENT rejouable. Elles
        // comptaient toutes les erreurs : une tuile « Lecture 12 » menait a une
        // revision toujours vide, puisqu'une question de comprehension ne peut pas
        // etre reposee sans son texte d'origine.
        $errorsBySkill = UserError::concept($user->id)
            ->selectRaw('skill_type, count(*) as count')
            ->groupBy('skill_type')
            ->pluck('count', 'skill_type');

        // Les erreurs de comprehension restent affichees, mais comme bilan : elles
        // disent a l'apprenant ou il bute, sans promettre une revision impossible.
        $comprehensionBySkill = UserError::where('user_id', $user->id)
            ->where('mastered', false)
            ->whereIn('skill_type', ['reading', 'listening', 'speaking'])
            ->selectRaw('skill_type, count(*) as count')
            ->groupBy('skill_type')
            ->pluck('count', 'skill_type');

        // Pilier 4: Error category stats
        $errorsByCategory = UserError::categoryStats($user->id);

        // Pilier 3: Due errors count — meme perimetre que la seance de revision,
        // sinon le bouton « Reviser (N dues) » est un cul-de-sac.
        $dueForReviewCount = UserError::conceptDue($user->id)->count();

        return Inertia::render('errors/index', [
            'errors' => $errors,
            'errorsBySkill' => $errorsBySkill,
            'comprehensionBySkill' => $comprehensionBySkill,
            'errorsByCategory' => $errorsByCategory,
            'dueForReviewCount' => $dueForReviewCount,
        ]);
    }

    // GET /errors/practice - personalized error review session.
    // Only CONCEPT errors are re-practised here (grammar/vocab/writing…): re-testing
    // a comprehension question would just make the learner memorise that one passage.
    public function practice(Request $request, ErrorReviewItems $items)
    {
        $user = $request->user();
        $skillType = $request->query('skill');

        // Pilier 3: SM-2 due errors first, then recent unmastered — concept only.
        // Comprehension errors (reading/listening on a passage) are excluded: they
        // can't be re-posed without the original text.
        $dueErrors = UserError::conceptDue($user->id)
            ->when($skillType, fn($q) => $q->where('skill_type', $skillType))
            ->limit(10)
            ->get();

        if ($dueErrors->isEmpty()) {
            // Le repli prend TOUT, y compris les erreurs sans reponse attendue : la
            // page les presente alors en lecture (enonce, reponse donnee,
            // explication) au lieu de demander de les retaper. Les ecarter ici
            // aurait vide la tuile « expression ecrite » de la page des erreurs.
            $dueErrors = UserError::concept($user->id)
                ->when($skillType, fn($q) => $q->where('skill_type', $skillType))
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();
        }

        return Inertia::render('errors/practice', [
            // La reponse attendue ne part PAS au navigateur : le serveur corrige
            // dans submitReview. On dit seulement s'il y a quelque chose a retrouver
            // (une redaction n'a pas de reponse attendue : elle est montree en lecture).
            'errors' => $dueErrors->map(function ($e) use ($items, $user) {
                $rappel = trim((string) $e->correct_answer) !== '';
                $item = $rappel ? $items->pose($e, $user) : ['prompt' => $e->question_text, 'options' => [], 'variant' => false];

                return [
                    'id' => $e->id,
                    'skill_type' => $e->skill_type,
                    'prompt' => $item['prompt'],
                    'options' => $item['options'],
                    'variant' => $item['variant'],
                    'recall' => $rappel,
                    'user_answer' => $e->user_answer,
                    // L'explication d'une redaction est montree sans rappel ; pour
                    // le reste elle arrive avec la correction.
                    'explanation' => $rappel ? null : $e->explanation,
                    'mastered' => $e->mastered,
                    'review_count' => $e->reviewed_count,
                    'created_at' => $e->created_at,
                ];
            })->values(),
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
                    // Les choix sont LUS, pas filtres : les jeter recalculait les
                    // positions et la lettre attendue designait alors un autre choix.
                    'options' => \App\Models\Exercise::optionList($first['options'] ?? []),
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
    public function submitReview(Request $request, UserError $error, ErrorReviewItems $items)
    {
        // Sans cette garde, n'importe quel compte pouvait déclarer révisée l'erreur
        // d'un autre apprenant, en énumérant les identifiants : l'algorithme repoussait
        // la prochaine révision, voire marquait l'erreur acquise, et elle cessait de
        // revenir chez la personne concernée. Les pages de lecture des résultats
        // vérifiaient déjà le propriétaire ; ce point d'écriture ne le faisait pas.
        abort_unless($error->user_id === $request->user()->id, 403);

        // La page envoie la REPONSE, plus un « juste/faux » qu'elle calculait
        // elle-meme : c'est le serveur qui corrige, contre la question vraiment posee.
        $validated = $request->validate([
            'answer' => 'required|string|max:1000',
        ]);

        // Une seule correction par question posee : sinon une premiere reponse
        // quelconque renvoyait la solution, et la seconde, juste, comptait.
        $pose = $items->consommer($error);
        if ($pose === null) {
            return response()->json(['message' => 'Cette question a deja ete corrigee. Recharge la question pour la refaire.'], 409);
        }

        $attendu = $items->attendu($error, $pose);
        if (trim($attendu['expected']) === '') {
            return response()->json(['message' => "Cette erreur n'a pas de reponse a retrouver."], 422);
        }

        $juste = ErrorReviewItems::estJuste($validated['answer'], $attendu['expected']);

        // Pilier 3: Use SM-2 scheduling instead of simple counter
        $this->sm2->schedule($error, $juste);

        return response()->json([
            'success' => true,
            'correct' => $juste,
            'correct_answer' => $attendu['expected'],
            'explanation' => $attendu['explanation'],
            'mastered' => $error->mastered,
            'next_review_at' => $error->next_review_at?->format('Y-m-d H:i'),
            'interval_days' => $error->interval_days,
        ]);
    }
}
