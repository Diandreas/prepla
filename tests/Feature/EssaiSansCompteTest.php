<?php

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\UserExerciseAttempt;
use App\Services\AI\MistralEvaluationService;
use App\Services\AI\WritingCorrectorService;

/**
 * Essayer un exercice SANS COMPTE, pour donner envie avant l'inscription.
 *
 * Un visiteur anonyme n'est pas un apprenant. Ces tests gardent les quatre
 * portes qui ne doivent jamais s'ouvrir : la reponse devoilee avant qu'il ait
 * repondu, une trace en base, un appel a l'IA qu'il ferait payer, un exercice
 * qui ne nous appartient pas a montrer.
 */
// Ces tests portent sur ce que le serveur envoie et enregistre, pas sur le
// rendu des assets : ils ne dependent donc pas d'un build front a jour.
beforeEach(fn () => $this->withoutVite());

function exerciceDEssai(array $surcharges = [], string $composant = 'mcq', string $competence = 'reading'): Exercise
{
    $langue = Language::firstOrCreate(['slug' => 'german'], ['name' => 'Allemand', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::firstOrCreate(['slug' => 'goethe'], ['language_id' => $langue->id, 'name' => 'Goethe-Zertifikat']);
    $section = ExamSection::firstOrCreate(
        ['exam_id' => $exam->id, 'slug' => 'lesen-'.$competence],
        ['name' => 'Lesen', 'skill_type' => $competence],
    );
    $type = ExerciseType::firstOrCreate(
        ['section_id' => $section->id, 'slug' => $composant],
        ['name' => $composant, 'skill_type' => $competence, 'component_key' => $composant],
    );

    return Exercise::create(array_replace_recursive([
        'exam_id' => $exam->id,
        'exercise_type_id' => $type->id,
        'exam_section_id' => $section->id,
        'difficulty' => 'B1',
        'is_ai_generated' => false,
        'content' => [
            'source' => 'epreuve-verifiee',
            'title' => 'Goethe B1 · Lesen, Teil 2',
            'passage' => 'Ein kurzer Text.',
        ],
        'questions' => [
            [
                'id' => 'q1', 'type' => 'mcq', 'text' => 'Wo wohnt Anna?',
                'options' => ['In Berlin', 'In Wien', 'In Bern'],
                'correct_answer' => 'B',
                'explanation' => 'Le texte dit « Anna wohnt in Wien ».',
            ],
            [
                'id' => 'q2', 'type' => 'mcq', 'text' => 'Was macht sie?',
                'options' => ['Sie studiert', 'Sie arbeitet', 'Sie reist'],
                'correct_answer' => 'A',
                'explanation' => '« Sie studiert Medizin » : elle fait des études.',
            ],
        ],
    ], $surcharges));
}

/** Une IA qui fait echouer le test si on l'appelle. */
function interdireLIA(): void
{
    $piege = fn () => throw new RuntimeException("Un visiteur anonyme a declenche un appel a l'IA.");

    app()->instance(MistralEvaluationService::class, Mockery::mock(MistralEvaluationService::class, fn ($m) => $m->shouldReceive('explainMistake', 'evaluate', 'evaluateSpeaking')->andReturnUsing($piege)));
    app()->instance(WritingCorrectorService::class, Mockery::mock(WritingCorrectorService::class, fn ($m) => $m->shouldReceive('correct')->andReturnUsing($piege)));
}

test('la liste des essais s ouvre sans compte', function () {
    $exercice = exerciceDEssai();

    $this->get(route('essai.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('essai/index')
            ->where('langues.0.examens.0.exercices.0.id', $exercice->id)
            ->where('langues.0.examens.0.exercices.0.officiel', true));
});

test('un exercice s ouvre sans compte, dans le vrai lecteur', function () {
    $exercice = exerciceDEssai();

    $this->get(route('essai.show', $exercice))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('essai/show')
            ->where('submitUrl', route('essai.corriger', $exercice))
            ->where('exercise.questions.0.text', 'Wo wohnt Anna?'));
});

/**
 * La bonne reponse ne doit pas partir avec la page : n'importe qui la lirait
 * dans le code source avant de repondre, et l'essai ne prouverait plus rien.
 */
test('les reponses ne partent pas avec la page', function () {
    $exercice = exerciceDEssai();

    $this->get(route('essai.show', $exercice))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->missing('exercise.questions.0.correct_answer')
            ->missing('exercise.questions.0.explanation')
            ->missing('exercise.questions.1.correct_answer'));
});

test('la correction rend le resultat et ses explications', function () {
    interdireLIA();
    $exercice = exerciceDEssai();

    $this->post(route('essai.corriger', $exercice), [
        'answers' => ['q1' => 'B', 'q2' => 'C'],
        'time_spent' => 40,
    ])
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('essai/resultat')
            ->where('score.justes', 1)
            ->where('score.total', 2)
            ->where('questions.0.juste', true)
            ->where('questions.1.juste', false)
            ->where('questions.1.explication', '« Sie studiert Medizin » : elle fait des études.'));
});

/**
 * Un essai n'est rattache a personne : il ne doit laisser aucune trace, ni
 * tentative, ni XP, ni progression.
 */
test('un essai n enregistre rien', function () {
    interdireLIA();
    $exercice = exerciceDEssai();
    $avant = UserExerciseAttempt::count();

    $this->post(route('essai.corriger', $exercice), ['answers' => ['q1' => 'A', 'q2' => 'B']])->assertOk();

    expect(UserExerciseAttempt::count())->toBe($avant);
});

/**
 * Une reponse fausse sans explication ecrite part a l'IA pour en obtenir une.
 * Un tel exercice ne doit donc pas etre ouvert a un visiteur anonyme.
 */
test('un exercice dont une question n a pas d explication reste ferme', function () {
    $exercice = exerciceDEssai(['questions' => [1 => ['explanation' => '']]]);

    $this->get(route('essai.show', $exercice))->assertNotFound();
    $this->post(route('essai.corriger', $exercice), ['answers' => ['q1' => 'A']])->assertNotFound();
});

test('une redaction reste fermee, sa correction passe par l IA', function () {
    $exercice = exerciceDEssai([], 'short-writing', 'writing');

    $this->get(route('essai.show', $exercice))->assertNotFound();
});

test('un exercice genere par l IA ou appartenant a un centre reste ferme', function () {
    $genere = exerciceDEssai(['is_ai_generated' => true]);
    $sansSource = exerciceDEssai(['content' => ['source' => 'ai-generated']]);

    $this->get(route('essai.show', $genere))->assertNotFound();
    $this->get(route('essai.show', $sansSource))->assertNotFound();
});

/**
 * La synthese vocale est reservee aux comptes et payante : une ecoute n'est
 * ouverte que si son son est deja fabrique.
 */
test('une ecoute sans son fabrique reste fermee', function () {
    $sansSon = exerciceDEssai([], 'mcq', 'listening');

    $this->get(route('essai.show', $sansSon))->assertNotFound();

    $avecSon = exerciceDEssai([
        'questions' => [
            0 => ['audio_url' => '/storage/audio/q1.mp3'],
            1 => ['audio_url' => '/storage/audio/q2.mp3'],
        ],
    ], 'mcq', 'listening');

    $this->get(route('essai.show', $avecSon))->assertOk();
});

test('la page d accueil mene a l essai', function () {
    expect(file_get_contents(resource_path('js/components/landing/hero-section.tsx')))
        ->toContain('href="/essayer"');
});
