<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Une partie d'un enfant : un jeu d'un theme, son score et ses etoiles. */
class KidsResultat extends Model
{
    protected $table = 'kids_resultats';

    public const UPDATED_AT = null;

    protected $fillable = ['enfant_id', 'theme', 'activite', 'score', 'etoiles', 'duree', 'mots_rates'];

    protected function casts(): array
    {
        return ['mots_rates' => 'array', 'score' => 'integer', 'etoiles' => 'integer', 'duree' => 'integer'];
    }

    public function enfant(): BelongsTo
    {
        return $this->belongsTo(KidsEnfant::class, 'enfant_id');
    }
}
