<?php

namespace App\Models\Concerns\Utilisateur;

use App\Models\CenterAccessGrant;
use App\Models\LanguageCenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Acces vendus, cote eleve : acces en cours ouvert par son centre. Cote User.
 * Reserve au lot C. Toute regle de quota passe par EnsureExerciseQuota::exempte()
 * (partagee avec l'interface par HandleInertiaRequests, cle quotaExempt).
 */
trait AAccesVendus
{
    public function accesCentre(): HasMany
    {
        return $this->hasMany(CenterAccessGrant::class);
    }

    /**
     * L'acces vendu qui vaut en ce moment, s'il y en a un.
     *
     * Il faut tout a la fois : un acces commence, pas termine, pas coupe ; un
     * centre actif, toujours en formule revente ; et que la personne en soit
     * ENCORE eleve. Un eleve retire du centre perd l'acces, meme si la coupure
     * n'a pas ete enregistree (retrait par une autre voie).
     */
    public function accesCentreEnCours(): ?CenterAccessGrant
    {
        return CenterAccessGrant::enCours()
            ->where('user_id', $this->id)
            ->whereHas('center', fn (Builder $c) => $c
                ->where('is_active', true)
                ->where('formule', LanguageCenter::FORMULE_REVENTE))
            ->whereExists(fn ($q) => $q->selectRaw('1')
                ->from('center_user')
                ->whereColumn('center_user.center_id', 'center_access_grants.center_id')
                ->whereColumn('center_user.user_id', 'center_access_grants.user_id')
                ->where('center_user.role', 'student'))
            ->with('center')
            ->orderByDesc('ends_at')
            ->get()
            // Le marqueur libre-service l'emporte sur la colonne (LanguageCenter::formule()).
            ->first(fn (CenterAccessGrant $g) => $g->center->estEnRevente());
    }

    public function aUnAccesCentreEnCours(): bool
    {
        return $this->accesCentreEnCours() !== null;
    }
}
