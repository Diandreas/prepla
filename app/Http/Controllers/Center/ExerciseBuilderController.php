<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use App\Models\CenterMedia;
use App\Models\Exam;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\LanguageCenter;
use App\Services\AI\ExerciseGeneratorService;
use App\Services\Content\ExerciseSchemaRegistry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExerciseBuilderController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');

        $exercises = Exercise::where('center_id', $center->id)
            ->with('exerciseType:id,name,component_key,skill_type')
            ->latest()
            ->get()
            ->map(fn (Exercise $e) => [
                'id' => $e->id,
                'type' => $e->exerciseType?->name,
                'component_key' => $e->exerciseType?->component_key,
                'difficulty' => $e->difficulty,
                'questions_count' => is_array($e->questions) ? count($e->questions) : 0,
                'is_ai_generated' => $e->is_ai_generated,
            ]);

        return Inertia::render('center/exercises/index', [
            'exercises' => $exercises,
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('center/exercises/builder', $this->builderProps($request));
    }

    public function edit(Request $request, Exercise $exercise): Response
    {
        $this->authorize('update', $exercise);

        return Inertia::render('center/exercises/builder', array_merge(
            $this->builderProps($request),
            [
                'exercise' => [
                    'id' => $exercise->id,
                    'exercise_type_id' => $exercise->exercise_type_id,
                    'difficulty' => $exercise->difficulty,
                    'content' => $exercise->content,
                    'questions' => $exercise->questions,
                ],
            ],
        ));
    }

    /**
     * IA-assistée : génère un brouillon éditable SANS le publier comme contenu
     * global. On réutilise le générateur existant puis on récupère uniquement
     * content+questions (l'exercice temporaire est supprimé).
     */
    public function aiDraft(Request $request, ExerciseGeneratorService $generator)
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');

        $validated = $request->validate([
            'exercise_type_id' => 'required|exists:exercise_types,id',
            'exam_id' => 'required|exists:exams,id',
            'difficulty' => 'required|in:A1,A2,B1,B2,C1,C2',
        ]);

        $type = ExerciseType::findOrFail($validated['exercise_type_id']);
        $exam = Exam::findOrFail($validated['exam_id']);

        try {
            $draft = $generator->generate($type, $exam, $validated['difficulty']);
        } catch (\Throwable $e) {
            return response()->json(['error' => "La génération IA a échoué. Réessayez."], 422);
        }

        $payload = [
            'content' => $draft->content,
            'questions' => $draft->questions,
        ];

        // Discard the auto-persisted draft — the teacher will save the edited version.
        $draft->delete();

        return response()->json($payload);
    }

    public function store(Request $request)
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');
        $this->authorize('create', Exercise::class);

        $data = $this->validatePayload($request);

        $exercise = Exercise::create([
            'exercise_type_id' => $data['exercise_type_id'],
            'exam_id' => $data['exam_id'],
            'center_id' => $center->id,
            'creator_id' => $request->user()->id,
            'content' => $data['content'],
            'questions' => $data['questions'],
            'difficulty' => $data['difficulty'],
            'is_ai_generated' => $data['is_ai_generated'],
        ]);

        return redirect()->route('center.exercises.index')
            ->with('success', 'Exercice créé.');
    }

    public function update(Request $request, Exercise $exercise)
    {
        $this->authorize('update', $exercise);
        $data = $this->validatePayload($request);

        $exercise->update([
            'exercise_type_id' => $data['exercise_type_id'],
            'exam_id' => $data['exam_id'],
            'content' => $data['content'],
            'questions' => $data['questions'],
            'difficulty' => $data['difficulty'],
        ]);

        return redirect()->route('center.exercises.index')
            ->with('success', 'Exercice mis à jour.');
    }

    // ───────────────────────────── helpers ─────────────────────────────

    protected function builderProps(Request $request): array
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');

        return [
            // exam_id (résolu via la section) est indispensable au front pour filtrer
            // le sélecteur de Type par l'examen choisi — chaque examen a ses PROPRES
            // lignes ExerciseType (une par section), donc sans ce filtre le menu
            // affichait les ~230 types de TOUS les examens mélangés, avec des libellés
            // dupliqués des dizaines de fois ("Multiple Choice (reading)" pour IELTS,
            // TOEFL, Cambridge...).
            'exerciseTypes' => ExerciseType::whereIn(
                'component_key',
                array_keys(ExerciseSchemaRegistry::all())
            )->with('section:id,exam_id')
                ->get(['id', 'name', 'component_key', 'skill_type', 'section_id'])
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'component_key' => $t->component_key,
                    'skill_type' => $t->skill_type,
                    'exam_id' => $t->section?->exam_id,
                ]),
            'exams' => Exam::get(['id', 'name']),
            'defaultExamId' => $center->default_exam_id,
        ];
    }

    /** @return array{exercise_type_id:int,exam_id:int,difficulty:string,content:array,questions:array,is_ai_generated:bool} */
    protected function validatePayload(Request $request): array
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');

        $validated = $request->validate([
            'exercise_type_id' => 'required|exists:exercise_types,id',
            'exam_id' => 'required|exists:exams,id',
            'difficulty' => 'required|in:A1,A2,B1,B2,C1,C2',
            'content' => 'nullable|array',
            'questions' => 'required|array|min:1',
            'is_ai_generated' => 'boolean',
        ]);

        $type = ExerciseType::findOrFail($validated['exercise_type_id']);

        // Validate the authored questions against the type's family.
        [$ok, $error, $questions] = ExerciseSchemaRegistry::validateQuestions(
            $type->component_key,
            $validated['questions']
        );
        abort_if(! $ok, 422, $error);

        // Media safety: any image_url/audio_url referenced must belong to THIS
        // center's media library (never an arbitrary external URL).
        $this->assertMediaBelongsToCenter($center, $validated['content'] ?? [], $questions);

        return [
            'exercise_type_id' => (int) $validated['exercise_type_id'],
            'exam_id' => (int) $validated['exam_id'],
            'difficulty' => $validated['difficulty'],
            'content' => $validated['content'] ?? [],
            'questions' => $questions,
            'is_ai_generated' => (bool) ($validated['is_ai_generated'] ?? false),
        ];
    }

    protected function assertMediaBelongsToCenter(LanguageCenter $center, array $content, array $questions): void
    {
        $urls = [];
        foreach (['image_url', 'audio_url'] as $k) {
            if (! empty($content[$k])) {
                $urls[] = $content[$k];
            }
        }
        foreach ($questions as $q) {
            foreach (['image_url', 'audio_url'] as $k) {
                if (! empty($q[$k])) {
                    $urls[] = $q[$k];
                }
            }
        }
        if (empty($urls)) {
            return;
        }

        $ownedUrls = CenterMedia::where('center_id', $center->id)
            ->get()
            ->map(fn (CenterMedia $m) => $m->url)
            ->all();

        foreach ($urls as $url) {
            abort_if(! in_array($url, $ownedUrls, true), 422,
                "Un média référencé n'appartient pas à votre centre.");
        }
    }
}
