<?php

use App\Services\AI\MistralEvaluationService;
use App\Services\AI\MistralService;
use Illuminate\Support\Facades\Http;

/**
 * Ce que l'apprenant repart avec, à la fin d'un exercice.
 *
 * L'IA produisait déjà trois choses pour chaque erreur — la règle, la forme
 * correcte, et une astuce pour la retenir — et le rapport de fin de séance
 * n'affichait QUE la règle : les deux autres étaient jetées à l'affichage. On
 * lisait « le participe passé s'accorde » et on repartait sans savoir à quoi
 * ressemble la bonne phrase, ni quoi en faire.
 *
 * Et pour une rédaction ou une prise de parole, rien ne montrait ce qu'il
 * fallait écrire : une correction dit ce qui ne va pas, pas ce qui va.
 */
function reponseDeLIA(array $charge): void
{
    Http::fake(['api.mistral.ai/*' => Http::response([
        'choices' => [['message' => ['content' => json_encode($charge)]]],
    ], 200)]);
}

test('la correction d une tache ouverte rapporte une reponse type', function () {
    reponseDeLIA([
        'isCorrect' => false,
        'accuracy' => 40,
        'error_category' => 'grammar.tense',
        'model_answer' => 'Gestern bin ich ins Kino gegangen und der Film hat mir sehr gefallen.',
        'explanation' => [
            'concept' => 'Le parfait se forme avec sein pour les verbes de mouvement.',
            'evidence' => 'ich <evidence>bin</evidence> gegangen',
            'hint' => 'Mouvement ou changement d’état : sein, pas haben.',
        ],
    ]);

    $resultat = app(MistralEvaluationService::class)
        ->evaluate('Raconte ta soirée.', 'Gestern ich habe gegangen ins Kino.', 'German', 'B1');

    expect($resultat['model_answer'])->toContain('Kino')
        ->and($resultat['explanation']['hint'])->not->toBeEmpty();
});

test('l IA est explicitement priee de fournir une reponse type a son niveau', function () {
    // Sans la consigne, le modele ne rend rien : c'est le prompt qui porte
    // l'exigence pedagogique, pas le code qui la lit.
    $service = app(MistralEvaluationService::class);
    $vu = null;

    $faux = new class($vu) extends MistralService
    {
        public static ?string $prompt = null;

        public function __construct($x = null) {}

        public function chat(array $messages, string $model = 'mistral-small-latest'): ?string
        {
            self::$prompt = json_encode($messages, JSON_UNESCAPED_UNICODE);

            return json_encode(['isCorrect' => true, 'accuracy' => 90]);
        }
    };
    app()->instance(MistralService::class, $faux);

    app(MistralEvaluationService::class)->evaluate('Raconte ta soirée.', 'Ich bin ins Kino gegangen.', 'German', 'B1');

    expect($faux::$prompt)->toContain('model_answer')
        ->and($faux::$prompt)->toContain("at the student's level");

    unset($service, $vu);
});

test('une reponse absente ne casse rien', function () {
    // Les anciens exercices et les modeles capricieux n'en renvoient pas.
    reponseDeLIA(['isCorrect' => true, 'accuracy' => 95]);

    $resultat = app(MistralEvaluationService::class)->evaluate('x', 'y', 'German', 'B1');

    expect($resultat['model_answer'])->toBeNull()
        ->and($resultat['accuracy'])->toBe(95);
});

test('la correction d une prise de parole rapporte aussi une reponse type', function () {
    reponseDeLIA([
        'isCorrect' => false,
        'accuracy' => 45,
        'model_answer' => 'Ich heiße Awa, ich komme aus Dakar und ich lerne seit zwei Jahren Deutsch.',
        'explanation' => ['concept' => 'Présente-toi en trois informations.', 'evidence' => '', 'hint' => 'Nom, origine, durée.'],
    ]);

    $resultat = app(MistralEvaluationService::class)
        ->evaluateSpeaking('Présente-toi.', 'Ich Awa.', 'German');

    expect($resultat['model_answer'])->toContain('Dakar');
});
