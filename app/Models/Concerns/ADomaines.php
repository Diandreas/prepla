<?php

namespace App\Models\Concerns;

use App\Models\CenterDomain;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sous-domaines et domaines propres du centre (lot B). Cote LanguageCenter.
 */
trait ADomaines
{
    /**
     * Un centre supprime emporte ses domaines par la cle etrangere (cascade),
     * sans evenement de modele : on efface ici leur cache, pour qu'aucun
     * hote ne mene encore a son identifiant.
     */
    public static function bootADomaines(): void
    {
        static::deleting(function ($centre) {
            \App\Services\Center\Domaines::oublier(...$centre->domaines()->pluck('host')->all());
        });
    }

    public function domaines(): HasMany
    {
        return $this->hasMany(CenterDomain::class, 'center_id');
    }

    /**
     * Le domaine sur lequel fabriquer les liens du centre hors requete
     * (courriels, notifications) : le principal, s'il est verifie. Sinon aucun,
     * et les liens restent sur l'hote principal de PrePla.
     */
    public function domainePrincipal(): ?CenterDomain
    {
        return $this->domaines()
            ->whereNotNull('verified_at')
            ->where('is_primary', true)
            ->first();
    }
}
