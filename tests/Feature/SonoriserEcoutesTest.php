<?php

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use Illuminate\Support\Facades\Http;

/**
 * 24 epreuves d'ecoute n'ont jamais eu de texte parle : leurs questions
 * existent, leurs reponses aussi, mais la bande n'a jamais ete ecrite. La
 * commande la fabrique — et refuse de l'enregistrer si la reponse attendue
 * ne s'y trouve pas, sinon l'exercice devient une devinette.
 */
function epreuveMuette(string $format = 'note-completion', array $questions = []): Exercise
{
    $langue = Language::firstOrCreate(
        ['slug' => 'german'],
        ['name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']
    );
    $examen = Exam::firstOrCreate(
        ['slug' => 'goethe-son'],
        ['language_id' => $langue->id, 'name' => 'Goethe-Zertifikat']
    );
    $section = ExamSection::firstOrCreate(
        ['exam_id' => $examen->id, 'slug' => 'hoeren'],
        ['name' => 'Hören', 'skill_type' => 'listening']
    );
    $type = ExerciseType::firstOrCreate(
        ['section_id' => $section->id, 'slug' => 'hoeren-' . $format],
        ['name' => $format, 'skill_type' => 'listening', 'component_key' => $format]
    );

    return Exercise::create([
        'exam_id' => $examen->id,
        'exercise_type_id' => $type->id,
        'exam_section_id' => $section->id,
        'difficulty' => 'A2',
        'content' => ['instructions' => 'Hören Sie den Text.'],
        'questions' => $questions !== [] ? $questions : [[
            'id' => 'q1',
            'type' => 'note-completion',
            'notes' => [
                ['label' => 'Name:', 'value' => ''],
                ['label' => 'Beruf:', 'value' => ''],
            ],
            'correct_answers' => ['0' => 'Anna', '1' => 'Studentin'],
        ]],
    ]);
}

function reponseIa(string $passage): void
{
    Http::fake(['*' => Http::response(['choices' => [['message' => [
        'content' => json_encode(['audio_text' => $passage]),
    ]]]])]);
}

test('le passage ecrit est enregistre quand il contient les reponses', function () {
    $exercice = epreuveMuette();
    reponseIa('Guten Tag! Ich heiße Anna und ich bin Studentin in Berlin. '
        . 'Ich wohne seit zwei Jahren hier und lerne gern Deutsch mit meinen Freunden.');

    expect($exercice->answerableQuestions())->toBe([]);

    $this->artisan('prepla:sonoriser-ecoutes')->assertSuccessful();

    $exercice->refresh();
    expect($exercice->content['audio_text'])->toContain('Anna')
        ->and($exercice->content['audio_text'])->toContain('Studentin')
        // Et l'epreuve redevient jouable, sans rien changer d'autre.
        ->and($exercice->answerableQuestions())->toHaveCount(1)
        ->and($exercice->content['instructions'])->toBe('Hören Sie den Text.');
});

test('un passage qui oublie une reponse est refuse, rien n est enregistre', function () {
    $exercice = epreuveMuette();
    // « Studentin » n'y figure pas : a l'oral, la case resterait introuvable.
    reponseIa('Guten Tag! Ich heiße Anna und ich wohne in Berlin. '
        . 'Am Wochenende gehe ich gern ins Kino mit meinen Freunden aus der Nachbarschaft.');

    $this->artisan('prepla:sonoriser-ecoutes')->assertSuccessful();

    expect($exercice->refresh()->content)->not->toHaveKey('audio_text');
});

test('l essai a blanc ne touche pas la base', function () {
    $exercice = epreuveMuette();
    reponseIa('Guten Tag! Ich heiße Anna und ich bin Studentin in Berlin. '
        . 'Ich arbeite nebenbei in einem kleinen Café am Markt und lerne jeden Abend.');

    $this->artisan('prepla:sonoriser-ecoutes', ['--sec' => true])->assertSuccessful();

    expect($exercice->refresh()->content)->not->toHaveKey('audio_text');
});

test('une epreuve qui a deja son texte est laissee tranquille', function () {
    $exercice = epreuveMuette();
    $exercice->update(['content' => ['audio_text' => 'Déjà enregistré.']]);
    reponseIa('Ein ganz anderer Text, der niemals geschrieben werden sollte, weil es schon einen gibt.');

    $this->artisan('prepla:sonoriser-ecoutes')->assertSuccessful();

    expect($exercice->refresh()->content['audio_text'])->toBe('Déjà enregistré.');
});

test('pour un QCM on demande le libelle, pas la lettre', function () {
    $exercice = epreuveMuette('mcq', [[
        'id' => 'q1',
        'type' => 'mcq',
        'text' => 'Wo arbeitet Anna?',
        'options' => ['In einer Schule', 'In einem Café', 'In einem Büro', 'Im Krankenhaus'],
        'correct_answer' => 'B',
    ]]);
    reponseIa('Anna erzählt: Ich arbeite seit einem Jahr in einem Café im Zentrum von Berlin. '
        . 'Die Arbeit gefällt mir, weil ich jeden Tag viele nette Leute treffe.');

    $this->artisan('prepla:sonoriser-ecoutes')->assertSuccessful();

    // La consigne envoyee a l'IA porte le libelle du bon choix, jamais « B ».
    Http::assertSent(function ($requete) {
        $envoye = $requete->data()['messages'][0]['content'] ?? '';

        return str_contains($envoye, 'In einem Café') && ! str_contains($envoye, 'expected answer: B');
    });

    expect($exercice->refresh()->content['audio_text'])->toContain('Café');
});
