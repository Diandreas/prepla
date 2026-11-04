<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Le releve d'un mois termine : eleves actifs et palier d'un centre. Ecrit par
 * la commande centres:actifs, une ligne par centre et par mois.
 */
class CenterMonthlyActivity extends Model
{
    protected $table = 'center_monthly_activity';

    protected $fillable = [
        'center_id',
        'periode',
        'active_students',
        'palier',
        'computed_at',
    ];

    protected function casts(): array
    {
        return [
            'active_students' => 'integer',
            'computed_at' => 'datetime',
        ];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(LanguageCenter::class, 'center_id');
    }
}
