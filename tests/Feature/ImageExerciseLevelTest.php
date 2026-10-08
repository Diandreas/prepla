<?php

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Content\ExerciseTypeSuitability;

/**
 * Décrire une courbe, étiqueter un schéma, commenter une image : des épreuves de B1
 * et au-delà. Elles étaient proposées à tout le monde dans la galerie des types, et
 * le lien direct les servait sans rien vérifier — un apprenant A1 recevait
 * « décrivez l'évolution du chômage en 150 mots ».
 */
function typesVisuels(): array
{
    $language = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'osd', 'name' => 'ÖSD Zertifikat B2']);
    $writing = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'schreiben', 'name' => 'Schreiben', 'skill_type' => 'writing']);
    $speaking = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'sprechen', 'name' => 'Sprechen', 'skill_type' => 'speaking']);

    return [
        'exam' => $exam,
        'writing' => $writing,
        'speaking' => $speaking,
        'graphique' => ExerciseType::create([
            'section_id' => $writing->id, 'slug' => 'graph-description', 'name' => 'Décrire un graphique',
            'skill_type' => 'writing', 'component_key' => 'graph-description',
        ]),
        'image' => ExerciseType::create([
            'section_id' => $speaking->id, 'slug' => 'picture-description', 'name' => 'Décrire une image',
            'skill_type' => 'speaking', 'component_key' => 'speaking-recorder',
        ]),
        'simple' => ExerciseType::create([
            'section_id' => $writing->id, 'slug' => 'short-writing', 'name' => 'Court écrit',
            'skill_type' => 'writing', 'component_key' => 'short-writing',
        ]),
    ];
}

test('la description de graphique ou d image attend le bon niveau', function () {
    $t = typesVisuels();
    $pertinence = app(ExerciseTypeSuitability::class);

    foreach (['A1', 'A2'] as $debutant) {
        expect($pertinence->convient($t['graphique'], $debutant))->toBeFalse()
            ->and($pertinence->raison($t['graphique'], $debutant))->toContain('B1')
            ->and($pertinence->convient($t['image'], $debutant))->toBeFalse();
    }

    // La description de graphique s'ouvre à B1 ; la Bildbesprechung de l'ÖSD à B2,
    // qui est son niveau d'épreuve.
    expect($pertinence->convient($t['graphique'], 'B1'))->toBeTrue()
        ->and($pertinence->convient($t['image'], 'B1'))->toBeFalse()
        ->and($pertinence->convient($t['image'], 'B2'))->toBeTrue()
        // Et un format ordinaire reste disponible dès le début.
        ->and($pertinence->convient($t['simple'], 'A1'))->toBeTrue();
});

test('la galerie ne propose pas a un debutant un exercice de description', function () {
    $t = typesVisuels();

    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'target_exam_id' => $t['exam']->id, 'current_level' => 'A2', 'onboarding_completed_at' => now(),
    ]);

    $this->actingAs($user)->get(route('practice.section', [$t['exam']->id, $t['writing']->id]))
        ->assertOk()
        ->assertInertia(function ($page) {
            $slugs = collect($page->toArray()['props']['exerciseTypes'])->pluck('component_key');

            expect($slugs)->not->toContain('graph-description')
                ->and($slugs)->toContain('short-writing');

            return true;
        });
});

test('le lien direct refuse un exercice hors niveau et le dit', function () {
    $t = typesVisuels();

    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'target_exam_id' => $t['exam']->id, 'current_level' => 'A1', 'onboarding_completed_at' => now(),
    ]);

    $this->actingAs($user)->get(route('practice.drill.type', [$t['exam']->id, $t['graphique']->id]))
        ->assertRedirect(route('practice.section', [$t['exam']->id, $t['writing']->id]))
        ->assertSessionHas('error', fn ($message) => str_contains($message, 'B1'));
});

test('un apprenant B2 atteint bien la tache de description d image', function () {
    $t = typesVisuels();

    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'target_exam_id' => $t['exam']->id, 'current_level' => 'B2', 'onboarding_completed_at' => now(),
    ]);

    $this->actingAs($user)->get(route('practice.section', [$t['exam']->id, $t['speaking']->id]))
        ->assertOk()
        ->assertInertia(function ($page) {
            $slugs = collect($page->toArray()['props']['exerciseTypes'])->pluck('component_key');

            expect($slugs)->toContain('speaking-recorder');

            return true;
        });
});
