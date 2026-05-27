<?php

namespace App\Console\Commands;

use App\Models\Exercise;
use App\Services\AI\TtsAudioGenerator;
use Illuminate\Console\Command;

/**
 * Pré-génère les audios TTS pour les exercices de type Listening.
 * Le résultat est injecté dans le JSON `questions` de chaque exercice
 * sous la clé `audio_url` — le player React l'affiche automatiquement.
 *
 * Exemples :
 *   php artisan tts:generate-audio 123                   # un exercice précis
 *   php artisan tts:generate-audio --all-listening       # tous les listenings sans audio
 *   php artisan tts:generate-audio 123 --voice=aura-zeus-en
 *   php artisan tts:generate-audio --all-listening --limit=10 --dry-run
 */
class GenerateTtsAudioCommand extends Command
{
    protected $signature = 'tts:generate-audio
                            {exercise_id? : Specific exercise ID to process}
                            {--all-listening : Process all listening exercises missing audio}
                            {--voice= : Override voice (default depends on language)}
                            {--limit= : Max number of exercises to process}
                            {--dry-run : Show what would be done without calling the API}
                            {--force : Re-generate even if audio_url already set}';

    protected $description = 'Generate Deepgram Aura TTS audio for listening exercises';

    public function handle(TtsAudioGenerator $tts): int
    {
        $exercises = $this->resolveExercises();
        if ($exercises->isEmpty()) {
            $this->warn('No matching exercises.');
            return self::SUCCESS;
        }

        $this->info("Processing {$exercises->count()} exercise(s)…");
        $bar = $this->output->createProgressBar($exercises->count());
        $bar->start();

        $generated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($exercises as $exercise) {
            $bar->advance();

            $questions = $exercise->questions ?? [];
            if (empty($questions)) {
                $skipped++;
                continue;
            }

            $language = strtolower($exercise->exam?->language?->slug ?? 'english');
            $voice = $this->option('voice') ?: $tts->defaultVoiceFor($language);
            $modified = false;

            foreach ($questions as $idx => $question) {
                // Skip si déjà fait, sauf --force
                if (!$this->option('force') && !empty($question['audio_url'])) {
                    continue;
                }

                if ($this->option('dry-run')) {
                    $preview = mb_substr($question['audio_text'] ?? $question['passage'] ?? '', 0, 60);
                    $this->newLine();
                    $this->line("  [dry] ex#{$exercise->id} q#{$idx}: \"{$preview}…\" → {$voice}");
                    continue;
                }

                $url = $tts->generateForQuestion($question, $language);
                if ($url) {
                    $questions[$idx]['audio_url'] = $url;
                    $modified = true;
                    $generated++;
                } else {
                    $failed++;
                }
            }

            if ($modified && !$this->option('dry-run')) {
                $exercise->questions = $questions;
                $exercise->save();
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("✅ Generated {$generated} audio file(s)");
        if ($skipped > 0) $this->warn("⚠️  Skipped {$skipped} exercise(s) without questions");
        if ($failed > 0)  $this->error("❌ Failed {$failed} TTS call(s) — check log");

        return self::SUCCESS;
    }

    private function resolveExercises()
    {
        $query = Exercise::query()->with(['exerciseType', 'exam.language']);

        if ($id = $this->argument('exercise_id')) {
            $query->where('id', $id);
        } elseif ($this->option('all-listening')) {
            $query->whereHas('exerciseType', function ($q) {
                $q->where('skill_type', 'listening');
            });
        } else {
            $this->error('Pass an exercise_id or use --all-listening');
            return collect();
        }

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        return $query->get();
    }
}
