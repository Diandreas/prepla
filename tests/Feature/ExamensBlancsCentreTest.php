<?php

use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamBlueprint;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\LanguageCenter;
use App\Models\MockExam;
use App\Models\MockExamPassage;
use App\Models\User;
use App\Models\UserProfile;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * L'examen blanc compose par un centre : creation depuis la structure
 * officielle, mise en devoir, passation par l'eleve, resultats par section,
 * et etancheite entre centres.
 */
function examensBlancsMonde(): array
{
    $language = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => '🇩🇪']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'goethe', 'name' => 'Goethe']);
    $lesen = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'lesen', 'name' => 'Lesen', 'skill_type' => 'reading']);
    $type = ExerciseType::create([
        'section_id' => $lesen->id, 'slug' => 'mcq', 'name' => 'MCQ', 'skill_type' => 'reading', 'component_key' => 'mcq',
    ]);
    ExamBlueprint::create([
        'exam_id' => $exam->id, 'level' => 'B1', 'name' => 'Goethe B1', 'total_duration_minutes' => 180, 'scoring_config' => [],
        'sections_config' => [[
            'slug' => 'lesen', 'name' => 'Lesen', 'time_limit' => 65,
            'parts' => [['part' => 1, 'name' => 'Teil 1', 'type' => 'mcq', 'items' => 6]],
        ]],
    ]);

    $centre = function (string $slug) {
        return LanguageCenter::create(['name' => "Centre {$slug}", 'slug' => $slug, 'owner_email' => "{$slug}@exemple.test", 'is_active' => true]);
    };
    $membre = function (LanguageCenter $c, string $role) {
        $user = User::factory()->create();
        UserProfile::factory()->for($user)->create(['onboarding_completed_at' => now()]);
        $c->members()->attach($user->id, ['role' => $role, 'joined_at' => now()]);

        return $user;
    };

    $a = $centre('centre-a');
    $b = $centre('centre-b');
    $adminA = $membre($a, 'center_admin');
    $adminB = $membre($b, 'center_admin');
    $eleve = $membre($a, 'student');

    $classe = $a->classrooms()->create(['name' => 'B1 soir', 'level' => 'B1', 'exam_id' => $exam->id, 'invite_code' => Classroom::generateInviteCode()]);
    $classe->members()->attach($eleve->id, ['role_in_class' => 'student']);

    $question = [['id' => 'q1', 'text' => 'Wie?', 'options' => ['a', 'b'], 'correct_answer' => 'a']];
    $catalogue = Exercise::create(['exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'difficulty' => 'B1', 'content' => [], 'questions' => $question]);
    $duCentreA = Exercise::create(['exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'center_id' => $a->id, 'difficulty' => 'B1', 'content' => [], 'questions' => $question]);
    $duCentreB = Exercise::create(['exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'center_id' => $b->id, 'difficulty' => 'B1', 'content' => [], 'questions' => $question]);

    return compact('exam', 'a', 'b', 'adminA', 'adminB', 'eleve', 'classe', 'catalogue', 'duCentreA', 'duCentreB');
}

function composer(array $m, array $exercices, bool $publier = true): \Illuminate\Testing\TestResponse
{
    return test()->actingAs($m['adminA'])->post('/center/examens-blancs', [
        'exam_id' => $m['exam']->id,
        'level' => 'B1',
        'title' => 'Blanc de novembre',
        'durees' => ['lesen' => 40],
        'items' => collect($exercices)->map(fn ($e) => ['exercise_id' => $e->id, 'section_slug' => 'lesen', 'partie' => '1'])->all(),
        'publier' => $publier,
    ]);
}

it('propose la structure officielle et les exercices permis a la creation', function () {
    $m = examensBlancsMonde();

    $this->actingAs($m['adminA'])->get("/center/examens-blancs/creer?exam_id={$m['exam']->id}&level=B1")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('center/examens-blancs/edit')
            ->where('sections.0.slug', 'lesen')
            ->where('sections.0.duree', 65)
            ->where('sections.0.parties.0.cle', '1')
            ->has('exercices', 2));
});

it('cree un examen blanc du centre avec son minutage', function () {
    $m = examensBlancsMonde();

    composer($m, [$m['catalogue'], $m['duCentreA']])->assertRedirect();

    $mock = MockExam::firstOrFail();
    expect($mock->center_id)->toBe($m['a']->id)
        ->and($mock->creator_id)->toBe($m['adminA']->id)
        ->and($mock->is_published)->toBeTrue()
        ->and($mock->dureeTotale())->toBe(40)
        ->and($mock->items()->count())->toBe(2)
        // Le catalogue PrePla reste a PrePla.
        ->and($m['catalogue']->fresh()->mock_exam_id)->toBeNull();
});

it('refuse un exercice d un autre centre', function () {
    $m = examensBlancsMonde();

    composer($m, [$m['duCentreB']])->assertSessionHasErrors('items');
    expect(MockExam::count())->toBe(0);
});

it('isole les examens blancs entre centres', function () {
    $m = examensBlancsMonde();
    composer($m, [$m['catalogue']]);
    $mock = MockExam::firstOrFail();

    $this->actingAs($m['adminB'])->get('/center/examens-blancs')
        ->assertInertia(fn (Assert $page) => $page->has('examens', 0));
    $this->actingAs($m['adminB'])->get("/center/examens-blancs/{$mock->id}")->assertNotFound();
    $this->actingAs($m['adminB'])->get("/center/examens-blancs/{$mock->id}/modifier")->assertNotFound();
    $this->actingAs($m['adminB'])->delete("/center/examens-blancs/{$mock->id}")->assertNotFound();
    expect(MockExam::count())->toBe(1);

    $this->actingAs($m['adminA'])->get('/center/examens-blancs')
        ->assertInertia(fn (Assert $page) => $page->has('examens', 1));
});

it('n est pas accessible a un eleve cote centre', function () {
    $m = examensBlancsMonde();

    expect($this->actingAs($m['eleve'])->get('/center/examens-blancs')->status())->not->toBe(200);
});

it('ne melange pas l examen blanc du centre aux epreuves PrePla', function () {
    $m = examensBlancsMonde();
    composer($m, [$m['catalogue']]);

    expect(MockExam::catalogue()->count())->toBe(0);
});

it('donne l examen blanc a une classe, l eleve le passe et le centre voit ses resultats', function () {
    $m = examensBlancsMonde();
    composer($m, [$m['catalogue'], $m['duCentreA']]);
    $mock = MockExam::firstOrFail();

    // Avant le devoir, l'eleve ne peut pas l'ouvrir.
    $this->actingAs($m['eleve'])->get("/examens-blancs/{$mock->id}")->assertNotFound();

    $this->actingAs($m['adminA'])->post("/center/examens-blancs/{$mock->id}/donner", ['classroom_id' => $m['classe']->id])
        ->assertRedirect();
    $devoir = Assignment::firstOrFail();
    expect($devoir->items()->first()->itemable_type)->toBe(MockExam::class);

    $this->actingAs($m['eleve'])->get("/examens-blancs/{$mock->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('practice/exam-simulator')
            ->has('exercises', 2)
            ->where('totalExamsTime', 40));

    // Une bonne reponse sur deux exercices : 50 % en Lesen.
    $this->actingAs($m['eleve'])->post("/examens-blancs/{$mock->id}", [
        'answers_by_exercise' => [$m['catalogue']->id => ['q1' => 'a']],
        'time_spent' => 600,
    ])->assertRedirect(route('devoirs.index'));

    $passage = MockExamPassage::firstOrFail();
    expect($passage->scores_sections)->toBe(['lesen' => 50])
        ->and($passage->score)->toBe(50)
        ->and($passage->assignment_id)->toBe($devoir->id);

    // Le devoir est rendu cote eleve.
    $this->actingAs($m['eleve'])->get('/devoirs')
        ->assertInertia(fn (Assert $page) => $page->has('termines', 1));

    $this->actingAs($m['adminA'])->get("/center/examens-blancs/{$mock->id}")
        ->assertInertia(fn (Assert $page) => $page->has('passages', 1)
            ->where('passages.0.score', 50)
            ->where('passages.0.sections.lesen', 50));
});

it('refuse de donner un brouillon ou a la classe d un autre centre', function () {
    $m = examensBlancsMonde();
    composer($m, [$m['catalogue']], false);
    $mock = MockExam::firstOrFail();

    $this->actingAs($m['adminA'])->post("/center/examens-blancs/{$mock->id}/donner", ['classroom_id' => $m['classe']->id])
        ->assertSessionHasErrors('classroom_id');

    $mock->update(['is_published' => true]);
    $autre = $m['b']->classrooms()->create(['name' => 'B', 'invite_code' => Classroom::generateInviteCode()]);
    $this->actingAs($m['adminA'])->post("/center/examens-blancs/{$mock->id}/donner", ['classroom_id' => $autre->id])
        ->assertForbidden();
    expect(Assignment::count())->toBe(0);
});
