<?php

namespace App\Services;

use App\Models\CefrLexicon;
use Illuminate\Support\Facades\Cache;

/**
 * Service de référence pour interroger le lexique CEFR multi-langue.
 *
 * Usage typique :
 *   $level = $lexicon->levelFor('serendipity', 'en');   // 'C2'
 *   $words = $lexicon->wordsBelow('B2', 'fr');          // toutes les entrées A1-B1
 *   $coverage = $lexicon->coverage($text, 'en');         // % de mots ≤ B1 dans un texte
 */
class LexiconService
{
    public function __construct(
        private readonly int $cacheTtl = 86400  // 24h
    ) {}

    /**
     * Récupère le niveau CEFR d'un mot pour une langue donnée.
     * Renvoie le niveau le plus bas où le mot est attesté.
     *
     * @return string|null 'A1'..'C2' ou null si inconnu
     */
    public function levelFor(string $word, string $language): ?string
    {
        $key = "lex:{$language}:" . strtolower($word);

        return Cache::remember($key, $this->cacheTtl, function () use ($word, $language) {
            // Tri en PHP plutôt que via SQL FIELD() pour rester portable MySQL/SQLite/PostgreSQL
            $levels = CefrLexicon::query()
                ->where('language', $language)
                ->where('word', strtolower(trim($word)))
                ->pluck('level')
                ->all();

            if (empty($levels)) return null;

            // Renvoie le niveau le plus bas
            usort($levels, fn ($a, $b) => CefrLexicon::levelRank($a) <=> CefrLexicon::levelRank($b));
            return $levels[0];
        });
    }

    /**
     * Variante batch : récupère les niveaux pour une liste de mots en une requête.
     *
     * @param  array<string>  $words
     * @return array<string,?string>  ['word' => 'B1', ...]
     */
    public function levelsFor(array $words, string $language): array
    {
        $normalized = array_unique(array_map(fn ($w) => strtolower(trim($w)), $words));

        $rows = CefrLexicon::query()
            ->where('language', $language)
            ->whereIn('word', $normalized)
            ->get(['word', 'level'])
            ->groupBy('word')
            ->map(function ($group) {
                // Prendre le niveau le plus bas si plusieurs records pour le même mot
                return $group->sortBy(fn ($r) => CefrLexicon::levelRank($r->level))->first()->level;
            })
            ->toArray();

        return array_combine($normalized, array_map(fn ($w) => $rows[$w] ?? null, $normalized));
    }

    /**
     * Estime la "couverture CEFR" d'un texte : % de mots ≤ niveau donné.
     * Utile pour valider qu'un exercice généré est calibré au bon niveau.
     */
    public function coverage(string $text, string $language, string $maxLevel = 'B2'): array
    {
        $tokens = $this->tokenize($text);
        if (empty($tokens)) {
            return ['total' => 0, 'covered' => 0, 'pct' => 0, 'above' => []];
        }

        $levels = $this->levelsFor($tokens, $language);
        $maxRank = CefrLexicon::levelRank($maxLevel);
        $covered = 0;
        $above = [];

        foreach ($tokens as $token) {
            $lvl = $levels[$token] ?? null;
            if ($lvl === null) {
                continue;   // mot hors lexique : ignoré (pas pénalisant)
            }
            if (CefrLexicon::levelRank($lvl) <= $maxRank) {
                $covered++;
            } else {
                $above[$token] = $lvl;
            }
        }

        return [
            'total'   => \count($tokens),
            'covered' => $covered,
            'pct'     => \count($tokens) > 0 ? round($covered / \count($tokens) * 100, 1) : 0,
            'above'   => $above,
        ];
    }

    /**
     * Renvoie les mots d'un texte au-dessus du niveau de l'utilisateur.
     * Ces mots sont candidats pour le bouton "+ Lexique".
     */
    public function newWordsFor(string $text, string $language, string $userLevel): array
    {
        $coverage = $this->coverage($text, $language, $userLevel);
        return $coverage['above'];
    }

    private function tokenize(string $text): array
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s\'-]+/u', ' ', $text);
        $tokens = preg_split('/\s+/u', trim($text)) ?: [];
        return array_values(array_filter($tokens, fn ($t) => mb_strlen($t) > 1));
    }
}
