<?php

namespace App\Services\Content;

use App\Models\ExerciseType;

/**
 * Quels formats d'exercice ont du sens pour cet apprenant.
 *
 * Deux refus, et une seule regle pour les poser partout.
 *
 * 1. LE NIVEAU. Decrire une courbe, etiqueter un schema, commenter une image : ce
 *    sont des epreuves de B1 et au-dela. Elles etaient proposees a tout le monde, y
 *    compris a un debutant, dans la galerie des types de la competence — et le lien
 *    direct les servait sans rien verifier. Un apprenant A1 tombait sur « decrivez
 *    l'evolution du chomage en 150 mots ».
 *
 * 2. L'IMAGE. Ces formats ne veulent rien dire sans leur visuel. Faute d'image, le
 *    composant affichait « Aucun graphique disponible » : un enonce qui demande de
 *    decrire quelque chose d'absent. Un exercice visuel n'est donc servi que si son
 *    visuel existe vraiment — une image deja choisie, des donnees de graphique, ou
 *    une description a partir de laquelle l'illustration est fabriquee.
 *
 * Le refus n'est jamais silencieux : `raison()` donne la phrase a montrer.
 */
class ExerciseTypeSuitability
{
    /** L'echelle, du plus bas au plus haut. */
    private const ECHELLE = ['A0', 'A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

    /**
     * Palier minimum, par composant. Volontairement limite aux formats dont
     * l'exigence est manifeste : on ne restreint pas ce qui se pratique utilement
     * des le debut.
     */
    public const NIVEAU_MINIMUM = [
        // Decrire une courbe, un tableau, un diagramme (IELTS Task 1 et equivalents).
        'graph-description' => 'B1',
        // Etiqueter un schema, un plan, une carte.
        'diagram-labeling' => 'B1',
        // Synthese de plusieurs sources, discussion academique : epreuves avancees.
        'synthesis' => 'B2',
        'academic-discussion' => 'B2',
    ];

    /**
     * Palier minimum pour une tache orale de description d'image, par slug de type.
     * La Bildbesprechung de l'OSD Zertifikat B2 se passe a B2 : c'est son niveau.
     */
    public const NIVEAU_MINIMUM_PAR_SLUG = [
        'picture-description' => 'B2',
    ];

    /** Les composants qui n'ont aucun sens sans visuel. */
    public const COMPOSANTS_VISUELS = ['graph-description', 'diagram-labeling'];

    /** Les types oraux de description d'image, reconnus par leur slug. */
    public const SLUGS_IMAGE = ['picture-description'];

    public function __construct(private ImagePromptLibrary $images) {}

    /** Ce type convient-il a un apprenant de ce niveau ? */
    public function convient(ExerciseType $type, ?string $niveau): bool
    {
        return $this->raison($type, $niveau) === null;
    }

    /**
     * La raison du refus, a montrer a l'apprenant — ou null si le type convient.
     */
    public function raison(ExerciseType $type, ?string $niveau): ?string
    {
        $minimum = self::NIVEAU_MINIMUM_PAR_SLUG[$type->slug]
            ?? self::NIVEAU_MINIMUM[$type->component_key]
            ?? null;

        if ($minimum !== null && ! $this->atteint($niveau, $minimum)) {
            return "Cet exercice se prépare à partir du niveau {$minimum}. Continue ton parcours : il s'ouvrira tout seul.";
        }

        if ($this->exigeUneImage($type) && ! $this->images->peutIllustrer($type)) {
            return "Cet exercice a besoin d'une image que nous n'avons pas encore préparée pour cet examen.";
        }

        return null;
    }

    /** Ce type a-t-il besoin d'un visuel pour vouloir dire quelque chose ? */
    public function exigeUneImage(ExerciseType $type): bool
    {
        return in_array($type->component_key, self::COMPOSANTS_VISUELS, true)
            || in_array($type->slug, self::SLUGS_IMAGE, true);
    }

    /** Le niveau atteint est-il au moins le palier demande ? */
    private function atteint(?string $niveau, string $minimum): bool
    {
        $rangAtteint = array_search($niveau ?? 'A1', self::ECHELLE, true);
        $rangDemande = array_search($minimum, self::ECHELLE, true);

        if ($rangAtteint === false) {
            // Niveau inconnu : on ne bloque pas sur une valeur qu'on ne sait pas lire.
            return true;
        }

        return $rangAtteint >= $rangDemande;
    }
}
