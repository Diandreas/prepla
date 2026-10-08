<?php

namespace App\Services\Content;

use App\Models\ExerciseType;
use App\Services\ImageLibraryService;

/**
 * Peut-on illustrer ce type d'exercice, et avec quoi ?
 *
 * Trois sources, de la plus sure a la plus souple :
 *  1. la bibliotheque d'images deja preparee (storage/app/public/exercise-images/) ;
 *  2. les donnees de graphique que le generateur produit lui-meme (chart_data) ;
 *  3. une description de scene, a partir de laquelle l'illustration est fabriquee a
 *     l'affichage — le meme procede que les QCM imagés utilisent depuis toujours.
 *
 * La bibliotheque ne contient aujourd'hui que des graphiques IELTS : sans la
 * troisieme source, une Bildbesprechung allemande n'aurait jamais d'image, et
 * l'apprenant lirait « decrivez l'image » devant un cadre vide.
 */
class ImagePromptLibrary
{
    /** Les composants qui savent fabriquer leur propre visuel a partir de donnees. */
    private const SAIT_SE_DESSINER = ['graph-description'];

    /** Les composants qui savent afficher une illustration decrite en mots. */
    private const SAIT_ILLUSTRER = ['graph-description', 'diagram-labeling', 'picture-mcq', 'speaking-recorder'];

    public function __construct(private ImageLibraryService $bibliotheque) {}

    /** Ce type d'exercice peut-il etre illustre d'une facon ou d'une autre ? */
    public function peutIllustrer(ExerciseType $type): bool
    {
        $composant = (string) $type->component_key;

        return in_array($composant, self::SAIT_SE_DESSINER, true)
            || in_array($composant, self::SAIT_ILLUSTRER, true);
    }

    /**
     * Une question visuelle porte-t-elle vraiment de quoi montrer son visuel ?
     *
     * Sert au filtre de service : plutot que d'afficher « Aucun graphique
     * disponible », on ecarte la question.
     */
    public static function questionIllustrable(array $question): bool
    {
        foreach (['image_url', 'image_prompt'] as $champ) {
            if (is_string($question[$champ] ?? null) && trim($question[$champ]) !== '') {
                return true;
            }
        }

        // Un graphique peut venir de ses donnees, sans aucune image.
        if (is_array($question['chart_data'] ?? null) && $question['chart_data'] !== []) {
            return true;
        }

        // Un QCM imagé peut decrire ses images une par une.
        foreach (['image_options', 'image_prompts'] as $champ) {
            if (is_array($question[$champ] ?? null) && $question[$champ] !== []) {
                return true;
            }
        }

        return false;
    }
}
