<?php

use App\Models\Classroom;
use App\Models\Exam;
use App\Models\Language;
use App\Models\LanguageCenter;
use App\Models\TeacherInvitation;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Hash;

/**
 * Faire entrer un enseignant demandait soit qu'il s'inscrive et tâtonne, soit qu'on
 * lui fabrique un compte et qu'on se passe son mot de passe de main en main. Le lien
 * d'invitation règle les deux : il clique, il choisit SON mot de passe, et son espace
 * avec sa première classe existent déjà à l'arrivée.
 */
function invitationContext(): array
{
    $language = Language::create(['slug' => 'german', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => 'de']);
    $exam = Exam::create(['language_id' => $language->id, 'slug' => 'goethe-b1', 'name' => 'Goethe B1']);

    $admin = User::factory()->create(['role' => 'super_admin']);
    UserProfile::factory()->for($admin)->create(['onboarding_completed_at' => now()]);

    return [$admin, $exam];
}

test('un lien d invitation ouvre l espace et sa classe, mot de passe choisi par l interesse', function () {
    [$admin, $exam] = invitationContext();

    $this->actingAs($admin)->get(route('teach.invitations'))->assertOk();

    $response = $this->post(route('teach.invitations.store'), [
        'name' => 'Zidane Mbarga',
        'email' => 'zidane@exemple.test',
        'exam_id' => $exam->id,
        'space_name' => 'Cours d’allemand de Zidane',
        'classroom_name' => 'Groupe du samedi',
        'level' => 'A2',
    ]);

    // Le lien n'existe qu'une fois, dans la reponse : la base n'a que son empreinte.
    $link = session('invitationLink');
    expect($link)->toBeString()->and($link)->toContain('/invitation/');
    expect(TeacherInvitation::sole()->token_hash)->not->toContain(basename($link));

    // L'invite ouvre le lien sans compte, et voit son nom deja renseigne.
    $token = basename(parse_url($link, PHP_URL_PATH));
    $this->post(route('logout'));
    $this->get($link)
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('teach/invitation')->where('invitation.name', 'Zidane Mbarga'));

    $this->post(route('teach.invitation.accept', $token), [
        'phone' => '+237 6 55 44 33 22',
        'password' => 'motdepasse-solide',
        'password_confirmation' => 'motdepasse-solide',
    ])->assertRedirect(route('center.dashboard'));

    $zidane = User::where('email', 'zidane@exemple.test')->sole();
    $center = LanguageCenter::sole();
    $classroom = Classroom::sole();

    expect($zidane->name)->toBe('Zidane Mbarga')
        // Son mot de passe est bien le sien : personne d'autre ne l'a choisi.
        ->and(Hash::check('motdepasse-solide', $zidane->password))->toBeTrue()
        ->and($zidane->phone)->toBe('+237 6 55 44 33 22')
        ->and($zidane->centerRole())->toBe('center_admin')
        ->and($center->name)->toBe('Cours d’allemand de Zidane')
        ->and($classroom->name)->toBe('Groupe du samedi')
        // Le code que ses eleves utiliseront est pret.
        ->and($classroom->invite_code)->not->toBeEmpty()
        ->and($classroom->teachers()->pluck('users.id')->all())->toBe([$zidane->id]);

    // Et il est deja connecte dans son espace.
    $this->get(route('center.dashboard'))->assertOk();

    // Le lien ne sert qu'une fois.
    expect(TeacherInvitation::sole()->accepted_at)->not->toBeNull();
    $this->post(route('logout'));
    $this->get($link)->assertOk()->assertInertia(fn ($page) => $page->where('invitation', null));
});

test('un lien expire ne cree rien', function () {
    [$admin, $exam] = invitationContext();

    [$invitation, $token] = TeacherInvitation::issue([
        'name' => 'Prof Tardif', 'email' => 'tardif@exemple.test', 'role' => 'center_admin',
        'space_name' => 'Espace', 'classroom_name' => 'Classe', 'invited_by' => $admin->id,
    ]);
    $invitation->update(['expires_at' => now()->subDay()]);

    $this->post(route('teach.invitation.accept', $token), [
        'phone' => '+237 6 00 00 00 00',
        'password' => 'motdepasse-solide',
        'password_confirmation' => 'motdepasse-solide',
    ])->assertRedirect(route('login'));

    expect(User::where('email', 'tardif@exemple.test')->exists())->toBeFalse()
        ->and(LanguageCenter::count())->toBe(0);
});

test('un apprenant ordinaire ne peut pas inviter', function () {
    $learner = User::factory()->create();
    UserProfile::factory()->for($learner)->create(['onboarding_completed_at' => now()]);

    $this->actingAs($learner)->get(route('teach.invitations'))->assertForbidden();
    $this->post(route('teach.invitations.store'), [
        'name' => 'Quelqu un', 'email' => 'q@exemple.test', 'classroom_name' => 'Classe',
    ])->assertForbidden();

    expect(TeacherInvitation::count())->toBe(0);
});
