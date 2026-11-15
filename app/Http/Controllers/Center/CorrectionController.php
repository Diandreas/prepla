<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use App\Models\LanguageCenter;
use App\Models\UserExerciseAttempt;
use App\Services\Center\CorrectionHumaine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * « A corriger » : l'enseignant relit les redactions et prises de parole de
 * ses eleves, voit la note de l'IA, donne la sienne et un commentaire.
 * Droits : CorrectionHumaine::peutRelire (centre courant, classes animees).
 */
class CorrectionController extends Controller
{
    public function __construct(private CorrectionHumaine $corrections) {}

    public function index(Request $request): Response
    {
        $centre = $this->centre($request);
        $classes = $this->corrections->classes($centre, $request->user());
        $classeId = $request->integer('classe') ?: null;
        $revues = $request->query('vue') === 'revues';

        return Inertia::render('center/corrections/index', [
            'productions' => $this->corrections->file($centre, $request->user(), $classeId, $revues),
            'classes' => $classes->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values(),
            'filtres' => ['classe' => $classeId, 'vue' => $revues ? 'revues' : 'a_corriger'],
        ]);
    }

    public function show(Request $request, UserExerciseAttempt $attempt): Response
    {
        $this->verifier($request, $attempt);

        return Inertia::render('center/corrections/show', [
            'production' => $this->corrections->detail($attempt),
            'dimensions' => CorrectionHumaine::DIMENSIONS,
        ]);
    }

    public function store(Request $request, UserExerciseAttempt $attempt): RedirectResponse
    {
        $centre = $this->verifier($request, $attempt);
        $regles = ['commentaire' => ['nullable', 'string', 'max:5000']];
        foreach (CorrectionHumaine::DIMENSIONS as $d) {
            $regles["sous_notes.{$d}"] = ['required', 'numeric', 'min:0', 'max:9'];
        }
        $donnees = $request->validate($regles);

        $this->corrections->noter($centre, $request->user(), $attempt, $donnees['sous_notes'], $donnees['commentaire'] ?? null);

        return redirect()->route('center.corrections.index')->with('success', 'Correction enregistrée.');
    }

    public function audio(Request $request, UserExerciseAttempt $attempt, string $question): StreamedResponse
    {
        $this->verifier($request, $attempt);
        $chemin = ($attempt->audios ?? [])[$question] ?? null;
        abort_unless(is_string($chemin) && Storage::disk(CorrectionHumaine::DISQUE)->exists($chemin), 404);

        return Storage::disk(CorrectionHumaine::DISQUE)->response($chemin);
    }

    private function centre(Request $request): LanguageCenter
    {
        /** @var LanguageCenter $centre */
        $centre = $request->attributes->get('center');

        return $centre;
    }

    private function verifier(Request $request, UserExerciseAttempt $attempt): LanguageCenter
    {
        $centre = $this->centre($request);
        abort_unless($this->corrections->peutRelire($centre, $request->user(), $attempt), 403);

        return $centre;
    }
}
