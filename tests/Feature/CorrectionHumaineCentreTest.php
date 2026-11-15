<?php

use App\Models\Classroom;
use App\Models\CorrectionEnseignant;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Models\UserExerciseAttempt;
use App\Models\UserProfile;
use App\Services\Center\CorrectionHumaine;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Correction humaine par le centre : file « A corriger », note de l'enseignant
 * dans le bareme de l'examen, commentaire visible par l'eleve, et isolation
 * stricte entre centres et entre classes.
 */
function correctionCentre(): array
{
    $langue = Language::create(['slug' => 'french', 'name' => 'French', 'native_name' => 'Français', 'flag' => '🇫🇷']);
    $examen = Exam::create(['language_id' => $langue->id, 'slug' => 'delf-b1', 'name' => 'DELF B1']);
    $exercice = function (string $competence) use ($examen) {
        $section = ExamSection::create(['exam_id' => $examen->id, 'slug' => $competence, 'name' => $competence, 'skill_type' => $competence]);
        $type = ExerciseType::create(['section_id' => $section->id, 'slug' => "t-{$competence}", 'name' => 'T', 'skill_type' => $competence, 'component_key' => 'essay']);

        return Exercise::create(['exam_id' => $examen->id, 'exercise_type_id' => $type->id, 'difficulty' => 'B1', 'content' => [],
            'questions' => [['id' => 'q1', 'prompt' => 'Racontez vos vacances.']]]);
    };
    $ecrit = $exercice('writing');
    $oral = $exercice('speaking');
    $lecture = $exercice('reading');

    $membre = function (LanguageCenter $centre, string $role, string $nom = 'X') {
        $u = User::factory()->create(['name' => $nom]);
        UserProfile::factory()->for($u)->create(['onboarding_completed_at' => now()]);
        $centre->members()->attach($u->id, ['role' => $role, 'joined_at' => now()]);

        return $u;
    };

    $centre = LanguageCenter::create(['name' => 'Institut Test', 'slug' => 'institut-test', 'owner_email' => 'd@exemple.test', 'is_active' => true]);
    $admin = $membre($centre, 'center_admin');
    $prof1 = $membre($centre, 'teacher');
    $prof2 = $membre($centre, 'teacher');
    $classeA = $centre->classrooms()->create(['name' => 'B1 soir', 'exam_id' => $examen->id, 'invite_code' => Classroom::generateInviteCode()]);
    $classeA->members()->attach($prof1->id, ['role_in_class' => 'teacher']);
    $classeB = $centre->classrooms()->create(['name' => 'B1 matin', 'exam_id' => $examen->id, 'invite_code' => Classroom::generateInviteCode()]);
    $classeB->members()->attach($prof2->id, ['role_in_class' => 'teacher']);
    $awa = $membre($centre, 'student', 'Awa');
    $zoe = $membre($centre, 'student', 'Zoé');
    $classeA->members()->attach($awa->id, ['role_in_class' => 'student']);
    $classeB->members()->attach($zoe->id, ['role_in_class' => 'student']);

    $autre = LanguageCenter::create(['name' => 'Concurrent', 'slug' => 'concurrent', 'owner_email' => 'x@exemple.test', 'is_active' => true]);
    $adminAutre = $membre($autre, 'center_admin');

    $tentative = fn (User $u, Exercise $e, array $extra = []) => UserExerciseAttempt::create(array_merge([
        'user_id' => $u->id, 'exercise_id' => $e->id, 'answers' => ['q1' => 'Je suis allée à la mer.'], 'score' => 50,
        'accuracy_percent' => 55, 'time_spent' => 60, 'xp_earned' => 5,
        'feedback' => [['question_id' => 'q1', 'accuracy' => 55, 'sub_scores' => ['task_achievement' => 5], 'grille_officielle' => ['affichage' => '13 / 25'], 'explanation' => 'Correct.']],
    ], $extra));

    return compact('centre', 'admin', 'prof1', 'prof2', 'classeA', 'classeB', 'awa', 'zoe', 'adminAutre', 'ecrit', 'oral', 'lecture', 'tentative');
}

it('liste les redactions et prises de parole non revues, pas les autres competences', function () {
    $c = correctionCentre();
    $ecrit = ($c['tentative'])($c['awa'], $c['ecrit']);
    $oral = ($c['tentative'])($c['zoe'], $c['oral']);
    ($c['tentative'])($c['awa'], $c['lecture']);

    $this->actingAs($c['admin'])->get(route('center.corrections.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $p) => $p->component('center/corrections/index')
            ->has('productions', 2)
            ->where('productions', fn ($l) => collect($l)->pluck('id')->sort()->values()->all() === [$ecrit->id, $oral->id])
            ->has('classes', 2));
});

it('filtre par classe et ne montre a un enseignant que ses classes', function () {
    $c = correctionCentre();
    $ecrit = ($c['tentative'])($c['awa'], $c['ecrit']);
    ($c['tentative'])($c['zoe'], $c['oral']);

    $this->actingAs($c['admin'])->get(route('center.corrections.index', ['classe' => $c['classeB']->id]))
        ->assertInertia(fn (Assert $p) => $p->has('productions', 1)->where('productions.0.eleve', 'Zoé'));

    $this->actingAs($c['prof1'])->get(route('center.corrections.index'))
        ->assertInertia(fn (Assert $p) => $p->has('productions', 1)->where('productions.0.id', $ecrit->id)->has('classes', 1));

    // Filtrer sur la classe d'un collegue ne revele rien.
    $this->actingAs($c['prof1'])->get(route('center.corrections.index', ['classe' => $c['classeB']->id]))
        ->assertInertia(fn (Assert $p) => $p->has('productions', 0));
});

it('enregistre la note de l enseignant dans le bareme de l examen et la retire de la file', function () {
    $c = correctionCentre();
    $t = ($c['tentative'])($c['awa'], $c['ecrit']);

    $this->actingAs($c['prof1'])->get(route('center.corrections.show', $t))
        ->assertOk()
        ->assertInertia(fn (Assert $p) => $p->component('center/corrections/show')
            ->where('production.items.0.texte', 'Je suis allée à la mer.')
            ->where('production.items.0.ia.grille.affichage', '13 / 25'));

    $this->actingAs($c['prof1'])->post(route('center.corrections.store', $t), [
        'sous_notes' => ['task_achievement' => 9, 'coherence_cohesion' => 9, 'lexical_resource' => 9, 'grammar_accuracy' => 9],
        'commentaire' => 'Très bien, attention aux accords.',
    ])->assertRedirect(route('center.corrections.index'));

    $correction = CorrectionEnseignant::where('attempt_id', $t->id)->firstOrFail();
    expect($correction->center_id)->toBe($c['centre']->id)
        ->and($correction->teacher_id)->toBe($c['prof1']->id)
        ->and($correction->grille['affichage'])->toBe('25 / 25')
        ->and($correction->grille)->not->toHaveKey('mention');

    $this->actingAs($c['prof1'])->get(route('center.corrections.index'))
        ->assertInertia(fn (Assert $p) => $p->has('productions', 0));
    $this->actingAs($c['prof1'])->get(route('center.corrections.index', ['vue' => 'revues']))
        ->assertInertia(fn (Assert $p) => $p->has('productions', 1)->where('productions.0.note_enseignant', '25 / 25'));
});

it('refuse une note hors du bareme', function () {
    $c = correctionCentre();
    $t = ($c['tentative'])($c['awa'], $c['ecrit']);

    $this->actingAs($c['prof1'])->post(route('center.corrections.store', $t), [
        'sous_notes' => ['task_achievement' => 12, 'coherence_cohesion' => 5, 'lexical_resource' => 5],
    ])->assertSessionHasErrors(['sous_notes.task_achievement', 'sous_notes.grammar_accuracy']);

    expect(CorrectionEnseignant::count())->toBe(0);
});

it('isole strictement les centres et les classes', function () {
    $c = correctionCentre();
    $t = ($c['tentative'])($c['awa'], $c['ecrit']);
    $notes = ['sous_notes' => ['task_achievement' => 1, 'coherence_cohesion' => 1, 'lexical_resource' => 1, 'grammar_accuracy' => 1]];

    // Un autre centre : ni la file, ni la fiche, ni la note.
    $this->actingAs($c['adminAutre'])->get(route('center.corrections.index'))
        ->assertInertia(fn (Assert $p) => $p->has('productions', 0));
    $this->actingAs($c['adminAutre'])->get(route('center.corrections.show', $t))->assertForbidden();
    $this->actingAs($c['adminAutre'])->post(route('center.corrections.store', $t), $notes)->assertForbidden();

    // Un enseignant du meme centre, mais d'une autre classe.
    $this->actingAs($c['prof2'])->get(route('center.corrections.show', $t))->assertForbidden();
    $this->actingAs($c['prof2'])->post(route('center.corrections.store', $t), $notes)->assertForbidden();

    // Une tentative de lecture n'est pas une production a corriger.
    $lecture = ($c['tentative'])($c['awa'], $c['lecture']);
    $this->actingAs($c['admin'])->get(route('center.corrections.show', $lecture))->assertForbidden();

    // Un eleve ne corrige pas.
    $this->actingAs($c['awa'])->post(route('center.corrections.store', $t), $notes)->assertForbidden();

    expect(CorrectionEnseignant::count())->toBe(0);
});

it('fait ecouter l audio d une prise de parole a l enseignant de la classe seulement', function () {
    Storage::fake(CorrectionHumaine::DISQUE);
    $c = correctionCentre();
    $audios = CorrectionHumaine::conserverAudios($c['zoe']->id, ['q1' => UploadedFile::fake()->create('voix.webm', 10, 'audio/webm'), 'q2' => 'texte']);
    expect($audios)->toHaveKey('q1')->not->toHaveKey('q2');
    Storage::disk(CorrectionHumaine::DISQUE)->assertExists($audios['q1']);

    $t = ($c['tentative'])($c['zoe'], $c['oral'], ['answers' => [], 'audios' => $audios,
        'feedback' => [['question_id' => 'q1', 'accuracy' => 40, 'transcription' => 'Je parle de mes vacances.']]]);

    $this->actingAs($c['prof2'])->get(route('center.corrections.show', $t))
        ->assertInertia(fn (Assert $p) => $p->where('production.competence', 'speaking')
            ->where('production.items.0.transcription', 'Je parle de mes vacances.')
            ->where('production.items.0.audio', route('center.corrections.audio', ['attempt' => $t->id, 'question' => 'q1'])));
    $this->actingAs($c['prof2'])->get(route('center.corrections.audio', ['attempt' => $t->id, 'question' => 'q1']))->assertOk();
    $this->actingAs($c['prof1'])->get(route('center.corrections.audio', ['attempt' => $t->id, 'question' => 'q1']))->assertForbidden();
    $this->actingAs($c['adminAutre'])->get(route('center.corrections.audio', ['attempt' => $t->id, 'question' => 'q1']))->assertForbidden();
});

it('montre a l eleve la note de l enseignant et son commentaire sur la page de resultat', function () {
    $c = correctionCentre();
    $t = ($c['tentative'])($c['awa'], $c['ecrit']);

    $this->actingAs($c['awa'])->get(route('exercise.result', $t))
        ->assertInertia(fn (Assert $p) => $p->where('correctionEnseignant', null));

    $this->actingAs($c['prof1'])->post(route('center.corrections.store', $t), [
        'sous_notes' => ['task_achievement' => 4.5, 'coherence_cohesion' => 4.5, 'lexical_resource' => 4.5, 'grammar_accuracy' => 4.5],
        'commentaire' => 'Revois le passé composé.',
    ]);

    $this->actingAs($c['awa'])->get(route('exercise.result', $t))
        ->assertOk()
        ->assertInertia(fn (Assert $p) => $p->component('exercise/result')
            ->where('correctionEnseignant.commentaire', 'Revois le passé composé.')
            ->where('correctionEnseignant.grille.affichage', '12,5 / 25')
            ->where('correctionEnseignant.enseignant', $c['prof1']->name)
            ->missing('attempt.audios'));
});

it('garde separees les corrections de deux centres pour un eleve inscrit dans les deux', function () {
    $c = correctionCentre();
    $t = ($c['tentative'])($c['awa'], $c['ecrit']);

    // Awa est aussi eleve du centre concurrent, dans une classe de ce centre.
    $autre = LanguageCenter::where('slug', 'concurrent')->firstOrFail();
    $autre->members()->attach($c['awa']->id, ['role' => 'student', 'joined_at' => now()]);
    $classeAutre = $autre->classrooms()->create(['name' => 'Soir', 'invite_code' => Classroom::generateInviteCode()]);
    $classeAutre->members()->attach($c['awa']->id, ['role_in_class' => 'student']);

    $this->actingAs($c['prof1'])->post(route('center.corrections.store', $t), [
        'sous_notes' => ['task_achievement' => 9, 'coherence_cohesion' => 9, 'lexical_resource' => 9, 'grammar_accuracy' => 9],
        'commentaire' => 'Commentaire du centre A.',
    ])->assertRedirect();

    // B voit toujours la production a corriger, sans la correction de A.
    $this->actingAs($c['adminAutre'])->get(route('center.corrections.index'))
        ->assertInertia(fn (Assert $p) => $p->has('productions', 1)->where('productions.0.note_enseignant', null));
    $this->actingAs($c['adminAutre'])->get(route('center.corrections.index', ['vue' => 'revues']))
        ->assertInertia(fn (Assert $p) => $p->has('productions', 0));
    $this->actingAs($c['adminAutre'])->get(route('center.corrections.show', $t))
        ->assertOk()
        ->assertInertia(fn (Assert $p) => $p->where('production.correction', null));

    // B corrige a son tour : la correction de A reste intacte.
    $this->actingAs($c['adminAutre'])->post(route('center.corrections.store', $t), [
        'sous_notes' => ['task_achievement' => 4.5, 'coherence_cohesion' => 4.5, 'lexical_resource' => 4.5, 'grammar_accuracy' => 4.5],
        'commentaire' => 'Commentaire du centre B.',
    ])->assertRedirect();

    expect(CorrectionEnseignant::where('attempt_id', $t->id)->count())->toBe(2);
    $deA = CorrectionEnseignant::where('attempt_id', $t->id)->where('center_id', $c['centre']->id)->firstOrFail();
    expect($deA->teacher_id)->toBe($c['prof1']->id)
        ->and($deA->commentaire)->toBe('Commentaire du centre A.')
        ->and($deA->grille['affichage'])->toBe('25 / 25');

    // Chaque centre ne voit que la sienne.
    $this->actingAs($c['prof1'])->get(route('center.corrections.show', $t))
        ->assertInertia(fn (Assert $p) => $p->where('production.correction.commentaire', 'Commentaire du centre A.'));
    $this->actingAs($c['adminAutre'])->get(route('center.corrections.show', $t))
        ->assertInertia(fn (Assert $p) => $p->where('production.correction.commentaire', 'Commentaire du centre B.'));
    $this->actingAs($c['prof1'])->get(route('center.corrections.index', ['vue' => 'revues']))
        ->assertInertia(fn (Assert $p) => $p->where('productions.0.note_enseignant', '25 / 25'));
});
