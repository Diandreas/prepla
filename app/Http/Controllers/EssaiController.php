<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use App\Services\Content\ExercicesDEssai;
use App\Services\ExerciseScoringService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Essayer un exercice SANS COMPTE.
 *
 * On ne force pas quelqu'un a s'inscrire pour decouvrir ce qu'il achete. Le
 * visiteur joue le vrai lecteur, sur un vrai exercice, et recoit la vraie
 * correction avec ses explications — puis on l'invite a creer son compte.
 *
 * Rien n'est enregistre : ni tentative, ni XP, ni progression. Il n'y a pas
 * d'apprenant a qui les rattacher, et un essai ne doit pas peser dans les
 * statistiques. Ce qui est ouvert est decide par ExercicesDEssai, et rien
 * d'autre : un identifiant hors de sa liste repond 404, pour qu'on ne puisse
 * pas faire corriger n'importe quel exercice de la base.
 */
class EssaiController extends Controller
{
    public function __construct(private readonly ExercicesDEssai $essais) {}

    public function index(): Response
    {
        return Inertia::render('essai/index', [
            'langues' => $this->essais->catalogue(),
        ]);
    }

    public function show(Exercise $exercise): Response
    {
        abort_unless($this->essais->estEssayable($exercise), 404);

        $exercise->load(['exerciseType.section', 'exam.language']);

        return Inertia::render('essai/show', [
            'exercise' => $this->essais->pourLeLecteur($exercise),
            'submitUrl' => route('essai.corriger', $exercise),
        ]);
    }

    public function corriger(Request $request, Exercise $exercise, ExerciseScoringService $correcteur): Response
    {
        abort_unless($this->essais->estEssayable($exercise), 404);

        $valide = $request->validate([
            'answers' => 'required|array',
            'time_spent' => 'nullable|integer|min:0',
        ]);

        $exercise->load(['exerciseType.section', 'exam.language']);

        // estEssayable() garantit des formats a reponse fermee dont chaque
        // question porte son explication : la correction ne peut donc pas
        // appeler l'IA, meme sur une reponse fausse.
        $resultat = $correcteur->score($exercise, $valide['answers']);

        $parQuestion = collect($resultat['feedback'] ?? [])->keyBy('question_id');

        return Inertia::render('essai/resultat', [
            'exercise' => [
                'id' => $exercise->id,
                'titre' => $exercise->content['title'] ?? $exercise->exerciseType->name,
                'examen' => $exercise->exam->name,
                'niveau' => $exercise->difficulty,
                'officiel' => ExercicesDEssai::SOURCES[$exercise->content['source'] ?? ''] ?? false,
            ],
            // `score` est le NOMBRE de bonnes reponses, `accuracy` le pourcentage.
            // Une question que le visiteur n'a pas pu faire (panne de notre cote)
            // sort du compte, comme pour un apprenant.
            'score' => [
                'justes' => (int) ($resultat['score'] ?? 0),
                'total' => $parQuestion->reject(fn ($q) => ! empty($q['technical_failure']))->count(),
                'pourcentage' => (float) ($resultat['accuracy'] ?? 0),
            ],
            'questions' => collect($exercise->answerableQuestions())
                ->map(function (array $question) use ($parQuestion, $valide) {
                    $retour = $parQuestion->get($question['id'] ?? '') ?? [];

                    return [
                        'id' => $question['id'] ?? null,
                        'texte' => $question['text'] ?? $question['prompt'] ?? null,
                        'options' => array_values((array) ($question['options'] ?? $question['answer_options'] ?? [])),
                        'ta_reponse' => $valide['answers'][$question['id'] ?? ''] ?? null,
                        'bonne_reponse' => $retour['correct_answer'] ?? $question['correct_answer'] ?? $question['correct_answers'] ?? null,
                        'juste' => (bool) ($retour['correct'] ?? false),
                        'explication' => $retour['explanation'] ?? $question['explanation'] ?? null,
                    ];
                })
                ->values()
                ->all(),
        ]);
    }
}
