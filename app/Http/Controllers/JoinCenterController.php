<?php

namespace App\Http\Controllers;

use App\Models\CenterUser;
use App\Models\Classroom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class JoinCenterController extends Controller
{
    public function show(Request $request): Response
    {
        return Inertia::render('join', [
            'alreadyInCenter' => $request->user()->center() !== null,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string',
        ]);

        $user = $request->user();

        // One center per user in this lot.
        if ($user->center() !== null) {
            throw ValidationException::withMessages([
                'code' => 'Vous appartenez déjà à un centre.',
            ]);
        }

        $classroom = Classroom::with('center')
            ->whereNull('archived_at')
            ->where('invite_code', strtoupper(trim($validated['code'])))
            ->first();

        if (! $classroom || ! $classroom->center || ! $classroom->center->is_active) {
            throw ValidationException::withMessages([
                'code' => "Code invalide ou centre indisponible.",
            ]);
        }

        $center = $classroom->center;

        // Seat check.
        if ($center->seatsAvailable() < 1) {
            throw ValidationException::withMessages([
                'code' => "Ce centre a atteint sa limite de licences. Contactez votre établissement.",
            ]);
        }

        DB::transaction(function () use ($user, $center, $classroom) {
            CenterUser::create([
                'center_id' => $center->id,
                'user_id' => $user->id,
                'role' => 'student',
                'joined_at' => now(),
            ]);

            $classroom->members()->syncWithoutDetaching([
                $user->id => ['role_in_class' => 'student'],
            ]);

            // Inherit the classroom/center exam so the learner experience is
            // pre-configured; keeps onboarding satisfied (exam already chosen).
            $examId = $classroom->exam_id ?? $center->default_exam_id;
            if ($examId && $user->profile) {
                $user->profile->update(['target_exam_id' => $examId]);
            }
        });

        return redirect()->route('dashboard')
            ->with('success', "Vous avez rejoint « {$center->name} » — classe « {$classroom->name} ».");
    }
}
