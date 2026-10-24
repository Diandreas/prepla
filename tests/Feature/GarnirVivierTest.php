<?php

use App\Models\Exam;
use App\Models\ExamBlueprint;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Services\AI\ExerciseGeneratorService;

/**
 * Le vivier d'exercices libres etait presque vide : un candidat telc B1 trouvait
 * douze exercices pour cinq modules, donc presque chaque tuile de format tombait
 * sur une generation en direct — longue, et impossible quand le quota est a sec.
 */
function examenAGarnir(): Exam
{
    $langue = Language::create(['slug' => 'german', 'name' => 'Allemand', 'native_name' => 'Deutsch', 'flag' => 'de', 'is_active' => true]);
    $exam = Exam::create(['language_id' => $langue->id, 'slug' => 'telc-vivier', 'name' => 'telc Deutsch']);

    foreach ([['lesen', 'reading', 'mcq'], ['sprachbausteine', 'grammar', 'gap-fill'],
        ['schreiben', 'writing', 'synthesis']] as [$slug, $skill, $composant]) {
        $section = ExamSection::create([
            'exam_id' => $exam->id, 'slug' => $slug, 'name' => $slug, 'skill_type' => $skill, 'time_limit' => 30,
        ]);
        ExerciseType::create([
            'section_id' => $section->id, 'slug' => $composant, 'name' => $slug,
            'skill_type' => $skill, 'component_key' => $composant,
        ]);
    }

    // Au A1 : pas de Sprachbausteine. Le module existe en base, mais pas a ce niveau.
    ExamBlueprint::create([
        'exam_id' => $exam->id, 'level' => 'A1', 'variant' => null,
        'name' => 'telc A1', 'total_duration_minutes' => 80, 'scoring_config' => [],
        'sections_config' => [['slug' => 'lesen', 'time_limit' => 25, 'task_count' => 3]],
    ]);

    return $exam;
}

/** Un generateur qui compte ses appels au lieu d'appeler Mistral. */
function generateurCompteur(): object
{
    $faux = new class extends ExerciseGeneratorService
    {
        public array $demandes = [];

        public function __construct() {}

        public function generate(ExerciseType $exerciseType, Exam $exam, string $difficulty = 'B1', ?array $lessonContext = null, ?array $partieExamen = null, ?string $faiblesse = null): Exercise
        {
            $this->demandes[] = $difficulty.':'.$exerciseType->slug;

            return Exercise::create([
                'exam_id' => $exam->id,
                'exercise_type_id' => $exerciseType->id,
                'difficulty' => $difficulty,
                'content' => ['title' => 'test'],
                'questions' => [['id' => 'q1', 'type' => $exerciseType->component_key, 'text' => 'x']],
                'is_ai_generated' => true,
            ]);
        }
    };
    app()->instance(ExerciseGeneratorService::class, $faux);

    return $faux;
}

test('les exercices ecrits tombent bien dans le vivier libre', function () {
    $exam = examenAGarnir();
    generateurCompteur();

    $this->artisan('prepla:garnir-vivier', ['--exam' => ['telc-vivier'], '--par-type' => 2])
        ->assertSuccessful();

    $vivier = Exercise::where('exam_id', $exam->id)
        ->whereNull('center_id')->whereNull('lesson_id')
        ->whereNull('node_id')->whereNull('mock_exam_id')->count();

    // Un exercice rattache a une lecon ou a une epreuve blanche ne serait jamais
    // propose a la page « Pratiquer » : la commande n'aurait rien servi.
    expect($vivier)->toBe(2);
});

test('un module absent du niveau n est pas garni', function () {
    examenAGarnir();
    $faux = generateurCompteur();

    $this->artisan('prepla:garnir-vivier', ['--exam' => ['telc-vivier'], '--par-type' => 1])
        ->assertSuccessful();

    expect($faux->demandes)->toBe(['A1:mcq'])
        ->not->toContain('A1:gap-fill');       // les Sprachbausteine n'existent pas au telc A1
});

test('un type trop difficile pour le niveau ne coute rien', function () {
    $exam = examenAGarnir();
    // La synthese se prepare a partir du B2 ; au A1 elle ne doit pas etre demandee.
    ExamBlueprint::create([
        'exam_id' => $exam->id, 'level' => 'A1', 'variant' => null,
        'name' => 'telc A1 synthese', 'total_duration_minutes' => 30, 'scoring_config' => [],
        'sections_config' => [['slug' => 'schreiben', 'time_limit' => 30, 'task_count' => 1]],
    ]);
    $faux = generateurCompteur();

    $this->artisan('prepla:garnir-vivier', ['--exam' => ['telc-vivier'], '--par-type' => 1])
        ->assertSuccessful();

    expect($faux->demandes)->not->toContain('A1:synthesis');
});

test('ce qui existe deja n est pas refait', function () {
    $exam = examenAGarnir();
    $type = ExerciseType::whereHas('section', fn ($q) => $q->where('slug', 'lesen'))->first();
    Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'difficulty' => 'A1',
        'content' => ['title' => 'deja la'], 'questions' => [['id' => 'q1', 'type' => 'mcq', 'text' => 'x']],
    ]);
    $faux = generateurCompteur();

    $this->artisan('prepla:garnir-vivier', ['--exam' => ['telc-vivier'], '--par-type' => 1])
        ->assertSuccessful();

    expect($faux->demandes)->toBe([]);
});

test('le mode sec ne depense rien et annonce le manque', function () {
    examenAGarnir();
    $faux = generateurCompteur();

    $this->artisan('prepla:garnir-vivier', ['--exam' => ['telc-vivier'], '--par-type' => 3, '--sec' => true])
        ->expectsOutputToContain('3 exercice(s) manquent')
        ->assertSuccessful();

    expect($faux->demandes)->toBe([])
        ->and(Exercise::count())->toBe(0);
});

test('un examen masque n est pas garni', function () {
    $exam = examenAGarnir();
    $exam->language->update(['is_active' => false]);
    $faux = generateurCompteur();

    // Les huit examens hors perimetre sont caches par la langue : les garnir
    // depenserait du quota pour des exercices que personne ne verra.
    $this->artisan('prepla:garnir-vivier', ['--exam' => ['telc-vivier'], '--par-type' => 1])
        ->assertFailed();

    expect($faux->demandes)->toBe([]);
});
