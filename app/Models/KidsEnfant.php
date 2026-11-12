<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Un enfant de PrePla Kids. Pas de compte : il joue depuis la session de son parent. */
class KidsEnfant extends Model
{
    protected $table = 'kids_enfants';

    protected $fillable = ['parent_id', 'prenom', 'age', 'avatar', 'langue', 'langue_aide'];

    protected $attributes = ['etoiles' => 0, 'serie' => 0, 'langue_aide' => 'fr'];

    protected function casts(): array
    {
        return ['dernier_jour' => 'date', 'age' => 'integer', 'etoiles' => 'integer', 'serie' => 'integer'];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function resultats(): HasMany
    {
        return $this->hasMany(KidsResultat::class, 'enfant_id');
    }
}
