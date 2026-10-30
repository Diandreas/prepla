<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * La page publique des structures d'examen, ouverte sans compte.
 *
 * Elle existe pour qu'un enseignant puisse relire NOS donnees en face des
 * documents de l'organisme, sans avoir a s'inscrire ni a nous croire. Elle lit
 * donc les fichiers de configuration tels quels — aucune valeur n'est recopiee
 * ici — et affiche pour chaque partie sa source : « officiel » quand elle est
 * verifiee dans le document de l'organisme, « reconstituee » sinon. Cacher cette
 * distinction serait lui faire valider ce que nous n'avons pas verifie.
 */
class StructuresPubliquesController extends Controller
{
    public function __invoke(Request $request)
    {
        $langues = collect(config('languages.languages'))
            ->map(fn (array $langue) => [
                'slug' => $langue['slug'],
                'name' => $langue['name'],
                'exams' => collect($langue['exams'] ?? [])
                    ->map(fn ($slug) => $this->examen(is_array($slug) ? ($slug['slug'] ?? '') : $slug))
                    ->filter()
                    ->values()
                    ->all(),
            ])
            ->filter(fn (array $langue) => $langue['exams'] !== [])
            ->values();

        $choisie = $request->query('langue');
        $retenues = $langues->firstWhere('slug', $choisie)
            ? $langues->where('slug', $choisie)->values()
            : $langues;

        return view('structures', [
            'langues' => $langues,
            'retenues' => $retenues,
            'choisie' => $langues->firstWhere('slug', $choisie) ? $choisie : null,
        ]);
    }

    /**
     * Un examen, mis a plat pour l'affichage.
     *
     * Deux formes cohabitent dans les configurations, et c'est voulu : un examen
     * qui delivre un niveau est decrit palier par palier (`levels`), un examen de
     * positionnement n'a qu'une structure (`sections`). On ne les uniformise pas,
     * on les presente chacune pour ce qu'elle est.
     */
    private function examen(string $slug): ?array
    {
        if ($slug === '' || ! file_exists(config_path("exams/{$slug}.php"))) {
            return null;
        }

        $config = require config_path("exams/{$slug}.php");

        $paliers = [];

        foreach ($config['levels'] ?? [] as $code => $palier) {
            $paliers[] = [
                'code' => $code,
                'name' => $palier['name'] ?? $code,
                'duree' => $palier['total_duration'] ?? null,
                'sections' => $this->sections($palier['sections'] ?? []),
            ];
        }

        if ($paliers === []) {
            $sections = $config['sections']
                ?? array_merge(
                    $config['mandatory_sections'] ?? [],
                    $config['optional_sections'] ?? []
                );

            if ($sections !== []) {
                $paliers[] = [
                    'code' => null,
                    'name' => 'Structure unique',
                    'duree' => null,
                    'sections' => $this->sections($sections),
                ];
            }
        }

        return [
            'slug' => $slug,
            'name' => $config['name'] ?? $slug,
            'verifications' => $config['verifications'] ?? [],
            'paliers' => $paliers,
        ];
    }

    /** @return list<array<string, mixed>> */
    private function sections(array $sections): array
    {
        return collect($sections)->map(function (array $section) {
            $parties = collect($section['parts'] ?? $section['tasks'] ?? [])
                ->map(fn ($partie, $rang) => $this->partie($partie, $rang))
                ->all();

            return [
                'slug' => $section['slug'] ?? '',
                'name' => $section['name'] ?? ($section['slug'] ?? ''),
                'competence' => $section['skill_type'] ?? null,
                'duree' => $section['time_limit'] ?? null,
                'poids' => $section['scoring_weight'] ?? null,
                'max' => $section['max_score'] ?? null,
                'description' => $section['description'] ?? null,
                'questions' => $this->questions($parties),
                'parties' => $parties,
                'formats' => $section['exercise_types'] ?? [],
            ];
        })->all();
    }

    /** @param  array<string, mixed>|string  $partie */
    private function partie($partie, int $rang): array
    {
        if (! is_array($partie)) {
            return ['numero' => $rang + 1, 'name' => (string) $partie, 'formats' => []];
        }

        // Une partie annonce UN format, ou PLUSIEURS quand l'epreuve laisse le
        // choix du type de texte au candidat.
        $formats = is_array($partie['types'] ?? null)
            ? $partie['types']
            : array_filter([$partie['type'] ?? null]);

        return [
            'numero' => $partie['part'] ?? ($rang + 1),
            'name' => $partie['name'] ?? null,
            'intitule' => $partie['name_fr'] ?? null,
            'tache' => $partie['task'] ?? $partie['description'] ?? null,
            'items' => $partie['items'] ?? null,
            'points' => $partie['points'] ?? null,
            'formats' => array_values($formats),
            'source' => $partie['source'] ?? null,
        ];
    }

    /** @param  list<array<string, mixed>>  $parties */
    private function questions(array $parties): ?int
    {
        $somme = 0;
        foreach ($parties as $partie) {
            if (is_int($partie['items'] ?? null)) {
                $somme += $partie['items'];
            }
        }

        return $somme > 0 ? $somme : null;
    }
}
