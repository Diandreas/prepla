<?php

namespace App\Services\Cours;

use App\Models\CenterMedia;
use App\Models\CourseBlock;
use App\Models\CourseModule;
use App\Models\CoursePath;
use App\Models\CourseSession;
use App\Models\Exercise;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * L'editeur de parcours du centre : l'arbre complet envoye a l'editeur,
 * le reordonnancement (glisser-deposer ou haut/bas) et les duplications.
 */
class CreateurDeParcours
{
    /** L'arbre du parcours tel que l'editeur le manipule. */
    public function arbre(CoursePath $parcours): array
    {
        $parcours->load('modules.sessions.blocks');
        $exercices = Exercise::whereIn('id', $parcours->modules->flatMap(fn ($m) => $m->sessions)->flatMap(fn ($s) => $s->blocks)
            ->where('type', 'exercice')->map(fn ($b) => $b->contenu['exercise_id'] ?? null)->filter())
            ->with('exerciseType:id,name')->get()->keyBy('id');

        return [
            'id' => $parcours->id,
            'titre' => $parcours->titre,
            'description' => $parcours->description,
            'statut' => $parcours->statut,
            'classroom_id' => $parcours->classroom_id,
            'modules' => $parcours->modules->map(fn (CourseModule $m) => [
                'id' => $m->id,
                'titre' => $m->titre,
                'description' => $m->description,
                'seances' => $m->sessions->map(fn (CourseSession $s) => [
                    'id' => $s->id,
                    'titre' => $s->titre,
                    'description' => $s->description,
                    'statut' => $s->statut,
                    'obligatoire' => $s->obligatoire,
                    'blocs_ordre_impose' => $s->blocs_ordre_impose,
                    'duree_minutes' => $s->duree_minutes,
                    'deblocage' => $s->deblocage,
                    'seuil_quiz' => $s->seuil_quiz,
                    'deblocage_at' => $s->deblocage_at?->format('Y-m-d\TH:i'),
                    'debut_at' => $s->debut_at?->format('Y-m-d\TH:i'),
                    'limite_at' => $s->limite_at?->format('Y-m-d\TH:i'),
                    'blocs' => $s->blocks->map(fn (CourseBlock $b) => [
                        'id' => $b->id,
                        'type' => $b->type,
                        'titre' => $b->titre,
                        'obligatoire' => $b->obligatoire,
                        'contenu' => $b->contenu ?? [],
                        'exercice' => $b->type === 'exercice' && ($e = $exercices->get($b->contenu['exercise_id'] ?? 0))
                            ? ['id' => $e->id, 'titre' => (string) (($e->content['title'] ?? null) ?: ($e->exerciseType?->name ?? 'Exercice'))]
                            : null,
                    ])->values(),
                ])->values(),
            ])->values(),
        ];
    }

    /**
     * Applique un nouvel ordre complet : modules, seances (eventuellement
     * deplacees d'un module a l'autre) et blocs (d'une seance a l'autre).
     * Tout identifiant doit appartenir a ce parcours.
     *
     * @param  list<array{id: int, seances: list<array{id: int, blocs: list<int>}>}>  $ordre
     */
    public function reordonner(CoursePath $parcours, array $ordre): void
    {
        $parcours->load('modules.sessions.blocks');
        $modules = $parcours->modules->keyBy('id');
        $seances = $parcours->modules->flatMap(fn ($m) => $m->sessions)->keyBy('id');
        $blocs = $seances->flatMap(fn ($s) => $s->blocks)->keyBy('id');

        $vusM = $vusS = $vusB = [];
        foreach ($ordre as $m) {
            $vusM[] = (int) ($m['id'] ?? 0);
            foreach (($m['seances'] ?? []) as $s) {
                $vusS[] = (int) ($s['id'] ?? 0);
                foreach (($s['blocs'] ?? []) as $b) {
                    $vusB[] = (int) $b;
                }
            }
        }
        $memes = fn (array $a, $b) => collect($a)->sort()->values()->all() === $b->keys()->map(fn ($k) => (int) $k)->sort()->values()->all();
        if (! $memes($vusM, $modules) || ! $memes($vusS, $seances) || ! $memes($vusB, $blocs)) {
            throw ValidationException::withMessages(['ordre' => 'Le parcours a changé : rechargez la page.']);
        }

        DB::transaction(function () use ($ordre, $modules, $seances, $blocs) {
            foreach ($ordre as $i => $m) {
                $modules[(int) $m['id']]->update(['position' => $i + 1]);
                foreach (($m['seances'] ?? []) as $j => $s) {
                    $seances[(int) $s['id']]->update(['position' => $j + 1, 'course_module_id' => (int) $m['id']]);
                    foreach (($s['blocs'] ?? []) as $k => $b) {
                        $blocs[(int) $b]->update(['position' => $k + 1, 'course_session_id' => (int) $s['id']]);
                    }
                }
            }
        });
    }

    /** Une copie de la seance, juste apres elle, en brouillon. */
    public function dupliquerSeance(CourseSession $seance, ?CourseModule $vers = null): CourseSession
    {
        return DB::transaction(function () use ($seance, $vers) {
            $module = $vers ?? $seance->module;
            if ($vers === null) {
                $module->sessions()->where('position', '>', $seance->position)->increment('position');
            }
            $copie = $seance->replicate(['created_at', 'updated_at']);
            $copie->course_module_id = $module->id;
            $copie->position = $vers === null ? $seance->position + 1 : ((int) $module->sessions()->max('position')) + 1;
            if ($vers === null) {
                $copie->titre = mb_substr($seance->titre.' (copie)', 0, 160);
                $copie->statut = CoursePath::BROUILLON;
            }
            $copie->save();
            foreach ($seance->blocks as $b) {
                $nb = $b->replicate(['created_at', 'updated_at']);
                $nb->course_session_id = $copie->id;
                $nb->save();
            }

            return $copie;
        });
    }

    /**
     * Copie complete d'un parcours, vers une classe du meme centre ou comme
     * modele (classe nulle). La copie part en brouillon : le centre la relit
     * avant que les eleves ne la voient.
     */
    public function dupliquerParcours(CoursePath $source, ?int $classeId, int $auteurId): CoursePath
    {
        return DB::transaction(function () use ($source, $classeId, $auteurId) {
            $source->load('modules.sessions.blocks');
            $copie = CoursePath::create([
                'center_id' => $source->center_id,
                'classroom_id' => $classeId,
                'titre' => $source->titre,
                'description' => $source->description,
                'statut' => CoursePath::BROUILLON,
                'source_path_id' => $source->id,
                'created_by' => $auteurId,
            ]);
            foreach ($source->modules as $m) {
                $nm = $copie->modules()->create(['position' => $m->position, 'titre' => $m->titre, 'description' => $m->description]);
                foreach ($m->sessions as $s) {
                    $this->dupliquerSeance($s, $nm);
                }
            }

            return $copie;
        });
    }

    /** Les medias du centre que l'editeur peut choisir. */
    public function medias(int $centreId): array
    {
        return CenterMedia::where('center_id', $centreId)->latest()->limit(500)->get()
            ->map(fn (CenterMedia $m) => BlocsDeCours::ficheMedia($m) + ['nom' => $m->original_name])
            ->all();
    }
}
