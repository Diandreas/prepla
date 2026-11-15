<?php

use App\Models\CenterUser;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\Language;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Models\UserProfile;
use App\Notifications\InvitationEleveNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

/**
 * Import en masse cote centre : eleves (CSV) et QCM (CSV / JSON), apercu sans
 * ecriture, erreurs ligne par ligne, doublons ignores, invitations, limites,
 * et isolation entre centres.
 */
beforeEach(function () {
    $this->withoutVite();
    Notification::fake();
});

function importCentre(string $slug = 'centre-a', int $places = 50): array
{
    $centre = LanguageCenter::create([
        'name' => "Centre {$slug}", 'slug' => $slug, 'owner_email' => "{$slug}@exemple.test",
        'is_active' => true, 'seats_limit' => $places,
    ]);
    $resp = User::factory()->create();
    UserProfile::factory()->for($resp)->create(['onboarding_completed_at' => now()]);
    $centre->members()->attach($resp->id, ['role' => 'center_admin', 'joined_at' => now()]);
    $classe = $centre->classrooms()->create(['name' => 'B1 du soir', 'invite_code' => Classroom::generateInviteCode()]);

    return compact('centre', 'resp', 'classe');
}

function importExamen(LanguageCenter $centre): Exam
{
    $fr = Language::firstOrCreate(['slug' => 'french'], ['name' => 'Français', 'native_name' => 'Français', 'flag' => 'fr', 'is_active' => true]);
    $delf = Exam::firstOrCreate(['slug' => 'delf'], ['language_id' => $fr->id, 'name' => 'DELF']);
    if (! ExerciseType::where('component_key', 'mcq')->exists()) {
        $ce = ExamSection::create(['exam_id' => $delf->id, 'slug' => 'ce', 'name' => 'CE', 'skill_type' => 'reading']);
        ExerciseType::create(['section_id' => $ce->id, 'slug' => 'mcq', 'name' => 'QCM', 'skill_type' => 'reading', 'component_key' => 'mcq']);
    }
    $centre->examensProposes()->syncWithoutDetaching([$delf->id]);

    return $delf;
}

function csvEleves(string ...$lignes): UploadedFile
{
    return UploadedFile::fake()->createWithContent('eleves.csv', implode("\n", ["nom;email;telephone;classe", ...$lignes]));
}

it('montre un apercu ligne par ligne sans rien enregistrer', function () {
    $c = importCentre();

    $res = $this->actingAs($c['resp'])->post(route('center.import.eleves.apercu'), ['fichier' => csvEleves(
        'Awa Diallo;awa@exemple.com;+33 6 12 34 56 78;B1 du soir',
        ';pas-un-email;;Classe fantome',
        'Awa bis;AWA@exemple.com;;b1 du soir',
    )])->assertOk();

    expect($res->json('lignes.0.statut'))->toBe('nouveau')
        ->and($res->json('lignes.1.statut'))->toBe('erreur')
        ->and($res->json('lignes.1.ligne'))->toBe(3)
        ->and($res->json('lignes.1.erreurs'))->toHaveCount(3)
        ->and($res->json('lignes.2.statut'))->toBe('doublon');

    expect(User::where('email', 'awa@exemple.com')->exists())->toBeFalse();
    Notification::assertNothingSent();
});

it('importe les eleves valides, ignore les doublons et envoie une invitation', function () {
    $c = importCentre();
    $deja = User::factory()->create(['email' => 'deja@exemple.com']);
    CenterUser::create(['center_id' => $c['centre']->id, 'user_id' => $deja->id, 'role' => 'student', 'joined_at' => now()]);
    $c['classe']->members()->attach($deja->id, ['role_in_class' => 'student']);
    $libre = User::factory()->create(['email' => 'libre@exemple.com']);

    $res = $this->actingAs($c['resp'])->post(route('center.import.eleves'), ['fichier' => csvEleves(
        'Awa Diallo;awa@exemple.com;+33 6 12 34 56 78;B1 du soir',
        'Deja la;deja@exemple.com;;B1 du soir',
        'Libre;libre@exemple.com;;B1 du soir',
        'Sans classe;x@exemple.com;;Inconnue',
    )])->assertOk();

    expect($res->json('bilan'))->toBe(['crees' => 1, 'rattaches' => 1, 'ignores' => 1, 'erreurs' => 1]);

    $awa = User::where('email', 'awa@exemple.com')->sole();
    expect($awa->phone)->toBe('+33 6 12 34 56 78')
        ->and($c['classe']->students()->pluck('users.id')->sort()->values()->all())
        ->toEqual(collect([$deja->id, $libre->id, $awa->id])->sort()->values()->all());

    // Compte cree : le lien choisit le mot de passe, avec un jeton valable.
    Notification::assertSentTo($awa, InvitationEleveNotification::class, function ($n) use ($awa) {
        $jeton = urldecode(explode('?', basename($n->lien))[0]);

        return $n->nouveauCompte && Password::broker()->tokenExists($awa, $jeton);
    });
    Notification::assertSentTo($libre, InvitationEleveNotification::class, fn ($n) => ! $n->nouveauCompte);
    Notification::assertNotSentTo($deja, InvitationEleveNotification::class);
});

it("refuse un compte d'un autre centre ou de l'equipe, et une classe d'un autre centre", function () {
    $a = importCentre('centre-a');
    $b = importCentre('centre-b');
    $b['centre']->classrooms()->create(['name' => 'Classe B', 'invite_code' => Classroom::generateInviteCode()]);
    $eleveB = User::factory()->create(['email' => 'eleve-b@exemple.com']);
    CenterUser::create(['center_id' => $b['centre']->id, 'user_id' => $eleveB->id, 'role' => 'student', 'joined_at' => now()]);

    $res = $this->actingAs($a['resp'])->post(route('center.import.eleves'), ['fichier' => csvEleves(
        'Eleve B;eleve-b@exemple.com;;B1 du soir',
        "Resp;{$a['resp']->email};;B1 du soir",
        'Intrus;intrus@exemple.com;;Classe B',
    )])->assertOk();

    expect($res->json('bilan.erreurs'))->toBe(3)
        ->and(collect($res->json('lignes'))->pluck('statut')->unique()->all())->toBe(['erreur'])
        ->and(CenterUser::where('user_id', $eleveB->id)->pluck('center_id')->all())->toBe([$b['centre']->id])
        ->and(User::where('email', 'intrus@exemple.com')->exists())->toBeFalse();
});

it("n'inscrit pas au-dela des licences du centre", function () {
    $c = importCentre(places: 1);

    $res = $this->actingAs($c['resp'])->post(route('center.import.eleves'), ['fichier' => csvEleves(
        'Un;un@exemple.com;;B1 du soir',
        'Deux;deux@exemple.com;;B1 du soir',
    )])->assertOk();

    expect($res->json('bilan.crees'))->toBe(1)
        ->and($res->json('lignes.1.statut'))->toBe('erreur');
});

it("limite un enseignant aux classes qu'il encadre", function () {
    $c = importCentre();
    $prof = User::factory()->create();
    $c['centre']->members()->attach($prof->id, ['role' => 'teacher', 'joined_at' => now()]);
    $sienne = $c['centre']->classrooms()->create(['name' => 'A2 matin', 'invite_code' => Classroom::generateInviteCode()]);
    $sienne->members()->attach($prof->id, ['role_in_class' => 'teacher']);

    $res = $this->actingAs($prof)->post(route('center.import.eleves.apercu'), ['fichier' => csvEleves(
        'Un;un@exemple.com;;A2 matin',
        'Deux;deux@exemple.com;;B1 du soir',
    )])->assertOk();

    expect($res->json('lignes.0.statut'))->toBe('nouveau')
        ->and($res->json('lignes.1.statut'))->toBe('erreur');
});

it('refuse un fichier trop gros, trop long ou du mauvais format', function () {
    $c = importCentre();

    $this->actingAs($c['resp'])->postJson(route('center.import.eleves.apercu'), [
        'fichier' => UploadedFile::fake()->create('eleves.csv', 600, 'text/csv'),
    ])->assertJsonValidationErrors('fichier');

    $this->actingAs($c['resp'])->postJson(route('center.import.eleves.apercu'), [
        'fichier' => UploadedFile::fake()->createWithContent('eleves.pdf', 'nom;email'),
    ])->assertJsonValidationErrors('fichier');

    $trop = array_map(fn ($i) => "E{$i};e{$i}@exemple.com;;B1 du soir", range(1, 501));
    $this->actingAs($c['resp'])->postJson(route('center.import.eleves'), ['fichier' => csvEleves(...$trop)])
        ->assertJsonValidationErrors('fichier');
    expect(User::where('email', 'e1@exemple.com')->exists())->toBeFalse();
});

it('fournit les modeles CSV', function () {
    $c = importCentre();
    importExamen($c['centre']);

    $eleves = $this->actingAs($c['resp'])->get(route('center.import.eleves.modele'))->assertOk()->streamedContent();
    expect($eleves)->toContain('nom;email;telephone;classe');

    $qcm = $this->actingAs($c['resp'])->get(route('center.import.exercices.modele'))->assertOk()->streamedContent();
    expect($qcm)->toContain('question;options;bonne_reponse;competence;niveau;examen')->toContain(';delf;');
});

it('importe des QCM en brouillon depuis un CSV, ligne par ligne', function () {
    $c = importCentre();
    $delf = importExamen($c['centre']);

    $csv = implode("\n", [
        'question,options,bonne_reponse,competence,niveau,examen,explication',
        '"Où est Anna ?","Au bureau | À la maison | Au cinéma",B,lecture,a2,delf,Elle se repose.',
        '"Quel temps ?","Pluie | Soleil",Soleil,reading,B1,DELF,',
        '"Sans réponse","Un | Deux",Trois,reading,B1,delf,',
        '"Niveau faux","Un | Deux",A,reading,Z9,delf,',
        '"Écoute","Un | Deux",A,listening,A1,delf,',
    ]);

    $this->actingAs($c['resp'])->post(route('center.import.exercices.apercu'), [
        'fichier' => UploadedFile::fake()->createWithContent('qcm.csv', $csv),
    ])->assertOk()->assertJsonPath('resume', ['valide' => 2, 'erreur' => 3]);
    expect(Exercise::count())->toBe(0);

    $res = $this->actingAs($c['resp'])->post(route('center.import.exercices'), [
        'fichier' => UploadedFile::fake()->createWithContent('qcm.csv', $csv),
    ])->assertOk();

    expect($res->json('bilan'))->toBe(['crees' => 2, 'erreurs' => 3]);
    $premier = Exercise::orderBy('id')->first();
    expect($premier->center_id)->toBe($c['centre']->id)
        ->and($premier->center_status)->toBe(Exercise::STATUT_BROUILLON)
        ->and($premier->exam_id)->toBe($delf->id)
        ->and($premier->difficulty)->toBe('A2')
        ->and($premier->questions[0]['correct_answer'])->toBe('B')
        ->and($premier->questions[0]['options'])->toBe(['Au bureau', 'À la maison', 'Au cinéma'])
        ->and(Exercise::orderByDesc('id')->first()->questions[0]['correct_answer'])->toBe('B');
});

it('importe des QCM depuis un JSON et refuse un JSON mal forme', function () {
    $c = importCentre();
    importExamen($c['centre']);

    $json = json_encode([
        ['question' => 'Q1', 'options' => ['Oui', 'Non'], 'bonne_reponse' => 'A', 'competence' => 'reading', 'niveau' => 'B2', 'examen' => 'delf'],
        ['question' => 'Q2', 'options' => [['objet'], 'Non'], 'bonne_reponse' => 'A', 'competence' => 'reading', 'niveau' => 'B2', 'examen' => 'delf'],
    ]);
    $res = $this->actingAs($c['resp'])->post(route('center.import.exercices'), [
        'fichier' => UploadedFile::fake()->createWithContent('qcm.json', $json),
    ])->assertOk();
    expect($res->json('bilan'))->toBe(['crees' => 1, 'erreurs' => 1]);

    $this->actingAs($c['resp'])->postJson(route('center.import.exercices.apercu'), [
        'fichier' => UploadedFile::fake()->createWithContent('qcm.json', '{"question": "seule"}'),
    ])->assertJsonValidationErrors('fichier');
});

it("n'accepte que les examens proposes par le centre et range les QCM dans SON centre", function () {
    $a = importCentre('centre-a');
    $b = importCentre('centre-b');
    importExamen($b['centre']);
    // Le centre A ne propose que l'examen telc : le DELF lui est inconnu.
    $de = Language::create(['slug' => 'german', 'name' => 'Allemand', 'native_name' => 'Deutsch', 'flag' => 'de', 'is_active' => true]);
    $telc = Exam::create(['language_id' => $de->id, 'slug' => 'telc', 'name' => 'telc']);
    $a['centre']->examensProposes()->sync([$telc->id]);

    $csv = "question;options;bonne_reponse;competence;niveau;examen\nQ;Un | Deux;A;reading;B1;delf";

    $res = $this->actingAs($a['resp'])->post(route('center.import.exercices'), [
        'fichier' => UploadedFile::fake()->createWithContent('qcm.csv', $csv),
    ])->assertOk();
    expect($res->json('bilan.crees'))->toBe(0)->and(Exercise::count())->toBe(0);

    $this->actingAs($b['resp'])->post(route('center.import.exercices'), [
        'fichier' => UploadedFile::fake()->createWithContent('qcm.csv', $csv),
    ])->assertOk()->assertJsonPath('bilan.crees', 1);
    expect(Exercise::sole()->center_id)->toBe($b['centre']->id);
});

it("ferme l'import a qui n'est pas du personnel du centre", function () {
    $eleve = User::factory()->create();

    $this->actingAs($eleve)->post(route('center.import.eleves.apercu'), ['fichier' => csvEleves('A;a@exemple.com;;B1 du soir')])
        ->assertForbidden();
    $this->actingAs($eleve)->get(route('center.import.page'))->assertForbidden();
});

it("affiche la page d'import avec les classes et examens du centre", function () {
    $c = importCentre();
    importExamen($c['centre']);

    $this->actingAs($c['resp'])->get(route('center.import.page'))->assertOk()
        ->assertInertia(fn ($p) => $p->component('center/import')
            ->where('classes', ['B1 du soir'])
            ->where('examens.0.slug', 'delf')
            ->where('peutImporterExercices', true));
});
