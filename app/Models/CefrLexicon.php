<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CefrLexicon extends Model
{
    use HasFactory;

    protected $fillable = [
        'language',
        'word',
        'lemma',
        'pos',
        'level',
        'freq_total',
        'freq_per_level',
        'source',
    ];

    protected $casts = [
        'freq_per_level' => 'array',
        'freq_total' => 'float',
    ];

    public const LEVELS = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

    public const SOURCES = ['cefrj', 'efllex', 'flelex_tt', 'flelex_crf', 'goethe'];

    public const SOURCE_PRIORITY = [
        'en' => ['cefrj', 'efllex'],
        'fr' => ['flelex_tt', 'flelex_crf'],
        'de' => ['goethe'],
    ];

    public static function levelRank(string $level): int
    {
        return array_search($level, self::LEVELS) ?: 0;
    }
}
