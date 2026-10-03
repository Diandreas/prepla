<?php

use App\Models\CurriculumSkeleton;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\LearningPathNode;
use App\Models\LevelAssessment;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Http;

/**
 * Le parcours complet d'un examen de palier, tel qu'un apprenant le vit : il ouvre
 * l'épreuve, la manque, reçoit des reprises sur ce qu'il n'a pas compris, les fait,
 * repasse l'épreuve et monte de niveau.
 *
 * C'est la situation exacte d'un apprenant de production dont le palier A1 était
 * terminé : son examen venait de s'ouvrir, et rien de cette chaîne n'avait encore
 * été exercé en vrai.
 */
test('un apprenant manque son examen de palier, reprend, puis monte de niveau', function () {
    // L'IA rend des questions CONFORMES au type demandé : le générateur rejette les
    // questions mal formées pour leur composant, et il a raison de le faire.
    Http::fake(function ($request) {
        $prompt = $request->body();
        // Chaque composant attend sa forme : un trou veut un mot, une completion de
        // phrase veut des choix et une lettre, comme le QCM.
        $trous = str_contains($prompt, 'gap-fill');
        $completion = str_contains($prompt, 'sentence-completion');

        $questions = collect(range(1, 3))->map(fn ($i) => $trous
            ? [
                'id' => "q{$i}",
                'type' => 'gap-fill',
                'text' => "Ich _______ Anna, und du ? (phrase {$i})",
                'correct_answer' => 'heiße',
                'explanation' => "« heißen » se conjugue « ich heiße ».",
                'error_category' => 'grammar.tense',
            ]
            : [
                'id' => "q{$i}",
                'type' => $completion ? 'sentence-completion' : 'mcq',
                'text' => $completion ? "Ich ... Anna. (phrase {$i})" : "Wie heißt du ? (question {$i})",
                'options' => ['Ich heiße Anna', 'Ich bin müde', 'Es regnet', 'Guten Tag'],
                'correct_answer' => 'A',
                'explanation' => 'On répond à « wie heißt du » par son prénom.',
                'error_category' => 'grammar.tense',
            ])->all();

        return Http::response(['choices' => [['message' => ['content' => json_encode([
            'content' => ['title' => 'Examen de niveau A1'],
            'questions' => $questions,
        ])]]]]);
    });

    $language = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'goethe-a1', 'name' => 'Goethe A1']);
    $section = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'grammar', 'name' => 'Grammar', 'skill_type' => 'grammar']);
    foreach (['mcq', 'gap-fill', 'sentence-completion'] as $key) {
        ExerciseType::create([
            'section_id' => $section->id, 'slug' => $key, 'name' => $key,
            'skill_type' => 'grammar', 'component_key' => $key,
        ]);
    }

    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'target_exam_id' => $exam->id, 'current_level' => 'A1',
        'native_language' => 'Français', 'onboarding_completed_at' => now(),
    ]);

    // Palier A1 terminé, palier A2 devant : la situation de l'apprenant concerné.
    $skeleton = CurriculumSkeleton::create([
        'user_id' => $user->id, 'exam_id' => $exam->id,
        'objectives' => [
            ['order' => 0, 'title' => 'Se présenter', 'concept' => 'grammar.basic', 'level' => 'A1', 'status' => 'done', 'priority' => 'normal'],
            ['order' => 1, 'title' => 'Saluer', 'concept' => 'grammar.basic', 'level' => 'A1', 'status' => 'done', 'priority' => 'normal'],
            ['order' => 2, 'title' => 'Raconter au passé', 'concept' => 'grammar.tense', 'level' => 'A2', 'status' => 'pending', 'priority' => 'normal'],
        ],
        'current_objective_index' => 2, 'consecutive_successes' => 0, 'consecutive_failures' => 0,
    ]);
    $skeleton->ensureLevelExams();

    expect($skeleton->fresh()->pendingLevelExam()['level'])->toBe('A1');

    // ─── 1. Il ouvre l'épreuve ───
    $this->actingAs($user)->get(route('level.exam', 'A1'))->assertRedirect();

    $node = LearningPathNode::where('exam_id', $exam->id)->where('node_type', 'level_exam')->sole();
    expect($node->level)->toBe('A1');

    $exercises = \App\Models\Exercise::where('node_id', $node->id)->get();
    expect($exercises)->toHaveCount(3); // trois exercices, comme une séance

    // Le player s'ouvre sur l'examen, sans rien y ajouter.
    $this->get(route('node.start', $node))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('exercises/player')->has('exercises', 3));

    // ─── 2. Il le manque ───
    $answers = $exercises->mapWithKeys(fn ($exercise) => [
        $exercise->id => collect($exercise->questions)
            ->mapWithKeys(fn ($question) => [$question['id'] => 'zzz']) // tout faux
            ->all(),
    ])->all();

    $this->post(route('exercise.submit_session', $node), [
        'exercise_ids' => $exercises->pluck('id')->all(),
        'answers_by_exercise' => $answers,
        'time_spent' => 300,
    ])->assertRedirect();

    $skeleton = $skeleton->fresh();
    $reprises = collect($skeleton->objectives)->where('is_remedial', true);

    expect($user->profile->fresh()->current_level)->toBe('A1') // pas promu
        ->and(LevelAssessment::count())->toBe(0)
        ->and($reprises)->not->toBeEmpty()
        ->and($skeleton->currentObjective()['is_remedial'])->toBeTrue()
        // L'épreuve se referme le temps de la remédiation.
        ->and($skeleton->pendingLevelExam())->toBeNull();

    // ─── 3. Il fait ses reprises ───
    $objectives = $skeleton->objectives;
    foreach ($objectives as $index => $objective) {
        if (($objective['is_remedial'] ?? false) === true) {
            $objectives[$index]['status'] = 'done';
        }
    }
    $skeleton->objectives = $objectives;
    $skeleton->save();

    // L'épreuve revient d'elle-même.
    expect($skeleton->fresh()->pendingLevelExam()['level'])->toBe('A1');

    // ─── 4. Il la repasse et la réussit ───
    $answers = $exercises->mapWithKeys(fn ($exercise) => [
        $exercise->id => collect($exercise->questions)
            ->mapWithKeys(fn ($question) => [$question['id'] => $question['correct_answer']])
            ->all(),
    ])->all();

    $this->post(route('exercise.submit_session', $node), [
        'exercise_ids' => $exercises->pluck('id')->all(),
        'answers_by_exercise' => $answers,
        'time_spent' => 240,
    ])->assertRedirect();

    $skeleton = $skeleton->fresh();

    expect($user->profile->fresh()->current_level)->toBe('A2') // promu
        ->and(LevelAssessment::where('user_id', $user->id)->value('assessed_level'))->toBe('A2')
        ->and(LevelAssessment::where('user_id', $user->id)->value('assessment_type'))->toBe('boss_test')
        // L'examen A1 est passé ; le suivant attend la fin du palier A2.
        ->and(collect($skeleton->objectives)->firstWhere('level', 'A1')['status'])->toBe('done')
        ->and($skeleton->pendingLevelExam())->toBeNull();
});
