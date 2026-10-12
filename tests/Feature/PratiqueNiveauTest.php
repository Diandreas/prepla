<?php

use App\Models\Exam;
use App\Models\ExamBlueprint;
use App\Models\ExamSection;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\User;
use App\Models\UserProfile;

/**
 * La page « Pratiquer » d'un examen montrait TOUS ses modules, quel que soit le
 * niveau de l'apprenant. Les sections sont partagées entre les niveaux — une
 * ligne par examen et par slug — et la page affichait la durée du dernier niveau
 * enregistré.
 *
 * Un candidat telc A1 voyait donc les Sprachbausteine, qui n'existent pas à son
 * niveau, et « Hören und Schreiben », qui n'existe qu'au C2 : six modules et
 * 305 minutes au lieu de quatre et 80.
 */
function examenAPlusieursNiveaux(): Exam
{
    $langue = Language::create(['slug' => 'german', 'name' => 'Allemand', 'native_name' => 'Deutsch', 'flag' => 'de', 'is_active' => true]);
    $exam = Exam::create(['language_id' => $langue->id, 'slug' => 'telc-test', 'name' => 'telc Deutsch']);

    foreach ([['lesen', 'reading'], ['hoeren', 'listening'], ['schreiben', 'writing'],
        ['sprechen', 'speaking'], ['sprachbausteine', 'grammar'], ['hoeren-schreiben', 'listening']] as [$slug, $skill]) {
        $section = ExamSection::create([
            'exam_id' => $exam->id, 'slug' => $slug, 'name' => $slug, 'skill_type' => $skill,
            // La durée écrite en base est celle du DERNIER niveau enregistré.
            'time_limit' => 90,
        ]);
        ExerciseType::create([
            'section_id' => $section->id, 'slug' => $slug.'-mcq', 'name' => $slug,
            'skill_type' => $skill, 'component_key' => 'mcq',
        ]);
    }

    // Au A1 : quatre modules, 80 minutes. Pas de Sprachbausteine, pas de module double.
    ExamBlueprint::create([
        'exam_id' => $exam->id, 'level' => 'A1', 'variant' => null,
        'name' => 'telc Deutsch A1', 'total_duration_minutes' => 80, 'scoring_config' => [],
        'sections_config' => [
            ['slug' => 'hoeren', 'time_limit' => 20, 'task_count' => 3],
            ['slug' => 'lesen', 'time_limit' => 25, 'task_count' => 3],
            ['slug' => 'schreiben', 'time_limit' => 20, 'task_count' => 2],
            ['slug' => 'sprechen', 'time_limit' => 15, 'task_count' => 3],
        ],
    ]);

    return $exam;
}

function apprenantAuNiveau(string $niveau): User
{
    $u = User::create([
        'name' => 'Amina', 'email' => 'amina+'.strtolower($niveau).'@exemple.test',
        'password' => 'mot-de-passe-de-test', 'email_verified_at' => now(),
    ]);
    UserProfile::create(['user_id' => $u->id, 'current_level' => $niveau, 'onboarding_completed_at' => now()]);

    return $u;
}

test('un apprenant ne voit que les modules de son niveau', function () {
    $exam = examenAPlusieursNiveaux();

    $reponse = $this->actingAs(apprenantAuNiveau('A1'))->get('/practice/'.$exam->id);
    $reponse->assertOk();

    $props = $reponse->viewData('page')['props'];
    $modules = collect($props['exam']['sections'])->pluck('slug');

    expect($modules)->toHaveCount(4)
        ->toContain('hoeren')->toContain('lesen')->toContain('schreiben')->toContain('sprechen')
        ->not->toContain('sprachbausteine')     // n'existe pas au A1
        ->not->toContain('hoeren-schreiben');   // n'existe qu'au C2
});

test('les durees affichees sont celles du niveau, pas celles de la base', function () {
    $exam = examenAPlusieursNiveaux();

    $props = $this->actingAs(apprenantAuNiveau('A1'))->get('/practice/'.$exam->id)->viewData('page')['props'];
    $durees = collect($props['exam']['sections'])->pluck('time_limit', 'slug');

    expect($durees['hoeren'])->toBe(20)
        ->and($durees['lesen'])->toBe(25)
        ->and($durees['schreiben'])->toBe(20)
        // La durée officielle du niveau est transmise telle quelle : elle ne se
        // déduit pas toujours de la somme des modules.
        ->and($props['levelDuration'])->toBe(80)
        ->and($props['levelIsOfficial'])->toBeTrue();
});

test('les modules suivent l ordre de l epreuve, pas l ordre alphabetique', function () {
    $exam = examenAPlusieursNiveaux();

    $props = $this->actingAs(apprenantAuNiveau('A1'))->get('/practice/'.$exam->id)->viewData('page')['props'];

    expect(collect($props['exam']['sections'])->pluck('slug')->all())
        ->toBe(['hoeren', 'lesen', 'schreiben', 'sprechen']);
});

test('un niveau que l examen ne decrit pas montre tout, et le dit', function () {
    $exam = examenAPlusieursNiveaux();

    // Le C1 n'a pas de plan ici : on ne masque rien au hasard, et la page
    // annonce un entrainement au format plutot qu'une epreuve blanche.
    $props = $this->actingAs(apprenantAuNiveau('C1'))->get('/practice/'.$exam->id)->viewData('page')['props'];

    expect(collect($props['exam']['sections']))->toHaveCount(6)
        ->and($props['levelIsOfficial'])->toBeFalse();
});
