<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Un acces PrePla Plus paye par Mobile Money, ouvert a la main par l'equipe. */
class ActivationManuelle extends Model
{
    protected $table = 'activations_manuelles';

    protected $fillable = [
        'user_id', 'activated_by', 'email', 'formule', 'jours', 'moyen', 'montant', 'reference', 'debut', 'fin', 'annulee_le',
    ];

    protected function casts(): array
    {
        return ['debut' => 'datetime', 'fin' => 'datetime', 'annulee_le' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'activated_by');
    }
}
