<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Releve mensuel d'un centre : un par centre, mois (YYYY-MM) et nature.
 *
 * Seul un brouillon se recalcule : une fois envoye ou paye, le releve est fige,
 * meme si la commande est relancee sur ce mois.
 */
class CenterStatement extends Model
{
    public const NATURES = ['revente', 'commission', 'paliers'];

    public const STATUTS = ['brouillon', 'envoye', 'paye'];

    protected $fillable = [
        'center_id', 'periode', 'nature', 'montant_brut', 'part_prepla',
        'lignes', 'statut', 'computed_at',
    ];

    protected function casts(): array
    {
        return [
            'lignes' => 'array',
            'montant_brut' => 'integer',
            'part_prepla' => 'integer',
            'computed_at' => 'datetime',
        ];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(LanguageCenter::class, 'center_id');
    }

    public function estFige(): bool
    {
        return $this->statut !== 'brouillon';
    }
}
