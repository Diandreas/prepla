<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Language;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function examSelect(): Response
    {
        $languages = Language::where('is_active', true)
            ->with('exams')
            ->get();

        return Inertia::render('onboarding/exam-select', [
            'languages' => $languages,
        ]);
    }

    public function storeExam(Request $request)
    {
        $validated = $request->validate([
            'exam_id' => 'required|exists:exams,id',
        ]);

        $profile = UserProfile::updateOrCreate(
            ['user_id' => $request->user()->id],
            ['target_exam_id' => $validated['exam_id']]
        );

        return redirect()->route('onboarding.goal');
    }

    public function goalSetting(): Response
    {
        $profile = auth()->user()->profile;
        $exam = $profile?->targetExam;

        return Inertia::render('onboarding/goal-setting', [
            'exam' => $exam?->load('language'),
            'profile' => $profile,
        ]);
    }

    public function storeGoal(Request $request)
    {
        $validated = $request->validate([
            'target_score' => 'nullable|integer|min:0',
            'exam_date' => 'nullable|date|after:today',
        ]);

        $request->user()->profile->update($validated);

        return redirect()->route('onboarding.placement');
    }

    public function placementTest(): Response
    {
        $profile = auth()->user()->profile;
        $exam = $profile?->targetExam?->load('language');

        return Inertia::render('onboarding/placement-test', [
            'exam' => $exam,
        ]);
    }

    public function submitPlacement(Request $request)
    {
        $validated = $request->validate([
            'answers' => 'required|array',
            'estimated_level' => 'required|string|in:A1,A2,B1,B2,C1,C2',
        ]);

        $request->user()->profile->update([
            'current_level' => $validated['estimated_level'],
        ]);

        return redirect()->route('onboarding.result');
    }

    public function result(): Response
    {
        $profile = auth()->user()->profile->load('targetExam.language');

        return Inertia::render('onboarding/result', [
            'profile' => $profile,
        ]);
    }

    public function complete(Request $request)
    {
        $request->user()->profile->update([
            'onboarding_completed_at' => now(),
        ]);

        return redirect()->route('dashboard');
    }
}
