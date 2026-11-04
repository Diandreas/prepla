<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un acces ouvert par un centre en formule « revente » a l'un de ses eleves.
 *
 * Jamais supprime : couper un acces le date (revoked_at) et dit qui l'a fait
 * (revoked_by). La ligne reste dans le releve du mois ou elle a ete ouverte,
 * meme si l'eleve supprime son compte : user_id passe alors a null et
 * eleve_nom, copie a la creation, garde son nom.
 */
class CenterAccessGrant extends Model
{
    public const MOYENS_DE_PAIEMENT = ['mobile_money', 'cash', 'autre'];

    public const DEVISE = 'XAF';

    protected $fillable = [
        'center_id', 'user_id', 'eleve_nom', 'starts_at', 'ends_at',
        'amount', 'currency', 'payment_method', 'reference',
        'granted_by', 'revoked_at', 'revoked_by', 'note',
    ];

    protected static function booted(): void
    {
        // Copie le nom de l'eleve a l'ouverture : il survit a la suppression du compte.
        static::creating(function (CenterAccessGrant $acces) {
            if ($acces->eleve_nom === null && $acces->user_id !== null) {
                $acces->eleve_nom = User::whereKey($acces->user_id)->value('name');
            }
        });
    }

    /** Le nom de l'eleve : celui du compte, ou celui garde si le compte a ete supprime. */
    public function nomEleve(): ?string
    {
        return $this->user?->name ?? $this->eleve_nom;
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'revoked_at' => 'datetime',
            'amount' => 'integer',
        ];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(LanguageCenter::class, 'center_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    /** Non coupes et pas encore termines (en cours ou a venir). */
    public function scopeNonTermines(Builder $query): Builder
    {
        return $query->whereNull('revoked_at')->where('ends_at', '>', now());
    }

    /** En cours a cet instant : commence, pas termine, pas coupe. */
    public function scopeEnCours(Builder $query): Builder
    {
        return $query->whereNull('revoked_at')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now());
    }

    public function estEnCours(): bool
    {
        return $this->revoked_at === null
            && $this->starts_at->lte(now())
            && $this->ends_at->gt(now());
    }
}
