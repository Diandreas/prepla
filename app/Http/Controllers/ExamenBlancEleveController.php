<?php

namespace App\Http\Controllers;

use App\Models\MockExam;
use App\Services\Center\ExamensBlancsCentre;
use App\Services\Center\ParcoursEleveDeCentre;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

/**
 * L'eleve passe l'examen blanc que son centre lui a donne en devoir, dans le
 * lecteur d'examen blanc habituel (chronometre compris).
 *
 * Il ne l'ouvre que s'il figure dans un devoir publie d'une de ses classes :
 * un examen blanc de centre n'est pas un contenu public.
 */
class ExamenBlancEleveController extends Controller
{
    public function __construct(
        private ExamensBlancsCentre $examens,
        private ParcoursEleveDeCentre $parcours,
    ) {}

    public function show(Request $request, MockExam $mockExam): Response
    {
        $this->devoir($request, $mockExam);
        $mockExam->load('blueprint.exam');
        $exam = $mockExam->blueprint->exam;
        $exam->load(['language', 'sections' => fn ($q) => $q->where('slug', '!=', 'level-assessment')->with('exerciseTypes')]);

        $exercices = $this->examens->exercicesDe($mockExam);
        // La copie est corrigee sur exactement ce qui a ete servi.
        Cache::put($this->cle($request, $mockExam), $exercices->pluck('id')->all(), now()->addHours(6));

        return Inertia::render('practice/exam-simulator', [
            'exam' => $exam,
            'exercises' => $exercices,
            'totalExamsTime' => $mockExam->dureeTotale() ?: ($mockExam->blueprint->total_duration_minutes ?? 60),
            'mockExam' => $mockExam->only(['id', 'title', 'description']),
            'availableMockExams' => [],
            'submitUrl' => route('examens-blancs.store', $mockExam->id),
        ]);
    }

    public function store(Request $request, MockExam $mockExam): RedirectResponse
    {
        $devoir = $this->devoir($request, $mockExam);
        $donnees = $request->validate([
            'answers_by_exercise' => 'nullable|array',
            'time_spent' => 'required|integer|min:0',
        ]);

        // Un double envoi ne doit pas enregistrer deux copies.
        if (! Cache::pull($this->cle($request, $mockExam))) {
            return redirect()->route('devoirs.index')->with('error', 'Cet examen blanc a déjà été rendu ou a expiré.');
        }

        $passage = $this->examens->corriger($mockExam, $request->user(), $donnees['answers_by_exercise'] ?? [], (int) $donnees['time_spent'], $devoir);

        return redirect()->route('devoirs.index')->with('success', "Examen blanc rendu. Score : {$passage->score} %.");
    }

    private function devoir(Request $request, MockExam $mockExam): \App\Models\Assignment
    {
        abort_if($mockExam->center_id === null || ! $mockExam->is_published, 404);
        $devoir = $this->examens->devoirDeLEleve($request->user(), $mockExam, $this->parcours);
        abort_if($devoir === null, 404);

        return $devoir;
    }

    private function cle(Request $request, MockExam $mockExam): string
    {
        return "examen-blanc-centre:{$request->user()->id}:{$mockExam->id}";
    }
}
