<?php

namespace App\Http\Controllers;

use App\Models\CurriculumSkeleton;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\LearningPathNode;
use App\Services\AI\ExerciseGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Examen de fin de palier.
 *
 * Un niveau s'achevait sans rien pour le consolider : on enchaînait sur le palier
 * suivant sans jamais vérifier que le précédent tenait. La promotion existait dans
 * le code depuis l'origine, mais rien ne l'appelait — la table des évaluations de
 * niveau est restée vide depuis l'ouverture, et un apprenant a pu boucler trente
 * objectifs en restant marqué débutant.
 *
 * L'examen est partagé par examen et par niveau : il porte sur le palier, pas sur le
 * parcours particulier d'un apprenant. Il est donc écrit une fois, puis resservi.
 */
class LevelExamController extends Controller
{
    /** Trois exercices, comme une séance ordinaire : assez pour juger, pas de quoi décourager. */
    private const TYPES = ['mcq', 'gap-fill', 'sentence-completion'];

    public function start(string $level, ExerciseGeneratorService $generator): RedirectResponse
    {
        $user = auth()->user();
        $profile = $user->profile?->load('targetExam.language');
        $exam = $profile?->targetExam;

        if (!$exam) {
            return redirect()->route('dashboard')->with('error', "Choisis d'abord l'examen que tu prépares.");
        }

        $level = strtoupper($level);
        if (!in_array($level, CurriculumSkeleton::CEFR_LEVELS, true)) {
            return redirect()->route('dashboard');
        }

        $skeleton = CurriculumSkeleton::where('user_id', $user->id)->first();
        $pending = $skeleton?->pendingLevelExam();

        // On n'ouvre pas un examen dont le palier n'est pas fini : il sanctionnerait
        // un travail pas encore fait.
        if (!$pending || ($pending['level'] ?? null) !== $level) {
            return redirect()->route('dashboard')
                ->with('error', "Termine d'abord les objectifs de ce niveau — l'examen vient après.");
        }

        $node = LearningPathNode::firstOrCreate(
            ['exam_id' => $exam->id, 'title' => "Examen de niveau {$level}"],
            [
                'chapter_name' => "Palier {$level}",
                'chapter_order' => 99,
                'sort_order' => 0,
                'description' => "Consolide tout le niveau {$level} avant de passer au suivant.",
                'node_type' => 'level_exam',
                'skill_type' => 'grammar',
                'xp_reward' => 60,
                'level' => $level,
            ]
        );

        if (Exercise::where('node_id', $node->id)->doesntExist()) {
            $this->writeExam($node, $exam, $level, $profile->native_language ?? 'Français', $generator);
        }

        if (Exercise::where('node_id', $node->id)->doesntExist()) {
            return redirect()->route('dashboard')
                ->with('error', "L'examen de niveau n'a pas pu être écrit. Réessaie dans quelques minutes.");
        }

        return redirect()->route('node.start', $node);
    }

    /**
     * Écrit l'examen une fois pour toutes. Une génération par examen et par niveau
     * et par heure : un échec partiel ne doit pas relancer trois appels à chaque clic.
     */
    private function writeExam(LearningPathNode $node, $exam, string $level, string $nativeLanguage, ExerciseGeneratorService $generator): void
    {
        $verrou = Cache::lock("level-exam:{$exam->id}:{$level}", 120);
        if (!$verrou->get()) {
            return;
        }

        try {
            $order = 1;
            foreach (self::TYPES as $componentKey) {
                $type = ExerciseType::where('component_key', $componentKey)->first();
                if (!$type) {
                    continue;
                }

                try {
                    $exercise = $generator->generate($type, $exam, $level, [
                        'title' => "Examen de niveau {$level}",
                        'concept' => 'level_exam.' . strtolower($level),
                        'native_language' => $nativeLanguage,
                        'is_synthesis' => true,
                    ]);
                    $exercise->update(['node_id' => $node->id, 'order_in_node' => $order++]);
                } catch (\Throwable $e) {
                    // Un type qui échoue ne doit pas emporter l'examen entier.
                    Log::warning('Examen de niveau : un exercice manque', [
                        'level' => $level,
                        'type' => $componentKey,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } finally {
            $verrou->release();
        }
    }
}
