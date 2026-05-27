<?php

namespace App\Console\Commands;

use App\Models\CefrLexicon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Ingère un fichier vocabulaire CEFR dans la table cefr_lexicons.
 *
 * Formats supportés (auto-détectés depuis le nom de la source) :
 *
 *  cefrj         : CSV  — headword, pos, CEFR, ...
 *  efllex        : TSV  — word, tag, level_freq@a1..c1, ...
 *  flelex_tt     : TSV  — word, tag, freq_A1..C2, freq_total
 *  flelex_crf    : TSV  — idem
 *
 * Exemple :
 *   php artisan lexicon:import docs/resources/en/cefrj_vocab.csv --lang=en --source=cefrj
 *   php artisan lexicon:import docs/resources/fr/flelex_tt.csv --lang=fr --source=flelex_tt
 */
class ImportCefrLexiconCommand extends Command
{
    protected $signature = 'lexicon:import
                            {file : Path to the CSV/TSV file (relative to project root or absolute)}
                            {--lang= : ISO language code (en, fr, de)}
                            {--source= : Source slug (cefrj, efllex, flelex_tt, flelex_crf)}
                            {--threshold=1.0 : Frequency threshold to consider a word "attested" at a level (default 1.0/million)}
                            {--truncate : Drop existing records for this source before import}
                            {--limit= : Import only the first N rows (for testing)}';

    protected $description = 'Import a CEFR-tagged vocabulary file into cefr_lexicons';

    public function handle(): int
    {
        $file = $this->argument('file');
        if (!file_exists($file)) {
            // Try relative to project root
            $rel = base_path($file);
            if (file_exists($rel)) {
                $file = $rel;
            } else {
                $this->error("File not found: {$file}");
                return self::FAILURE;
            }
        }

        $lang = $this->option('lang');
        $source = $this->option('source');
        if (!$lang || !$source) {
            $this->error('--lang and --source are required');
            return self::FAILURE;
        }

        if (!in_array($source, CefrLexicon::SOURCES, true)) {
            $this->error('Invalid source. Allowed: ' . implode(', ', CefrLexicon::SOURCES));
            return self::FAILURE;
        }

        if ($this->option('truncate')) {
            $deleted = CefrLexicon::where('language', $lang)->where('source', $source)->delete();
            $this->warn("Truncated {$deleted} existing records for {$lang}/{$source}");
        }

        $rows = match ($source) {
            'cefrj'      => $this->parseCefrJ($file),
            'efllex'     => $this->parseEflLex($file),
            'flelex_tt',
            'flelex_crf' => $this->parseFleLex($file),
            default      => throw new \InvalidArgumentException("No parser for source: {$source}"),
        };

        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $threshold = (float) $this->option('threshold');

        $this->info("Parsed " . count($rows) . " rows from {$file}");

        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        $imported = 0;
        $skipped = 0;
        $chunks = array_chunk($rows, 500);

        $stopLoop = false;
        foreach ($chunks as $chunk) {
            $batch = [];
            foreach ($chunk as $row) {
                $bar->advance();
                if ($limit !== null && $imported >= $limit) {
                    $stopLoop = true;
                    break;
                }

                $built = $this->buildRecord($row, $lang, $source, $threshold);
                if (!$built) {
                    $skipped++;
                    continue;
                }
                $batch[] = $built;
                $imported++;
            }

            if (!empty($batch)) {
                DB::table('cefr_lexicons')->upsert(
                    $batch,
                    ['language', 'word', 'pos', 'source'],
                    ['lemma', 'level', 'freq_total', 'freq_per_level', 'updated_at']
                );
            }

            if ($stopLoop) break;
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("✅ Imported {$imported} records into cefr_lexicons ({$lang}/{$source})");
        if ($skipped > 0) {
            $this->warn("⚠️  Skipped {$skipped} rows (missing word, unknown level, or below threshold)");
        }

        return self::SUCCESS;
    }

    // ─────────── Parsers ───────────

    /**
     * CEFR-J format: CSV with headers: headword, pos, CEFR, CoreInventory 1, CoreInventory 2, Threshold
     */
    private function parseCefrJ(string $file): array
    {
        $rows = [];
        if (($h = fopen($file, 'r')) === false) return $rows;

        $header = fgetcsv($h);
        while (($r = fgetcsv($h)) !== false) {
            $row = array_combine($header, $r) ?: [];
            $rows[] = $row;
        }
        fclose($h);
        return $rows;
    }

    /**
     * EFLLex format: TSV with `word \t tag \t level_freq@a1 \t a2 \t b1 \t b2 \t c1 \t total_freq@total ...`
     */
    private function parseEflLex(string $file): array
    {
        $rows = [];
        if (($h = fopen($file, 'r')) === false) return $rows;

        $header = fgetcsv($h, 0, "\t");
        while (($r = fgetcsv($h, 0, "\t")) !== false) {
            if (count($r) < 8) continue;   // ligne incomplète
            $row = array_combine($header, $r) ?: [];
            $rows[] = $row;
        }
        fclose($h);
        return $rows;
    }

    /**
     * FLELex format (TT and CRF identique): TSV with `word \t tag \t freq_A1 \t A2 \t B1 \t B2 \t C1 \t C2 \t freq_total`
     */
    private function parseFleLex(string $file): array
    {
        $rows = [];
        if (($h = fopen($file, 'r')) === false) return $rows;

        $header = fgetcsv($h, 0, "\t");
        while (($r = fgetcsv($h, 0, "\t")) !== false) {
            if (count($r) < 9) continue;
            $row = array_combine($header, $r) ?: [];
            $rows[] = $row;
        }
        fclose($h);
        return $rows;
    }

    // ─────────── Record builder ───────────

    /**
     * Construit un record cefr_lexicons à partir d'une ligne brute, ou null si la ligne est inutilisable.
     */
    private function buildRecord(array $row, string $lang, string $source, float $threshold): ?array
    {
        $now = now();
        $word = null;
        $pos = null;
        $level = null;
        $freqTotal = null;
        $freqPerLevel = null;

        if ($source === 'cefrj') {
            $word = strtolower(trim($row['headword'] ?? ''));
            $pos = $row['pos'] ?? null;
            $level = strtoupper(trim($row['CEFR'] ?? ''));
        } elseif ($source === 'efllex' || $source === 'flelex_tt' || $source === 'flelex_crf') {
            $word = strtolower(trim($row['word'] ?? ''));
            $pos = $row['tag'] ?? null;

            $freqPerLevel = $this->extractFreqPerLevel($row);
            $level = $this->deriveLevel($freqPerLevel, $threshold);
            $freqTotal = (float) ($row['freq_total'] ?? $row['total_freq@total'] ?? 0);
        }

        if (!$word || !$level || !in_array($level, CefrLexicon::LEVELS, true)) {
            return null;
        }

        return [
            'language'       => $lang,
            'word'           => mb_substr($word, 0, 100),
            'lemma'          => mb_substr($word, 0, 100),  // approximation: word=lemma sans plus de NLP
            'pos'            => $pos ? mb_substr((string) $pos, 0, 20) : null,
            'level'          => $level,
            'freq_total'     => $freqTotal,
            'freq_per_level' => $freqPerLevel ? json_encode($freqPerLevel) : null,
            'source'         => $source,
            'created_at'     => $now,
            'updated_at'     => $now,
        ];
    }

    private function extractFreqPerLevel(array $row): array
    {
        $out = [];
        foreach (CefrLexicon::LEVELS as $lvl) {
            // FLELex: 'freq_A1' / EFLLex: 'level_freq@a1'
            $candidates = [
                "freq_{$lvl}",
                "level_freq@" . strtolower($lvl),
            ];
            foreach ($candidates as $col) {
                if (isset($row[$col]) && $row[$col] !== '') {
                    $out[$lvl] = (float) $row[$col];
                    break;
                }
            }
        }
        return $out;
    }

    /**
     * Détermine le niveau le plus bas où la fréquence dépasse le seuil.
     */
    private function deriveLevel(array $freqPerLevel, float $threshold): ?string
    {
        foreach (CefrLexicon::LEVELS as $lvl) {
            if (isset($freqPerLevel[$lvl]) && $freqPerLevel[$lvl] >= $threshold) {
                return $lvl;
            }
        }
        return null;
    }
}
