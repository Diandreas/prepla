<?php

namespace App\Http\Controllers;

use App\Services\Content\BibliothequeEpreuvesVerifiees;
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
    /**
     * Les epreuves ecrites a la main, rangees par examen / niveau / module, puis
     * par numero de partie.
     *
     * @var array<string, array<int, array<string, mixed>>>
     */
    private array $exemples = [];

    public function __construct(private readonly BibliothequeEpreuvesVerifiees $bibliotheque) {}

    public function __invoke(Request $request)
    {
        $this->exemples = $this->indexerLesExemples();

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
            $sections = $this->sections($palier['sections'] ?? [], $slug, (string) $code);

            $paliers[] = [
                'code' => $code,
                'name' => $palier['name'] ?? $code,
                'duree' => $palier['total_duration'] ?? null,
                'sections' => $sections,
                'exemples' => $this->compterLesExemples($sections),
            ];
        }

        if ($paliers === []) {
            $sections = $config['sections']
                ?? array_merge(
                    $config['mandatory_sections'] ?? [],
                    $config['optional_sections'] ?? []
                );

            if ($sections !== []) {
                $decrites = $this->sections($sections, $slug, '');

                $paliers[] = [
                    'code' => null,
                    'name' => 'Structure unique',
                    'duree' => null,
                    'sections' => $decrites,
                    'exemples' => $this->compterLesExemples($decrites),
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
    private function sections(array $sections, string $examen, string $niveau): array
    {
        return collect($sections)->map(function (array $section) use ($examen, $niveau) {
            $cle = $examen.'/'.$niveau.'/'.($section['slug'] ?? '');

            $parties = collect($section['parts'] ?? $section['tasks'] ?? [])
                ->map(function ($partie, $rang) use ($cle) {
                    $decrite = $this->partie($partie, $rang);
                    $decrite['exemple'] = $this->exemple($cle, $decrite['numero']);

                    return $decrite;
                })
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

    /**
     * Combien de parties de ce palier portent un exercice reel.
     *
     * Affiche tel quel : un enseignant doit voir d'un coup d'oeil ou il y a
     * quelque chose a lire, sans deplier les niveaux un par un.
     *
     * @param  list<array<string, mixed>>  $sections
     */
    private function compterLesExemples(array $sections): int
    {
        $total = 0;
        foreach ($sections as $section) {
            foreach ($section['parties'] as $partie) {
                if (! empty($partie['exemple'])) {
                    $total++;
                }
            }
        }

        return $total;
    }

    /**
     * Les epreuves ecrites a la main, rangees par « examen/niveau/module » puis
     * par numero de partie. La bibliotheque sait deja les trouver : on ne refait
     * pas sa lecture, on la range pour l'affichage.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function indexerLesExemples(): array
    {
        $index = [];

        foreach ($this->bibliotheque->modules() as $cle => $module) {
            foreach ($module['parts'] ?? [] as $partie) {
                if (is_array($partie) && isset($partie['part'])) {
                    $index[$cle][(int) $partie['part']] = $partie;
                }
            }
        }

        return $index;
    }

    /**
     * L'exercice reel d'une partie, quand nous en avons ecrit un.
     *
     * Une structure se verifie sur le papier ; un exercice, non. Montrer le texte,
     * les questions, la bonne reponse et son explication est le seul moyen pour un
     * enseignant de dire si ce que nous servons ressemble vraiment a l'epreuve.
     *
     * Les parties ne portent pas toutes les memes cles — `options` ici,
     * `answer_options` la, `correct_answers` au pluriel pour un formulaire : on
     * les accepte toutes plutot que d'imposer une forme aux contenus existants.
     */
    private function exemple(string $cle, int $numero): ?array
    {
        $partie = $this->exemples[$cle][$numero] ?? null;

        if (! is_array($partie)) {
            return null;
        }

        $questions = collect($partie['questions'] ?? [])
            ->map(function (array $question) {
                $reponse = $question['correct_answer']
                    ?? $question['correct_answers']
                    ?? null;

                return [
                    'texte' => $question['text'] ?? null,
                    'audio' => $question['audio_text'] ?? null,
                    'champs' => array_values((array) ($question['fields'] ?? [])),
                    'options' => array_values((array) ($question['options'] ?? $question['answer_options'] ?? [])),
                    'reponse' => is_array($reponse) ? implode(' · ', $reponse) : $reponse,
                    'explication' => $question['explanation'] ?? null,
                ];
            })
            ->all();

        return [
            'titre' => $partie['title'] ?? null,
            'resume' => $partie['description'] ?? null,
            'consigne' => $partie['content']['instructions'] ?? null,
            'texte' => $partie['content']['passage'] ?? null,
            'audio' => $partie['content']['audio_text'] ?? null,
            'questions' => $questions,
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
