<?php

use App\Models\Exam;
use App\Models\ExerciseType;
use App\Services\AI\ExerciseGeneratorService;
use App\Services\AI\MistralService;
use App\Services\Content\StructureOfficielle;

/**
 * Un exercice d'entrainement doit porter le nombre d'items de SA partie.
 *
 * C'est ce qui separe un bon exercice de langue d'un entrainement a l'examen.
 * Le Lesen Teil 2 du Goethe B1 compte six questions a choix multiple : avec
 * trois, l'apprenant travaille sa comprehension mais decouvre le format le jour
 * de l'epreuve, et son temps ne tombe plus juste.
 *
 * Le defaut etait double : le modele ne recevait le nombre officiel que lorsque
 * l'apprenant avait nomme une partie — presque jamais —, et rien ne verifiait
 * le resultat. Releve en production avant correction : 33 exercices sur 121.
 */
beforeEach(function () {
    $this->seed(\Database\Seeders\LanguageSeeder::class);
    $this->seed(\Database\Seeders\ExamSeeder::class);
});

function typeDeLecture(string $examen, string $section, string $slug): array
{
    $exam = Exam::where('slug', $examen)->sole();
    $type = ExerciseType::whereHas('section', fn ($q) => $q->where('exam_id', $exam->id)
        ->where('slug', $section))->where('slug', $slug)->sole();

    return [$exam, $type];
}

/** Une reponse du fournisseur portant N questions a choix multiple. */
function reponseIAStructure(int $questions): string
{
    $liste = [];
    foreach (range(1, $questions) as $i) {
        $liste[] = [
            'id' => "q{$i}", 'type' => 'mcq',
            'text' => "Frage {$i}?",
            'options' => ['Eins', 'Zwei', 'Drei'],
            'correct_answer' => 'A',
            'explanation' => 'Parce que.',
        ];
    }

    return json_encode([
        'content' => ['title' => 'Test', 'instructions' => 'Lis.', 'passage' => 'Ein Text.'],
        'questions' => $liste,
    ]);
}

test('la structure connait le nombre d items de chaque partie', function () {
    [$exam, $type] = typeDeLecture('goethe', 'lesen', 'mcq');

    // Au Goethe B1, deux parties sont des QCM : le Teil 2 en six questions et
    // le Teil 5 en quatre.
    expect(app(StructureOfficielle::class)->itemsAttendus($exam, 'B1', $type))
        ->toEqualCanonicalizing([6, 4]);
});

test('un exercice au mauvais nombre d items est refuse, et l essai recommence', function () {
    [$exam, $type] = typeDeLecture('goethe', 'lesen', 'mcq');

    // Premier essai : trois questions, le gabarit d'autrefois. Second : six,
    // comme l'epreuve.
    $this->mock(MistralService::class, function ($mock) {
        $mock->shouldReceive('chat')->twice()
            ->andReturn(reponseIAStructure(3), reponseIAStructure(6));
    });

    $exercice = app(ExerciseGeneratorService::class)->generate($type, $exam, 'B1');

    expect($exercice->questions)->toHaveCount(6);
});

test('le nombre officiel est demande meme sans partie nommee', function () {
    // Le defaut d'origine : la consigne n'arrivait qu'avec « ?partie=2 ».
    [$exam, $type] = typeDeLecture('goethe', 'lesen', 'mcq');

    $promptVu = null;
    $this->mock(MistralService::class, function ($mock) use (&$promptVu) {
        $mock->shouldReceive('chat')->once()->andReturnUsing(function (array $messages) use (&$promptVu) {
            $promptVu = $messages[1]['content'] ?? '';

            return reponseIAStructure(6);
        });
    });

    app(ExerciseGeneratorService::class)->generate($type, $exam, 'B1');

    expect($promptVu)->toContain('EXACTLY 6')
        ->and($promptVu)->toContain('Lesen, Teil 2');
});

test('aucun exercice n est enregistre si le compte reste faux', function () {
    [$exam, $type] = typeDeLecture('goethe', 'lesen', 'mcq');

    $this->mock(MistralService::class, function ($mock) {
        $mock->shouldReceive('chat')->twice()->andReturn(reponseIAStructure(3), reponseIAStructure(3));
    });

    $erreur = null;
    try {
        app(ExerciseGeneratorService::class)->generate($type, $exam, 'B1');
    } catch (\Throwable $e) {
        $erreur = $e->getMessage();
    }

    // Et la raison est dite : « AI failed » tout seul coutait une enquete.
    expect($erreur)->toContain('3 item(s) au lieu des 6 de la partie officielle');

    // Mieux vaut rien que du faux : l'ecran retombe sur un exercice existant.
    expect(\App\Models\Exercise::count())->toBe(0);
});
