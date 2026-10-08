<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Exam;
use App\Models\LanguageCenter;
use App\Models\TeacherInvitation;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Inviter un enseignant par un lien, plutôt que de lui fabriquer un compte.
 *
 * Fabriquer le compte obligeait à choisir son mot de passe à sa place, puis à se le
 * passer par messagerie : un identifiant de quelqu'un d'autre, connu de plusieurs
 * personnes, et qu'il faut penser à changer. Le lien évite tout cela — il choisit
 * son mot de passe, et son espace l'attend déjà monté.
 */
class TeacherInvitationController extends Controller
{
    /** Qui peut inviter : l'équipe, et un responsable d'espace pour ses collègues. */
    private function assertMayInvite(User $user): void
    {
        abort_unless($user->isSuperAdmin() || $user->centerRole() === 'center_admin', 403);
    }

    public function index(Request $request): Response
    {
        $this->assertMayInvite($request->user());

        return Inertia::render('teach/invitations', [
            'invitations' => TeacherInvitation::with('center:id,name')
                ->where('invited_by', $request->user()->id)
                ->latest()
                ->limit(25)
                ->get()
                ->map(fn (TeacherInvitation $invitation) => [
                    'id' => $invitation->id,
                    'name' => $invitation->name,
                    'email' => $invitation->email,
                    'space' => $invitation->center?->name ?? $invitation->space_name,
                    'accepted_at' => $invitation->accepted_at?->translatedFormat('d M Y'),
                    'expires_at' => $invitation->expires_at->translatedFormat('d M Y'),
                    'usable' => $invitation->isUsable(),
                ]),
            // Un responsable d'espace invite DANS son espace ; l'équipe en ouvre un.
            'ownCenter' => $request->user()->isSuperAdmin() ? null : $request->user()->center()?->only(['id', 'name']),
            'exams' => Exam::with('language:id,name')->get(['id', 'name', 'language_id'])
                ->map(fn (Exam $exam) => ['id' => $exam->id, 'name' => $exam->name, 'language' => $exam->language?->name]),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $this->assertMayInvite($user);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users,email',
            'exam_id' => 'nullable|exists:exams,id',
            'space_name' => 'nullable|string|max:120',
            'classroom_name' => 'required|string|max:120',
            'level' => 'nullable|string|max:10',
        ]);

        $center = $user->isSuperAdmin() ? null : $user->center();

        [$invitation, $plainToken] = TeacherInvitation::issue([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'center_id' => $center?->id,
            'role' => $center ? 'teacher' : 'center_admin',
            'space_name' => $validated['space_name'] ?? ($validated['name'] . ' — cours de langue'),
            'classroom_name' => $validated['classroom_name'],
            'level' => $validated['level'] ?? null,
            'exam_id' => $validated['exam_id'] ?? null,
            'invited_by' => $user->id,
        ]);

        // Le lien complet n'existe qu'ici : la base n'en garde qu'une empreinte.
        return back()
            ->with('invitationLink', route('teach.invitation.show', $plainToken))
            ->with('success', "Lien d'invitation prêt pour {$invitation->name}.");
    }

    public function show(string $token): Response
    {
        $invitation = TeacherInvitation::findUsable($token);

        return Inertia::render('teach/invitation', [
            'token' => $token,
            'invitation' => $invitation ? [
                'name' => $invitation->name,
                'email' => $invitation->email,
                'space' => $invitation->center?->name ?? $invitation->space_name,
                'classroom' => $invitation->classroom_name,
                'joins_existing' => $invitation->joinsExistingSpace(),
            ] : null,
        ]);
    }

    public function accept(Request $request, string $token)
    {
        $invitation = TeacherInvitation::findUsable($token);

        if (! $invitation) {
            return redirect()->route('login')
                ->with('error', "Ce lien d'invitation n'est plus valable. Demande-en un nouveau.");
        }

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'phone' => 'required|string|min:6|max:32',
        ]);

        // Un compte a pu être créé entre l'envoi du lien et son ouverture.
        if (User::where('email', $invitation->email)->exists()) {
            return redirect()->route('login')
                ->with('error', 'Un compte existe déjà avec cette adresse. Connecte-toi.');
        }

        $user = DB::transaction(function () use ($invitation, $validated) {
            $user = User::create([
                'name' => $invitation->name,
                'email' => $invitation->email,
                'phone' => $validated['phone'],
                'phone_prompted_at' => now(),
                // Mot de passe choisi par la personne elle-même : il n'a transité par
                // personne d'autre, et il n'y a rien à lui faire changer ensuite.
                'password' => Hash::make($validated['password']),
            ]);

            UserProfile::create(['user_id' => $user->id, 'target_exam_id' => $invitation->exam_id]);

            $center = $invitation->center ?? LanguageCenter::create([
                'name' => $invitation->space_name ?: ($invitation->name . ' — cours de langue'),
                'slug' => $this->uniqueSlug($invitation->space_name ?: $invitation->name),
                'owner_email' => $invitation->email,
                'default_exam_id' => $invitation->exam_id,
                'is_active' => true,
            ]);

            $center->members()->attach($user->id, ['role' => $invitation->role, 'joined_at' => now()]);

            $classroom = $center->classrooms()->create([
                'name' => $invitation->classroom_name,
                'level' => $invitation->level,
                'exam_id' => $invitation->exam_id,
                'invite_code' => Classroom::generateInviteCode(),
            ]);
            $classroom->members()->attach($user->id, ['role_in_class' => 'teacher']);

            $invitation->update(['accepted_at' => now()]);

            return $user;
        });

        event(new Registered($user));
        Auth::login($user);

        return redirect()->route('center.dashboard')
            ->with('success', "Bienvenue ! Ton espace et ta première classe sont prêts, avec leur code d'invitation.");
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'espace';
        $slug = $base;
        $suffix = 2;

        while (LanguageCenter::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }
}
