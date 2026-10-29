<?php

use App\Models\Exam;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\User;
use App\Models\UserExerciseAttempt;

/**
 * Retirer du contenu est la seule operation qu'on ne peut pas defaire.
 *
 * Deux regles la bordent, et ce sont elles que ces tests gardent : on ne touche
 * jamais a un exercice qu'un apprenant a ouvert, et on ne supprime que sur la
 * foi d'une structure verifiee dans le document de l'organisme.
 */
beforeEach(function () {
    $this->seed(\Database\Seeders\LanguageSeeder::class);
    $this->seed(\Database\Seeders\ExamSeeder::class);
});

function exerciceDe(string $examen, string $section, string $slug, int $questions, string $niveau = 'B1'): Exercise
{
    $exam = Exam::where('slug', $examen)->sole();
    $type = ExerciseType::whereHas('section', fn ($q) => $q->where('exam_id', $exam->id)
        ->where('slug', $section))->where('slug', $slug)->sole();

    $liste = [];
    foreach (range(1, $questions) as $i) {
        $liste[] = ['id' => "q{$i}", 'type' => $type->component_key, 'text' => "Frage {$i}?",
            'options' => ['A', 'B', 'C'], 'correct_answer' => 'A'];
    }

    return Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'exam_section_id' => $type->section_id,
        'difficulty' => $niveau, 'is_ai_generated' => true, 'xp_reward' => 10,
        'content' => ['title' => 'Essai'], 'questions' => $liste,
    ]);
}

test('un exercice au mauvais format et jamais ouvert est retire', function () {
    // Le Goethe B1 Lesen Teil 2 compte six questions a choix multiple.
    $trompeur = exerciceDe('goethe', 'lesen', 'mcq', 3);

    $this->artisan('prepla:retirer-non-conformes', ['--examen' => 'goethe', '--vraiment' => true])
        ->assertSuccessful();

    expect(Exercise::find($trompeur->id))->toBeNull();
});

test('un exercice conforme n est jamais retire', function () {
    $bon = exerciceDe('goethe', 'lesen', 'mcq', 6);

    $this->artisan('prepla:retirer-non-conformes', ['--examen' => 'goethe', '--vraiment' => true]);

    expect(Exercise::find($bon->id))->not->toBeNull();
});

test('un exercice qu un apprenant a ouvert est garde, meme au mauvais format', function () {
    // Sa tentative y renvoie, son historique le cite, sa revision le reprend :
    // le supprimer effacerait du travail reel.
    $trompeur = exerciceDe('goethe', 'lesen', 'mcq', 3);
    UserExerciseAttempt::create([
        'user_id' => User::factory()->create()->id,
        'exercise_id' => $trompeur->id,
        'score' => 2,
        'answers' => ['q1' => 'A'],
    ]);

    $this->artisan('prepla:retirer-non-conformes', ['--examen' => 'goethe', '--vraiment' => true]);

    expect(Exercise::find($trompeur->id))->not->toBeNull();
});

test('a blanc, rien n est supprime', function () {
    // Le mode a blanc est le defaut : il faut le demander pour ecrire.
    $trompeur = exerciceDe('goethe', 'lesen', 'mcq', 3);

    $this->artisan('prepla:retirer-non-conformes', ['--examen' => 'goethe'])->assertSuccessful();

    expect(Exercise::find($trompeur->id))->not->toBeNull();
});

test('avec --meme-tentes, un exercice ouvert est retire et sa tentative avec lui', function () {
    // La cle etrangere des tentatives est en cascade : effacer l exercice efface
    // le travail fait dessus. C est voulu ici — on ne veut plus aucun exercice au
    // mauvais format — mais la commande doit l annoncer, pas le faire en silence.
    $trompeur = exerciceDe('goethe', 'lesen', 'mcq', 3);
    UserExerciseAttempt::create([
        'user_id' => User::factory()->create()->id,
        'exercise_id' => $trompeur->id,
        'score' => 2,
        'answers' => ['q1' => 'A'],
    ]);

    $this->artisan('prepla:retirer-non-conformes', [
        '--examen' => 'goethe', '--vraiment' => true, '--meme-tentes' => true,
    ])->expectsOutputToContain('1 tentative(s) de 1 apprenant(s)')->assertSuccessful();

    expect(Exercise::find($trompeur->id))->toBeNull()
        ->and(UserExerciseAttempt::count())->toBe(0);
});

test('sans --meme-tentes, la tentative reste intacte', function () {
    $trompeur = exerciceDe('goethe', 'lesen', 'mcq', 3);
    UserExerciseAttempt::create([
        'user_id' => User::factory()->create()->id,
        'exercise_id' => $trompeur->id,
        'score' => 2,
        'answers' => ['q1' => 'A'],
    ]);

    $this->artisan('prepla:retirer-non-conformes', ['--examen' => 'goethe', '--vraiment' => true]);

    expect(UserExerciseAttempt::count())->toBe(1);
});
