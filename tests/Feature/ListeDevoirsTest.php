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
use Illuminate\Support\Facades\DB;

/**
 * La liste des devoirs du centre : pour chaque devoir, sa classe, son echeance,
 * son statut et l'avancement de la classe — sans une requete par devoir.
 */
function listeDevoirsCentre(): array
{
    $language = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => '🇩🇪']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'goethe-b1', 'name' => 'Goethe B1']);
    $section = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'grammar', 'name' => 'Grammar', 'skill_type' => 'grammar']);
    $type = ExerciseType::create([
        'section_id' => $section->id, 'slug' => 'mcq', 'name' => 'MCQ', 'skill_type' => 'grammar', 'component_key' => 'mcq',
    ]);

    $center = LanguageCenter::create([
        'name' => 'Institut test', 'slug' => 'institut-test',
        'owner_email' => 'direction@exemple.test', 'is_active' => true,
    ]);

    $membre = function (string $role) use ($center) {
        $user = User::factory()->create();
        UserProfile::factory()->for($user)->create(['onboarding_completed_at' => now()]);
        $center->members()->attach($user->id, ['role' => $role, 'joined_at' => now()]);

        return $user;
    };

    $admin = $membre('center_admin');
    $prof = $membre('teacher');
    $collegue = $membre('teacher');

    $classe = $center->classrooms()->create([
        'name' => 'Allemand B1 soir', 'level' => 'b1', 'exam_id' => $exam->id, 'invite_code' => Classroom::generateInviteCode(),
    ]);
    $classe->members()->attach($prof->id, ['role_in_class' => 'teacher']);

    $autre = $center->classrooms()->create([
        'name' => 'Allemand A2', 'exam_id' => $exam->id, 'invite_code' => Classroom::generateInviteCode(),
    ]);
    $autre->members()->attach($collegue->id, ['role_in_class' => 'teacher']);

    $eleves = collect(range(1, 3))->map(function () use ($membre, $classe) {
        $eleve = $membre('student');
        $classe->members()->attach($eleve->id, ['role_in_class' => 'student']);

        return $eleve;
    });

    $exercice = fn () => Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'center_id' => $center->id,
        'difficulty' => 'B1', 'content' => [], 'questions' => [],
    ]);

    return compact('admin', 'prof', 'collegue', 'classe', 'autre', 'eleves', 'exercice');
}

function devoirAvec(Classroom $classe, array $exercices, array $attributs = []): Assignment
{
    $devoir = Assignment::create(array_merge([
        'classroom_id' => $classe->id, 'created_by' => $classe->teachers()->first()->id,
        'title' => 'Devoir', 'published_at' => now()->subDay(),
    ], $attributs));

    foreach ($exercices as $i => $exercice) {
        AssignmentItem::create([
            'assignment_id' => $devoir->id, 'itemable_type' => Exercise::class,
            'itemable_id' => $exercice->id, 'sort_order' => $i,
        ]);
    }

    return $devoir;
}

function devoirsDeLaPage($page): \Illuminate\Support\Collection
{
    return collect($page->toArray()['props']['assignments'])->keyBy('title');
}

test('chaque devoir porte la progression de sa classe', function () {
    $c = listeDevoirsCentre();
    [$e1, $e2] = [($c['exercice'])(), ($c['exercice'])()];
    $devoir = devoirAvec($c['classe'], [$e1, $e2], ['title' => 'Verbes', 'due_at' => now()->addDays(3)->startOfDay()]);

    [$fini, $commence] = $c['eleves'];
    foreach ([$e1, $e2] as $ex) {
        UserExerciseAttempt::factory()->create(['user_id' => $fini->id, 'exercise_id' => $ex->id]);
    }
    // Deux tentatives du meme exercice ne font pas deux exercices faits.
    UserExerciseAttempt::factory()->count(2)->create(['user_id' => $commence->id, 'exercise_id' => $e1->id]);
    // Une tentative d'avant la publication ne compte pas.
    $ancienne = UserExerciseAttempt::factory()->create(['user_id' => $commence->id, 'exercise_id' => $e2->id]);
    $ancienne->forceFill(['created_at' => now()->subDays(5)])->save();

    $this->actingAs($c['prof'])->get(route('center.assignments.index'))
        ->assertOk()
        ->assertInertia(function ($page) {
            $d = devoirsDeLaPage($page)['Verbes'];

            expect($d['progress'])->toBe(['students' => 3, 'finished' => 1, 'started' => 1])
                ->and($d['exercises_count'])->toBe(2)
                ->and($d['statut'])->toBe('publie')
                ->and($d['days_left'])->toBe(3)
                ->and($d['overdue'])->toBeFalse()
                ->and($d['en_retard'])->toBeFalse()
                ->and($d['classroom']['name'])->toBe('Allemand B1 soir')
                ->and($d['classroom']['level'])->toBe('B1')
                ->and($d['classroom']['langue']['cle'])->toBe('german');

            return true;
        });
});

test('le statut suit la publication, l echeance et la classe', function () {
    $c = listeDevoirsCentre();
    $ex = ($c['exercice'])();

    devoirAvec($c['classe'], [$ex], ['title' => 'Brouillon', 'published_at' => null]);
    devoirAvec($c['classe'], [$ex], ['title' => 'Echu', 'due_at' => now()->subDays(2)->startOfDay()]);
    // Echeance a minuit aujourd'hui : valable toute la journee, pas en retard.
    devoirAvec($c['classe'], [$ex], ['title' => 'Aujourdhui', 'due_at' => now()->startOfDay()]);
    $tous = devoirAvec($c['classe'], [$ex], ['title' => 'Tous finis', 'due_at' => now()->addWeek()]);

    foreach ($c['eleves'] as $eleve) {
        UserExerciseAttempt::factory()->create(['user_id' => $eleve->id, 'exercise_id' => $ex->id]);
    }

    $this->actingAs($c['prof'])->get(route('center.assignments.index'))
        ->assertOk()
        ->assertInertia(function ($page) {
            $d = devoirsDeLaPage($page);

            expect($d['Brouillon']['statut'])->toBe('brouillon')
                ->and($d['Aujourdhui']['statut'])->toBe('termine') // toute la classe a fini
                ->and($d['Aujourdhui']['days_left'])->toBe(0)
                ->and($d['Aujourdhui']['overdue'])->toBeFalse()
                ->and($d['Tous finis']['statut'])->toBe('termine')
                ->and($d['Echu']['statut'])->toBe('termine')
                ->and($d['Echu']['overdue'])->toBeTrue()
                ->and($d['Echu']['days_left'])->toBe(-2)
                // Tous ont fait l'exercice, meme apres l'echeance : personne en retard.
                ->and($d['Echu']['en_retard'])->toBeFalse();

            return true;
        });
});

test('un devoir echu que des eleves n ont pas fini est en retard', function () {
    $c = listeDevoirsCentre();
    $ex = ($c['exercice'])();
    devoirAvec($c['classe'], [$ex], ['title' => 'Oublie', 'due_at' => now()->subDay()->startOfDay()]);
    UserExerciseAttempt::factory()->create(['user_id' => $c['eleves'][0]->id, 'exercise_id' => $ex->id]);

    $this->actingAs($c['prof'])->get(route('center.assignments.index'))
        ->assertInertia(function ($page) {
            $d = devoirsDeLaPage($page)['Oublie'];
            expect($d['en_retard'])->toBeTrue()->and($d['progress']['finished'])->toBe(1);

            return true;
        });
});

test('un enseignant ne voit que les devoirs et les classes qu il encadre', function () {
    $c = listeDevoirsCentre();
    $ex = ($c['exercice'])();
    devoirAvec($c['classe'], [$ex], ['title' => 'A moi']);
    devoirAvec($c['autre'], [$ex], ['title' => 'Au collegue']);

    $this->actingAs($c['prof'])->get(route('center.assignments.index'))
        ->assertOk()
        ->assertInertia(function ($page) {
            $props = $page->toArray()['props'];
            expect(collect($props['assignments'])->pluck('title')->all())->toBe(['A moi'])
                ->and(collect($props['classrooms'])->pluck('name')->all())->toBe(['Allemand B1 soir']);

            return true;
        });

    $this->actingAs($c['admin'])->get(route('center.assignments.index'))
        ->assertInertia(fn ($page) => $page->has('assignments', 2)->has('classrooms', 2));
});

test('la liste ne fait pas une requete par devoir', function () {
    $c = listeDevoirsCentre();
    $ex = ($c['exercice'])();
    $compter = function () use ($c) {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($c['admin'])->get(route('center.assignments.index'))->assertOk();
        $n = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $n;
    };

    devoirAvec($c['classe'], [$ex], ['title' => 'Un']);
    $compter(); // la premiere requete met en cache des reglages du centre et de l'abonnement
    $avecUn = $compter();

    foreach (range(1, 6) as $i) {
        $classe = $i % 2 ? $c['classe'] : $c['autre'];
        devoirAvec($classe, [$ex, ($c['exercice'])()], ['title' => "Devoir $i", 'due_at' => now()->addDays($i)]);
        UserExerciseAttempt::factory()->create(['user_id' => $c['eleves'][0]->id, 'exercise_id' => $ex->id]);
    }

    expect($compter())->toBe($avecUn);
});
