<?php

namespace App\Models;

use App\Services\Center\Domaines;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un sous-domaine (ecole.prepla.mirlab.cloud) ou un domaine propre
 * (prepa.ecole.cm) par lequel on entre directement chez un centre.
 *
 * Gere par le super administrateur seul (SuperAdmin\DomaineController). Toute
 * modification efface le cache de resolution : un domaine retire cesse
 * aussitot de mener au centre.
 */
class CenterDomain extends Model
{
    public const SOUS_DOMAINE = 'subdomain';

    public const DOMAINE_PROPRE = 'custom';

    protected $fillable = [
        'center_id',
        'host',
        'type',
        'verification_token',
        'verified_at',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'is_primary' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        $oublier = fn (CenterDomain $d) => Domaines::oublier($d->host, $d->getOriginal('host'));

        static::saved($oublier);
        static::deleted($oublier);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(LanguageCenter::class, 'center_id');
    }

    public function estVerifie(): bool
    {
        return $this->verified_at !== null;
    }

    /** Le nom de l'enregistrement TXT a creer chez l'hebergeur DNS du centre. */
    public function nomTxt(): string
    {
        return '_prepla.'.$this->host;
    }

    /** La valeur attendue dans cet enregistrement TXT. */
    public function valeurTxt(): string
    {
        return 'prepla-verification='.$this->verification_token;
    }
}
