<?php

use App\Models\Exam;
use App\Models\ExamBlueprint;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\AI\ExerciseGeneratorService;
use Illuminate\Support\Facades\Http;

/**
 * S'entrainer a UNE partie d'epreuve doit donner CETTE partie.
 *
 * Plusieurs parties d'un meme module partagent souvent le meme format de
 * reponse : au Goethe B1, le Lesen Teil 1 et le Teil 4 sont tous deux des
 * vrai-faux. Le vivier d'exercices ne regardait que le format, si bien que
 * l'apprenant qui demandait le Teil 4 — sept courriers de lecteurs, pour ou
 * contre — recevait le Teil 1, et rien ne le lui disait.
 */
beforeEach(function () {
    Http::preventStrayRequests();
    Http::fake();

    $this->seed(\Database\Seeders\LanguageSeeder::class);
    $this->seed(\Database\Seeders\ExamSeeder::class);
});

/** Un exercice de pratique libre, rattache a une partie precise. */
function exercicePourLaPartie(Exam $exam, ExerciseType $type, int $partie, string $titre): Exercise
{
    return Exercise::create([
        'exam_id' => $exam->id,
        'exercise_type_id' => $type->id,
        'exam_section_id' => $type->section_id,
        'difficulty' => 'B1',
        'is_ai_generated' => false,
        'xp_reward' => 10,
        'content' => ['title' => $titre, 'exam_part' => $partie, 'exam_part_name' => "Lesen, Teil {$partie}"],
        'questions' => [[
            'id' => "p{$partie}-q1", 'type' => 'true-false',
            'text' => 'Eine Aussage.', 'correct_answer' => 'Richtig',
            'answer_options' => ['Richtig', 'Falsch'],
        ]],
    ]);
}

function apprenantDuNiveau(Exam $exam): User
{
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'target_exam_id' => $exam->id,
        'current_level' => 'B1',
        'onboarding_completed_at' => now(),
    ]);

    return $user;
}

test('la pratique d une partie precise sert un exercice de cette partie', function () {
    $exam = Exam::where('slug', 'goethe')->sole();
    $type = ExerciseType::whereHas('section', fn ($q) => $q->where('exam_id', $exam->id)
        ->where('slug', 'lesen'))->where('slug', 'true-false')->sole();

    $teil1 = exercicePourLaPartie($exam, $type, 1, 'Teil 1');
    $teil4 = exercicePourLaPartie($exam, $type, 4, 'Teil 4');

    $this->actingAs(apprenantDuNiveau($exam));
    $this->mock(ExerciseGeneratorService::class, fn ($mock) => $mock->shouldNotReceive('generate'));

    // Le vivier tire au hasard : une seule requete pourrait tomber juste par
    // chance. Dix de suite ne le peuvent pas.
    foreach (range(1, 10) as $essai) {
        $this->get(route('practice.drill.type', [$exam, $type]).'?partie=4')
            ->assertRedirect(route('exercise.show', $teil4));
    }

    // Le Teil 1 existe toujours : on l'a ecarte, pas supprime.
    expect(Exercise::find($teil1->id))->not->toBeNull();
});

test('sans exercice pour la partie demandee, le format fait l appoint', function () {
    // Mieux vaut un exercice du bon format qu'un ecran d'echec : l'apprenant
    // travaille quand meme sa competence, et l'IA n'est pas appelee pour rien.
    $exam = Exam::where('slug', 'goethe')->sole();
    $type = ExerciseType::whereHas('section', fn ($q) => $q->where('exam_id', $exam->id)
        ->where('slug', 'lesen'))->where('slug', 'true-false')->sole();

    $teil1 = exercicePourLaPartie($exam, $type, 1, 'Teil 1');

    $this->actingAs(apprenantDuNiveau($exam));
    $this->mock(ExerciseGeneratorService::class, fn ($mock) => $mock->shouldNotReceive('generate'));

    $this->get(route('practice.drill.type', [$exam, $type]).'?partie=4')
        ->assertRedirect(route('exercise.show', $teil1));
});
