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
use App\Services\AI\MistralEvaluationService;
use App\Services\AI\MistralService;
use App\Services\AI\WritingCorrectorService;
use App\Services\Content\ErreursDeRedaction;

/**
 * C'est a l'ecrit et a l'oral longs que les faiblesses profondes se revelent :
 * un QCM dit qu'une reponse est fausse, une redaction dit POURQUOI.
 *
 * Or la correction d'une redaction d'exercice etait affichee puis oubliee : ses
 * fautes n'entraient jamais au dossier de l'apprenant, et une copie ne laissait
 * au mieux qu'une seule erreur vague. A l'oral, l'IA ne rendait qu'une etiquette.
 * Et le centre ne voyait que des comptes, en anglais, sans aucune faute a lire.
 */
beforeEach(fn () => $this->withoutVite());

function apprenantEtProduction(string $competence, string $typeQuestion): array
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
        'questions' => [['id' => 'q1', 'type' => $typeQuestion, 'text' => 'Schreiben Sie über Ihren Urlaub.']],
    ]);

    $eleve = User::factory()->create();
    UserProfile::factory()->for($eleve)->create([
        'target_exam_id' => $exam->id, 'current_level' => 'B1', 'onboarding_completed_at' => now(),
    ]);

    return compact('exam', 'exercice', 'node', 'eleve');
}

function correctionDeRedactionReussie(): array
{
    return [
        // 7,5 / 9 : la redaction est VALIDEE.
        'score' => 7.5,
        'band_scores' => ['task_achievement' => 8, 'coherence_cohesion' => 7, 'lexical_resource' => 6, 'grammar_accuracy' => 7],
        'corrections' => [
            ['original' => 'weil ich habe Zeit', 'corrected' => 'weil ich Zeit habe', 'category' => 'grammar.word-order', 'explanation' => 'Après « weil », le verbe conjugué va à la fin.'],
            ['original' => 'gut', 'corrected' => 'erholsam', 'category' => 'vocabulary.range', 'explanation' => '« gut » revient trop souvent.'],
        ],
        'diagnostic' => [
            ['dimension' => 'lexical_range', 'observation' => '« gut » revient 5 fois, sans aucun synonyme.', 'severity' => 'major'],
            ['dimension' => 'invente', 'observation' => 'Une dimension inconnue, a ecarter.', 'severity' => 'minor'],
        ],
        'feedback' => 'Bon récit, vocabulaire répétitif.',
    ];
}

test('une redaction validee laisse quand meme ses fautes au dossier, avec leur sous-categorie', function () {
    $d = apprenantEtProduction('writing', 'essay');
    app()->instance(WritingCorrectorService::class, Mockery::mock(WritingCorrectorService::class, fn ($m) => $m->shouldReceive('correct')->andReturn(correctionDeRedactionReussie())));

    $this->actingAs($d['eleve'])->post(route('exercise.submit_session', $d['node']), [
        'exercise_ids' => [$d['exercice']->id],
        'answers_by_exercise' => [$d['exercice']->id => ['q1' => 'Mein Urlaub war gut, weil ich habe Zeit.']],
        'time_spent' => 300,
    ])->assertRedirect();

    $erreurs = UserError::where('user_id', $d['eleve']->id)->orderBy('id')->get();

    expect($erreurs)->toHaveCount(2)
        ->and($erreurs[0]->error_category)->toBe('grammar')
        ->and($erreurs[0]->subcategory)->toBe('word-order')
        ->and($erreurs[0]->user_answer)->toBe('weil ich habe Zeit')
        ->and($erreurs[0]->skill_type)->toBe('writing')
        ->and($erreurs[1]->error_category)->toBe('vocabulary')
        ->and($erreurs[1]->subcategory)->toBe('range');

    // Le profil est garde avec la copie, nettoye de ce qui n'est pas exploitable.
    $retour = UserExerciseAttempt::where('user_id', $d['eleve']->id)->sole()->feedback[0];
    expect($retour['diagnostic'])->toHaveCount(1)
        ->and($retour['diagnostic'][0]['dimension'])->toBe('lexical_range');
});

/**
 * Le chemin de l'entrainement et des epreuves n'enregistrait AUCUNE faiblesse :
 * une redaction corrigee la etait affichee puis oubliee.
 */
test('une redaction faite hors parcours entre aussi au dossier', function () {
    $d = apprenantEtProduction('writing', 'essay');
    $d['exercice']->update(['node_id' => null]);
    app()->instance(WritingCorrectorService::class, Mockery::mock(WritingCorrectorService::class, fn ($m) => $m->shouldReceive('correct')->andReturn(correctionDeRedactionReussie())));

    $this->actingAs($d['eleve'])->post(route('exercise.submit', $d['exercice']), [
        'answers' => ['q1' => 'Mein Urlaub war gut, weil ich habe Zeit.'],
        'time_spent' => 300,
    ])->assertRedirect();

    expect(UserError::where('user_id', $d['eleve']->id)->where('subcategory', 'word-order')->exists())->toBeTrue();
});

test('une prise de parole laisse ses fautes au dossier, comme une faiblesse orale', function () {
    $d = apprenantEtProduction('speaking', 'speaking');
    app()->instance(MistralEvaluationService::class, Mockery::mock(MistralEvaluationService::class, fn ($m) => $m->shouldReceive('evaluateSpeaking')->andReturn([
        'isCorrect' => true, 'accuracy' => 70,
        'covered_points' => ['le lieu'], 'missing_points' => ['les activités'],
        'error_category' => null, 'error_subcategory' => null, 'model_answer' => null,
        'explanation' => ['concept' => 'Gut gemacht.', 'french_translation' => ['concept' => 'Bien joué.', 'hint' => 'Ajoute un connecteur.']],
        'corrections' => [
            ['original' => 'ich bin gegangen in Berlin', 'corrected' => 'ich bin nach Berlin gefahren', 'category' => 'grammar.preposition', 'explanation' => 'On va « nach » une ville.'],
        ],
        'diagnostic' => [['dimension' => 'fluency', 'observation' => 'Trois phrases abandonnées en cours de route.', 'severity' => 'minor']],
    ])));

    $this->actingAs($d['eleve'])->post(route('exercise.submit_session', $d['node']), [
        'exercise_ids' => [$d['exercice']->id],
        'answers_by_exercise' => [$d['exercice']->id => ['q1' => 'Ich bin gegangen in Berlin, äh, mit Freunden.']],
        'time_spent' => 120,
    ])->assertRedirect();

    $erreur = UserError::where('user_id', $d['eleve']->id)->sole();

    expect($erreur->skill_type)->toBe('speaking')
        ->and($erreur->error_category)->toBe('grammar')
        ->and($erreur->subcategory)->toBe('preposition')
        ->and($erreur->question_id)->toStartWith('oral-');
});

/**
 * L'IA peut rendre n'importe quoi : on ne garde des fautes orales que celles qui
 * ont un avant et un apres, cinq au plus ; et du profil, que les dimensions
 * connues. Et le diagnostic n'arrive que s'il est demande, sans changer la note.
 */
test('la lecture de l evaluation orale ne garde que l exploitable', function () {
    $fautes = [];
    for ($i = 1; $i <= 7; $i++) {
        $fautes[] = ['original' => "faute $i", 'corrected' => "juste $i", 'category' => 'grammar.case', 'explanation' => 'x'];
    }
    $fautes[] = ['original' => '', 'corrected' => 'sans avant'];

    $mistral = Mockery::mock(MistralService::class);
    $mistral->shouldReceive('chat')->andReturn(json_encode([
        'isCorrect' => true, 'accuracy' => 65, 'covered_points' => [], 'missing_points' => [],
        'error_category' => null, 'explanation' => ['concept' => 'ok'],
        'errors' => $fautes,
        'diagnostic' => [
            ['dimension' => 'syntactic_complexity', 'observation' => 'Aucune subordonnée.', 'severity' => 'major'],
            ['dimension' => 'pronunciation', 'observation' => 'Accent marqué.', 'severity' => 'major'],
        ],
    ]));

    $resultat = (new MistralEvaluationService($mistral))->evaluateSpeaking('Erzählen Sie.', 'Ich bin müde.', 'German');

    expect($resultat['accuracy'])->toBe(65)
        ->and($resultat['corrections'])->toHaveCount(5)
        ->and($resultat['corrections'][0])->toBe(['original' => 'faute 1', 'corrected' => 'juste 1', 'category' => 'grammar.case', 'explanation' => 'x'])
        // La prononciation ne se lit pas dans une transcription : ecartee.
        ->and($resultat['diagnostic'])->toHaveCount(1)
        ->and($resultat['diagnostic'][0]['dimension'])->toBe('syntactic_complexity');
});

test('une etiquette orale ou inconnue est ramenee a sa famille a l ecrit', function () {
    $eleve = User::factory()->create();

    app(ErreursDeRedaction::class)->memoriser($eleve, [
        ['original' => 'a', 'corrected' => 'b', 'category' => 'speaking.fluency'],
        ['original' => 'c', 'corrected' => 'd', 'category' => 'grammar.inventee'],
    ], 'a c', 'writing');

    $erreurs = UserError::where('user_id', $eleve->id)->orderBy('id')->get();

    expect($erreurs[0]->error_category)->toBe('writing')
        ->and($erreurs[0]->subcategory)->toBeNull()
        ->and($erreurs[1]->error_category)->toBe('grammar')
        ->and($erreurs[1]->subcategory)->toBeNull();
});

/** Le centre voit les fautes elles-memes, en francais, et le profil de l'apprenant. */
test('le centre voit chaque faiblesse avec ses fautes, et le profil de l apprenant', function () {
    $d = apprenantEtProduction('writing', 'essay');
    app()->instance(WritingCorrectorService::class, Mockery::mock(WritingCorrectorService::class, fn ($m) => $m->shouldReceive('correct')->andReturn(correctionDeRedactionReussie())));

    $this->actingAs($d['eleve'])->post(route('exercise.submit_session', $d['node']), [
        'exercise_ids' => [$d['exercice']->id],
        'answers_by_exercise' => [$d['exercice']->id => ['q1' => 'Mein Urlaub war gut, weil ich habe Zeit.']],
        'time_spent' => 300,
    ]);

    $centre = LanguageCenter::create(['name' => 'Centre', 'slug' => 'centre', 'owner_email' => 'c@exemple.test', 'is_active' => true]);
    $resp = User::factory()->create();
    UserProfile::factory()->for($resp)->create(['onboarding_completed_at' => now()]);
    $centre->members()->attach($resp->id, ['role' => 'center_admin', 'joined_at' => now()]);
    $centre->members()->attach($d['eleve']->id, ['role' => 'student', 'joined_at' => now()]);
    $classe = $centre->classrooms()->create(['name' => 'B1', 'level' => 'B1', 'exam_id' => $d['exam']->id, 'invite_code' => Classroom::generateInviteCode()]);
    $classe->members()->attach($d['eleve']->id, ['role_in_class' => 'student']);

    $this->actingAs($resp)
        ->get(route('center.students.show', $d['eleve']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('faiblesses.0.libelle', fn ($l) => in_array($l, ['Grammaire · ordre des mots', 'Vocabulaire · étendue (répétitions)'], true))
            ->has('faiblesses', 2)
            ->where('profil.0.dimension', 'Étendue du vocabulaire')
            ->where('profil.0.observation', '« gut » revient 5 fois, sans aucun synonyme.'));

    $tentative = UserExerciseAttempt::where('user_id', $d['eleve']->id)->sole();

    $this->actingAs($resp)
        ->get(route('center.students.attempt', ['user' => $d['eleve'], 'attempt' => $tentative]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('tentative.questions.0.production.corrections.0.avant', 'weil ich habe Zeit')
            ->where('tentative.questions.0.production.corrections.0.apres', 'weil ich Zeit habe')
            ->where('tentative.questions.0.production.corrections.0.categorie', 'Grammaire · ordre des mots')
            ->where('tentative.questions.0.production.diagnostic.0.dimension', 'Étendue du vocabulaire')
            ->where('tentative.questions.0.production.criteres.0.critere', 'Réalisation de la tâche'));
});

/**
 * A l'oral, l'explication est un OBJET — concept, exemple, conseil, traduction.
 * La page l'aurait affiche brut : on le lit en francais.
 */
test('l explication orale se lit en francais sur la copie', function () {
    $d = apprenantEtProduction('speaking', 'speaking');
    $tentative = UserExerciseAttempt::create([
        'user_id' => $d['eleve']->id, 'exercise_id' => $d['exercice']->id,
        'answers' => ['q1' => 'audio'], 'score' => 1, 'accuracy_percent' => 70, 'time_spent' => 60, 'xp_earned' => 5,
        'feedback' => [[
            'question_id' => 'q1', 'correct' => true, 'accuracy' => 70,
            'explanation' => ['concept' => 'Gut.', 'french_translation' => ['concept' => 'Bien.', 'hint' => 'Ajoute « weil ».']],
            'transcription' => 'Ich bin müde.',
        ]],
    ]);

    $detail = app(\App\Services\Center\SuiviApprenant::class)->detail($tentative);

    expect($detail['questions'][0]['explication'])->toBe('Bien. Ajoute « weil ».')
        ->and($detail['questions'][0]['production']['transcription'])->toBe('Ich bin müde.');
});
