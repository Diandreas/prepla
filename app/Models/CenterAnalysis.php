<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une analyse IA gardee en base : celle d'un eleve, d'une classe ou du centre.
 * Voir App\Services\Center\AnalyseIa.
 */
class CenterAnalysis extends Model
{
    protected $table = 'center_analyses';

    protected $fillable = [
        'center_id',
        'sujet_type',
        'sujet_id',
        'contenu',
        'source',
        'faits_empreinte',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'contenu' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(LanguageCenter::class, 'center_id');
    }
}
