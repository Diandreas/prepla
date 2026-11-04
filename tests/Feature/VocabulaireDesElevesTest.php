<?php

use App\Models\Classroom;
use App\Models\DictionaryWord;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Models\UserError;
use App\Models\UserProfile;
use App\Models\UserWordProgress;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * L'enseignant doit pouvoir verifier ou en est le vocabulaire de ses eleves.
 *
 * Chaque mot du lexique personnel (« Mes mots ») passe de « decouvert » a « en
 * cours » puis « maitrise », et revient a date fixe. Tout etait enregistre, mais
 * le centre n'en voyait rien.
 */
beforeEach(fn () => $this->withoutVite());

function centreDeVocabulaire(): array
{
    $centre = LanguageCenter::create([
        'name' => 'Institut Mots', 'slug' => 'institut-mots', 'owner_email' => 'resp@exemple.test', 'is_active' => true,
    ]);

    $membre = function (string $role) use ($centre) {
        $u = User::factory()->create();
        UserProfile::factory()->for($u)->create(['onboarding_completed_at' => now()]);
        $centre->members()->attach($u->id, ['role' => $role, 'joined_at' => now()]);

        return $u;
    };

    $responsable = $membre('center_admin');
    $prof = $membre('teacher');
    $autreProf = $membre('teacher');
    $eleve = $membre('student');
    $camarade = $membre('student');
    $autreEleve = $membre('student');

    $classe = $centre->classrooms()->create(['name' => 'Groupe B1', 'level' => 'B1', 'invite_code' => Classroom::generateInviteCode()]);
    $classe->members()->attach($prof->id, ['role_in_class' => 'teacher']);
    $classe->members()->attach($eleve->id, ['role_in_class' => 'student']);
    $classe->members()->attach($camarade->id, ['role_in_class' => 'student']);

    $autreClasse = $centre->classrooms()->create(['name' => 'Groupe A2', 'level' => 'A2', 'invite_code' => Classroom::generateInviteCode()]);
    $autreClasse->members()->attach($autreProf->id, ['role_in_class' => 'teacher']);
    $autreClasse->members()->attach($autreEleve->id, ['role_in_class' => 'student']);

    return compact('centre', 'responsable', 'prof', 'autreProf', 'eleve', 'camarade', 'autreEleve', 'classe', 'autreClasse');
}

function motAppris(User $eleve, string $mot, string $etape, array $champs = []): UserWordProgress
{
    $entree = DictionaryWord::firstOrCreate(
        ['word' => $mot, 'language' => 'fr'],
        ['definition' => "définition de {$mot}", 'translation' => "{$mot} (trad.)", 'example' => '', 'skill_level' => 'B1'],
    );

    return UserWordProgress::create(array_merge([
        'user_id' => $eleve->id,
        'dictionary_word_id' => $entree->id,
        'status' => $etape,
    ], $champs));
}

test('l enseignant voit l etape de chaque mot de son eleve et ce qui attend une revision', function () {
    $c = centreDeVocabulaire();
    motAppris($c['eleve'], 'maison', 'mastered', ['next_review_at' => now()->addDays(5), 'last_reviewed_at' => now()->subDay(), 'recognition_count' => 1, 'recall_count' => 2]);
    motAppris($c['eleve'], 'voiture', 'learning', ['next_review_at' => now()->subHour(), 'last_reviewed_at' => now()->subDays(2)]);
    motAppris($c['eleve'], 'arbre', 'learning', ['next_review_at' => now()->subDays(10), 'last_reviewed_at' => now()->subDays(13)]);
    motAppris($c['eleve'], 'fleur', 'discovered');
    // Le lexique d'un autre eleve ne se melange pas au sien.
    motAppris($c['camarade'], 'soleil', 'learning');

    $this->actingAs($c['prof'])
        ->get(route('center.students.vocabulaire', $c['eleve']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('center/students/vocabulaire')
            ->where('bilan.total', 4)
            ->where('bilan.par_etape', ['discovered' => 1, 'learning' => 2, 'mastered' => 1])
            // voiture et arbre sont dus ; fleur n'a jamais ete planifie ; maison attend.
            ->where('bilan.a_reviser', 3)
            ->where('bilan.laisses_de_cote', 1)
            ->where('bilan.revises_7_jours', 2)
            ->has('mots', 4)
            // Ce qui demande une action passe en tete.
            ->where('mots.0.a_reviser', true)
            ->where('mots.3.mot', 'maison')
            ->where('mots.3.rappels', 2)
        );
});

test('la page montre aussi les fautes de vocabulaire des exercices, et seulement elles', function () {
    $c = centreDeVocabulaire();
    UserError::create([
        'user_id' => $c['eleve']->id, 'question_id' => 'q1', 'exercise_type_slug' => 'mcq', 'error_category' => 'vocabulary', 'subcategory' => 'lexical',
        'skill_type' => 'reading', 'question_text' => 'Il a ___ le bus.', 'user_answer' => 'pris en retard',
        'correct_answer' => 'raté', 'explanation' => '« Rater le bus » : ne pas l’attraper.', 'mastered' => false,
    ]);
    UserError::create([
        'user_id' => $c['eleve']->id, 'question_id' => 'q1', 'exercise_type_slug' => 'mcq', 'error_category' => 'grammar', 'subcategory' => 'accord',
        'skill_type' => 'writing', 'question_text' => 'Elles sont ___.', 'user_answer' => 'parti',
        'correct_answer' => 'parties', 'explanation' => 'Accord avec le sujet.', 'mastered' => false,
    ]);

    $this->actingAs($c['responsable'])
        ->get(route('center.students.vocabulaire', $c['eleve']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('fautes', 1)
            ->where('fautes.0.categorie', 'vocabulary')
            ->where('fautes.0.exemples.0.correction', 'raté')
        );
});

test('la classe voit qui revise et quels mots resistent a plusieurs eleves', function () {
    $c = centreDeVocabulaire();
    motAppris($c['eleve'], 'pourtant', 'learning', ['next_review_at' => now()->subDay()]);
    motAppris($c['camarade'], 'pourtant', 'learning', ['next_review_at' => now()->addDay()]);
    motAppris($c['eleve'], 'maison', 'mastered', ['next_review_at' => now()->addDays(3), 'last_reviewed_at' => now()->subDay()]);
    // Un seul eleve sur ce mot : ce n'est pas une difficulte de classe.
    motAppris($c['camarade'], 'néanmoins', 'learning');
    // Un eleve d'une autre classe ne compte pas.
    motAppris($c['autreEleve'], 'pourtant', 'learning');

    $this->actingAs($c['prof'])
        ->get(route('center.classes.vocabulaire', $c['classe']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('center/classes/vocabulaire')
            ->has('eleves', 2)
            ->has('mots_difficiles', 1)
            ->where('mots_difficiles.0.mot', 'pourtant')
            ->where('mots_difficiles.0.eleves', 2)
            ->where('mots_difficiles.0.a_reviser', 1)
        );
});

test('un enseignant ne voit pas le vocabulaire des eleves d une autre classe', function () {
    $c = centreDeVocabulaire();

    $this->actingAs($c['prof'])->get(route('center.students.vocabulaire', $c['autreEleve']))->assertForbidden();
    $this->actingAs($c['prof'])->get(route('center.classes.vocabulaire', $c['autreClasse']))->assertForbidden();
    // Ni celui d'un collegue, qui n'est pas un eleve.
    $this->actingAs($c['responsable'])->get(route('center.students.vocabulaire', $c['autreProf']))->assertForbidden();
    // Un eleve n'entre pas dans l'espace du centre.
    $this->actingAs($c['eleve'])->get(route('center.students.vocabulaire', $c['camarade']))->assertForbidden();
});

test('regarder le vocabulaire d un eleve ne modifie pas son lexique', function () {
    $c = centreDeVocabulaire();
    $mot = motAppris($c['eleve'], 'voiture', 'learning', ['next_review_at' => now()->subDay()]);
    $avant = $mot->fresh()->toArray();

    $this->actingAs($c['prof'])->get(route('center.students.vocabulaire', $c['eleve']))->assertOk();

    expect($mot->fresh()->toArray())->toBe($avant)
        ->and(UserWordProgress::where('user_id', $c['eleve']->id)->count())->toBe(1);
});
