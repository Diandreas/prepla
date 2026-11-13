<?php

namespace App\Models\Concerns;

/**
 * Le parcours que suivent les eleves d'un centre, en plus de ses devoirs :
 * - 'libre'  : « Parcours PrePla par défaut ». L'entrainement PrePla, sur
 *   l'examen et le niveau de leur classe.
 * - 'centre' : « Parcours créé par le centre ». Le centre construit le parcours
 *   de chaque classe (App\Services\Center\ParcoursDeClasse) ; l'entrainement
 *   libre PrePla leur est masque.
 *
 * Les valeurs stockees n'ont pas change quand les libelles ont change.
 *
 * Range dans settings (json) : aucune colonne a ajouter, et un centre qui n'a
 * jamais touche ce reglage est en 'libre', comme avant que le reglage existe.
 * Le responsable le change dans la page de personnalisation.
 */
trait AContenuEleves
{
    /** La cle dans settings. */
    public const CONTENU_ELEVES = 'contenu_eleves';

    /** « Parcours PrePla par défaut » : devoirs + entrainement PrePla. La valeur par defaut. */
    public const CONTENU_LIBRE = 'libre';

    /** « Parcours créé par le centre » : devoirs + parcours de la classe. */
    public const CONTENU_CENTRE = 'centre';

    public const CONTENUS_ELEVES = [self::CONTENU_LIBRE, self::CONTENU_CENTRE];

    public function contenuEleves(): string
    {
        $valeur = ($this->settings ?? [])[self::CONTENU_ELEVES] ?? null;

        // Une valeur inconnue (ou absente) vaut 'libre' : on ne retire jamais
        // l'entrainement a des eleves sur une donnee qu'on ne comprend pas.
        return in_array($valeur, self::CONTENUS_ELEVES, true) ? $valeur : self::CONTENU_LIBRE;
    }

    /** Les eleves ne voient-ils que le travail donne par le centre ? */
    public function reserveSesElevesASonContenu(): bool
    {
        return $this->contenuEleves() === self::CONTENU_CENTRE;
    }

    /** Enregistre le reglage sans toucher les autres cles de settings. */
    public function choisirContenuEleves(string $contenu): void
    {
        $settings = $this->settings ?? [];
        $settings[self::CONTENU_ELEVES] = in_array($contenu, self::CONTENUS_ELEVES, true) ? $contenu : self::CONTENU_LIBRE;
        $this->settings = $settings;
        $this->save();
    }

    /**
     * La langue des analyses IA du centre : 'fr' (par defaut) ou 'en', pour un
     * centre anglophone ou une demonstration en anglais.
     */
    public function langueAnalyses(): string
    {
        return (($this->settings ?? [])['langue_analyses'] ?? 'fr') === 'en' ? 'en' : 'fr';
    }

    public function choisirLangueAnalyses(string $langue): void
    {
        $settings = $this->settings ?? [];
        $settings['langue_analyses'] = $langue === 'en' ? 'en' : 'fr';
        $this->settings = $settings;
        $this->save();
    }
}
