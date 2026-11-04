<?php

use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\LanguageCenter;
use App\Models\LearningPathNode;
use App\Models\User;
use App\Models\UserError;
use App\Models\UserExerciseAttempt;
use App\Models\UserProfile;
use App\Services\AI\CorrectionsDeProduction;
use App\Services\AI\DeepgramSttService;
use App\Services\AI\DiagnosticDeProduction;
use App\Services\AI\MistralEvaluationService;
use App\Services\AI\MistralService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * L'IA rend parfois un champ sous une autre forme que celle demandee : une
 * explication en objet, une categorie en liste. Chaque fois, toute la correction
 * tombait — la note comprise. L'apprenant perdait sa copie, parfois son examen
 * blanc entier, et le centre ne pouvait plus ouvrir la copie.
 *
 * Ce qui ne se lit pas est ignore ; le reste de la correction tient.
 */
beforeEach(fn () => $this->withoutVite());

function productionACorriger(string $competence, string $typeQuestion): array
{
    $langue = Language::create(['slug' => 'german', 'name' => 'Allemand', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::create(['language_id' => $langue->id, 'slug' => 'goethe', 'name' => 'Goethe-Zertifikat']);
    $section = ExamSection::create(['exam_id' => $exam->id, 'slug' => $competence, 'name' => $competence, 'skill_type' => $competence]);
    $type = ExerciseType::create([
        'section_id' => $section->id, 'slug' => $typeQuestion, 'name' => $typeQuestion,
        'skill_type' => $competence, 'component_key' => $competence === 'speaking' ? 'speaking-recorder' : 'essay-editor',
    ]);
    $node = LearningPathNode::create([
        'exam_id' => $exam->id, 'sort_order' => 1, 'title' => 'Production', 'node_type' => 'general', 'level' => 'B1',
    ]);
    $exercice = Exercise::create([
        'exam_id' => $exam->id, 'exercise_type_id' => $type->id, 'exam_section_id' => $section->id,
        'node_id' => $node->id, 'order_in_node' => 1, 'difficulty' => 'B1',
        'content' => ['title' => 'Goethe B1 · '.ucfirst($competence)],
        'questions' => [['id' => 'q1', 'type' => $typeQuestion, 'text' => 'Erzählen Sie von Ihrem Urlaub.']],
    ]);

    $eleve = User::factory()->create();
    UserProfile::factory()->for($eleve)->create([
        'target_exam_id' => $exam->id, 'current_level' => 'B1', 'onboarding_completed_at' => now(), 'xp_total' => 0,
    ]);

    return compact('exam', 'exercice', 'node', 'eleve');
}

function iaRendant(array $charge): void
{
    $mistral = Mockery::mock(MistralService::class);
    $mistral->shouldReceive('chat')->andReturn(json_encode($charge));
    app()->instance(MistralService::class, $mistral);
}

/** Une redaction corrigee : une faute dont l'explication est un objet, et un profil illisible. */
function redactionMalFormee(): array
{
    return [
        'score' => 7.5,
        'band_scores' => ['task_achievement' => 8, 'coherence_cohesion' => 7, 'lexical_resource' => 6, 'grammar_accuracy' => 7],
        'corrections' => [[
            'original' => 'weil ich habe Zeit', 'corrected' => 'weil ich Zeit habe',
            'category' => ['family' => 'grammar'], 'subcategory' => ['word-order'],
            'explanation' => ['concept' => 'Après « weil », le verbe va à la fin.'],
        ]],
        'diagnostic' => [['dimension' => 'lexical_range', 'observation' => ['« gut » revient 5 fois', 'aucun synonyme'], 'severity' => 'major']],
        'feedback' => 'Bon récit.',
    ];
}

test('une faute illisible est ignoree, une explication en objet est lue', function () {
    $propres = CorrectionsDeProduction::nettoyer([
        ['original' => 'weil ich habe Zeit', 'corrected' => 'weil ich Zeit habe', 'category' => 'grammar.word-order', 'explanation' => ['concept' => 'Le verbe va à la fin.']],
        ['original' => ['weil ich habe Zeit'], 'corrected' => 'weil ich Zeit habe'],
        ['original' => 'gut', 'corrected' => 'erholsam', 'category' => ['vocabulary', 'range'], 'explanation' => 'Répétition.'],
        'pas une faute',
    ]);

    expect($propres)->toHaveCount(2)
        ->and($propres[0]['explanation'])->toBe('Le verbe va à la fin.')
        ->and($propres[1]['category'])->toBe('')
        ->and(DiagnosticDeProduction::nettoyer([
            ['dimension' => 'lexical_range', 'observation' => ['« gut » revient 5 fois'], 'severity' => 'major'],
            ['dimension' => 'coherence', 'observation' => 'Aucun connecteur.', 'severity' => 'major'],
        ]))->toHaveCount(1);
});

test('une faute orale mal formee ne coute pas sa note a l apprenant', function () {
    $mistral = Mockery::mock(MistralService::class);
    $mistral->shouldReceive('chat')->andReturn(json_encode([
        'isCorrect' => true, 'accuracy' => 70, 'covered_points' => ['le lieu'], 'missing_points' => [],
        'explanation' => ['concept' => 'Gut gemacht.'],
        'errors' => [[
            'original' => 'ich bin gegangen in Berlin', 'corrected' => 'ich bin nach Berlin gefahren',
            'category' => ['grammar', 'preposition'], 'explanation' => ['concept' => 'On va « nach » une ville.'],
        ]],
        'diagnostic' => [['dimension' => 'fluency', 'observation' => ['« äh » six fois'], 'severity' => 'minor']],
    ]));

    $resultat = (new MistralEvaluationService($mistral))->evaluateSpeaking('Erzählen Sie.', 'Ich bin gegangen in Berlin.', 'German');

    expect($resultat['accuracy'])->toBe(70)
        ->and($resultat['corrections'][0]['explanation'])->toBe('On va « nach » une ville.')
        ->and($resultat['diagnostic'])->toBe([]);
});

test('une redaction a la correction mal formee est enregistree et entre au dossier', function () {
    $d = productionACorriger('writing', 'essay');
    iaRendant(redactionMalFormee());

    $this->actingAs($d['eleve'])->post(route('exercise.submit_session', $d['node']), [
        'exercise_ids' => [$d['exercice']->id],
        'answers_by_exercise' => [$d['exercice']->id => ['q1' => 'Mein Urlaub war gut, weil ich habe Zeit.']],
        'time_spent' => 300,
    ])->assertRedirect();

    $erreur = UserError::where('user_id', $d['eleve']->id)->sole();

    expect(UserExerciseAttempt::where('user_id', $d['eleve']->id)->count())->toBe(1)
        ->and($erreur->user_answer)->toBe('weil ich habe Zeit')
        ->and($erreur->explanation)->toBe('Après « weil », le verbe va à la fin.')
        // Sans categorie lisible, la faute reste une faute de redaction.
        ->and($erreur->error_category)->toBe('writing');
});

test('hors parcours, la meme copie rapporte son XP au lieu de partir en erreur', function () {
    $d = productionACorriger('writing', 'essay');
    $d['exercice']->update(['node_id' => null]);
    iaRendant(redactionMalFormee());

    $this->actingAs($d['eleve'])->post(route('exercise.submit', $d['exercice']), [
        'answers' => ['q1' => 'Mein Urlaub war gut, weil ich habe Zeit.'],
        'time_spent' => 300,
    ])->assertRedirect();

    expect($d['eleve']->profile->fresh()->xp_total)->toBeGreaterThan(0);
});

test('un examen blanc corrige avec une sortie mal formee n est pas perdu', function () {
    $d = productionACorriger('writing', 'essay');
    iaRendant(redactionMalFormee());
    // L'examen tire est retire du cache des la soumission : un plantage pendant
    // la correction le faisait perdre en entier.
    Cache::put("practice-simulation:{$d['eleve']->id}:{$d['exam']->id}", [$d['exercice']->id], now()->addHour());

    $this->actingAs($d['eleve'])->post(route('practice.simulate.store', $d['exam']), [
        'answers_by_exercise' => [$d['exercice']->id => ['q1' => 'Mein Urlaub war gut, weil ich habe Zeit.']],
        'time_spent' => 300,
    ])->assertRedirect();

    expect(UserExerciseAttempt::where('user_id', $d['eleve']->id)->count())->toBe(1);
});

test('le centre ouvre une copie corrigee avant le filtre, meme mal formee', function () {
    $d = productionACorriger('writing', 'essay');
    $centre = LanguageCenter::create(['name' => 'Centre', 'slug' => 'centre', 'owner_email' => 'c@exemple.test', 'is_active' => true]);
    $resp = User::factory()->create();
    UserProfile::factory()->for($resp)->create(['onboarding_completed_at' => now()]);
    $centre->members()->attach($resp->id, ['role' => 'center_admin', 'joined_at' => now()]);
    $centre->members()->attach($d['eleve']->id, ['role' => 'student', 'joined_at' => now()]);
    $classe = $centre->classrooms()->create(['name' => 'B1', 'level' => 'B1', 'exam_id' => $d['exam']->id, 'invite_code' => Classroom::generateInviteCode()]);
    $classe->members()->attach($d['eleve']->id, ['role_in_class' => 'student']);

    // Telle qu'elle a ete enregistree avant que la sortie de l'IA ne soit filtree.
    $tentative = UserExerciseAttempt::create([
        'user_id' => $d['eleve']->id, 'exercise_id' => $d['exercice']->id,
        'answers' => ['q1' => 'Mein Urlaub war gut, weil ich habe Zeit.'], 'score' => 1, 'accuracy_percent' => 83,
        'time_spent' => 300, 'xp_earned' => 10,
        'feedback' => [['question_id' => 'q1', 'correct' => true, 'accuracy' => 83, 'band_score' => 7.5] + redactionMalFormee()],
    ]);

    $this->actingAs($resp)
        ->get(route('center.students.attempt', ['user' => $d['eleve'], 'attempt' => $tentative]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('tentative.questions.0.production.corrections.0.avant', 'weil ich habe Zeit')
            ->where('tentative.questions.0.production.corrections.0.explication', 'Après « weil », le verbe va à la fin.')
            ->where('tentative.questions.0.production.diagnostic', []));
});

test('une panne de la correction ecrite ne compte pas comme une copie ratee', function () {
    Http::fake(['api.mistral.ai/*' => Http::response('', 500)]);
    $d = productionACorriger('writing', 'essay');

    $this->actingAs($d['eleve'])->post(route('exercise.submit_session', $d['node']), [
        'exercise_ids' => [$d['exercice']->id],
        'answers_by_exercise' => [$d['exercice']->id => ['q1' => 'Mein Urlaub war gut, weil ich habe Zeit.']],
        'time_spent' => 300,
    ])->assertRedirect();

    $retour = UserExerciseAttempt::where('user_id', $d['eleve']->id)->sole()->feedback[0];

    // Elle sort du score, et n'inscrit aucune faiblesse que personne n'a constatee.
    expect($retour['technical_failure'])->toBeTrue()
        ->and($retour['correct'])->toBeFalse()
        ->and(UserError::where('user_id', $d['eleve']->id)->exists())->toBeFalse();
});

test('un oral rate sans faute relevee reste une faiblesse orale, avec ce qui a ete dit', function () {
    $d = productionACorriger('speaking', 'speaking');
    $stt = Mockery::mock(DeepgramSttService::class);
    $stt->shouldReceive('transcribe')->andReturn('Ich weiß nicht.');
    app()->instance(DeepgramSttService::class, $stt);
    iaRendant([
        'isCorrect' => false, 'accuracy' => 20, 'covered_points' => [], 'missing_points' => ['le lieu', 'les activités'],
        'error_category' => 'speaking', 'error_subcategory' => 'completeness',
        'explanation' => ['concept' => 'Réponse trop courte.'], 'errors' => [], 'diagnostic' => [],
    ]);

    // Un vrai fichier : le faux de Laravel porte une ressource que la tentative
    // ne sait pas enregistrer, ce qu'un envoi reel ne fait pas.
    $chemin = tempnam(sys_get_temp_dir(), 'oral');
    file_put_contents($chemin, 'audio');

    $this->actingAs($d['eleve'])->post(route('exercise.submit_session', $d['node']), [
        'exercise_ids' => [$d['exercice']->id],
        'answers_by_exercise' => [$d['exercice']->id => ['q1' => new UploadedFile($chemin, 'reponse.webm', 'audio/webm', null, true)]],
        'time_spent' => 60,
    ])->assertRedirect();

    $erreur = UserError::where('user_id', $d['eleve']->id)->sole();

    // Elle etait rangee en compréhension écrite, avec pour reponse le chemin du
    // fichier temporaire de l'enregistrement.
    expect($erreur->error_category)->toBe('speaking')
        ->and($erreur->user_answer)->toBe('Ich weiß nicht.');
});

test('une correction identique au texte de l eleve est ecartee', function () {
    $propres = \App\Services\AI\CorrectionsDeProduction::nettoyer([
        ['original' => 'kannst du mir ein Hotel empfehlen?', 'corrected' => 'kannst du mir ein Hotel empfehlen?', 'category' => 'grammar.article', 'explanation' => 'correct'],
        ['original' => 'ein Frage', 'corrected' => 'eine Frage', 'category' => 'grammar.article', 'explanation' => 'Frage est feminin.'],
    ]);

    expect($propres)->toHaveCount(1)->and($propres[0]['corrected'])->toBe('eine Frage');
});
