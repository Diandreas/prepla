<?php

use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\Language;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Models\UserProfile;

/**
 * La liste des devoirs montrait à un enseignant ceux des classes de ses collègues,
 * et chaque clic tombait sur un refus — la politique n'autorise que ses propres
 * classes. Le formulaire de création lui proposait aussi ces classes, et refusait
 * l'envoi après qu'il ait tout saisi.
 */
function centerWithTwoTeachers(): array
{
    $language = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'goethe-a2', 'name' => 'Goethe A2']);

    $center = LanguageCenter::create([
        'name' => 'Ecole de langues', 'slug' => 'ecole-de-langues',
        'owner_email' => 'direction@exemple.test', 'is_active' => true,
    ]);

    $faire = function (string $role) use ($center) {
        $user = User::factory()->create();
        UserProfile::factory()->for($user)->create(['onboarding_completed_at' => now()]);
        $center->members()->attach($user->id, ['role' => $role, 'joined_at' => now()]);

        return $user;
    };

    $admin = $faire('center_admin');
    $zidane = $faire('teacher');
    $collegue = $faire('teacher');

    $classeZidane = $center->classrooms()->create([
        'name' => 'Allemand A2', 'exam_id' => $exam->id, 'invite_code' => Classroom::generateInviteCode(),
    ]);
    $classeZidane->members()->attach($zidane->id, ['role_in_class' => 'teacher']);

    $classeCollegue = $center->classrooms()->create([
        'name' => 'Anglais B1', 'exam_id' => $exam->id, 'invite_code' => Classroom::generateInviteCode(),
    ]);
    $classeCollegue->members()->attach($collegue->id, ['role_in_class' => 'teacher']);

    foreach ([[$classeZidane, 'Devoir de Zidane'], [$classeCollegue, 'Devoir du collegue']] as [$classe, $titre]) {
        Assignment::create([
            'classroom_id' => $classe->id, 'created_by' => $classe->teachers()->first()->id,
            'title' => $titre, 'published_at' => now(),
        ]);
    }

    return [$admin, $zidane, $classeZidane, $classeCollegue];
}

test('un enseignant ne voit que les devoirs de ses classes', function () {
    [$admin, $zidane] = centerWithTwoTeachers();

    $this->actingAs($zidane)->get(route('center.assignments.index'))
        ->assertOk()
        ->assertInertia(function ($page) {
            $titres = collect($page->toArray()['props']['assignments'])->pluck('title');

            expect($titres->all())->toBe(['Devoir de Zidane']);

            return true;
        });
});

test('un responsable d espace voit les devoirs de tout le monde', function () {
    [$admin] = centerWithTwoTeachers();

    $this->actingAs($admin)->get(route('center.assignments.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('assignments', 2));
});

test('le formulaire ne propose a l enseignant que ses classes', function () {
    [$admin, $zidane, $classeZidane] = centerWithTwoTeachers();

    $this->actingAs($zidane)->get(route('center.assignments.create'))
        ->assertOk()
        ->assertInertia(function ($page) use ($classeZidane) {
            $classes = collect($page->toArray()['props']['classrooms']);

            // Proposer la classe d'un collegue menait a un refus apres coup.
            expect($classes->pluck('id')->all())->toBe([$classeZidane->id]);

            return true;
        });
});
