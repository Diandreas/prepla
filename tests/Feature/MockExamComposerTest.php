<?php

use App\Models\Exam;
use App\Models\ExamBlueprint;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\MockExam;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Content\MockExamComposer;

/**
 * Les épreuves blanches étaient écrites à la main : seize sujets pour seize
 * examens, presque tous sans niveau. Un apprenant A1 ou A2 était donc renvoyé
 * avec « à ton niveau, commence par une compétence », et un examen récemment
 * ajouté n'avait rien du tout.
 */
function examenAvecVivier(string $niveau = 'A2', int $parSection = 2): array
{
    $language = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'osd', 'name' => 'ÖSD Zertifikat']);

    $sections = [];
    foreach ([['lesen', 'reading', 'mcq'], ['hoeren', 'listening', 'mcq'], ['schreiben', 'writing', 'short-writing']] as [$slug, $skill, $composant]) {
        $section = ExamSection::create([
            'exam_id' => $exam->id, 'slug' => $slug, 'name' => $slug, 'skill_type' => $skill, 'time_limit' => 30,
        ]);
        $type = ExerciseType::create([
            'section_id' => $section->id, 'slug' => $slug.'-'.$composant, 'name' => $slug,
            'skill_type' => $skill, 'component_key' => $composant,
        ]);

        foreach (range(1, $parSection) as $i) {
            Exercise::create([
                'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'exam_section_id' => $section->id,
                'difficulty' => $niveau, 'content' => [],
                'questions' => [[
                    'id' => 'q1', 'type' => $composant === 'mcq' ? 'mcq' : 'short-writing',
                    'text' => "Question {$slug} {$i}",
                    'options' => $composant === 'mcq' ? ['Ja', 'Nein'] : null,
                    'correct_answer' => $composant === 'mcq' ? 'A' : null,
                    'explanation' => 'Parce que.',
                ]],
            ]);
        }
        $sections[$slug] = $section;
    }

    return [$exam, $sections];
}

test('une epreuve blanche est composee a partir du vivier existant', function () {
    [$exam] = examenAvecVivier('A2');

    $mock = app(MockExamComposer::class)->pour($exam->fresh(), 'A2');

    expect($mock)->not->toBeNull()
        ->and($mock->is_published)->toBeTrue()
        ->and($mock->title)->toContain('A2')
        // Un exercice par module.
        ->and($mock->exercises()->count())->toBe(3)
        ->and($mock->blueprint->level)->toBe('A2');
});

test('les exercices du vivier sont recopies, jamais deplaces', function () {
    [$exam] = examenAvecVivier('A2');
    $libresAvant = Exercise::whereNull('mock_exam_id')->count();

    app(MockExamComposer::class)->pour($exam->fresh(), 'A2');

    // La pratique libre exclut ce qui appartient a une epreuve : deplacer les
    // originaux l'aurait videe.
    expect(Exercise::whereNull('mock_exam_id')->count())->toBe($libresAvant);
});

test('une epreuve deja composee est reservie au lieu d etre refaite', function () {
    [$exam] = examenAvecVivier('A2');
    $composeur = app(MockExamComposer::class);

    $premier = $composeur->pour($exam->fresh(), 'A2');
    $second = $composeur->pour($exam->fresh(), 'A2');

    expect($second->id)->toBe($premier->id)
        ->and(MockExam::count())->toBe(1);
});

test('sans assez de modules on ne pretend pas servir une epreuve', function () {
    $language = Language::create(['slug' => 'english', 'name' => 'English', 'native_name' => 'English', 'flag' => 'gb']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'ielts', 'name' => 'IELTS']);
    $section = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'writing', 'name' => 'Writing', 'skill_type' => 'writing']);
    $type = ExerciseType::create([
        'section_id' => $section->id, 'slug' => 'short-writing', 'name' => 'Court écrit',
        'skill_type' => 'writing', 'component_key' => 'short-writing',
    ]);
    Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'exam_section_id' => $section->id,
        'difficulty' => 'B1', 'content' => [],
        'questions' => [['id' => 'q1', 'type' => 'short-writing', 'text' => 'Ecris un message.']],
    ]);

    expect(app(MockExamComposer::class)->pour($exam->fresh(), 'B1'))->toBeNull()
        ->and(MockExam::count())->toBe(0);
});

test('un apprenant debutant atteint enfin une epreuve a son niveau', function () {
    [$exam] = examenAvecVivier('A2');

    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'target_exam_id' => $exam->id, 'current_level' => 'A2', 'onboarding_completed_at' => now(),
    ]);

    // Avant, le simulateur refusait : « à ton niveau, commence par une compétence ».
    $this->actingAs($user)->get(route('practice.simulate', $exam->id))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('practice/exam-simulator')->has('mockExam'));

    expect(ExamBlueprint::where('exam_id', $exam->id)->where('level', 'A2')->exists())->toBeTrue();
});

/**
 * Les contenus prepares portent une cle de catalogue unique : la recopier
 * violait sa contrainte et faisait echouer toute la composition.
 */
test('un contenu prepare se recopie sans heurter sa cle unique', function () {
    [$exam] = examenAvecVivier('B1');

    Exercise::where('exam_id', $exam->id)->take(2)->get()
        ->each(fn ($e, $i) => $e->update(['catalog_key' => "starter-v1:{$exam->id}:{$i}"]));

    $mock = app(MockExamComposer::class)->pour($exam->fresh(), 'B1');

    expect($mock)->not->toBeNull()
        ->and($mock->exercises()->whereNotNull('catalog_key')->count())->toBe(0)
        ->and(Exercise::whereNotNull('catalog_key')->count())->toBe(2);
});

/**
 * Tous les examens n'ont pas d'epreuve officielle a chaque niveau. On ne fait pas
 * passer un entrainement au format pour un sujet officiel : le titre le dit.
 */
test('un niveau sans structure officielle est annonce comme un entrainement', function () {
    [$exam] = examenAvecVivier('A2');

    // Aucun blueprint declare pour l'examen : le niveau est donc derive.
    $mock = app(MockExamComposer::class)->pour($exam->fresh(), 'A2');

    expect($mock->title)->toContain('entraînement au format')
        ->and($mock->title)->not->toContain('épreuve blanche')
        ->and($mock->description)->toContain("ne propose pas d'épreuve officielle");

    // Quand la structure existe vraiment a ce niveau, c'est bien une epreuve blanche.
    $exam->update(['levels' => ['B1']]);
    ExamBlueprint::create([
        'exam_id' => $exam->id, 'level' => 'B1', 'variant' => null,
        'name' => 'Officiel B1', 'total_duration_minutes' => 120,
        'scoring_config' => [], 'sections_config' => [],
    ]);
    App\Models\Exercise::where('exam_id', $exam->id)->update(['difficulty' => 'B1']);

    expect(app(MockExamComposer::class)->pour($exam->fresh(), 'B1')->title)
        ->toContain('épreuve blanche B1');
});

/**
 * Un vrai sujet compte plusieurs taches par module — quatre textes a lire, deux
 * redactions. En n'en posant qu'une, on servait le bon format au bon niveau, mais
 * pas l'epreuve.
 */
test('un module monte autant de taches que la structure en annonce', function () {
    [$exam] = examenAvecVivier('B1', 4);

    $exam->update(['levels' => ['B1']]);
    ExamBlueprint::create([
        'exam_id' => $exam->id, 'level' => 'B1', 'variant' => null,
        'name' => 'Officiel B1', 'total_duration_minutes' => 120, 'scoring_config' => [],
        'sections_config' => [
            ['slug' => 'lesen', 'task_count' => 3],
            ['slug' => 'hoeren', 'task_count' => 2],
            ['slug' => 'schreiben'],
        ],
    ]);

    $mock = app(MockExamComposer::class)->pour($exam->fresh(), 'B1');

    // 3 lectures + 2 ecoutes + 1 redaction (aucun nombre annonce : une tache).
    expect($mock->exercises()->count())->toBe(6)
        ->and($mock->description)->toContain('6 tâches');
});

/**
 * Un module varie ses formats : l'OSD B2 demande un entretien, une description
 * d'image puis une discussion — pas trois fois la meme tache.
 */
test('un module varie ses formats avant d en repeter un', function () {
    $language = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'osd', 'name' => 'ÖSD Zertifikat']);
    $section = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'sprechen', 'name' => 'Sprechen', 'skill_type' => 'speaking']);
    $autre = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'lesen', 'name' => 'Lesen', 'skill_type' => 'reading']);

    foreach (['entretien', 'discussion', 'expose'] as $slug) {
        $type = ExerciseType::create([
            'section_id' => $section->id, 'slug' => $slug, 'name' => $slug,
            'skill_type' => 'speaking', 'component_key' => 'speaking-recorder',
        ]);
        foreach (range(1, 3) as $i) {
            Exercise::create([
                'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'exam_section_id' => $section->id,
                'difficulty' => 'B2', 'content' => [],
                'questions' => [['id' => 'q1', 'type' => 'speaking-recorder', 'text' => "Parle de {$slug} {$i}."]],
            ]);
        }
    }

    // Un second module, pour atteindre le minimum de deux.
    $typeLecture = ExerciseType::create([
        'section_id' => $autre->id, 'slug' => 'mcq', 'name' => 'QCM',
        'skill_type' => 'reading', 'component_key' => 'mcq',
    ]);
    Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => $typeLecture->id, 'exam_section_id' => $autre->id,
        'difficulty' => 'B2', 'content' => [],
        'questions' => [['id' => 'q1', 'type' => 'mcq', 'text' => 'Wo?', 'options' => ['A', 'B'], 'correct_answer' => 'A']],
    ]);

    $exam->update(['levels' => ['B2']]);
    ExamBlueprint::create([
        'exam_id' => $exam->id, 'level' => 'B2', 'variant' => null,
        'name' => 'B2', 'total_duration_minutes' => 120, 'scoring_config' => [],
        'sections_config' => [['slug' => 'sprechen', 'task_count' => 3], ['slug' => 'lesen', 'task_count' => 1]],
    ]);

    $mock = app(MockExamComposer::class)->pour($exam->fresh(), 'B2');
    $formats = $mock->exercises()->where('exam_section_id', $section->id)->pluck('exercise_type_id');

    // Trois taches orales, trois formats differents.
    expect($formats)->toHaveCount(3)
        ->and($formats->unique())->toHaveCount(3);
});

/**
 * Un plan derive gardait la photo du jour ou il avait ete fabrique : quand on
 * corrigeait le nombre de taches d'un examen, les niveaux derives continuaient de
 * composer avec l'ancien compte, sans que rien ne le montre.
 */
test('un plan derive reprend la structure a jour', function () {
    [$exam] = examenAvecVivier('A2', 4);

    // Plan de reference, d'abord avec une seule tache par module.
    $reference = ExamBlueprint::create([
        'exam_id' => $exam->id, 'level' => null, 'variant' => null,
        'name' => 'Reference', 'total_duration_minutes' => 120, 'scoring_config' => [],
        'sections_config' => [['slug' => 'lesen'], ['slug' => 'hoeren']],
    ]);

    app(MockExamComposer::class)->pour($exam->fresh(), 'A2');

    // La structure est corrigee : trois textes a lire.
    $reference->update(['sections_config' => [['slug' => 'lesen', 'task_count' => 3], ['slug' => 'hoeren']]]);

    $refait = app(MockExamComposer::class)->pour($exam->fresh(), 'A2', false, true);

    expect($refait->exercises()->count())->toBeGreaterThan(3);
});
