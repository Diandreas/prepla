<?php

namespace App\Services\Center;

use App\Models\CenterGoogleDrive;
use App\Models\CenterMedia;
use App\Models\Classroom;
use App\Models\ClassroomPathStep;
use App\Models\Exercise;
use App\Models\LanguageCenter;
use App\Models\MockExam;

/**
 * La sauvegarde des contenus d'un centre dans le dossier « Sauvegardes » de
 * SON Drive : exercices du centre, parcours de classe, examens blancs, classes
 * et liste des medias, en un fichier JSON.
 *
 * Chaque requete filtre sur center_id (ou sur les classes du centre) : rien
 * d'un autre centre, et aucune donnee personnelle d'eleve (pas de liste de
 * membres, pas de resultats).
 */
class SauvegardeDrive
{
    public function __construct(private GoogleDrive $drive) {}

    public function contenu(LanguageCenter $centre): array
    {
        $classes = Classroom::where('center_id', $centre->id)->get();
        $idsClasses = $classes->pluck('id');

        return [
            'format' => 'prepla-sauvegarde-centre',
            'version' => 1,
            'genere_le' => now()->toIso8601String(),
            'centre' => ['id' => $centre->id, 'nom' => $centre->name],
            'exercices' => Exercise::where('center_id', $centre->id)->get()->map->toArray()->values(),
            'parcours' => ClassroomPathStep::whereIn('classroom_id', $idsClasses)->orderBy('classroom_id')->orderBy('position')
                ->get()->map->toArray()->values(),
            'examens_blancs' => MockExam::where('center_id', $centre->id)->with('items')->get()->map->toArray()->values(),
            'classes' => $classes->map(fn (Classroom $c) => collect($c->toArray())->except(['students', 'members'])->all())->values(),
            'medias' => CenterMedia::where('center_id', $centre->id)->get()->map(fn (CenterMedia $m) => [
                'id' => $m->id,
                'type' => $m->type,
                'provider' => $m->provider,
                'drive_file_id' => $m->drive_file_id,
                'nom' => $m->original_name,
                'titre' => $m->title,
                'mime' => $m->mime,
                'taille' => $m->size,
                'source_rights' => $m->source_rights,
                'rights_certified_at' => $m->rights_certified_at?->toIso8601String(),
            ])->values(),
        ];
    }

    /** Depose la sauvegarde dans le Drive du centre et note la date. */
    public function sauvegarder(CenterGoogleDrive $drive): string
    {
        $centre = $drive->center;
        $json = json_encode($this->contenu($centre), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $nom = 'sauvegarde-prepla-'.now()->format('Y-m-d-His').'.json';

        $id = $this->drive->deposerJson($drive, 'Sauvegardes', $nom, (string) $json);
        $drive->forceFill(['derniere_sauvegarde_le' => now(), 'derniere_sauvegarde_id' => $id])->save();

        return $id;
    }
}
