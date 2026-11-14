<?php

use App\Models\CenterMedia;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Audios d'examen du centre (mediatheque : formats, 100 Mo, nom de piste,
 * source et droits certifies), nombre d'ecoutes par exercice, et banque de
 * sujets (statut brouillon / valide, copie d'un exercice du centre ou du
 * catalogue PrePla).
 */
beforeEach(function () {
    $this->withoutVite();
    Storage::fake('public');
});

function audiosCentre(): array
{
    $de = Language::create(['slug' => 'german', 'name' => 'Allemand', 'native_name' => 'Deutsch', 'flag' => 'de', 'is_active' => true]);
    $goethe = Exam::create(['language_id' => $de->id, 'slug' => 'goethe', 'name' => 'Goethe']);
    $autre = Exam::create(['language_id' => $de->id, 'slug' => 'telc', 'name' => 'telc']);
    $hoeren = ExamSection::create(['exam_id' => $goethe->id, 'slug' => 'hoeren', 'name' => 'Hören', 'skill_type' => 'listening']);
    $qcm = ExerciseType::create(['section_id' => $hoeren->id, 'slug' => 'mcq', 'name' => 'Multiple Choice', 'skill_type' => 'listening', 'component_key' => 'mcq']);

    $centre = LanguageCenter::create([
        'name' => 'Centre Akwa', 'slug' => 'centre-akwa', 'owner_email' => 'akwa@exemple.test', 'is_active' => true,
    ]);
    $centre->examensProposes()->sync([$goethe->id]);

    $resp = User::factory()->create();
    UserProfile::factory()->for($resp)->create(['onboarding_completed_at' => now()]);
    $centre->members()->attach($resp->id, ['role' => 'center_admin', 'joined_at' => now()]);

    return compact('goethe', 'autre', 'qcm', 'centre', 'resp');
}

function audiosQuestions(): array
{
    return [[
        'id' => '', 'text' => 'Wo ist Anna?', 'options' => ['Im Büro', 'Zu Hause', 'Im Kino'],
        'correct_answer' => 'A', 'explanation' => 'Sie arbeitet.',
    ]];
}

function envoiAudio(array $champs = []): array
{
    return array_merge([
        'type' => 'audio',
        'audio' => UploadedFile::fake()->create('piste-12.mp3', 3000, 'audio/mpeg'),
        'title' => 'TCF CO piste 12',
        'source_rights' => 'Enregistrement du centre',
        'rights_certified' => '1',
    ], $champs);
}

it('enregistre un audio avec son nom de piste, sa source et la certification des droits', function () {
    $c = audiosCentre();

    $this->actingAs($c['resp'])->post(route('center.media.store'), envoiAudio())
        ->assertOk()
        ->assertJsonPath('title', 'TCF CO piste 12')
        ->assertJsonPath('source_rights', 'Enregistrement du centre');

    $media = CenterMedia::sole();
    expect($media->title)->toBe('TCF CO piste 12')
        ->and($media->source_rights)->toBe('Enregistrement du centre')
        ->and($media->rights_certified_at)->not->toBeNull();
    Storage::disk('public')->assertExists($media->path);
});

it('refuse un audio sans source ou sans la case de certification', function () {
    $c = audiosCentre();

    $this->actingAs($c['resp'])->postJson(route('center.media.store'), envoiAudio(['source_rights' => '']))
        ->assertStatus(422)->assertJsonValidationErrors('source_rights');
    $this->actingAs($c['resp'])->postJson(route('center.media.store'), envoiAudio(['rights_certified' => '0']))
        ->assertStatus(422)->assertJsonValidationErrors('rights_certified');

    expect(CenterMedia::count())->toBe(0);
});

it('accepte un audio de 90 Mo et refuse au-dela de 100 Mo', function () {
    $c = audiosCentre();

    $this->actingAs($c['resp'])->postJson(route('center.media.store'), envoiAudio([
        'audio' => UploadedFile::fake()->create('long.mp3', 90 * 1024, 'audio/mpeg'),
    ]))->assertOk();

    $this->actingAs($c['resp'])->postJson(route('center.media.store'), envoiAudio([
        'audio' => UploadedFile::fake()->create('trop-long.mp3', 101 * 1024, 'audio/mpeg'),
    ]))->assertStatus(422)->assertJsonValidationErrors('audio');
});

it('accepte les formats audio m4a, aac, wav, ogg et webm', function (string $nom, string $mime) {
    $c = audiosCentre();

    $this->actingAs($c['resp'])->postJson(route('center.media.store'), envoiAudio([
        'audio' => UploadedFile::fake()->create($nom, 500, $mime),
    ]))->assertOk();
})->with([
    ['piste.m4a', 'audio/mp4'],
    ['piste.aac', 'audio/aac'],
    ['piste.wav', 'audio/wav'],
    ['piste.ogg', 'audio/ogg'],
    ['piste.webm', 'audio/webm'],
]);

it('renomme une piste et corrige sa source, seulement dans son centre', function () {
    $c = audiosCentre();
    $this->actingAs($c['resp'])->post(route('center.media.store'), envoiAudio())->assertOk();
    $media = CenterMedia::sole();

    $this->actingAs($c['resp'])->patchJson(route('center.media.update', $media), [
        'title' => 'Goethe B1 Hören Teil 2', 'source_rights' => 'Sujet sous licence',
    ])->assertOk()->assertJsonPath('title', 'Goethe B1 Hören Teil 2');
    expect($media->fresh()->source_rights)->toBe('Sujet sous licence');

    $this->actingAs($c['resp'])->patchJson(route('center.media.update', $media), [
        'title' => 'Sans source', 'source_rights' => '',
    ])->assertStatus(422);

    $autre = LanguageCenter::create(['name' => 'Autre', 'slug' => 'autre', 'owner_email' => 'autre@exemple.test', 'is_active' => true]);
    $intrus = User::factory()->create();
    UserProfile::factory()->for($intrus)->create(['onboarding_completed_at' => now()]);
    $autre->members()->attach($intrus->id, ['role' => 'center_admin', 'joined_at' => now()]);
    $this->actingAs($intrus)->patchJson(route('center.media.update', $media), ['title' => 'Vol', 'source_rights' => 'x'])
        ->assertForbidden();
});

it('affiche la page de la mediatheque avec les audios du centre', function () {
    $c = audiosCentre();
    $this->actingAs($c['resp'])->post(route('center.media.store'), envoiAudio())->assertOk();

    $this->actingAs($c['resp'])->get(route('center.media.page'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('center/media')
            ->has('media', 1)
            ->where('media.0.title', 'TCF CO piste 12')
            ->where('limites.audio_mo', 100));
});

it('garde le nombre d\'ecoutes choisi pour l\'audio du centre', function () {
    $c = audiosCentre();
    $fiche = $this->actingAs($c['resp'])->post(route('center.media.store'), envoiAudio())->json();

    $base = [
        'exercise_type_id' => $c['qcm']->id, 'exam_id' => $c['goethe']->id, 'difficulty' => 'B1',
        'questions' => audiosQuestions(),
    ];

    $this->actingAs($c['resp'])->post(route('center.exercises.store'), $base + [
        'content' => ['audio_url' => $fiche['url'], 'max_ecoutes' => 1],
    ])->assertRedirect();
    expect(Exercise::sole()->content['max_ecoutes'])->toBe(1);

    $this->actingAs($c['resp'])->post(route('center.exercises.store'), $base + [
        'content' => ['audio_url' => $fiche['url'], 'max_ecoutes' => 5],
    ])->assertSessionHasErrors('content.max_ecoutes');
});

it('passe un exercice en brouillon puis le valide, et filtre la liste par statut', function () {
    $c = audiosCentre();
    $ex = Exercise::create([
        'exercise_type_id' => $c['qcm']->id, 'exam_id' => $c['goethe']->id, 'center_id' => $c['centre']->id,
        'creator_id' => $c['resp']->id, 'content' => [], 'questions' => audiosQuestions(), 'difficulty' => 'B1',
        'center_status' => 'valide',
    ]);

    $this->actingAs($c['resp'])->patch(route('center.exercises.statut', $ex), ['statut' => 'brouillon'])->assertRedirect();
    expect($ex->fresh()->center_status)->toBe('brouillon');

    $this->actingAs($c['resp'])->get(route('center.exercises.index'))
        ->assertInertia(fn (Assert $page) => $page->where('exercises.0.statut', 'brouillon')
            ->where('exercises.0.exercise_type_id', $c['qcm']->id));

    $this->actingAs($c['resp'])->patch(route('center.exercises.statut', $ex), ['statut' => 'publie'])->assertSessionHasErrors('statut');
});

it('un exercice cree a la main est valide par defaut', function () {
    $c = audiosCentre();
    $this->actingAs($c['resp'])->post(route('center.exercises.store'), [
        'exercise_type_id' => $c['qcm']->id, 'exam_id' => $c['goethe']->id, 'difficulty' => 'B1',
        'content' => [], 'questions' => audiosQuestions(),
    ])->assertRedirect();

    expect(Exercise::sole()->center_status)->toBe('valide');
});

it('duplique un exercice du centre en brouillon', function () {
    $c = audiosCentre();
    $ex = Exercise::create([
        'exercise_type_id' => $c['qcm']->id, 'exam_id' => $c['goethe']->id, 'center_id' => $c['centre']->id,
        'creator_id' => $c['resp']->id, 'content' => ['title' => 'Annonce gare'], 'questions' => audiosQuestions(),
        'difficulty' => 'B1', 'center_status' => 'valide',
    ]);

    $this->actingAs($c['resp'])->post(route('center.exercises.dupliquer', $ex))->assertRedirect();

    $copie = Exercise::where('id', '!=', $ex->id)->sole();
    expect($copie->center_id)->toBe($c['centre']->id)
        ->and($copie->center_status)->toBe('brouillon')
        ->and($copie->content['title'])->toBe('Annonce gare (copie)')
        ->and($copie->questions)->toBe($ex->questions)
        ->and($ex->fresh()->content['title'])->toBe('Annonce gare');
});

it('copie un exercice du catalogue PrePla et garde son son d\'origine a l\'enregistrement', function () {
    $c = audiosCentre();
    $catalogue = Exercise::create([
        'exercise_type_id' => $c['qcm']->id, 'exam_id' => $c['goethe']->id,
        'content' => ['audio_url' => '/storage/audio/prepla-hoeren-1.mp3'], 'questions' => audiosQuestions(), 'difficulty' => 'B1',
    ]);

    $this->actingAs($c['resp'])->getJson(route('center.exercises.catalogue', ['exam_id' => $c['goethe']->id]))
        ->assertOk()->assertJsonCount(1, 'exercises')->assertJsonPath('exercises.0.source', 'prepla');

    $this->actingAs($c['resp'])->post(route('center.exercises.dupliquer', $catalogue))->assertRedirect();
    $copie = Exercise::where('center_id', $c['centre']->id)->sole();
    expect($copie->copied_from_id)->toBe($catalogue->id)->and($copie->center_status)->toBe('brouillon');

    // L'adaptation garde le son du catalogue : il n'est pas dans la mediatheque, mais il est permis.
    $this->actingAs($c['resp'])->patch(route('center.exercises.update', $copie), [
        'exercise_type_id' => $c['qcm']->id, 'exam_id' => $c['goethe']->id, 'difficulty' => 'A2',
        'content' => ['audio_url' => '/storage/audio/prepla-hoeren-1.mp3'], 'questions' => audiosQuestions(),
    ])->assertRedirect(route('center.exercises.index'));
    expect($copie->fresh()->difficulty)->toBe('A2')->and($copie->fresh()->center_status)->toBe('brouillon');
});

it('refuse de copier un exercice d\'un autre centre ou d\'un examen non propose', function () {
    $c = audiosCentre();
    $autre = LanguageCenter::create(['name' => 'Autre', 'slug' => 'autre2', 'owner_email' => 'b@exemple.test', 'is_active' => true]);
    $prive = Exercise::create([
        'exercise_type_id' => $c['qcm']->id, 'exam_id' => $c['goethe']->id, 'center_id' => $autre->id,
        'content' => [], 'questions' => audiosQuestions(), 'difficulty' => 'B1',
    ]);
    $horsOffre = Exercise::create([
        'exercise_type_id' => $c['qcm']->id, 'exam_id' => $c['autre']->id,
        'content' => [], 'questions' => audiosQuestions(), 'difficulty' => 'B1',
    ]);

    $this->actingAs($c['resp'])->post(route('center.exercises.dupliquer', $prive))->assertForbidden();
    $this->actingAs($c['resp'])->post(route('center.exercises.dupliquer', $horsOffre))->assertForbidden();
    $this->actingAs($c['resp'])->getJson(route('center.exercises.catalogue', ['exam_id' => $c['autre']->id]))->assertStatus(422);
    expect(Exercise::where('center_id', $c['centre']->id)->count())->toBe(0);
});
