<?php

use App\Models\Exam;
use App\Models\ExamBlueprint;
use App\Models\ExamSection;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Content\StructureOfficielle;

/**
 * Un examen ne se presente pas comme une liste de formats d'exercices. telc
 * numerote ses parties — « Leseverstehen, Teil 1 », « Sprachbausteine, Teil 2 »
 * — chacune avec sa tache, son nombre d'items et ses points. C'est sous ces noms
 * que le candidat revise.
 *
 * Les configurations decrivaient deja cette structure sous la cle « parts », et
 * le seeder la recopiait dans le plan de chaque niveau. Mais personne ne la
 * lisait : l'apprenant voyait nos noms internes, « matching-headings » et
 * « mcq-cloze », au lieu des parties de son examen.
 */
function examenStructure(): Exam
{
    $langue = Language::create(['slug' => 'german', 'name' => 'Allemand', 'native_name' => 'Deutsch', 'flag' => 'de', 'is_active' => true]);
    $exam = Exam::create(['language_id' => $langue->id, 'slug' => 'telc-struct', 'name' => 'telc Deutsch']);

    foreach ([['lesen', 'reading', ['matching-headings', 'mcq', 'read-daily-life']],
        ['sprachbausteine', 'grammar', ['mcq-cloze']]] as [$slug, $skill, $types]) {
        $section = ExamSection::create([
            'exam_id' => $exam->id, 'slug' => $slug, 'name' => ucfirst($slug), 'skill_type' => $skill, 'time_limit' => 60,
        ]);
        foreach ($types as $type) {
            ExerciseType::create([
                'section_id' => $section->id, 'slug' => $type, 'name' => $type,
                'skill_type' => $skill, 'component_key' => 'mcq',
            ]);
        }
    }

    ExamBlueprint::create([
        'exam_id' => $exam->id, 'level' => 'B2', 'variant' => null,
        'name' => 'telc Deutsch B2', 'total_duration_minutes' => 155, 'scoring_config' => [],
        'sections_config' => [
            [
                'slug' => 'lesen', 'name' => 'Leseverstehen', 'skill_type' => 'reading', 'time_limit' => 60,
                'parts' => [
                    ['part' => 1, 'name' => 'Leseverstehen, Teil 1', 'name_fr' => 'Compréhension globale',
                        'task' => 'Associer 5 textes à 10 titres.', 'type' => 'matching-headings',
                        'items' => 5, 'item_range' => '1-5', 'points' => 25, 'source' => 'officiel'],
                    ['part' => 2, 'name' => 'Leseverstehen, Teil 2', 'name_fr' => 'Compréhension détaillée',
                        'task' => '5 QCM à trois options.', 'type' => 'mcq',
                        'items' => 5, 'item_range' => '6-10', 'points' => 25, 'source' => 'officiel'],
                ],
            ],
            [
                'slug' => 'sprachbausteine', 'name' => 'Sprachbausteine', 'skill_type' => 'grammar', 'time_limit' => 30,
                'parts' => [
                    ['part' => 1, 'name' => 'Sprachbausteine, Teil 1', 'name_fr' => 'Grammaire',
                        'task' => '10 trous à trois choix.', 'type' => 'mcq-cloze',
                        'items' => 10, 'points' => 15, 'source' => 'officiel'],
                    // Une partie dont le type n'est PAS installe : elle doit rester
                    // affichable, mais annoncee comme non jouable.
                    ['part' => 2, 'name' => 'Sprachbausteine, Teil 2', 'name_fr' => 'Lexique',
                        'task' => '10 mots à replacer parmi 15.', 'type' => 'summary-completion',
                        'items' => 10, 'points' => 15, 'source' => 'officiel'],
                ],
            ],
        ],
    ]);

    // Le A1 n'a pas de Sprachbausteine, et son plan ne decrit aucune partie.
    ExamBlueprint::create([
        'exam_id' => $exam->id, 'level' => 'A1', 'variant' => null,
        'name' => 'telc Deutsch A1', 'total_duration_minutes' => 80, 'scoring_config' => [],
        'sections_config' => [['slug' => 'lesen', 'name' => 'Lesen', 'skill_type' => 'reading', 'time_limit' => 25]],
    ]);

    return $exam;
}

function apprenantStructure(string $niveau): User
{
    $u = User::create([
        'name' => 'Lena', 'email' => 'lena+'.strtolower($niveau).'@exemple.test',
        'password' => 'mot-de-passe-de-test', 'email_verified_at' => now(),
    ]);
    UserProfile::create(['user_id' => $u->id, 'current_level' => $niveau, 'onboarding_completed_at' => now()]);

    return $u;
}

test('les parties reviennent dans l ordre de l epreuve, avec leur identite officielle', function () {
    $parties = app(StructureOfficielle::class)->parties(examenStructure(), 'B2');

    expect(array_column($parties, 'name'))->toBe([
        'Leseverstehen, Teil 1', 'Leseverstehen, Teil 2',
        'Sprachbausteine, Teil 1', 'Sprachbausteine, Teil 2',
    ]);

    expect($parties[0])
        ->toMatchArray(['items' => 5, 'item_range' => '1-5', 'points' => 25, 'source' => 'officiel'])
        ->and($parties[0]['name_fr'])->toBe('Compréhension globale');
});

test('chaque partie porte le type d exercice qui permet de s y entrainer', function () {
    $parties = app(StructureOfficielle::class)->parties(examenStructure(), 'B2');

    // Les trois premieres ont leur type installe : elles sont jouables.
    expect($parties[0]['exercise_type_id'])->not->toBeNull()
        ->and($parties[1]['exercise_type_id'])->not->toBeNull()
        ->and($parties[2]['exercise_type_id'])->not->toBeNull();
});

test('une partie dont le type manque reste affichee mais pas jouable', function () {
    $parties = app(StructureOfficielle::class)->parties(examenStructure(), 'B2');

    // Mieux vaut annoncer « entrainement pas encore disponible » que de montrer
    // un lien mort — ou de cacher une partie que le candidat passera a l'examen.
    expect($parties[3]['name'])->toBe('Sprachbausteine, Teil 2')
        ->and($parties[3]['exercise_type_id'])->toBeNull();
});

test('un niveau sans structure decrite ne renvoie aucune partie', function () {
    $exam = examenStructure();

    expect(app(StructureOfficielle::class)->parties($exam, 'A1'))->toBe([])
        ->and(app(StructureOfficielle::class)->estDecrite($exam, 'A1'))->toBeFalse()
        ->and(app(StructureOfficielle::class)->estDecrite($exam, 'B2'))->toBeTrue();
});

test('les types qui ne reproduisent aucune partie sont annonces comme pedagogiques', function () {
    $exam = examenStructure();
    $pedagogiques = app(StructureOfficielle::class)->typesPedagogiques($exam, 'B2');

    $slugs = ExerciseType::whereIn('id', $pedagogiques)->pluck('slug')->sort()->values()->all();

    // « read-daily-life » ne correspond a aucune partie de l'epreuve : utile pour
    // progresser, mais le melanger aux parties officielles laissait croire au
    // candidat qu'il s'entrainait au format.
    expect($slugs)->toBe(['read-daily-life']);
});

test('un niveau non decrit ne declare pas tous ses types pedagogiques a tort', function () {
    $exam = examenStructure();

    // Au A1 aucune partie n'est decrite : tous les types du module restent
    // proposes, et aucun ne se fait passer pour une partie d'examen.
    $pedagogiques = app(StructureOfficielle::class)->typesPedagogiques($exam, 'A1');
    $slugs = ExerciseType::whereIn('id', $pedagogiques)->pluck('slug')->sort()->values()->all();

    expect($slugs)->toBe(['matching-headings', 'mcq', 'read-daily-life']);
});

test('la page pratiquer montre les parties de l epreuve du niveau', function () {
    $exam = examenStructure();

    $props = $this->actingAs(apprenantStructure('B2'))->get('/practice/'.$exam->id)->viewData('page')['props'];

    expect(collect($props['examParts'])->pluck('name')->all())->toBe([
        'Leseverstehen, Teil 1', 'Leseverstehen, Teil 2',
        'Sprachbausteine, Teil 1', 'Sprachbausteine, Teil 2',
    ]);
});

test('la page distingue les exercices pedagogiques des parties d examen', function () {
    $exam = examenStructure();

    $props = $this->actingAs(apprenantStructure('B2'))->get('/practice/'.$exam->id)->viewData('page')['props'];

    expect($props['pedagogicalTypeIds'])->toHaveCount(1);
});

test('la galerie d un module distingue les parties de l epreuve', function () {
    $exam = examenStructure();
    $section = ExamSection::where('exam_id', $exam->id)->where('slug', 'lesen')->first();

    $props = $this->actingAs(apprenantStructure('B2'))
        ->get('/practice/'.$exam->id.'/section/'.$section->id)->viewData('page')['props'];

    $types = collect($props['exerciseTypes'])->keyBy('name');

    // Les deux formats du Leseverstehen qui SONT des parties portent leur nom
    // officiel ; celui qui n'en est pas n'en porte aucun.
    expect($types['matching-headings']['exam_part_name'])->toBe('Leseverstehen, Teil 1')
        ->and($types['mcq']['exam_part_name'])->toBe('Leseverstehen, Teil 2')
        ->and($types['read-daily-life']['exam_part_name'])->toBeNull();
});

test('les parties de l epreuve viennent avant les exercices d entrainement', function () {
    $exam = examenStructure();
    $section = ExamSection::where('exam_id', $exam->id)->where('slug', 'lesen')->first();

    $props = $this->actingAs(apprenantStructure('B2'))
        ->get('/practice/'.$exam->id.'/section/'.$section->id)->viewData('page')['props'];

    // C'est ce que le candidat vient chercher : les parties d'abord, dans leur
    // ordre, et l'entrainement pedagogique ensuite.
    expect(collect($props['exerciseTypes'])->pluck('name')->all())
        ->toBe(['matching-headings', 'mcq', 'read-daily-life']);
});

test('la page d un module porte le nom et la duree du niveau', function () {
    $exam = examenStructure();
    $section = ExamSection::where('exam_id', $exam->id)->where('slug', 'lesen')->first();

    // En base, la section porte « Lesen » et 60 minutes. Le plan du B2 annonce
    // « Leseverstehen » : c'est ce que le candidat doit lire.
    $props = $this->actingAs(apprenantStructure('B2'))
        ->get('/practice/'.$exam->id.'/section/'.$section->id)->viewData('page')['props'];

    expect($props['section']['name'])->toBe('Leseverstehen');
});

test('un examen sans palier montre quand meme sa structure', function () {
    // Le TCF, le TEF, le TestDaF et le TOEFL n'ont qu'une epreuve, dont le
    // resultat situe le candidat : leur plan ne porte aucun niveau. Chercher le
    // plan du niveau de l'apprenant n'en trouvait aucun, et leurs parties
    // n'etaient jamais affichees — alors qu'elles sont decrites.
    $langue = Language::create(['slug' => 'french', 'name' => 'Français', 'native_name' => 'Français', 'flag' => 'fr', 'is_active' => true]);
    $exam = Exam::create(['language_id' => $langue->id, 'slug' => 'tcf-test', 'name' => 'TCF']);
    $section = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'co', 'name' => 'Compréhension orale', 'skill_type' => 'listening', 'time_limit' => 25]);
    ExerciseType::create(['section_id' => $section->id, 'slug' => 'mcq', 'name' => 'QCM', 'skill_type' => 'listening', 'component_key' => 'mcq']);

    ExamBlueprint::create([
        'exam_id' => $exam->id, 'level' => null, 'variant' => 'tcf-tout-public',
        'name' => 'TCF tout public', 'total_duration_minutes' => 85, 'scoring_config' => [],
        'sections_config' => [[
            'slug' => 'co', 'name' => 'Compréhension orale', 'skill_type' => 'listening',
            'parts' => [['part' => 1, 'name' => 'Compréhension orale', 'type' => 'mcq', 'items' => 29, 'source' => 'officiel']],
        ]],
    ]);

    expect(app(StructureOfficielle::class)->parties($exam, 'B1'))->toHaveCount(1);
});

test('un plan derive ne masque pas la structure officielle', function () {
    $langue = Language::create(['slug' => 'french', 'name' => 'Français', 'native_name' => 'Français', 'flag' => 'fr', 'is_active' => true]);
    $exam = Exam::create(['language_id' => $langue->id, 'slug' => 'tcf-derive', 'name' => 'TCF']);
    $section = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'co', 'name' => 'CO', 'skill_type' => 'listening', 'time_limit' => 25]);
    ExerciseType::create(['section_id' => $section->id, 'slug' => 'mcq', 'name' => 'QCM', 'skill_type' => 'listening', 'component_key' => 'mcq']);

    ExamBlueprint::create([
        'exam_id' => $exam->id, 'level' => null, 'variant' => 'tcf-tout-public',
        'name' => 'TCF tout public', 'total_duration_minutes' => 85, 'scoring_config' => [],
        'sections_config' => [['slug' => 'co', 'name' => 'CO', 'skill_type' => 'listening',
            'parts' => [['part' => 1, 'name' => 'Compréhension orale', 'type' => 'mcq', 'source' => 'officiel']]]],
    ]);
    // Le compositeur fabrique un plan pour le B1, que l'examen ne decrit pas.
    // Il ne porte aucune partie : s'en contenter privait le candidat de la
    // structure reelle de son epreuve.
    ExamBlueprint::create([
        'exam_id' => $exam->id, 'level' => 'B1', 'variant' => null,
        'name' => 'TCF — entraînement niveau B1', 'total_duration_minutes' => 85, 'scoring_config' => [],
        'sections_config' => [['slug' => 'co', 'name' => 'CO', 'skill_type' => 'listening']],
    ]);

    expect(app(StructureOfficielle::class)->parties($exam, 'B1'))->toHaveCount(1);
});

test('un examen qui declare des niveaux ne montre rien hors de ces niveaux', function () {
    $exam = examenStructure();

    // Cambridge ne propose rien au A1 : sa plus basse qualification est le
    // A2 Key. Montrer a un apprenant A1 les parties d'une autre qualification
    // lui ferait croire qu'il peut s'y presenter.
    expect(app(StructureOfficielle::class)->parties($exam, 'A1'))->toBe([]);
});

test('un plan dont les parties n ont pas de type ne compte pas comme structure', function () {
    $langue = Language::create(['slug' => 'german', 'name' => 'Allemand', 'native_name' => 'Deutsch', 'flag' => 'de', 'is_active' => true]);
    $exam = Exam::create(['language_id' => $langue->id, 'slug' => 'testdaf-test', 'name' => 'TestDaF']);
    $section = ExamSection::create(['exam_id' => $exam->id, 'slug' => 'lesen', 'name' => 'Lesen', 'skill_type' => 'reading', 'time_limit' => 55]);
    ExerciseType::create(['section_id' => $section->id, 'slug' => 'open-cloze', 'name' => 'Texte à trous', 'skill_type' => 'reading', 'component_key' => 'open-cloze']);

    // L'epreuve reelle, decrite avec des types ouvrables.
    ExamBlueprint::create([
        'exam_id' => $exam->id, 'level' => null, 'variant' => null,
        'name' => 'TestDaF', 'total_duration_minutes' => 190, 'scoring_config' => [],
        'sections_config' => [['slug' => 'lesen', 'name' => 'Lesen', 'skill_type' => 'reading',
            'parts' => [['part' => 1, 'name' => 'Lesen, Aufgabe 1', 'type' => 'open-cloze', 'source' => 'officiel']]]],
    ]);

    // Un plan derive qui a garde d'anciennes parties PUREMENT DESCRIPTIVES :
    // elles portent un nom mais aucun type, donc rien ne s'ouvre.
    ExamBlueprint::create([
        'exam_id' => $exam->id, 'level' => 'C1', 'variant' => null,
        'name' => 'TestDaF — entraînement niveau C1', 'total_duration_minutes' => 190, 'scoring_config' => [],
        'sections_config' => [['slug' => 'lesen', 'name' => 'Lesen', 'skill_type' => 'reading',
            'parts' => [['name' => 'Lesetext 1', 'description' => 'Un texte long.']]]],
    ]);

    $parties = app(StructureOfficielle::class)->parties($exam, 'C1');

    expect($parties)->toHaveCount(1)
        ->and($parties[0]['name'])->toBe('Lesen, Aufgabe 1')
        ->and($parties[0]['exercise_type_id'])->not->toBeNull();
});
