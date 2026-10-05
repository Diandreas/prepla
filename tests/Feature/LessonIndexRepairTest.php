<?php

use App\Models\CurriculumSkeleton;
use App\Models\Exam;
use App\Models\Language;
use App\Models\LearningPathNode;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

test('la reparation des anciens liens est explicite sauvegardee et ne touche pas la progression', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $language = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'goethe', 'name' => 'Goethe']);
    $node = LearningPathNode::create(['exam_id' => $exam->id, 'title' => 'Le passé', 'node_type' => 'lesson',
        'level' => 'A2', 'sort_order' => 1, 'chapter_order' => 1]);
    $path = CurriculumSkeleton::create(['user_id' => $user->id, 'exam_id' => $exam->id,
        'current_objective_index' => 1, 'objectives' => [
            ['concept' => 'level_exam.a1', 'is_level_exam' => true, 'level' => 'A1', 'status' => 'done'],
            ['concept' => 'grammar.past', 'level' => 'A2', 'status' => 'current'],
        ]]);
    $lesson = Lesson::create(['user_id' => $user->id, 'node_id' => $node->id,
        'skeleton_objective_index' => 0, 'concept' => 'grammar.past', 'title' => 'Le passé', 'status' => 'published',
        'theory_markdown' => '## Le passé']);
    $before = $path->fresh()->toArray();
    $this->artisan('prepla:repair-lesson-indexes')->assertSuccessful();
    expect($lesson->fresh()->skeleton_objective_index)->toBe(0);
    $this->artisan('prepla:repair-lesson-indexes --apply')->assertSuccessful();
    expect($lesson->fresh()->skeleton_objective_index)->toBe(1)
        ->and($path->fresh()->toArray())->toBe($before)
        ->and(Storage::disk('local')->allFiles('qa'))->toHaveCount(1);
    $this->artisan('prepla:repair-lesson-indexes --apply')->assertSuccessful();
    expect(Storage::disk('local')->allFiles('qa'))->toHaveCount(1);
});
