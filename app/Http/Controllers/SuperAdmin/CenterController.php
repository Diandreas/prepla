<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\CenterUser;
use App\Models\Exam;
use App\Models\LanguageCenter;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CenterController extends Controller
{
    public function index(): Response
    {
        $centers = LanguageCenter::query()
            ->withCount('classrooms')
            ->with('defaultExam:id,name')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (LanguageCenter $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'is_active' => $c->is_active,
                'seats_limit' => $c->seats_limit,
                'seats_used' => $c->seatsUsed(),
                'classrooms_count' => $c->classrooms_count,
                'default_exam' => $c->defaultExam?->name,
                'created_at' => $c->created_at,
            ]);

        return Inertia::render('admin/centers/index', [
            'centers' => $centers,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/centers/create', [
            'exams' => Exam::with('language:id,name')->get(['id', 'name', 'language_id']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'seats_limit' => 'required|integer|min:1|max:100000',
            'default_exam_id' => 'nullable|exists:exams,id',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255',
        ]);

        // Verifie AVANT de creer quoi que ce soit : un compte deja membre d'un autre
        // espace n'etait pas rattache, mais le centre etait cree quand meme et le
        // message annoncait pourtant « rattache comme administrateur ». Resultat : un
        // espace sans aucun administrateur, que personne ne pouvait ouvrir.
        $dejaMembre = User::where('email', $validated['admin_email'])->first();
        if ($dejaMembre && ($lien = CenterUser::where('user_id', $dejaMembre->id)->first())) {
            $nom = LanguageCenter::find($lien->center_id)?->name ?? 'un autre espace';

            return back()->withInput()->withErrors([
                'admin_email' => "Ce compte appartient déjà à l'espace « {$nom} » (rôle {$lien->role}). Détachez-le d'abord ou indiquez une autre adresse.",
            ]);
        }

        $center = LanguageCenter::create([
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($validated['name']),
            'owner_email' => $validated['admin_email'],
            'seats_limit' => $validated['seats_limit'],
            'default_exam_id' => $validated['default_exam_id'] ?? null,
            'is_active' => true,
        ]);

        // Create or reuse the center_admin account.
        $tempPassword = null;
        $admin = User::where('email', $validated['admin_email'])->first();
        if (! $admin) {
            $tempPassword = Str::random(10);
            $admin = User::create([
                'name' => $validated['admin_name'],
                'email' => $validated['admin_email'],
                'password' => Hash::make($tempPassword),
            ]);
            // Minimal profile so the account is usable without breaking existing flows.
            UserProfile::firstOrCreate(
                ['user_id' => $admin->id],
                ['onboarding_completed_at' => now()],
            );
        }

        // Le cas conflictuel a ete ecarte plus haut : le rattachement ne peut plus
        // etre silencieusement ignore, donc le message de succes ne peut plus mentir.
        CenterUser::create([
            'center_id' => $center->id,
            'user_id' => $admin->id,
            'role' => 'center_admin',
            'joined_at' => now(),
        ]);

        return redirect()
            ->route('admin.centers.show', $center->id)
            ->with('success', $tempPassword
                ? "Centre créé. Mot de passe provisoire de l'admin : {$tempPassword}"
                : "Centre créé. Le compte existant a été rattaché comme administrateur.");
    }

    public function show(LanguageCenter $center): Response
    {
        $center->load(['defaultExam:id,name', 'classrooms']);

        $staff = $center->staff()->get()->map(fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'role' => $u->getRelationValue('pivot')->role ?? null,
        ]);

        return Inertia::render('admin/centers/show', [
            'center' => [
                'id' => $center->id,
                'name' => $center->name,
                'slug' => $center->slug,
                'is_active' => $center->is_active,
                'seats_limit' => $center->seats_limit,
                'seats_used' => $center->seatsUsed(),
                'default_exam_id' => $center->default_exam_id,
                'default_exam' => $center->defaultExam?->name,
                'owner_email' => $center->owner_email,
                // Formule commerciale (lot C) : paliers, revente ou libre_service.
                'formule' => $center->formule(),
                'prix_acces' => $center->prix_acces,
                'duree_acces_jours' => $center->duree_acces_jours,
            ],
            'staff' => $staff,
            'classrooms' => $center->classrooms->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'invite_code' => $c->invite_code,
            ]),
            'exams' => Exam::get(['id', 'name']),
            // Sous-domaines et domaines propres (lot B).
            'domaines' => DomaineController::pourLaPage($center),
            'domainesReglages' => DomaineController::reglages(),
        ]);
    }

    public function update(Request $request, LanguageCenter $center)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'seats_limit' => 'required|integer|min:1|max:100000',
            'default_exam_id' => 'nullable|exists:exams,id',
            'is_active' => 'required|boolean',
        ]);

        $center->update($validated);

        return back()->with('success', 'Centre mis à jour.');
    }

    /**
     * La formule du centre et le prix d'un acces vendu. Seul le super
     * administrateur la choisit : la revente fait retomber les eleves du centre
     * dans le quota gratuit, sauf ceux a qui le centre ouvre un acces.
     *
     * La colonne et le marqueur settings.libre_service restent d'accord : c'est
     * le marqueur qui decide de l'exemption du personnel (exempteSonPersonnelDuQuota).
     */
    public function formule(Request $request, LanguageCenter $center)
    {
        $validated = $request->validate([
            'formule' => ['required', \Illuminate\Validation\Rule::in(LanguageCenter::FORMULES)],
            'prix_acces' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'duree_acces_jours' => ['nullable', 'integer', 'min:1', 'max:366'],
        ]);

        $settings = $center->settings ?? [];
        if ($validated['formule'] === LanguageCenter::FORMULE_LIBRE_SERVICE) {
            $settings[LanguageCenter::LIBRE_SERVICE] = true;
        } else {
            unset($settings[LanguageCenter::LIBRE_SERVICE]);
        }

        $center->update([
            'formule' => $validated['formule'],
            'prix_acces' => $validated['prix_acces'] ?? null,
            'duree_acces_jours' => $validated['duree_acces_jours'] ?? null,
            'settings' => $settings ?: null,
        ]);

        \Illuminate\Support\Facades\Log::info('acces-vendus: formule du centre changee', [
            'center_id' => $center->id, 'formule' => $validated['formule'], 'par' => $request->user()->id,
        ]);

        return back()->with('success', 'Formule du centre mise à jour.');
    }

    public function destroy(LanguageCenter $center)
    {
        $center->delete(); // cascades to center_user, classrooms, assignments
        return redirect()->route('admin.centers.index')->with('success', 'Centre supprimé.');
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'centre';
        $slug = $base;
        $i = 1;
        while (LanguageCenter::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
