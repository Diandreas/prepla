<?php

namespace App\Services\Content;

use App\Models\Exam;
use App\Models\Exercise;
use App\Models\ExerciseType;

/**
 * Les epreuves ecrites a la main, partie par partie, d'apres les documents
 * officiels des organismes.
 *
 * POURQUOI ELLES EXISTENT. Les structures d'examen sont desormais exactes : le
 * bon nombre de parties, le bon type de tache, le bon nombre d'items. Ce qu'on
 * y versait ne l'etait pas. Un exercice demande a l'IA respecte le format mais
 * invente un texte qui n'a ni la longueur, ni le registre, ni la densite
 * lexicale de l'epreuve reelle — et personne ne s'en apercoit, parce que la
 * coquille est juste. Ces fichiers-ci sont ecrits a la main et relus, et ils
 * servent de metre : on compare ce que produit l'IA a eux, au lieu de juger a
 * vue.
 *
 * CE QU'ELLE NE FAIT PAS. Elle ne fabrique aucun son. Les parties de
 * comprehension orale portent le texte de l'enregistrement dans `audio_text` ;
 * c'est `prepla:fabriquer-medias` qui en tire une piste, une fois, et l'inscrit
 * dans la question. L'IA reste utile la : pour la voix, et pour corriger les
 * productions ecrites et orales. Pas pour inventer le contenu.
 *
 * COMMENT ELLE SE BRANCHE. Elle n'a rien a intercepter. La pratique libre sert
 * d'abord un exercice deja en base que l'apprenant n'a pas fait, et ne demande
 * du neuf a l'IA qu'une fois le vivier epuise (voir PracticeController). Il
 * suffit donc que ces exercices SOIENT en base : le seeder les y met, et ils
 * passent avant toute generation.
 *
 * @see \Database\Seeders\EpreuvesVerifieesSeeder
 * @see StarterPracticeLibrary pour l'entrainement general, sans lien a un examen
 */
class BibliothequeEpreuvesVerifiees
{
    /** La version du catalogue : la changer force la reecriture des exercices. */
    public const VERSION = 'verifie-v1';

    private const RACINE = 'content/epreuves-verifiees';

    /** @var array<string, array>|null */
    private ?array $modules = null;

    /**
     * Tous les modules ecrits a la main, dans l'ordre des fichiers.
     *
     * @return array<string, array{exam: string, level: string, section: string, parts: array}>
     */
    public function modules(): array
    {
        if ($this->modules !== null) {
            return $this->modules;
        }

        $this->modules = [];
        foreach (glob(resource_path(self::RACINE.'/*/*/*.php')) ?: [] as $fichier) {
            $module = require $fichier;
            $cle = "{$module['exam']}/{$module['level']}/{$module['section']}";
            $this->modules[$cle] = $module + ['fichier' => $fichier];
        }
        ksort($this->modules);

        return $this->modules;
    }

    /**
     * Le type d'exercice qui joue cette partie, dans le bon module de l'examen.
     *
     * Un type porte un `slug` (le nom de la tache dans la config d'examen) et
     * une `component_key` (le composant du lecteur). Ce sont deux choses : le
     * slug `true-false` se joue dans `true-false-ng`. On valide contre la
     * seconde, on cherche par le premier.
     */
    public function typePour(Exam $exam, string $section, string $slug): ?ExerciseType
    {
        return ExerciseType::whereHas('section', fn ($q) => $q->where('exam_id', $exam->id)
            ->where('slug', $section))
            ->where('slug', $slug)
            ->first();
    }

    /**
     * Ecrit — ou met a jour — les exercices d'un module. Rien n'est supprime.
     *
     * @return array{ecrits: int, refus: array<int, string>}
     */
    public function installe(array $module): array
    {
        $exam = Exam::where('slug', $module['exam'])->first();
        if (! $exam) {
            return ['ecrits' => 0, 'refus' => ["examen {$module['exam']} absent de la base"]];
        }

        $ecrits = 0;
        $refus = [];

        foreach ($module['parts'] as $partie) {
            $ou = "{$module['exam']} {$module['level']} ".($partie['name'] ?? '?');

            $type = $this->typePour($exam, $module['section'], $partie['type']);
            if (! $type) {
                // Un type absent de `exercise_types` dans la config d'examen ne
                // recoit aucune ligne au seeding : la partie ne s'ouvrirait pas.
                $refus[] = "{$ou} : le type « {$partie['type']} » n'existe pas dans le module {$module['section']}";

                continue;
            }

            [$valide, $erreur, $questions] = ExerciseSchemaRegistry::validateQuestions(
                $type->component_key,
                $partie['questions'],
            );
            if (! $valide) {
                $refus[] = "{$ou} : {$erreur}";

                continue;
            }

            // Les identifiants restent uniques quand plusieurs exercices sont
            // reunis dans une meme session.
            $prefixe = self::VERSION.":{$module['exam']}:{$module['level']}:{$module['section']}:{$partie['part']}";
            $questions = array_map(
                fn (array $q) => array_merge($q, ['id' => "{$prefixe}:{$q['id']}"]),
                $questions,
            );

            $existant = Exercise::where('catalog_key', $prefixe)->first();
            $questions = $this->garderLesMedias($existant, $questions);

            Exercise::updateOrCreate(
                ['catalog_key' => $prefixe],
                [
                    'exam_id' => $exam->id,
                    'exercise_type_id' => $type->id,
                    'exam_section_id' => $type->section_id,
                    'difficulty' => $module['level'],
                    'is_ai_generated' => false,
                    'xp_reward' => 15,
                    'content' => array_merge($partie['content'] ?? [], [
                        'title' => $partie['title'],
                        'description' => $partie['description'] ?? '',
                        // La partie, pour que l'apprenant sache ou il est : trois
                        // Hoerverstehen partagent souvent le meme format de reponse.
                        'exam_part' => $partie['part'],
                        'exam_part_name' => $partie['name'],
                        'source' => 'epreuve-verifiee',
                        'source_label' => 'Écrit à la main d\'après le document officiel',
                    ]),
                    'questions' => $questions,
                ],
            );
            $ecrits++;
        }

        return ['ecrits' => $ecrits, 'refus' => $refus];
    }

    /** Ce qu'un reensemencement ne doit jamais effacer. */
    private const MEDIAS = ['audio_url', 'image_url', 'image_prompts_urls', 'image_options_urls'];

    /**
     * Reporte le son et les images deja fabriques sur les questions reecrites.
     *
     * Le fichier de contenu ne porte que le TEXTE de l'enregistrement : les
     * pistes sont fabriquees ensuite, une fois, et inscrites dans la question.
     * Sans ce report, relancer le seeder apres une simple correction de
     * consigne rendait muettes toutes les parties d'ecoute — sans rien dire,
     * et en obligeant a repayer la synthese.
     *
     * Le son suit la question par son identifiant. Si le texte de
     * l'enregistrement a change, l'ancienne piste ne vaut plus : on la laisse
     * tomber, et `prepla:fabriquer-medias` en refera une.
     *
     * @param  array<int, array>  $questions
     * @return array<int, array>
     */
    private function garderLesMedias(?Exercise $existant, array $questions): array
    {
        if (! $existant) {
            return $questions;
        }

        $avant = collect($existant->questions ?? [])->keyBy('id');

        return array_map(function (array $q) use ($avant) {
            $ancienne = $avant->get($q['id']);
            if (! $ancienne) {
                return $q;
            }

            // Le son dit le texte : si le texte change, le son ment.
            $memeTexte = ($ancienne['audio_text'] ?? null) === ($q['audio_text'] ?? null);

            foreach (self::MEDIAS as $cle) {
                if (empty($ancienne[$cle]) || ! empty($q[$cle])) {
                    continue;
                }
                if ($cle === 'audio_url' && ! $memeTexte) {
                    continue;
                }
                $q[$cle] = $ancienne[$cle];
            }

            return $q;
        }, $questions);
    }
}
