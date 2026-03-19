<?php

namespace App\Http\Controllers;

use App\Models\ExerciseType;
use App\Models\LearningPathNode;
use App\Services\AI\ExerciseGeneratorService;
use Illuminate\Http\RedirectResponse;

class NodeStartController extends Controller
{
    public function __construct(protected ExerciseGeneratorService $generator) {}

    public function __invoke(LearningPathNode $node): RedirectResponse
    {
        $user = auth()->user();
        $profile = $user->profile?->load('targetExam.language');

        // Must have a target exam
        $exam = $profile?->targetExam;
        if (!$exam) {
            return redirect()->route('practice.index');
        }

        // Load exam sections with exercise types
        $exam->load('sections.exerciseTypes');

        // Map node skill_type to exercise type
        $skillType = $node->skill_type ?? 'reading';
        $level = $node->level ?? ($profile->current_level ?? 'B1');
        $nodeType = $node->node_type ?? 'lesson';

        // Types that need audio/image library — excluded from auto-generation
        $mediaTypes = ['note-completion', 'speaking-recorder', 'visual-task', 'diagram-label', 'speaking-response'];
        // Preferred auto-generation order
        $preferredKeys = $nodeType === 'practice'
            ? ['gap-fill', 'true-false-ng', 'mcq', 'matching']
            : ['mcq', 'true-false-ng', 'gap-fill', 'matching'];

        // Find an exercise type matching the skill type, excluding media-dependent ones
        $exerciseType = null;
        foreach ($exam->sections as $section) {
            if ($section->skill_type === $skillType) {
                $eligible = $section->exerciseTypes->filter(
                    fn($t) => !in_array($t->component_key, $mediaTypes)
                );
                foreach ($preferredKeys as $key) {
                    $exerciseType = $eligible->firstWhere('component_key', $key);
                    if ($exerciseType) break;
                }
                if (!$exerciseType) $exerciseType = $eligible->first();
                break;
            }
        }

        // Fallback: any non-media exercise type from the exam
        if (!$exerciseType) {
            $exerciseType = $exam->sections->flatMap->exerciseTypes
                ->filter(fn($t) => !in_array($t->component_key, $mediaTypes))
                ->first();
        }

        if (!$exerciseType) {
            return redirect()->route('practice.exam', $exam->id);
        }

        // Generate the exercise directly
        $exercise = $this->generator->generate($exerciseType, $exam, $level);

        // Mark node as in_progress
        \App\Models\UserLearningProgress::where('user_id', $user->id)
            ->where('node_id', $node->id)
            ->whereIn('status', ['available'])
            ->update(['status' => 'in_progress']);

        // Store node context for post-submission tracking
        session(['current_node_id' => $node->id]);

        return redirect()->route('exercise.show', $exercise->id);
    }
}
