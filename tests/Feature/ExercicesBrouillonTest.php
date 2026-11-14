<?php

use App\Http\Controllers\Center\MediaController;
use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Un exercice en brouillon reste dans le createur : ni devoir, ni parcours de
 * classe, ni examen blanc, et aucun eleve ne peut l'ouvrir.
 */
beforeEach(fn () => $this->withoutVite());

function brouillonCentre(): array
{
    $de = Language::create(['slug' => 'german', 'name' => 'Allemand', 'native_name' => 'Deutsch', 'flag' => 'de', 'is_active' => true]);
    $goethe = Exam::create(['language_id' => $de->id, 'slug' => 'goethe', 'name' => 'Goethe']);
    $section = ExamSection::create(['exam_id' => $goethe->id, 'slug' => 'hoeren', 'name' => 'Hören', 'skill_type' => 'listening']);
    $type = ExerciseType::create(['section_id' => $section->id, 'slug' => 'mcq', 'name' => 'Multiple Choice', 'skill_type' => 'listening', 'component_key' => 'mcq']);

    $centre = LanguageCenter::create([
        'name' => 'Centre Akwa', 'slug' => 'centre-'.uniqid(), 'owner_email' => 'akwa@exemple.test', 'is_active' => true, 'seats_limit' => 50,
    ]);
    $membre = function (string $role) use ($centre) {
        $u = User::factory()->create();
        UserProfile::factory()->for($u)->create(['onboarding_completed_at' => now()]);
        $centre->members()->attach($u->id, ['role' => $role, 'joined_at' => now()]);

        return $u;
    };
    $resp = $membre('center_admin');
    $eleve = $membre('student');
    $classe = $centre->classrooms()->create([
        'name' => 'B1 soir', 'level' => 'B1', 'exam_id' => $goethe->id, 'invite_code' => Classroom::generateInviteCode(),
    ]);
    $classe->members()->attach($eleve->id, ['role_in_class' => 'student']);

    $exercice = fn (string $statut) => Exercise::create([
        'exam_id' => $goethe->id, 'exercise_type_id' => $type->id, 'difficulty' => 'B1', 'center_id' => $centre->id,
        'creator_id' => $resp->id, 'content' => [], 'center_status' => $statut,
        'questions' => [['id' => 'q1', 'text' => 'Wo?', 'options' => ['A', 'B'], 'correct_answer' => 'A']],
    ]);

    return compact('goethe', 'centre', 'resp', 'eleve', 'classe', 'exercice');
}

it('refuse un brouillon dans un nouveau devoir, avec un message clair', function () {
    $c = brouillonCentre();
    $brouillon = ($c['exercice'])(Exercise::STATUT_BROUILLON);

    $this->actingAs($c['resp'])->post(route('center.assignments.store'), [
        'classroom_id' => $c['classe']->id, 'title' => 'Devoir', 'exercise_ids' => [$brouillon->id],
    ])->assertSessionHasErrors(['exercise_ids' => 'Un exercice choisi est encore en brouillon : validez-le avant de le donner à une classe.']);

    expect(Assignment::count())->toBe(0);
});

it('refuse un brouillon ajoute a un devoir existant, accepte un exercice valide', function () {
    $c = brouillonCentre();
    $brouillon = ($c['exercice'])(Exercise::STATUT_BROUILLON);
    $valide = ($c['exercice'])(Exercise::STATUT_VALIDE);
    $devoir = $c['classe']->assignments()->create(['created_by' => $c['resp']->id, 'title' => 'Existant', 'published_at' => now()]);

    $this->actingAs($c['resp'])->post(route('center.assignments.exercises.add', $devoir), ['exercise_ids' => [$brouillon->id]])
        ->assertSessionHasErrors('exercise_ids');
    expect($devoir->items()->count())->toBe(0);

    $this->post(route('center.assignments.exercises.add', $devoir), ['exercise_ids' => [$valide->id]])->assertSessionHasNoErrors();
    expect($devoir->items()->pluck('itemable_id')->all())->toBe([$valide->id]);
});

it('ne propose pas les brouillons dans la page de creation de devoir', function () {
    $c = brouillonCentre();
    ($c['exercice'])(Exercise::STATUT_BROUILLON);
    $valide = ($c['exercice'])(Exercise::STATUT_VALIDE);

    $this->actingAs($c['resp'])->get(route('center.assignments.create'))
        ->assertInertia(fn (Assert $page) => $page->has('exercises', 1)->where('exercises.0.id', $valide->id));
});

it('refuse un brouillon dans le parcours de classe et ne le propose pas', function () {
    $c = brouillonCentre();
    $brouillon = ($c['exercice'])(Exercise::STATUT_BROUILLON);

    $this->actingAs($c['resp'])->post(route('center.classes.parcours.store', $c['classe']), ['exercise_id' => $brouillon->id])
        ->assertSessionHasErrors('exercise_id');
    expect($c['classe']->pathSteps()->count())->toBe(0);
});

it('ne sert pas un brouillon aux eleves, meme deja place dans un devoir ou un parcours', function () {
    $c = brouillonCentre();
    $ex = ($c['exercice'])(Exercise::STATUT_VALIDE);
    $devoir = $c['classe']->assignments()->create(['created_by' => $c['resp']->id, 'title' => 'Publie', 'published_at' => now()->subMinute()]);
    $devoir->items()->create(['itemable_type' => Exercise::class, 'itemable_id' => $ex->id, 'sort_order' => 0]);
    $c['classe']->pathSteps()->create(['position' => 1, 'exercise_id' => $ex->id]);

    expect(Gate::forUser($c['eleve'])->allows('view', $ex))->toBeTrue();

    // Repasse en brouillon apres coup.
    $ex->update(['center_status' => Exercise::STATUT_BROUILLON]);
    $ex->refresh();

    expect(Gate::forUser($c['eleve'])->allows('view', $ex))->toBeFalse()
        ->and(Gate::forUser($c['resp'])->allows('view', $ex))->toBeTrue()
        ->and(app(\App\Services\Center\ParcoursDeClasse::class)->jouables($c['classe'], [$ex->id]))->toBe([]);

    $this->actingAs($c['eleve'])->get(route('devoirs.show', $devoir->id))
        ->assertInertia(fn (Assert $page) => $page->has('exercices', 0));
});

it('garde les brouillons visibles pour le personnel dans le createur', function () {
    $c = brouillonCentre();
    $brouillon = ($c['exercice'])(Exercise::STATUT_BROUILLON);

    $this->actingAs($c['resp'])->get(route('center.exercises.index'))
        ->assertInertia(fn (Assert $page) => $page->where('exercises.0.id', $brouillon->id)->where('exercises.0.statut', 'brouillon'));
});

it('le message de droits de la mediatheque est en UTF-8 correct', function () {
    Storage::fake('public');
    $c = brouillonCentre();

    $this->actingAs($c['resp'])->postJson(route('center.media.store'), [
        'type' => 'audio', 'audio' => UploadedFile::fake()->create('a.mp3', 100, 'audio/mpeg'),
        'source_rights' => 'Centre', 'rights_certified' => '0',
    ])->assertStatus(422)->assertJsonPath('errors.rights_certified.0',
        'Cochez la case : vous certifiez avoir les droits de diffuser ce fichier à vos élèves.');

    $source = file_get_contents(app_path('Http/Controllers/Center/MediaController.php'));
    expect($source)->not->toContain("\u{00C3}");
});

it('lit la limite reelle de PHP et la dit clairement', function () {
    expect(MediaController::enOctets('110M'))->toBe(110 * 1024 * 1024)
        ->and(MediaController::enOctets('2G'))->toBe(2 * 1024 ** 3)
        ->and(MediaController::enOctets('512K'))->toBe(512 * 1024)
        ->and(MediaController::enOctets('1000'))->toBe(1000);

    $limite = min(MediaController::enOctets(ini_get('upload_max_filesize')), MediaController::enOctets(ini_get('post_max_size')));
    expect(MediaController::limiteServeurOctets())->toBe($limite)
        ->and(MediaController::messageLimiteServeur())->toContain('dépasse la limite actuelle du serveur ('.intdiv($limite, 1048576).' Mo)');
});

it('explique un fichier refuse par PHP (limite upload_max_filesize)', function () {
    Storage::fake('public');
    $c = brouillonCentre();
    $trop = new UploadedFile(UploadedFile::fake()->create('x.mp3', 10)->getPathname(), 'gros.mp3', 'audio/mpeg', UPLOAD_ERR_INI_SIZE, true);

    $this->actingAs($c['resp'])->postJson(route('center.media.store'), [
        'type' => 'audio', 'audio' => $trop, 'source_rights' => 'Centre', 'rights_certified' => '1',
    ])->assertStatus(422)->assertJsonPath('errors.audio.0', MediaController::messageLimiteServeur());
});

it('explique un envoi plus gros que post_max_size', function () {
    $c = brouillonCentre();

    $this->actingAs($c['resp'])->call('POST', route('center.media.store'), [], [], [], [
        'CONTENT_LENGTH' => (string) (MediaController::enOctets(ini_get('post_max_size')) + 1),
        'HTTP_ACCEPT' => 'application/json',
    ])->assertStatus(413)->assertJsonPath('message', MediaController::messageLimiteServeur());
});
