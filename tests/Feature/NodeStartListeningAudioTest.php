<?php

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\LearningPathNode;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\AI\ExerciseGeneratorService;
use App\Services\AI\TtsAudioGenerator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

test('a listening link whose file disappeared is rebuilt, or dropped when the provider fails', function () {
    Http::preventStrayRequests();
    Storage::fake('public');
    Storage::disk('public')->put('exercise-audio/present.mp3', 'audio');

    $language = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'audio-exam', 'name' => 'Audio exam']);
    $section = ExamSection::create([
        'exam_id' => $exam->id, 'slug' => 'listening', 'name' => 'Listening', 'skill_type' => 'listening',
    ]);
    $listening = ExerciseType::create([
        'section_id' => $section->id, 'slug' => 'note-completion', 'name' => 'Notes',
        'skill_type' => 'listening', 'component_key' => 'note-completion',
    ]);
    $reading = ExerciseType::create([
        'section_id' => $section->id, 'slug' => 'mcq', 'name' => 'MCQ', 'skill_type' => 'reading', 'component_key' => 'mcq',
    ]);
    $node = LearningPathNode::create([
        'exam_id' => $exam->id, 'sort_order' => 1, 'title' => 'Begrüßungen', 'node_type' => 'lesson', 'level' => 'A1',
    ]);

    $note = fn (string $id, string $text, string $url) => [
        'id' => $id, 'type' => 'note-completion', 'audio_text' => $text, 'audio_url' => $url,
        'notes' => [['label' => 'Gruß', 'value' => '']], 'correct_answers' => ['0' => $text],
    ];
    $present = Storage::disk('public')->url('exercise-audio/present.mp3');
    $exercise = Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => $listening->id, 'exam_section_id' => $section->id,
        'node_id' => $node->id, 'order_in_node' => 1, 'difficulty' => 'A1', 'content' => [],
        'questions' => [
            $note('q1', 'Guten Tag', $present),
            $note('q2', 'Auf Wiedersehen', Storage::disk('public')->url('exercise-audio/missing.mp3')),
            $note('q3', 'Tschüss', Storage::disk('public')->url('exercise-audio/lost.mp3')),
        ],
    ]);
    // Three linked exercises: the session never falls back to AI generation.
    foreach ([2, 3] as $order) {
        Exercise::create([
            'exam_id' => $exam->id, 'exercise_type_id' => $reading->id, 'exam_section_id' => $section->id,
            'node_id' => $node->id, 'order_in_node' => $order, 'difficulty' => 'A1', 'content' => [],
            'questions' => [['id' => 'q1', 'type' => 'mcq', 'text' => 'Hallo?', 'options' => ['Ja', 'Nein'], 'correct_answer' => 'A', 'explanation' => 'Oui.']],
        ]);
    }

    $rebuilt = Storage::disk('public')->url('exercise-audio/rebuilt.mp3');
    $this->mock(ExerciseGeneratorService::class, fn ($mock) => $mock->shouldNotReceive('generate'));
    // Only the two dead links may reach the provider; the file still on disk must not.
    $this->mock(TtsAudioGenerator::class, function ($mock) use ($rebuilt) {
        $mock->shouldReceive('generateForQuestion')->once()->withArgs(fn (array $question) => $question['id'] === 'q2')->andReturn($rebuilt);
        $mock->shouldReceive('generateForQuestion')->once()->withArgs(fn (array $question) => $question['id'] === 'q3')->andReturnNull();
    });

    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'target_exam_id' => $exam->id, 'current_level' => 'A1', 'onboarding_completed_at' => now(),
    ]);

    $this->actingAs($user)->get(route('node.start', $node))->assertOk();

    $questions = collect($exercise->fresh()->questions)->keyBy('id');
    expect($questions['q1']['audio_url'])->toBe($present)
        ->and($questions['q2']['audio_url'])->toBe($rebuilt)
        ->and($questions['q3'])->not->toHaveKey('audio_url');
});
