<?php

use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Models\UserProfile;
use App\Notifications\AssignmentPublishedNotification;
use Illuminate\Support\Facades\Notification;

/**
 * L'espace enseignant existait — classes, codes d'invitation, suivi des élèves,
 * devoirs — mais seul un super-administrateur pouvait créer le centre qui le porte,
 * et un devoir publié ne prévenait personne.
 */
function teachingSetup(): array
{
    $language = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'goethe-b1', 'name' => 'Goethe B1']);
    $section = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'grammar', 'name' => 'Grammar', 'skill_type' => 'grammar']);
    $type = ExerciseType::create([
        'section_id' => $section->id, 'slug' => 'mcq', 'name' => 'MCQ', 'skill_type' => 'grammar', 'component_key' => 'mcq',
    ]);

    return [$exam, $type];
}

test('un professeur ouvre son espace sans passer par un administrateur', function () {
    [$exam] = teachingSetup();

    $teacher = User::factory()->create(['name' => 'Zidane Mbarga']);
    UserProfile::factory()->for($teacher)->create(['onboarding_completed_at' => now()]);

    $this->actingAs($teacher)->get(route('teach.open'))->assertOk();

    $this->post(route('teach.open.store'), [
        'name' => 'Cours d’allemand de Zidane',
        'exam_id' => $exam->id,
        'classroom_name' => 'Groupe du samedi',
        'level' => 'A2',
    ])->assertRedirect(route('center.dashboard'));

    $center = LanguageCenter::sole();
    $classroom = Classroom::sole();

    expect($center->name)->toBe('Cours d’allemand de Zidane')
        ->and($center->owner_email)->toBe($teacher->email)
        ->and($center->is_active)->toBeTruthy()
        // center_admin : c'est son espace, il y gere les classes et les professeurs.
        ->and($teacher->fresh()->centerRole())->toBe('center_admin')
        ->and($teacher->fresh()->isCenterStaff())->toBeTrue()
        ->and($classroom->name)->toBe('Groupe du samedi')
        ->and($classroom->level)->toBe('A2')
        // Le code d'invitation est pret des l'ouverture : c'est ce qu'il envoie a ses eleves.
        ->and($classroom->invite_code)->not->toBeEmpty()
        // Il encadre sa classe, il n'y est pas eleve.
        ->and($classroom->teachers()->pluck('users.id')->all())->toBe([$teacher->id])
        ->and($classroom->students()->count())->toBe(0);

    // Et son espace s'ouvre vraiment.
    $this->get(route('center.dashboard'))->assertOk();
});

test('un eleve rejoint la classe avec le code puis le professeur le suit', function () {
    [$exam] = teachingSetup();

    $teacher = User::factory()->create();
    UserProfile::factory()->for($teacher)->create(['onboarding_completed_at' => now()]);
    $this->actingAs($teacher)->post(route('teach.open.store'), [
        'name' => 'Cours de Zidane', 'exam_id' => $exam->id, 'classroom_name' => 'Groupe A',
    ]);

    $classroom = Classroom::sole();

    $student = User::factory()->create(['name' => 'Awa']);
    UserProfile::factory()->for($student)->create(['onboarding_completed_at' => now()]);

    $this->actingAs($student)->post(route('center.join.store'), ['code' => $classroom->invite_code])
        ->assertRedirect();

    expect($classroom->students()->pluck('users.id')->all())->toBe([$student->id]);

    // Le professeur retrouve son eleve dans son suivi.
    $this->actingAs($teacher)->get(route('center.students.index'))->assertOk();
    $this->get(route('center.students.show', $student))->assertOk();
});

test('publier un devoir previent les eleves de la classe', function () {
    Notification::fake();
    [$exam, $type] = teachingSetup();

    $teacher = User::factory()->create(['name' => 'Zidane']);
    UserProfile::factory()->for($teacher)->create(['onboarding_completed_at' => now()]);
    $this->actingAs($teacher)->post(route('teach.open.store'), [
        'name' => 'Cours de Zidane', 'exam_id' => $exam->id, 'classroom_name' => 'Groupe A',
    ]);

    $center = LanguageCenter::sole();
    $classroom = Classroom::sole();

    $students = User::factory()->count(2)->create();
    foreach ($students as $student) {
        UserProfile::factory()->for($student)->create(['onboarding_completed_at' => now()]);
        $classroom->members()->attach($student->id, ['role_in_class' => 'student']);
        $center->members()->attach($student->id, ['role' => 'student', 'joined_at' => now()]);
    }

    // Un exercice du centre : seuls ceux-la peuvent etre donnes en devoir.
    $exercise = Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'center_id' => $center->id,
        'difficulty' => 'A2', 'content' => [],
        'questions' => [[
            'id' => 'q1', 'type' => 'mcq', 'text' => 'Wie heißt du ?',
            'options' => ['Ich heiße Anna', 'Es regnet'], 'correct_answer' => 'A', 'explanation' => 'Oui.',
        ]],
    ]);

    $this->actingAs($teacher)->post(route('center.assignments.store'), [
        'classroom_id' => $classroom->id,
        'title' => 'Le présent des verbes faibles',
        'instructions' => 'Trois exercices avant samedi.',
        'exercise_ids' => [$exercise->id],
    ])->assertRedirect();

    // Chaque eleve est prevenu, et personne d'autre.
    Notification::assertSentTo($students, AssignmentPublishedNotification::class);
    Notification::assertNotSentTo($teacher, AssignmentPublishedNotification::class);
    Notification::assertCount(2);
});
