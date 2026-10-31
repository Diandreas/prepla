<?php

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\User;
use App\Models\UserError;
use App\Models\UserProfile;

/**
 * Un exercice fait hors du parcours — entrainement, epreuve — etait corrige,
 * affiche, puis oublie : seules les seances du parcours retenaient les erreurs.
 * Le centre de revision et le suivi du centre ignoraient donc tout ce qu'un
 * apprenant ratait a l'entrainement.
 */
function exerciceHorsParcours(): array
{
    $langue = Language::create(['slug' => 'german', 'name' => 'Allemand', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::create(['language_id' => $langue->id, 'slug' => 'goethe', 'name' => 'Goethe']);
    $section = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'grammar', 'name' => 'Grammatik', 'skill_type' => 'grammar']);
    $type = ExerciseType::create(['section_id' => $section->id, 'slug' => 'mcq', 'name' => 'QCM', 'skill_type' => 'grammar', 'component_key' => 'mcq']);
    $exercice = Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'exam_section_id' => $section->id,
        'difficulty' => 'A2', 'content' => [],
        'questions' => [[
            'id' => 'q1', 'type' => 'mcq', 'text' => 'Ich ___ gestern ins Kino gegangen.',
            'options' => ['habe', 'bin', 'war'], 'correct_answer' => 'B',
            'explanation' => 'Verbe de mouvement : parfait avec « sein ».',
        ]],
    ]);

    $eleve = User::factory()->create();
    UserProfile::factory()->for($eleve)->create(['target_exam_id' => $exam->id, 'current_level' => 'A2', 'onboarding_completed_at' => now()]);

    return [$exercice, $eleve];
}

test('une erreur faite a l entrainement est retenue', function () {
    [$exercice, $eleve] = exerciceHorsParcours();

    $this->actingAs($eleve)->post(route('exercise.submit', $exercice), [
        'answers' => ['q1' => 'A'],
        'time_spent' => 20,
    ])->assertRedirect();

    $erreur = UserError::where('user_id', $eleve->id)->sole();

    expect($erreur->exercise_id)->toBe($exercice->id)
        ->and($erreur->user_answer)->toBe('A')
        // La reponse attendue en clair, comme pour une seance du parcours.
        ->and($erreur->correct_answer)->toBe('B) bin')
        ->and($erreur->explanation)->toBe('Verbe de mouvement : parfait avec « sein ».')
        ->and($erreur->mastered)->toBeFalse();
});

test('la reussir ensuite fait avancer sa revision', function () {
    [$exercice, $eleve] = exerciceHorsParcours();

    $this->actingAs($eleve)->post(route('exercise.submit', $exercice), ['answers' => ['q1' => 'A'], 'time_spent' => 20]);
    $avant = UserError::where('user_id', $eleve->id)->sole();

    // Une autre tentative, plus tard : la meme question, juste cette fois.
    $this->travel(5)->minutes();
    $this->actingAs($eleve)->post(route('exercise.submit', $exercice), ['answers' => ['q1' => 'B'], 'time_spent' => 15]);

    $apres = $avant->fresh();
    expect($apres->reviewed_count)->toBeGreaterThan($avant->reviewed_count ?? 0);
});
