<?php

use App\Models\Assignment;
use App\Models\AssignmentItem;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Models\UserExerciseAttempt;
use App\Models\UserProfile;

/**
 * Le serveur savait modifier une classe depuis le début, mais aucun bouton n'y
 * menait. Et un exercice raté restait dans la liste de l'enseignant pour toujours,
 * faute de pouvoir le retirer.
 */
function centerWithTeacher(): array
{
    $language = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $goethe = Exam::create(['language_id' => $language->id, 'slug' => 'goethe-a2', 'name' => 'Goethe A2']);
    $testdaf = Exam::create(['language_id' => $language->id, 'slug' => 'testdaf', 'name' => 'TestDaF']);
    $section = ExamSection::create(['exam_id' => $goethe->id, 'slug' => 'grammar', 'name' => 'Grammar', 'skill_type' => 'grammar']);
    $type = ExerciseType::create([
        'section_id' => $section->id, 'slug' => 'mcq', 'name' => 'MCQ', 'skill_type' => 'grammar', 'component_key' => 'mcq',
    ]);

    $center = LanguageCenter::create([
        'name' => 'Cours de Zidane', 'slug' => 'cours-de-zidane',
        'owner_email' => 'zidane@exemple.test', 'is_active' => true,
    ]);

    $teacher = User::factory()->create();
    UserProfile::factory()->for($teacher)->create(['onboarding_completed_at' => now()]);
    $center->members()->attach($teacher->id, ['role' => 'center_admin', 'joined_at' => now()]);

    $classroom = $center->classrooms()->create([
        'name' => 'Groupe A', 'level' => 'A1', 'exam_id' => $goethe->id,
        'invite_code' => Classroom::generateInviteCode(),
    ]);
    $classroom->members()->attach($teacher->id, ['role_in_class' => 'teacher']);

    return [$teacher, $center, $classroom, $goethe, $testdaf, $type];
}

test('l enseignant renomme sa classe et change son examen', function () {
    [$teacher, $center, $classroom, $goethe, $testdaf] = centerWithTeacher();

    // La page porte bien de quoi remplir le formulaire : l'examen choisi et la liste.
    $this->actingAs($teacher)->get(route('center.classes.show', $classroom))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('classroom.exam_id', $goethe->id)
            ->has('exams', 2));

    $this->patch(route('center.classes.update', $classroom), [
        'name' => 'Groupe du samedi',
        'level' => 'B1',
        'exam_id' => $testdaf->id,
    ])->assertRedirect();

    $classroom = $classroom->fresh();

    expect($classroom->name)->toBe('Groupe du samedi')
        ->and($classroom->level)->toBe('B1')
        // Plusieurs classes, plusieurs examens dans le meme espace.
        ->and($classroom->exam_id)->toBe($testdaf->id);
});

test('un exercice jamais travaille peut etre retire', function () {
    [$teacher, $center, $classroom, $goethe, $testdaf, $type] = centerWithTeacher();

    $exercise = Exercise::create([
        'exam_id' => $goethe->id, 'exercise_type_id' => $type->id, 'center_id' => $center->id,
        'difficulty' => 'A2', 'content' => [], 'questions' => [],
    ]);

    $this->actingAs($teacher)->delete(route('center.exercises.destroy', $exercise))
        ->assertRedirect(route('center.exercises.index'));

    expect(Exercise::find($exercise->id))->toBeNull();
});

test('un exercice deja donne en devoir est conserve', function () {
    [$teacher, $center, $classroom, $goethe, $testdaf, $type] = centerWithTeacher();

    $exercise = Exercise::create([
        'exam_id' => $goethe->id, 'exercise_type_id' => $type->id, 'center_id' => $center->id,
        'difficulty' => 'A2', 'content' => [], 'questions' => [],
    ]);

    $assignment = $classroom->assignments()->create([
        'created_by' => $teacher->id, 'title' => 'Devoir', 'published_at' => now(),
    ]);
    AssignmentItem::create([
        'assignment_id' => $assignment->id, 'itemable_type' => Exercise::class,
        'itemable_id' => $exercise->id, 'sort_order' => 0,
    ]);

    $this->actingAs($teacher)->delete(route('center.exercises.destroy', $exercise))
        ->assertSessionHas('error', fn (string $message) => str_contains($message, 'devoir'));

    // Effacer l'exercice viderait le devoir de ses eleves.
    expect(Exercise::find($exercise->id))->not->toBeNull();
});

test('un exercice deja travaille par un eleve est conserve', function () {
    [$teacher, $center, $classroom, $goethe, $testdaf, $type] = centerWithTeacher();

    $exercise = Exercise::create([
        'exam_id' => $goethe->id, 'exercise_type_id' => $type->id, 'center_id' => $center->id,
        'difficulty' => 'A2', 'content' => [], 'questions' => [],
    ]);

    $student = User::factory()->create();
    UserExerciseAttempt::create([
        'user_id' => $student->id, 'exercise_id' => $exercise->id, 'answers' => [],
        'score' => 1, 'accuracy_percent' => 50, 'time_spent' => 30, 'xp_earned' => 5, 'feedback' => [],
    ]);

    $this->actingAs($teacher)->delete(route('center.exercises.destroy', $exercise))
        ->assertSessionHas('error', fn (string $message) => str_contains($message, 'historique'));

    expect(Exercise::find($exercise->id))->not->toBeNull();
});
