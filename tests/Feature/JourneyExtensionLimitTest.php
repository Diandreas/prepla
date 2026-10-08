<?php

use App\Models\CurriculumSkeleton;
use App\Models\Exam;
use App\Models\Language;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Curriculum\CurriculumPlannerService;
use App\Services\LevelAdvancementService;
use Illuminate\Support\Facades\Http;

/**
 * Quand l'IA est indisponible, le prolongement reprend le programme de reference du
 * niveau. Sans filtre, un apprenant qui reste au meme palier recevait dix fois les
 * MEMES objectifs : le parcours s'allongeait indefiniment de doublons, avec un
 * examen de palier de plus a chaque tour. Et quand il n'y a plus rien de neuf, on ne
 * fabrique pas du remplissage : on le lui dit.
 */
function parcoursEntierementTermine(User $user): CurriculumSkeleton
{
    $skeleton = CurriculumSkeleton::where('user_id', $user->id)->sole();

    // L'examen de palier est pose puis marque reussi lui aussi : c'est l'etat d'un
    // apprenant qui a vraiment tout termine.
    $skeleton->ensureLevelExams();
    $skeleton = $skeleton->fresh();

    $objectifs = collect($skeleton->objectives)->map(function ($o) {
        $o['status'] = 'done';

        return $o;
    })->all();

    $skeleton->update(['objectives' => $objectifs, 'current_objective_index' => count($objectifs) - 1]);

    return $skeleton->fresh();
}

test('un parcours prolonge sans IA ne se remplit pas de doublons', function () {
    config(['services.mistral.api_key' => 'test-key']);
    Http::fake(['*' => Http::response(['message' => 'down'], 503)]);

    $language = Language::create(['slug' => 'english', 'name' => 'English', 'native_name' => 'English', 'flag' => 'gb']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'ielts', 'name' => 'IELTS']);

    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'target_exam_id' => $exam->id, 'current_level' => 'C2',
        'native_language' => 'Français', 'onboarding_completed_at' => now(),
    ]);

    CurriculumSkeleton::create([
        'user_id' => $user->id, 'exam_id' => $exam->id,
        'objectives' => [[
            'order' => 0, 'title' => 'Premier objectif', 'concept' => 'grammar.basic',
            'level' => 'C2', 'status' => 'done', 'priority' => 'normal',
        ]],
        'current_objective_index' => 0,
    ]);

    $planner = app(CurriculumPlannerService::class);
    $levels = app(LevelAdvancementService::class);

    $prolongements = 0;
    for ($tour = 1; $tour <= 5; $tour++) {
        parcoursEntierementTermine($user);
        if ($planner->extendForNextLevel($user->fresh(), $levels)) {
            $prolongements++;
        }
    }

    $skeleton = CurriculumSkeleton::where('user_id', $user->id)->sole();
    $pratique = collect($skeleton->objectives)->where('is_level_exam', '!=', true);

    expect($prolongements)->toBeGreaterThan(0)
        // Le programme de reference finit par etre epuise : on s'arrete.
        ->and($prolongements)->toBeLessThan(5)
        // Et aucun objectif de pratique n'est servi deux fois.
        ->and($pratique->pluck('title')->duplicates()->all())->toBe([]);
});

test('au bout du parcours, sans suite a ecrire, le message est franc', function () {
    config(['services.mistral.api_key' => 'test-key']);
    Http::fake(['*' => Http::response(['message' => 'down'], 503)]);

    $language = Language::create(['slug' => 'english', 'name' => 'English', 'native_name' => 'English', 'flag' => 'gb']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'ielts', 'name' => 'IELTS']);

    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'target_exam_id' => $exam->id, 'current_level' => 'C2',
        'native_language' => 'Français', 'onboarding_completed_at' => now(),
    ]);

    // Le programme de reference C2 deja entierement parcouru.
    $reference = json_decode(file_get_contents(base_path('database/data/curriculums/english.json')), true)['C2'] ?? [];
    expect($reference)->not->toBe([]);

    CurriculumSkeleton::create([
        'user_id' => $user->id, 'exam_id' => $exam->id,
        'objectives' => collect($reference)->map(fn ($o, $i) => $o + [
            'order' => $i, 'level' => 'C2', 'status' => 'done', 'priority' => 'normal',
        ])->all(),
        'current_objective_index' => 0,
    ]);
    parcoursEntierementTermine($user);

    $this->actingAs($user)->get(route('lessons.next'))
        ->assertRedirect(route('lessons.index'))
        ->assertSessionHas('error', "Tu as terminé tout ton parcours. La suite s'écrit — réessaie dans un moment.");
});
