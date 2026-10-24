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

test('une date qui ne se dit pas comme elle s ecrit aligne la grille', function () {
    // Le passage doit contenir la reponse MOT POUR MOT. Pour du texte cela
    // marche ; pour une date, non : on attend « 15th March » et une
    // conversation naturelle dit « the fifteenth of March ». Quatre exercices
    // de production restaient muets pour cette seule raison.
    $exercice = epreuveMuette('note-completion', [[
        'id' => 'q1', 'type' => 'note-completion', 'text' => 'Complète les notes.',
        'notes' => [['label' => 'Date', 'value' => '']],
        'correct_answers' => ['15th March'],
    ]]);

    Http::fake(['api.mistral.ai/*' => Http::sequence()
        // Premier essai : le passage dit la date autrement, la verification refuse.
        ->push(['choices' => [['message' => ['content' => json_encode([
            'audio_text' => 'A: When is the appointment? B: It is on the fifteenth of March, in the afternoon. A: Good, I will write that down right now.',
        ])]]]])
        // Second essai : l'IA dit sous quelle forme la reponse est prononcee.
        ->push(['choices' => [['message' => ['content' => json_encode([
            'audio_text' => 'A: When is the appointment? B: It is on the fifteenth of March, in the afternoon. A: Good, I will write that down right now.',
            'answers' => ['the fifteenth of March'],
        ])]]]]),
    ]);

    $this->artisan('prepla:sonoriser-ecoutes', ['--exercice' => $exercice->id])->assertSuccessful();

    $exercice->refresh();

    // La grille suit ce qui est reellement prononce : ce qu'on entend est ce
    // qu'on doit ecrire.
    expect($exercice->content['audio_text'])->toContain('fifteenth of March')
        ->and($exercice->questions[0]['correct_answers'][0])->toBe('the fifteenth of March');
});

test('un alignement qui ne figure pas dans le passage est refuse', function () {
    $exercice = epreuveMuette('note-completion', [[
        'id' => 'q1', 'type' => 'note-completion', 'text' => 'Complète les notes.',
        'notes' => [['label' => 'Date', 'value' => '']],
        'correct_answers' => ['15th March'],
    ]]);

    Http::fake(['api.mistral.ai/*' => Http::sequence()
        ->push(['choices' => [['message' => ['content' => json_encode([
            'audio_text' => 'A: When is the appointment? B: It is on the fifteenth of March, in the afternoon. A: Good, I will note it down.',
        ])]]]])
        // L'IA annonce une forme qui n'est PAS dans son propre passage :
        // sans verification, l'alignement ne serait qu'une facon de se donner raison.
        ->push(['choices' => [['message' => ['content' => json_encode([
            'audio_text' => 'A: When is the appointment? B: It is on the fifteenth of March, in the afternoon. A: Good, I will note it down.',
            'answers' => ['the twentieth of April'],
        ])]]]]),
    ]);

    $this->artisan('prepla:sonoriser-ecoutes', ['--exercice' => $exercice->id])->assertSuccessful();

    $exercice->refresh();
    expect($exercice->content['audio_text'] ?? null)->toBeNull()
        ->and($exercice->questions[0]['correct_answers'][0])->toBe('15th March');
});
