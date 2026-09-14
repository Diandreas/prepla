<?php

namespace Database\Seeders;

use App\Models\CurriculumSkeleton;
use App\Models\Exam;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LocalVisualQaSeeder extends Seeder
{
    public function run(): void
    {
        $database = realpath(config('database.connections.sqlite.database'));
        if (!app()->environment('local') || config('database.default') !== 'sqlite'
            || !$database || !str_starts_with($database, realpath(database_path()).DIRECTORY_SEPARATOR)) {
            throw new \RuntimeException('Ce compte de démonstration est réservé à la base SQLite locale du projet.');
        }

        DB::transaction(function () {
            if (!Exam::exists()) {
                $this->call([LanguageSeeder::class, ExamSeeder::class]);
            }
            $exam = Exam::where('slug', 'goethe')->first() ?? Exam::firstOrFail();
            $user = User::firstOrCreate(['email' => 'demo.visual@prepla.test'], [
                'name' => 'Camille · Démo locale',
                'password' => Hash::make('PrepLa-Demo-Local!2026'),
            ]);
            $user->profile()->firstOrCreate([], [
                'target_exam_id' => $exam->id, 'target_score' => 'B1',
                'current_level' => 'A2', 'native_language' => 'fr',
                'interface_language' => 'fr', 'xp_total' => 120,
                'streak_current' => 3, 'streak_last_date' => today(),
                'onboarding_completed_at' => now(), 'trial_ends_at' => now()->addDays(7),
            ]);
            $objectives = [
                ['order' => 0, 'title' => 'QA local · Se présenter', 'concept' => 'Présenter son identité en allemand', 'status' => 'done'],
                ['order' => 1, 'title' => 'QA local · Parler de ses habitudes', 'concept' => 'Le présent et la place du verbe', 'status' => 'current'],
                ['order' => 2, 'title' => 'QA local · Préparer une sortie', 'concept' => 'Proposer une activité et préciser un horaire', 'status' => 'pending'],
            ];
            CurriculumSkeleton::firstOrCreate(['user_id' => $user->id, 'exam_id' => $exam->id], [
                'objectives' => $objectives, 'current_objective_index' => 1,
            ]);
            foreach ($objectives as $index => $objective) {
                Lesson::firstOrCreate(['user_id' => $user->id, 'skeleton_objective_index' => $index], [
                    'title' => $objective['title'], 'concept' => $objective['concept'],
                    'theory_markdown' => "## Parler de son quotidien\n\nDans une phrase affirmative allemande, le verbe conjugué occupe la deuxième position.\n\n**Ich lerne jeden Tag Deutsch.** — J’apprends l’allemand chaque jour.\n\n**Heute lerne ich Deutsch.** — Aujourd’hui, j’apprends l’allemand.\n\n### À toi de jouer\n\nRepère le verbe, puis vérifie sa place dans la phrase.",
                    'key_takeaways' => ['Le verbe conjugué reste en deuxième position.', 'Le sujet peut se placer après le verbe.'],
                    'common_mistakes' => [],
                    'comprehension_quiz' => [['question' => 'Quelle phrase est correcte ?', 'options' => ['A) Heute ich lerne Deutsch.', 'B) Heute lerne ich Deutsch.'], 'correct_answer' => 'B', 'explanation' => 'Lerne occupe la deuxième position après Heute.']],
                    'status' => $index === 0 ? 'completed' : 'pending', 'generated_at' => now(),
                ]);
            }
            $this->command->info('Compte prêt : demo.visual@prepla.test — utilisateur '.$user->id.' — examen '.$exam->name);
        });
    }
}
