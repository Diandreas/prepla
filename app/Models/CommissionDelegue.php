<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ce qu'un paiement d'etudiant rapporte a son delegue. Montants en FCFA.
 * Une commission annulee (activation annulee) ne compte plus ; si elle avait
 * deja ete versee, elle reste « a reprendre ».
 */
class CommissionDelegue extends Model
{
    protected $table = 'commissions_delegues';

    protected $fillable = ['delegue_id', 'user_id', 'activation_manuelle_id', 'montant', 'taux', 'commission', 'periode', 'versee_le', 'annulee_le'];

    protected function casts(): array
    {
        return ['versee_le' => 'datetime', 'annulee_le' => 'datetime'];
    }

    /** A verser : comptee, pas encore versee. */
    public function scopeDue(Builder $q): Builder
    {
        return $q->whereNull('annulee_le')->whereNull('versee_le');
    }

    public function delegue(): BelongsTo
    {
        return $this->belongsTo(Delegue::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
