<?php

use App\Models\Classroom;
use App\Models\Exam;
use App\Models\Language;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Models\UserProfile;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Fiche eleve, cote centre : « Donner un devoir » arrive avec la classe de
 * l'eleve deja choisie, et « Relancer sur WhatsApp » a besoin de son numero.
 */
beforeEach(fn () => $this->withoutVite());

function actionsRapidesCentre(): array
{
    $langue = Language::create(['slug' => 'german', 'name' => 'Allemand', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::create(['language_id' => $langue->id, 'slug' => 'goethe', 'name' => 'Goethe-Zertifikat']);
    $centre = LanguageCenter::create(['name' => 'Centre Rapide', 'slug' => 'centre-rapide', 'owner_email' => 'x@exemple.test', 'is_active' => true]);

    $personne = function (string $role, array $attrs = []) use ($centre) {
        $u = User::factory()->create($attrs);
        UserProfile::factory()->for($u)->create(['onboarding_completed_at' => now()]);
        $centre->members()->attach($u->id, ['role' => $role, 'joined_at' => now()]);

        return $u;
    };

    $resp = $personne('center_admin');
    $eleve = $personne('student', ['phone' => '6 93 00 00 00']);
    $classe = $centre->classrooms()->create(['name' => 'Groupe B1', 'level' => 'B1', 'exam_id' => $exam->id, 'invite_code' => Classroom::generateInviteCode()]);
    $classe->members()->attach($eleve->id, ['role_in_class' => 'student']);
    $autre = $centre->classrooms()->create(['name' => 'Groupe A2', 'level' => 'A2', 'exam_id' => $exam->id, 'invite_code' => Classroom::generateInviteCode()]);

    return compact('centre', 'resp', 'eleve', 'classe', 'autre');
}

test('la fiche eleve envoie sa classe et son numero pour les actions rapides', function () {
    ['resp' => $resp, 'eleve' => $eleve, 'classe' => $classe] = actionsRapidesCentre();

    $this->actingAs($resp)->get(route('center.students.show', $eleve))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('student.classe_ids', [$classe->id])
            ->where('student.phone', '6 93 00 00 00'));
});

test('« Donner un devoir » preremplit la classe demandee', function () {
    ['resp' => $resp, 'autre' => $autre] = actionsRapidesCentre();

    $this->actingAs($resp)->get(route('center.assignments.create', ['classe' => $autre->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('classeDemandee', $autre->id));
});

test('une classe hors du centre n\'est jamais preremplie', function () {
    ['resp' => $resp] = actionsRapidesCentre();

    $this->actingAs($resp)->get(route('center.assignments.create', ['classe' => 999999]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('classeDemandee', null));
});

test('le tableau de bord du centre replie le detail et la fiche propose WhatsApp', function () {
    $tableau = file_get_contents(resource_path('js/pages/center/dashboard.tsx'));
    expect($tableau)->toContain('<Detail titre={t("Voir le détail")}');

    $fiche = file_get_contents(resource_path('js/pages/center/students/show.tsx'));
    expect($fiche)->toContain("t('Relancer sur WhatsApp')")->toContain('lienWhatsApp(');

    $anglais = json_decode(file_get_contents(resource_path('js/locales/en-centre/doherty-centre.json')), true);
    expect($anglais)->toHaveKeys(['Voir le détail', 'Relancer sur WhatsApp']);
});

test('les ecrans d\'IA montrent des etapes plutot qu\'un simple spinner', function () {
    $player = file_get_contents(resource_path('js/pages/exercises/player.tsx'));
    expect($player)->toContain('<GenerationEnCours')->toContain('ETAPES_ECRIT')->toContain('ETAPES_ORAL');

    $composant = file_get_contents(resource_path('js/components/generation-en-cours.tsx'));
    expect($composant)->toContain('Je lis ta copie…')->toContain('Je vérifie la grammaire…');
});
