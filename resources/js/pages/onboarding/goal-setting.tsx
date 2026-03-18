import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import OnboardingLayout from '@/layouts/onboarding-layout';
import { useState } from 'react';
import type { ExamWithLanguage, UserProfile } from '@/types';

interface Props {
    exam: ExamWithLanguage | null;
    profile: UserProfile | null;
}

export default function GoalSetting({ exam, profile }: Props) {
    const [targetScore, setTargetScore] = useState<string>(
        profile?.target_score?.toString() ?? ''
    );
    const [examDate, setExamDate] = useState<string>(profile?.exam_date ?? '');

    function handleSubmit() {
        router.post(route('onboarding.goal.store'), {
            target_score: targetScore ? parseInt(targetScore) : null,
            exam_date: examDate || null,
        });
    }

    return (
        <OnboardingLayout title="Set Your Goal" step={2}>
            <div className="space-y-8">
                <div className="text-center">
                    <h1 className="text-3xl font-bold">Set your goal</h1>
                    <p className="mt-2 text-muted-foreground">
                        {exam
                            ? `Preparing for ${exam.name} (${exam.language.name})`
                            : 'Tell us about your exam goals'}
                    </p>
                </div>

                <div className="mx-auto max-w-md space-y-6">
                    {exam?.max_score && (
                        <div className="space-y-2">
                            <Label htmlFor="target-score">
                                Target Score (max {exam.max_score})
                            </Label>
                            <Input
                                id="target-score"
                                type="number"
                                min={0}
                                max={exam.max_score}
                                placeholder={`e.g. ${Math.round(exam.max_score * 0.7)}`}
                                value={targetScore}
                                onChange={(e) => setTargetScore(e.target.value)}
                            />
                        </div>
                    )}

                    <div className="space-y-2">
                        <Label htmlFor="exam-date">Exam Date (optional)</Label>
                        <Input
                            id="exam-date"
                            type="date"
                            value={examDate}
                            onChange={(e) => setExamDate(e.target.value)}
                        />
                        <p className="text-xs text-muted-foreground">
                            We'll create a study plan based on your timeline
                        </p>
                    </div>
                </div>

                <div className="flex justify-between">
                    <Button
                        variant="outline"
                        onClick={() => router.get(route('onboarding.exam'))}
                    >
                        Back
                    </Button>
                    <Button size="lg" onClick={handleSubmit}>
                        Continue
                    </Button>
                </div>
            </div>
        </OnboardingLayout>
    );
}
