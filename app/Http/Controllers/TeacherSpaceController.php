<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Exam;
use App\Models\LanguageCenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Ouvrir son espace d'enseignant, sans passer par nous.
 *
 * L'espace enseignant existait déjà — classes, codes d'invitation, suivi des élèves,
 * devoirs — mais seul un super-administrateur pouvait créer le centre qui le porte.
 * Un professeur qui voulait suivre ses élèves devait donc nous écrire et attendre.
 * Il le crée maintenant lui-même, avec sa première classe et son code, en une étape.
 */
class TeacherSpaceController extends Controller
{
    public function show(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('teach/open', [
            // Un espace déjà ouvert : la page le dit au lieu d'en proposer un second.
            'existing' => $user->center()?->only(['id', 'name']),
            'role' => $user->centerRole(),
            'exams' => Exam::with('language:id,name')
                ->get(['id', 'name', 'language_id'])
                ->map(fn (Exam $exam) => [
                    'id' => $exam->id,
                    'name' => $exam->name,
                    'language' => $exam->language?->name,
                ]),
            'suggestedName' => trim(($user->name ? Str::of($user->name)->before(' ') . ' — ' : '') . 'Cours de langue'),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        // Déjà membre d'un espace : on n'en crée pas un second, on y renvoie.
        if ($existing = $user->center()) {
            return redirect()->route($user->isCenterStaff() ? 'center.dashboard' : 'dashboard')
                ->with('error', "Tu fais déjà partie de l'espace « {$existing->name} ».");
        }

        $validated = $request->validate([
            'name' => 'required|string|min:2|max:120',
            'exam_id' => 'nullable|exists:exams,id',
            'classroom_name' => 'required|string|min:2|max:120',
            'level' => 'nullable|string|max:10',
        ]);

        $center = DB::transaction(function () use ($request, $user, $validated) {
            $center = LanguageCenter::create([
                'name' => $validated['name'],
                'slug' => $this->uniqueSlug($validated['name']),
                'owner_email' => $user->email,
                'default_exam_id' => $validated['exam_id'] ?? null,
                'is_active' => true,
            ]);

            // center_admin, et pas teacher : c'est son espace, il en gère les classes
            // et pourra y inviter d'autres professeurs.
            $center->members()->attach($user->id, ['role' => 'center_admin', 'joined_at' => now()]);

            $classroom = $center->classrooms()->create([
                'name' => $validated['classroom_name'],
                'level' => $validated['level'] ?? null,
                'exam_id' => $validated['exam_id'] ?? null,
                'invite_code' => Classroom::generateInviteCode(),
            ]);

            // L'enseignant est aussi membre de sa classe : le suivi par classe le
            // compte parmi ses encadrants, pas parmi ses élèves.
            $classroom->members()->attach($user->id, ['role_in_class' => 'teacher']);

            return $center;
        });

        return redirect()->route('center.dashboard')
            ->with('success', "Espace « {$center->name} » ouvert. Ta première classe a son code d'invitation.");
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
