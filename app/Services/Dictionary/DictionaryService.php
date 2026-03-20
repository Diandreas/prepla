<?php

namespace App\Services\Dictionary;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DictionaryService
{
    /**
     * Map PrePla language slugs to Wiktionary language codes
     */
    private const LANGUAGE_MAP = [
        'english'    => 'en',
        'french'     => 'fr',
        'spanish'    => 'es',
        'chinese'    => 'zh',
        'arabic'     => 'ar',
        'german'     => 'de',
        'portuguese' => 'pt',
        'japanese'   => 'ja',
        'korean'     => 'ko',
        'russian'    => 'ru',
    ];

    /**
     * Map language codes to Wiktionary language names (used in response filtering)
     */
    private const LANGUAGE_NAMES = [
        'en' => 'English',
        'fr' => 'French',
        'es' => 'Spanish',
        'zh' => 'Chinese',
        'ar' => 'Arabic',
        'de' => 'German',
        'pt' => 'Portuguese',
        'ja' => 'Japanese',
        'ko' => 'Korean',
        'ru' => 'Russian',
    ];

    private const WIKTIONARY_BASE = 'https://en.wiktionary.org/api/rest_v1/page/definition';
    private const FORVO_BASE       = 'https://apifree.forvo.com';
    private const CACHE_TTL        = 60 * 60 * 24; // 24 hours

    private ?string $forvoKey;

    public function __construct()
    {
        $this->forvoKey = config('services.forvo.key');
    }

    /**
     * Look up a word and return normalized dictionary data.
     *
     * @return array{word: string, language: string, definitions: array, phonetics: array, audio: array, examples: array}
     */
    public function lookup(string $word, string $languageSlug): array
    {
        $langCode = self::LANGUAGE_MAP[$languageSlug] ?? null;

        if (! $langCode) {
            return $this->emptyResult($word, $languageSlug);
        }

        $cacheKey = "dictionary:{$langCode}:{$word}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($word, $langCode, $languageSlug) {
            $wiktionary = $this->fetchWiktionary($word, $langCode);
            $audio      = $this->fetchAudio($word, $langCode);

            return [
                'word'        => $word,
                'language'    => $languageSlug,
                'definitions' => $wiktionary['definitions'],
                'phonetics'   => $wiktionary['phonetics'],
                'audio'       => $audio,
                'examples'    => $wiktionary['examples'],
            ];
        });
    }

    /**
     * Fetch data from Wiktionary REST API.
     */
    private function fetchWiktionary(string $word, string $langCode): array
    {
        $result = ['definitions' => [], 'phonetics' => [], 'examples' => []];

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'PrePla/1.0 (language-learning-app; contact@prepla.app)',
            ])->get(self::WIKTIONARY_BASE . '/' . urlencode($word));

            if (! $response->successful()) {
                return $result;
            }

            $data = $response->json();

            // Wiktionary returns entries keyed by language code
            $entries = $data[$langCode] ?? [];

            foreach ($entries as $entry) {
                $partOfSpeech = $entry['partOfSpeech'] ?? '';

                // Definitions
                foreach ($entry['definitions'] ?? [] as $def) {
                    $definition = [
                        'part_of_speech' => $partOfSpeech,
                        'definition'     => strip_tags($def['definition'] ?? ''),
                        'examples'       => [],
                    ];

                    foreach ($def['parsedExamples'] ?? $def['examples'] ?? [] as $example) {
                        $text = is_array($example) ? ($example['example'] ?? '') : $example;
                        $text = strip_tags($text);
                        if ($text) {
                            $definition['examples'][] = $text;
                            $result['examples'][]     = $text;
                        }
                    }

                    $result['definitions'][] = $definition;
                }

                // Phonetics / IPA
                foreach ($entry['pronunciations'] ?? [] as $pronunciation) {
                    if (! empty($pronunciation['ipa'])) {
                        foreach ($pronunciation['ipa'] as $ipa) {
                            $result['phonetics'][] = [
                                'ipa'   => trim($ipa, '/[]'),
                                'audio' => $pronunciation['audio'] ?? null,
                            ];
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning("DictionaryService: Wiktionary lookup failed for '{$word}' ({$langCode}): " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Fetch audio pronunciations.
     * Uses Forvo if API key is configured, otherwise returns empty array.
     */
    private function fetchAudio(string $word, string $langCode): array
    {
        if (! $this->forvoKey) {
            return [];
        }

        $cacheKey = "dictionary:audio:{$langCode}:{$word}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($word, $langCode) {
            try {
                $response = Http::get(self::FORVO_BASE . '/key=' . $this->forvoKey
                    . '/format/json/action/word-pronunciations/word/' . urlencode($word)
                    . '/language/' . $langCode);

                if (! $response->successful()) {
                    return [];
                }

                $data  = $response->json();
                $items = $data['items'] ?? [];
                $audio = [];

                foreach ($items as $item) {
                    if (! empty($item['pathmp3'])) {
                        $audio[] = [
                            'url'     => $item['pathmp3'],
                            'speaker' => $item['username'] ?? null,
                            'country' => $item['country'] ?? null,
                            'votes'   => ($item['num_positive_votes'] ?? 0) - ($item['num_negative_votes'] ?? 0),
                        ];
                    }
                }

                // Sort by votes descending (best quality first)
                usort($audio, fn ($a, $b) => $b['votes'] <=> $a['votes']);

                return $audio;
            } catch (\Exception $e) {
                Log::warning("DictionaryService: Forvo lookup failed for '{$word}' ({$langCode}): " . $e->getMessage());
                return [];
            }
        });
    }

    private function emptyResult(string $word, string $languageSlug): array
    {
        return [
            'word'        => $word,
            'language'    => $languageSlug,
            'definitions' => [],
            'phonetics'   => [],
            'audio'       => [],
            'examples'    => [],
        ];
    }

    /**
     * Get the list of supported language slugs.
     */
    public static function supportedLanguages(): array
    {
        return array_keys(self::LANGUAGE_MAP);
    }
}
