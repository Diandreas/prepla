<?php

use App\Models\CurriculumSkeleton;
use App\Models\Exam;
use App\Models\Language;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Curriculum\CurriculumPlannerService;
use Illuminate\Support\Facades\Http;

/**
 * Sans IA, le parcours est repris du programme de reference. Ces objectifs
 * arrivaient SANS palier : il etait alors deduit par tiers, donc un programme
 * entierement A1 devenait A1/A2/B1 — et l'examen de palier A1 tombait apres un
 * tiers du parcours, sur des lecons A1, suivi de deux autres examens pour des
 * niveaux que l'apprenant n'avait jamais travailles.
 */
test('le parcours de reference reste au niveau demande et n a qu un examen', function () {
    config(['services.mistral.api_key' => 'test-key']);
    Http::fake(['*' => Http::response(['message' => 'down'], 503)]);

    $language = Language::create(['slug' => 'english', 'name' => 'English', 'native_name' => 'English', 'flag' => 'gb']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'ielts', 'name' => 'IELTS']);

    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'target_exam_id' => $exam->id, 'current_level' => 'A1',
        'native_language' => 'Français', 'onboarding_completed_at' => now(),
    ]);

    app(CurriculumPlannerService::class)->buildSkeleton($user, $exam, 'A1');

    $skeleton = CurriculumSkeleton::where('user_id', $user->id)->sole();
    $objectifs = collect($skeleton->objectives);
    $examens = $objectifs->where('is_level_exam', true);

    expect($objectifs->count())->toBeGreaterThan(2)
        // Tous les objectifs de pratique au niveau demande.
        ->and($objectifs->where('is_level_exam', '!=', true)->pluck('level')->unique()->all())->toBe(['A1'])
        // Un seul examen, et il ferme le palier.
        ->and($examens)->toHaveCount(1)
        ->and($examens->first()['level'])->toBe('A1')
        ->and($objectifs->last()['is_level_exam'] ?? false)->toBeTrue();
});
