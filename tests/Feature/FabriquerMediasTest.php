<?php

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Rien n'était préparé à l'avance : la voix était synthétisée au premier clic de
 * l'apprenant, et chaque illustration demandée à un service tiers au moment de
 * l'affichage. L'apprenant attendait, et un service indisponible lui donnait une
 * épreuve d'écoute muette ou une consigne « décrivez l'image » sans image.
 */
function exerciceAMedias(): Exercise
{
    $langue = Language::firstOrCreate(
        ['slug' => 'german'],
        ['name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']
    );
    $exam = Exam::firstOrCreate(['slug' => 'goethe-media'], ['language_id' => $langue->id, 'name' => 'Goethe-Zertifikat']);
    $section = ExamSection::firstOrCreate(
        ['exam_id' => $exam->id, 'slug' => 'hoeren'],
        ['name' => 'Hören', 'skill_type' => 'listening']
    );
    $type = ExerciseType::firstOrCreate(
        ['section_id' => $section->id, 'slug' => 'hoeren-mcq'],
        ['name' => 'Multiple Choice', 'skill_type' => 'listening', 'component_key' => 'mcq']
    );

    return Exercise::create([
        'exam_id' => $exam->id,
        'exercise_type_id' => $type->id,
        'difficulty' => 'B1',
        // Le passage parlé est rangé dans le contenu, comme souvent en base.
        'content' => ['passage' => 'Anna wohnt in Berlin und arbeitet in einem kleinen Café am Markt.'],
        'questions' => [[
            'id' => 'q1', 'type' => 'mcq', 'text' => 'Wo arbeitet Anna?',
            'options' => ['In einer Schule', 'In einem Café'], 'correct_answer' => 'B',
            'image_prompt' => 'a woman serving coffee in a small cafe',
        ]],
    ]);
}

beforeEach(function () {
    Storage::fake('public');
    config(['services.deepgram.api_key' => 'cle-de-test']);
    Http::fake([
        'api.deepgram.com/*' => Http::response(str_repeat('M', 4000), 200),
        'image.pollinations.ai/*' => Http::response(str_repeat('J', 9000), 200),
    ]);
});

test('le son et l image sont fabriques, ranges, et inscrits dans la question', function () {
    $exercice = exerciceAMedias();

    $this->artisan('prepla:fabriquer-medias')->assertSuccessful();

    $q = $exercice->refresh()->questions[0];

    // Inscrits dans la question : plus aucun appel a un tiers pendant l'epreuve.
    expect($q['audio_url'] ?? null)->toContain('tts/')
        ->and($q['image_url'] ?? null)->toContain('exercise-images/');

    // Et reellement poses sur le disque.
    $fichiers = Storage::disk('public')->allFiles();
    expect(collect($fichiers)->filter(fn ($f) => str_starts_with($f, 'tts/')))->toHaveCount(1)
        ->and(collect($fichiers)->filter(fn ($f) => str_starts_with($f, 'exercise-images/')))->toHaveCount(1);
});

test('l essai a blanc ne pose aucun fichier et ne touche pas la question', function () {
    $exercice = exerciceAMedias();

    $this->artisan('prepla:fabriquer-medias', ['--sec' => true])->assertSuccessful();

    expect(Storage::disk('public')->allFiles())->toBe([])
        ->and($exercice->refresh()->questions[0])->not->toHaveKey('audio_url');
});

test('ce qui est deja fabrique n est pas refait', function () {
    $exercice = exerciceAMedias();
    $this->artisan('prepla:fabriquer-medias')->assertSuccessful();
    $avant = Storage::disk('public')->allFiles();

    Http::fake(['*' => Http::response('', 500)]);   // un deuxieme appel echouerait
    $this->artisan('prepla:fabriquer-medias')->assertSuccessful();

    expect(Storage::disk('public')->allFiles())->toBe($avant)
        ->and($exercice->refresh()->questions[0]['audio_url'])->toContain('tts/');
});

test('un exercice sans son ni image est laisse tel quel', function () {
    $exercice = exerciceAMedias();
    $exercice->update([
        'content' => ['instructions' => 'Lis le texte.'],
        'questions' => [['id' => 'q1', 'type' => 'mcq', 'text' => 'Wo?', 'options' => ['A', 'B'], 'correct_answer' => 'A']],
    ]);

    $this->artisan('prepla:fabriquer-medias')->assertSuccessful();

    expect(Storage::disk('public')->allFiles())->toBe([])
        ->and($exercice->refresh()->questions[0])->not->toHaveKey('audio_url');
});
