<?php

use App\Models\Classroom;
use App\Models\CourseBlock;
use App\Models\CoursePath;
use App\Models\Exam;
use App\Models\ExamBlueprint;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\LanguageCenter;
use App\Models\MockExam;
use App\Models\User;
use App\Models\UserProfile;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Le centre cree ses propres exercices depuis un examen blanc ou un bloc de
 * parcours : le createur s'ouvre regle sur la partie (examen, niveau, type),
 * tout reste modifiable, et l'exercice enregistre revient a sa place.
 */
beforeEach(function () {
    $this->withoutVite();
});

function cdeMonde(): array
{
    $de = Language::create(['slug' => 'german', 'name' => 'Allemand', 'native_name' => 'Deutsch', 'flag' => 'de', 'is_active' => true]);
    $fr = Language::create(['slug' => 'french', 'name' => 'Français', 'native_name' => 'Français', 'flag' => 'fr', 'is_active' => true]);
    $goethe = Exam::create(['language_id' => $de->id, 'slug' => 'goethe', 'name' => 'Goethe']);
    $tcf = Exam::create(['language_id' => $fr->id, 'slug' => 'tcf', 'name' => 'TCF']);
    $lesen = ExamSection::create(['exam_id' => $goethe->id, 'slug' => 'lesen', 'name' => 'Lesen', 'skill_type' => 'reading']);
    $qcm = ExerciseType::create(['section_id' => $lesen->id, 'slug' => 'mcq', 'name' => 'Multiple Choice', 'skill_type' => 'reading', 'component_key' => 'mcq']);
    ExamBlueprint::create([
        'exam_id' => $goethe->id, 'level' => 'B1', 'name' => 'Goethe B1', 'total_duration_minutes' => 180, 'scoring_config' => [],
        'sections_config' => [['slug' => 'lesen', 'name' => 'Lesen', 'time_limit' => 65, 'parts' => [['part' => 1, 'name' => 'Teil 1', 'type' => 'mcq', 'items' => 6]]]],
    ]);

    $centre = function (string $slug) use ($goethe, $tcf) {
        $c = LanguageCenter::create(['name' => "Centre {$slug}", 'slug' => $slug, 'owner_email' => "{$slug}@exemple.test", 'is_active' => true]);
        $c->examensProposes()->sync([$goethe->id, $tcf->id]);

        return $c;
    };
    $a = $centre('centre-a');
    $b = $centre('centre-b');
    $admin = function (LanguageCenter $c) {
        $u = User::factory()->create();
        UserProfile::factory()->for($u)->create(['onboarding_completed_at' => now()]);
        $c->members()->attach($u->id, ['role' => 'center_admin', 'joined_at' => now()]);

        return $u;
    };
    $adminA = $admin($a);
    $adminB = $admin($b);
    $classe = $a->classrooms()->create(['name' => 'B1', 'level' => 'B1', 'exam_id' => $goethe->id, 'invite_code' => Classroom::generateInviteCode()]);

    return compact('goethe', 'tcf', 'qcm', 'a', 'b', 'adminA', 'adminB', 'classe');
}

function cdeExercice(array $m, array $plus = []): array
{
    return $plus + [
        'exercise_type_id' => $m['qcm']->id, 'exam_id' => $m['goethe']->id, 'difficulty' => 'B1',
        'content' => ['title' => 'Chez le médecin'],
        'questions' => [['id' => 'q1', 'text' => 'Wo arbeitet Anna?', 'options' => ['Im Krankenhaus', 'In der Schule'], 'correct_answer' => 'A', 'explanation' => 'Elle est infirmière.']],
    ];
}

function cdeBloc(array $m): CourseBlock
{
    $p = CoursePath::create(['center_id' => $m['a']->id, 'classroom_id' => $m['classe']->id, 'titre' => 'Allemand B1', 'statut' => 'brouillon']);
    $seance = $p->modules()->create(['position' => 1, 'titre' => 'Module 1'])
        ->sessions()->create(['position' => 1, 'titre' => 'Séance 1', 'statut' => 'brouillon']);

    return $seance->blocks()->create(['position' => 1, 'type' => 'exercice', 'obligatoire' => true, 'contenu' => ['exercise_id' => null, 'seuil' => 70]]);
}

// ── Examen blanc ─────────────────────────────────────────────────────────

it('enregistre l examen blanc puis ouvre le createur regle sur la partie', function () {
    $m = cdeMonde();

    $reponse = $this->actingAs($m['adminA'])->post('/center/examens-blancs', [
        'exam_id' => $m['goethe']->id, 'level' => 'B1', 'title' => 'Blanc', 'items' => [],
        'creer' => ['section_slug' => 'lesen', 'partie' => '1', 'exercise_type_id' => $m['qcm']->id],
    ]);

    $mock = MockExam::firstOrFail();
    expect($mock->is_published)->toBeFalse();
    $reponse->assertRedirect(route('center.exercises.create', [
        'examen_blanc' => $mock->id, 'section' => 'lesen', 'partie' => '1',
        'exam_id' => $m['goethe']->id, 'difficulty' => 'B1', 'exercise_type_id' => $m['qcm']->id,
    ]));

    // Le createur connait la cible et l'examen, sans rien imposer d'autre.
    $this->get(route('center.exercises.create', ['examen_blanc' => $mock->id, 'section' => 'lesen', 'partie' => '1', 'exam_id' => $m['goethe']->id, 'difficulty' => 'B1', 'exercise_type_id' => $m['qcm']->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('center/exercises/builder')
            ->where('cible.type', 'examen-blanc')
            ->where('cible.exam_id', $m['goethe']->id)
            ->where('cible.section', 'lesen')
            ->where('prefill.exercise_type_id', $m['qcm']->id)
            ->where('prefill.difficulty', 'B1'));
});

it('place l exercice cree dans la partie de l examen blanc', function () {
    $m = cdeMonde();
    $this->actingAs($m['adminA'])->post('/center/examens-blancs', ['exam_id' => $m['goethe']->id, 'level' => 'B1', 'title' => 'Blanc', 'items' => []]);
    $mock = MockExam::firstOrFail();

    $this->post(route('center.exercises.store'), cdeExercice($m, ['cible_examen_blanc' => $mock->id, 'cible_section' => 'lesen', 'cible_partie' => '1']))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('center.examens-blancs.edit', $mock->id));

    $exercice = Exercise::firstOrFail();
    $item = $mock->items()->sole();
    expect($exercice->center_id)->toBe($m['a']->id)
        ->and($item->exercise_id)->toBe($exercice->id)
        ->and($item->section_slug)->toBe('lesen')
        ->and($item->partie)->toBe('1');
});

it('refuse un exercice d un autre examen que celui de l examen blanc, sans rien creer', function () {
    $m = cdeMonde();
    $this->actingAs($m['adminA'])->post('/center/examens-blancs', ['exam_id' => $m['goethe']->id, 'level' => 'B1', 'title' => 'Blanc', 'items' => []]);
    $mock = MockExam::firstOrFail();
    $tcfType = ExerciseType::create([
        'section_id' => ExamSection::create(['exam_id' => $m['tcf']->id, 'slug' => 'ce', 'name' => 'CE', 'skill_type' => 'reading'])->id,
        'slug' => 'mcq', 'name' => 'QCM', 'skill_type' => 'reading', 'component_key' => 'mcq',
    ]);

    $this->post(route('center.exercises.store'), cdeExercice($m, [
        'exam_id' => $m['tcf']->id, 'exercise_type_id' => $tcfType->id,
        'cible_examen_blanc' => $mock->id, 'cible_section' => 'lesen',
    ]))->assertSessionHasErrors('exam_id');

    expect(Exercise::count())->toBe(0)->and($mock->items()->count())->toBe(0);
});

it('ne laisse pas viser l examen blanc d un autre centre', function () {
    $m = cdeMonde();
    $this->actingAs($m['adminA'])->post('/center/examens-blancs', ['exam_id' => $m['goethe']->id, 'level' => 'B1', 'title' => 'Blanc', 'items' => []]);
    $mock = MockExam::firstOrFail();

    $this->actingAs($m['adminB'])->get(route('center.exercises.create', ['examen_blanc' => $mock->id]))->assertNotFound();
    $this->post(route('center.exercises.store'), cdeExercice($m, ['cible_examen_blanc' => $mock->id, 'cible_section' => 'lesen']))
        ->assertNotFound();

    expect(Exercise::count())->toBe(0)->and($mock->items()->count())->toBe(0);
});

// ── Parcours ─────────────────────────────────────────────────────────────

it('remplit le bloc exercice du parcours avec l exercice cree, puis rouvre la seance', function () {
    $m = cdeMonde();
    $bloc = cdeBloc($m);

    $this->actingAs($m['adminA'])->get(route('center.exercises.create', ['bloc' => $bloc->id, 'exam_id' => $m['goethe']->id, 'difficulty' => 'B1']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('cible.type', 'cours')->where('cible.id', $bloc->id));

    $this->post(route('center.exercises.store'), cdeExercice($m, ['cible_bloc' => $bloc->id]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('center.cours.edit', ['parcours' => $bloc->session->module->course_path_id, 'seance' => $bloc->course_session_id]));

    $exercice = Exercise::firstOrFail();
    expect($bloc->fresh()->contenu)->toBe(['exercise_id' => $exercice->id, 'seuil' => 70]);

    $this->get(route('center.cours.edit', ['parcours' => $bloc->session->module->course_path_id, 'seance' => $bloc->course_session_id]))
        ->assertInertia(fn (Assert $page) => $page->where('seanceInitiale', $bloc->course_session_id)
            ->where('examParDefaut', $m['goethe']->id)
            ->where('niveauParDefaut', 'B1'));
});

it('refuse de mettre un brouillon dans un parcours', function () {
    $m = cdeMonde();
    $bloc = cdeBloc($m);

    $this->actingAs($m['adminA'])->post(route('center.exercises.store'), cdeExercice($m, ['cible_bloc' => $bloc->id, 'center_status' => 'brouillon']))
        ->assertSessionHasErrors('center_status');

    expect(Exercise::count())->toBe(0)->and($bloc->fresh()->contenu['exercise_id'])->toBeNull();
});

it('ne laisse pas viser le bloc de parcours d un autre centre', function () {
    $m = cdeMonde();
    $bloc = cdeBloc($m);

    $this->actingAs($m['adminB'])->post(route('center.exercises.store'), cdeExercice($m, ['cible_bloc' => $bloc->id]))
        ->assertNotFound();

    expect(Exercise::count())->toBe(0);
});
