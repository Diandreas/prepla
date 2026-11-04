<?php

namespace App\Models\Concerns;

use App\Models\Exam;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

/**
 * Marque d'un centre (nom affiche, logo, couleurs, marque blanche) et examens
 * proposes. Cote LanguageCenter. Reserve au lot A « Personnalisation » : chaque
 * lot ajoute ses methodes dans SON trait, pour ne pas se marcher dessus.
 *
 * Colonnes : display_name, logo_path, brand_primary, brand_accent,
 * hide_prepla_branding (migration 2026_11_04_100001), table center_exam (100002).
 */
trait AMarque
{
    /** Ajoute les colonnes de la marque au modele sans toucher LanguageCenter.php. */
    public function initializeAMarque(): void
    {
        $this->mergeFillable(['display_name', 'logo_path', 'brand_primary', 'brand_accent', 'hide_prepla_branding']);
        $this->mergeCasts(['hide_prepla_branding' => 'boolean']);
    }

    /**
     * Les examens retenus par le centre. Vide : tous les examens proposables.
     */
    public function examensProposes(): BelongsToMany
    {
        return $this->belongsToMany(Exam::class, 'center_exam', 'center_id', 'exam_id')->withTimestamps();
    }

    /**
     * Les identifiants des examens retenus, ou null si le centre n'en a retenu
     * aucun (il les propose alors tous). Lu a chaque appel : une selection
     * changee doit valoir tout de suite.
     *
     * @return array<int, int>|null
     */
    public function examensRetenus(): ?array
    {
        $ids = $this->examensProposes()->pluck('exams.id')->map(fn ($id) => (int) $id)->all();

        return $ids === [] ? null : $ids;
    }

    /** Le centre propose-t-il cet examen ? Toujours vrai sans selection. */
    public function proposeExamen(int|string|null $examId): bool
    {
        if ($examId === null || $examId === '') {
            return true;
        }

        $retenus = $this->examensRetenus();

        return $retenus === null || in_array((int) $examId, $retenus, true);
    }

    /** Le nom montre aux eleves : le nom affiche s'il est choisi, sinon le nom du centre. */
    public function nomAffiche(): string
    {
        $affiche = trim((string) $this->display_name);

        return $affiche !== '' ? $affiche : $this->name;
    }

    public function urlDuLogo(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    /**
     * Le centre a-t-il choisi une marque ? Sans rien de rempli, il garde celle
     * de PrePla : les centres existants ne changent pas d'apparence.
     */
    public function aUneMarque(): bool
    {
        return trim((string) $this->display_name) !== ''
            || $this->logo_path !== null
            || $this->brand_primary !== null
            || $this->brand_accent !== null
            || (bool) $this->hide_prepla_branding;
    }
}
