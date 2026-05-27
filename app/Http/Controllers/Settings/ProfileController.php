<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

use App\Models\CurriculumSkeleton;
use App\Models\Exam;
use App\Models\Lesson;
use App\Models\LearningPathNode;
use App\Models\UserLearningProgress;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $profile = $user->profile?->load('targetExam.language');
        $exams = Exam::with('language')->get();

        return Inertia::render('settings/profile', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'profile' => $profile,
            'exams' => $exams,
        ]);
    }

    /**
     * Update the user's profile settings (name/email).
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return back();
    }

    /**
     * Update the user's exam/learning settings.
     */
    public function updateLearning(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'target_exam_id' => 'nullable|exists:exams,id',
            'target_score' => 'nullable|numeric|min:0',
            'exam_date' => 'nullable|date',
            'current_level' => 'nullable|string|in:A1,A2,B1,B2,C1,C2',
            'interface_language' => 'nullable|string|in:en,fr',
        ]);

        $user    = $request->user();
        $profile = $user->profile;

        $examChanged = $profile->target_exam_id != ($validated['target_exam_id'] ?? null);

        $profile->update($validated);

        if ($examChanged && $profile->target_exam_id) {
            // Reset all progress tied to the old exam
            DB::transaction(function () use ($user) {
                // Delete adaptive curriculum skeleton
                CurriculumSkeleton::where('user_id', $user->id)->delete();

                // Delete AI-generated lessons
                Lesson::where('user_id', $user->id)->delete();

                // Delete progress on all nodes for this user
                UserLearningProgress::where('user_id', $user->id)->delete();

                // Delete AI-generated learning path nodes (node_type = 'lesson' or 'practice' generated JIT)
                LearningPathNode::where('user_id', $user->id)->delete();
            });

            $exam = Exam::find($profile->target_exam_id);
            app(\App\Services\AI\RoadmapGeneratorService::class)->generateForUser(
                $user,
                $exam,
                $profile->current_level ?? 'A1',
                $profile->exam_date
            );
        }

        return back()->with('status', 'settings-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
