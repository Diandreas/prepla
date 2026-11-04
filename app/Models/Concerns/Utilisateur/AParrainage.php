<?php

namespace App\Models\Concerns\Utilisateur;

use App\Models\LanguageCenter;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Parrainage, cote compte : centre parrain (referred_by_center_id). Cote User.
 * Reserve au lot D. Les colonnes de rattachement restent hors de $fillable.
 */
trait AParrainage
{
    public function initializeAParrainage(): void
    {
        $this->mergeCasts(['referred_at' => 'datetime']);
    }

    public function centreParrain(): BelongsTo
    {
        return $this->belongsTo(LanguageCenter::class, 'referred_by_center_id');
    }
}
