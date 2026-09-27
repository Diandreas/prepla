<?php

use App\Models\DictionaryWord;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserWordProgress;
use App\Services\AI\MistralService;

beforeEach(function () {
    $this->learner = User::factory()->create();
    UserProfile::factory()->for($this->learner)->create(['onboarding_completed_at' => now()]);
    $this->actingAs($this->learner);
});

test('un mot ajoute depuis un exercice apparait dans le lexique', function () {
    $word = DictionaryWord::create([
        'word' => 'Bahnhof',
        'language' => 'de',
        'definition' => 'Ort, an dem Züge halten.',
        'translation' => 'gare',
        'skill_level' => 'A2',
    ]);

    $this->postJson('/dictionary/save', ['dictionary_word_id' => $word->id])
        ->assertOk()
        ->assertJson(['saved' => true, 'already_known' => false]);

    // C'est bien la table que lit « Mon Lexique » : l'ancien bouton écrivait
    // ailleurs, et le mot n'y apparaissait jamais.
    expect(UserWordProgress::where('user_id', $this->learner->id)
        ->where('dictionary_word_id', $word->id)
        ->exists())->toBeTrue();

    $this->get('/dictionary')
        ->assertOk()
        ->assertSee('Bahnhof');
});

test('ajouter deux fois le meme mot ne le duplique pas', function () {
    $word = DictionaryWord::create([
        'word' => 'Bahnhof',
        'language' => 'de',
        'definition' => 'Ort, an dem Züge halten.',
        'translation' => 'gare',
        'skill_level' => 'A2',
    ]);

    $this->postJson('/dictionary/save', ['dictionary_word_id' => $word->id])->assertOk();
    $this->postJson('/dictionary/save', ['dictionary_word_id' => $word->id])
        ->assertOk()
        ->assertJson(['already_known' => true]);

    expect(UserWordProgress::where('user_id', $this->learner->id)->count())->toBe(1);
});

test('un mot inconnu est refuse sans rien enregistrer', function () {
    $this->postJson('/dictionary/save', ['dictionary_word_id' => 4242])
        ->assertStatus(422);

    expect(UserWordProgress::count())->toBe(0);
});

test('la recherche depuis un exercice retrouve le mot range sous le code ISO', function () {
    // L'exercice identifie sa langue par slug ('german'), le dictionnaire par code
    // ISO ('de') : sans normalisation, la recherche ne trouvait rien et redemandait
    // une définition à l'IA à chaque fois.
    $word = DictionaryWord::create([
        'word' => 'Bahnhof',
        'language' => 'de',
        'definition' => 'Ort, an dem Züge halten.',
        'translation' => 'gare',
        'skill_level' => 'A2',
    ]);

    $this->mock(MistralService::class, fn ($mock) => $mock->shouldNotReceive('chat'));

    $this->getJson('/dictionary/lookup/german/Bahnhof')
        ->assertOk()
        ->assertJson(['id' => $word->id, 'language' => 'de']);
});

test('un mot defini par l IA est range sous le code ISO, pas sous le slug', function () {
    $this->mock(MistralService::class, function ($mock) {
        $mock->shouldReceive('chat')->once()->andReturn(json_encode([
            'word' => 'Fahrplan',
            'definition' => 'Liste der Abfahrtszeiten.',
            'example' => 'Der Fahrplan hängt am Bahnhof.',
            'translation' => 'horaire',
            'skill_level' => 'B1',
        ]));
    });

    $this->getJson('/dictionary/lookup/german/Fahrplan')
        ->assertOk()
        ->assertJson(['language' => 'de']);

    expect(DictionaryWord::where('word', 'Fahrplan')->value('language'))->toBe('de');
});
