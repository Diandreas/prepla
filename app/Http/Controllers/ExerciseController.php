<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use App\Models\LeaderboardEntry;
use App\Models\LearningPathNode;
use App\Models\UserExerciseAttempt;
use App\Models\UserLearningProgress;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExerciseController extends Controller
{
    protected \App\Services\ExerciseScoringService $scoringService;
    protected \App\Services\StreakService $streakService;
    protected \App\Services\ErrorSpacedRepetitionService $errorSm2;

    public function __construct(
        \App\Services\ExerciseScoringService $scoringService,
        \App\Services\StreakService $streakService,
        \App\Services\ErrorSpacedRepetitionService $errorSm2
    ) {
        $this->scoringService = $scoringService;
        $this->streakService = $streakService;
        $this->errorSm2 = $errorSm2;
    }

    public function submitSession(Request $request, LearningPathNode $node)
    {
        $user = auth()->user();
        $validated = $request->validate([
            'answers' => 'required|array',
            'time_spent' => 'nullable|integer',
            'exercise_ids' => 'nullable|array',
            'exercise_ids.*' => 'integer|exists:exercises,id',
        ]);

        $answers = $validated['answers'];
        $timeSpent = $validated['time_spent'] ?? 0;

        // Prefer the exact list of exercise IDs the player rendered (covers generic fallback
        // exercises not yet linked via node_id). Fall back to node_id lookup for legacy flow.
        if (!empty($validated['exercise_ids'])) {
            $exercises = \App\Models\Exercise::whereIn('id', $validated['exercise_ids'])->get();
        } else {
            $exercises = \App\Models\Exercise::where('node_id', $node->id)->get();
        }
        $sessionResults = [];
        $totalXp = 0;
        $totalCorrect = 0;
        $totalQuestions = 0;
        $totalAccuracy = 0;
        $exerciseCount = 0;

        foreach ($exercises as $exercise) {
            $exerciseAnswers = [];
            foreach ($exercise->questions as $index => $question) {
                $qId = $question['id'] ?? (string)$index;
                if (isset($answers[$qId])) {
                    $exerciseAnswers[$qId] = $answers[$qId];
                    $totalQuestions++;
                }
            }

            if (!empty($exerciseAnswers)) {
                $result = $this->scoringService->score($exercise, $exerciseAnswers);
                
                // Enregistrer l'essai pour chaque exercice du set
                UserExerciseAttempt::create([
                    'user_id' => $user->id,
                    'exercise_id' => $exercise->id,
                    'answers' => $exerciseAnswers,
                    'score' => $result['score'],
                    'accuracy_percent' => $result['accuracy'],
                    'time_spent' => round($timeSpent / max(1, count($exercises))),
                    'xp_earned' => $result['xp'],
                    'feedback' => $result['feedback'],
                ]);

                // Track errors for long-term review
                foreach ($result['feedback'] as $qFeedback) {
                    if (!($qFeedback['correct'] ?? false)) {
                        $questionData = collect($exercise->questions)->firstWhere('id', $qFeedback['question_id']);

                        $skillType = $exercise->exerciseType->section->skill_type ?? 'reading';
                        $slug = $exercise->exerciseType->slug;

                        // Pedagogical family drives the Review Center: 'concept' errors
                        // (grammar/vocab/writing…) can be re-practised; 'comprehension'
                        // errors (reading/listening on a passage) feed the diagnostic only.
                        $family = \App\Models\UserError::classifyFamily($slug, $skillType);

                        if ($family === 'comprehension') {
                            // A reading/listening mistake is about understanding a specific
                            // passage, NOT a grammar concept. Force the category to the actual
                            // comprehension skill so it never gets mislabelled "grammar" (the AI
                            // and the deriveCategory fallback both lean toward 'grammar').
                            $errorCategory = in_array($skillType, ['reading', 'listening'], true) ? $skillType : 'reading';
                            $errorSubcategory = $qFeedback['error_subcategory'] ?? null;
                        } elseif (!empty($qFeedback['error_category'])) {
                            // Concept error: prefer the AI-provided category, else derive it.
                            $errorCategory = $qFeedback['error_category'];
                            $errorSubcategory = $qFeedback['error_subcategory'] ?? null;
                        } else {
                            $lessonConcept = \App\Models\Lesson::where('node_id', $exercise->node_id)->value('concept');
                            [$errorCategory, $errorSubcategory] = \App\Models\UserError::deriveCategory($lessonConcept, $skillType, $slug);
                        }

                        // Store the explanation text so the Review Center can show *why*.
                        $explanationText = $qFeedback['explanation'] ?? null;
                        if (is_array($explanationText)) {
                            $explanationText = $explanationText['concept'] ?? json_encode($explanationText);
                        }

                        $error = \App\Models\UserError::updateOrCreate(
                            [
                                'user_id' => $user->id,
                                'exercise_id' => $exercise->id,
                                'question_id' => $qFeedback['question_id'],
                            ],
                            [
                                'question_text' => $questionData['text'] ?? $questionData['prompt'] ?? 'Exercice practice',
                                'user_answer' => is_array($exerciseAnswers[$qFeedback['question_id']] ?? '') ? json_encode($exerciseAnswers[$qFeedback['question_id']]) : (string)($exerciseAnswers[$qFeedback['question_id']] ?? ''),
                                'correct_answer' => is_array($qFeedback['correct_answer'] ?? '') ? json_encode($qFeedback['correct_answer']) : (string)($qFeedback['correct_answer'] ?? ''),
                                'explanation' => $explanationText,
                                'skill_type' => $skillType,
                                'exercise_type_slug' => $slug,
                                'error_category' => $errorCategory,
                                'subcategory' => $errorSubcategory,
                                'mastered' => false,
                            ]
                        );

                        // Pilier 3: Initialize SM-2 scheduling for new error
                        if ($error->wasRecentlyCreated) {
                            $this->errorSm2->initializeForNewError($error);
                        } else {
                            // Existing error encountered again — reset SM-2
                            $this->errorSm2->schedule($error, false);
                        }
                    } else {
                        // If it was corrected now, update SM-2 and mark as mastered if threshold met
                        $existingError = \App\Models\UserError::where('user_id', $user->id)
                            ->where('question_id', $qFeedback['question_id'])
                            ->first();

                        if ($existingError) {
                            $this->errorSm2->schedule($existingError, true);
                        }
                    }
                }

                $totalXp += $result['xp'];
                $totalCorrect += $result['score'];
                $totalAccuracy += $result['accuracy'];
                $exerciseCount++;
                
                $sessionResults[] = [
                    'exercise_id' => $exercise->id,
                    'title' => $exercise->title,
                    'score' => $result['score'],
                    'total' => count($exercise->questions),
                    'accuracy' => $result['accuracy'],
                    'xp' => $result['xp'],
                    'feedback' => $result['feedback'],
                ];
            }
        }

        // 1. Marquer le nœud comme complété (Legacy)
        $progress = UserLearningProgress::where('user_id', $user->id)
            ->where('node_id', $node->id)
            ->first();

        if ($progress) {
            $progress->update([
                'status' => 'completed',
                'exercises_done' => $progress->exercises_required,
            ]);

            // 2. Débloquer le nœud suivant (Legacy)
            $nextNode = LearningPathNode::where('exam_id', $node->exam_id)
                ->where(function($query) use ($node) {
                    $query->where('chapter_order', '>', $node->chapter_order)
                          ->orWhere(function($q) use ($node) {
                              $q->where('chapter_order', $node->chapter_order)
                                ->where('sort_order', '>', $node->sort_order);
                          });
                })
                ->orderBy('chapter_order')
                ->orderBy('sort_order')
                ->first();

            if ($nextNode) {
                UserLearningProgress::updateOrCreate(
                    ['user_id' => $user->id, 'node_id' => $nextNode->id],
                    ['status' => 'available']
                );
            }
        }

        // --- MASTERY GATE (Bloom): ≥80% required to advance ---
        // Below threshold → track failures; after 2+ consecutive failures the
        // NextLessonGenerator switches to a 'consolidation' variant (alternate
        // explanation, more scaffolding, easier examples).
        $sessionAccuracy = $totalQuestions > 0 ? ($totalCorrect / $totalQuestions) * 100 : 0;
        // 60% = pass. 80% was too punishing (forces redoing sessions over and over).
        // Aligned with the lesson quiz pass band (~2/3).
        $MASTERY_THRESHOLD = 60;
        $skeleton = \App\Models\CurriculumSkeleton::where('user_id', $user->id)->first();
        if ($skeleton) {
            // The objective being practiced is the one in 'current_practice', which is
            // usually *behind* current_objective_index (advanceToPractice already moved
            // the pointer to the next lesson). Target it explicitly so finishing a
            // practice actually marks it done and the journey progresses.
            $practiceIndex = $skeleton->practiceObjectiveIndex();

            // Fallback: if no objective is in its practice phase (e.g. the lesson was
            // only borderline-passed, or the user navigated straight to the node), map
            // this node back to its originating lesson objective so finishing the
            // practice still advances the journey instead of silently doing nothing.
            if ($practiceIndex === null) {
                $lessonIndex = \App\Models\Lesson::where('node_id', $node->id)
                    ->value('skeleton_objective_index');
                if ($lessonIndex !== null && isset(($skeleton->objectives ?? [])[$lessonIndex])) {
                    $practiceIndex = (int) $lessonIndex;
                }
            }

            if ($practiceIndex !== null) {
                if ($sessionAccuracy >= $MASTERY_THRESHOLD) {
                    $skeleton->completePractice($practiceIndex);
                } else {
                    // Strict mastery: stay on this objective + count the failure
                    $skeleton->consecutive_failures = ($skeleton->consecutive_failures ?? 0) + 1;
                    $skeleton->save();
                }
            }
        }

        // 3. Ajouter l'XP cumulé
        $user->profile?->increment('xp_total', $totalXp);
        $this->incrementLeaderboard($user->id, $totalXp);
        $this->streakService->recordActivity($user);

        // On stocke les résultats en session car Inertia n'aime pas les redirections complexes avec data
        session(['last_session_report' => [
            'node_title' => $node->title,
            'accuracy' => $totalQuestions > 0 ? ($totalCorrect / $totalQuestions) * 100 : 0,
            'xp_earned' => $totalXp,
            'time_spent' => $timeSpent,
            'details' => $sessionResults,
        ]]);

        return redirect()->route('node.session_result', $node->id);
    }

    public function sessionResult(LearningPathNode $node)
    {
        $report = session('last_session_report');
        if (!$report) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('exercises/session-report', [
            'node' => $node->load('exam.language'),
            'report' => $report,
            'userLevel' => auth()->user()?->profile?->current_level ?? 'A1',
        ]);
    }

    public function verifySingle(Request $request)
    {
        $validated = $request->validate([
            'exercise_id' => 'required|exists:exercises,id',
            'question_id' => 'required|string',
            'answer' => 'required', // Can be string or file
        ]);

        $exercise = \App\Models\Exercise::with(['exam.language', 'exerciseType'])->findOrFail($validated['exercise_id']);
        $questionId = $validated['question_id'];
        $answer = $request->file('answer') ?? $request->input('answer');

        // On simule un array answers pour le scoring service
        $answers = [$questionId => $answer];

        $result = $this->scoringService->score($exercise, $answers);

        // On récupère le feedback spécifique à cette question
        $questionFeedback = collect($result['feedback'])->firstWhere('question_id', $questionId);

        return response()->json([
            'correct' => $questionFeedback['correct'] ?? false,
            'accuracy' => $questionFeedback['accuracy'] ?? 0,
            'explanation' => $questionFeedback['explanation'] ?? '',
            'transcription' => $questionFeedback['transcription'] ?? null,
            'covered_points' => $questionFeedback['covered_points'] ?? [],
            'missing_points' => $questionFeedback['missing_points'] ?? [],
        ]);
    }

    public function show(Exercise $exercise): Response
    {
        $exercise->load(['exerciseType.section', 'exam.language']);

        return Inertia::render('exercise/show', [
            'exercise' => $exercise,
        ]);
    }

    public function submit(Request $request, Exercise $exercise)
    {
        $user = auth()->user();
        $validated = $request->validate([
            'answers' => 'required|array',
            'time_spent' => 'required|integer|min:0',
        ]);

        // Score the exercise
        $result = $this->scoringService->score($exercise, $validated['answers']);

        $attempt = UserExerciseAttempt::create([
            'user_id' => $user->id,
            'exercise_id' => $exercise->id,
            'answers' => $validated['answers'],
            'score' => $result['score'],
            'accuracy_percent' => $result['accuracy'],
            'time_spent' => $validated['time_spent'],
            'xp_earned' => $result['xp'],
            'feedback' => $result['feedback'],
        ]);

        // Update user XP
        if ($user instanceof \App\Models\User && $user->profile) {
            $user->profile->increment('xp_total', $result['xp']);
            $this->streakService->recordActivity($user);
        }

        // Mettre à jour le classement hebdomadaire
        $this->incrementLeaderboard($user->id, $result['xp']);

        // Update node progress if this exercise came from a node
        $nodeCompleted = false;
        $nodeId = session('current_node_id');
        if ($nodeId) {
            $userId = $user->id;
            $progress = UserLearningProgress::where('user_id', $userId)
                ->where('node_id', $nodeId)
                ->whereIn('status', ['in_progress', 'available'])
                ->first();

            if ($progress) {
                $progress->increment('exercises_done');
                $progress->refresh();

                if ($progress->exercises_done >= $progress->exercises_required) {
                    $progress->update(['status' => 'completed']);
                    $nodeCompleted = true;

                    // Unlock the next node for this user
                    $node = LearningPathNode::find($nodeId);
                    if ($node) {
                        $nextNode = LearningPathNode::where('exam_id', $node->exam_id)
                            ->where('sort_order', '>', $node->sort_order)
                            ->orderBy('sort_order')
                            ->first();

                        if ($nextNode) {
                            UserLearningProgress::where('user_id', $userId)
                                ->where('node_id', $nextNode->id)
                                ->where('status', 'locked')
                                ->update(['status' => 'available']);
                        }
                    }

                    session()->forget('current_node_id');
                    session(['last_node_id' => $nodeId]);
                }
            }
        }

        return redirect()->route('exercise.result', [
            'attempt' => $attempt,
            'node_completed' => $nodeCompleted ? 1 : 0,
            'node_id' => $nodeId,
        ]);
    }

    public function result(UserExerciseAttempt $attempt, \Illuminate\Http\Request $request): Response
    {
        $attempt->load(['exercise.exerciseType.section', 'exercise.exam.language']);

        // Load node progress if applicable
        $nodeProgress = null;
        $nodeId = $request->query('node_id') ?: session('last_node_id');
        $nodeCompleted = (bool) $request->query('node_completed', 0);

        if ($nodeId) {
            $progress = UserLearningProgress::where('user_id', auth()->id())
                ->where('node_id', $nodeId)
                ->first();
            if ($progress) {
                $nodeProgress = [
                    'node_id' => $nodeId,
                    'exercises_done' => $progress->exercises_done,
                    'exercises_required' => $progress->exercises_required,
                    'completed' => $nodeCompleted,
                ];
            }
        }

        return Inertia::render('exercise/result', [
            'attempt' => $attempt,
            'nodeProgress' => $nodeProgress,
        ]);
    }


    public function explainMistake(Request $request)
    {
        $validated = $request->validate([
            'prompt' => 'required|string',
            'user_answer' => 'required|string',
            'correct_answer' => 'required|string',
            'language' => 'required|string',
        ]);

        $explanation = $this->scoringService->explainMistake(
            $validated['prompt'] ?? '',
            $validated['user_answer'] ?? '',
            $validated['correct_answer'] ?? '',
            $validated['language'] ?? 'English'
        );

        return response()->json(['explanation' => $explanation]);
    }

    public function chatMistake(Request $request)
    {
        $validated = $request->validate([
            'messages' => 'required|array',
            'context' => 'required|array',
        ]);

        $mistral = app(\App\Services\AI\MistralService::class);
        
        $systemPrompt = "You are a helpful language tutor. The user is practicing for an exam.
        Context of the mistake:
        Question: {$validated['context']['prompt']}
        User Answer: {$validated['context']['user_answer']}
        Correct Answer: {$validated['context']['correct_answer']}
        Language: {$validated['context']['language']}

        Help the user understand their mistake specifically. Be concise and pedagogical.

        FORMATTING (very important — the chat renders Markdown):
        - Always answer in **Markdown**, never one big block of plain text.
        - Use short paragraphs, **bold** for key words, and bullet lists for steps or rules.
        - When you explain a rule, a conjugation, a comparison, or several cases, USE A
          MARKDOWN TABLE (| col | col |) instead of prose — it reads like a clear schema.
        - Put example sentences on their own lines, with the key part in **bold**.
        - Keep it focused: a couple of short paragraphs + one table or list is ideal.
        - Reply in the user's language (French unless they write in another language).";

        $messages = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            $validated['messages']
        );

        $response = $mistral->chatRaw($messages);

        return response()->json(['message' => $response]);
    }

    private function incrementLeaderboard(int $userId, int $xp): void
    {
        if ($xp <= 0) return;

        $periodKey = now()->format('Y-\WW'); // ex: 2026-W12

        LeaderboardEntry::updateOrCreate(
            ['user_id' => $userId, 'period_type' => 'weekly', 'period_key' => $periodKey],
            ['xp' => 0] // valeur initiale si création
        );

        LeaderboardEntry::where('user_id', $userId)
            ->where('period_type', 'weekly')
            ->where('period_key', $periodKey)
            ->increment('xp', $xp);
    }
}
