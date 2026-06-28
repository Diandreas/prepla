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

        // Attach as center_admin (a user belongs to a single center in this lot).
        if (! CenterUser::where('user_id', $admin->id)->exists()) {
            CenterUser::create([
                'center_id' => $center->id,
                'user_id' => $admin->id,
                'role' => 'center_admin',
                'joined_at' => now(),
            ]);
        }

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
            ],
            'staff' => $staff,
            'classrooms' => $center->classrooms->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'invite_code' => $c->invite_code,
            ]),
            'exams' => Exam::get(['id', 'name']),
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
