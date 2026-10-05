<?php

namespace App\Console\Commands;

use App\Models\CurriculumSkeleton;
use App\Models\Lesson;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RepairLessonObjectiveIndexes extends Command
{
    protected $signature = 'prepla:repair-lesson-indexes {--apply : Apply unambiguous repairs with a recovery snapshot}';
    protected $description = 'Repair legacy lesson indexes shifted by inserted level exams; preserve objective progress.';

    public function handle(): int
    {
        $repairs = [];
        $ambiguous = 0;
        foreach (CurriculumSkeleton::all() as $path) {
            $objectives = collect($path->objectives ?? []);
            foreach (Lesson::where('user_id', $path->user_id)
                ->whereHas('node', fn ($q) => $q->where('exam_id', $path->exam_id))->get() as $lesson) {
                $old = $lesson->skeleton_objective_index;
                if (!$lesson->concept || ($objectives[$old]['concept'] ?? null) === $lesson->concept) {
                    continue;
                }
                $matches = $objectives->filter(fn ($o) => !($o['is_level_exam'] ?? false)
                    && ($o['concept'] ?? null) === $lesson->concept);
                if ($matches->count() !== 1) {
                    $ambiguous++;
                    continue;
                }
                $repairs[] = ['id' => $lesson->id, 'old' => $old, 'new' => $matches->keys()->first()];
            }
        }
        $this->info(count($repairs).' liens réparables ; '.$ambiguous.' ambigus (laissés intacts).');
        if ($this->option('apply') && $repairs !== []) {
            $snapshot = 'qa/lesson-indexes-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(3)).'.json';
            Storage::disk('local')->put($snapshot, json_encode($repairs, JSON_PRETTY_PRINT));
            DB::transaction(function () use ($repairs) {
                foreach ($repairs as $repair) {
                    Lesson::whereKey($repair['id'])->where('skeleton_objective_index', $repair['old'])
                        ->update(['skeleton_objective_index' => $repair['new']]);
                }
            });
            $this->info('Liens réparés. Sauvegarde privée : '.$snapshot);
        }
        return self::SUCCESS;
    }
}
