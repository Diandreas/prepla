import { Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import OnboardingLayout from '@/layouts/onboarding-layout';
import { CheckCircle } from 'lucide-react';
import type { UserProfile } from '@/types';

interface Props {
    profile: UserProfile & {
        target_exam?: {
            id: number;
            name: string;
            language: { name: string; flag: string };
        };
    };
}

const levelDescriptions: Record<string, string> = {
    A1: 'Beginner - You can understand basic phrases and introduce yourself.',
    A2: 'Elementary - You can communicate in simple, routine tasks.',
    B1: 'Intermediate - You can deal with most travel situations and describe experiences.',
    B2: 'Upper Intermediate - You can interact fluently with native speakers.',
    C1: 'Advanced - You can use language flexibly for social and professional purposes.',
    C2: 'Proficient - You can understand virtually everything and express yourself spontaneously.',
};

export default function Result({ profile }: Props) {
    function handleComplete() {
        router.post(route('onboarding.complete'));
    }

    const level = profile.current_level ?? 'A1';

    return (
        <OnboardingLayout title="Your Results" step={4}>
            <div className="space-y-8 text-center">
                <div>
                    <div className="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                        <CheckCircle className="h-10 w-10 text-green-600 dark:text-green-400" />
                    </div>
                    <h1 className="mt-6 text-3xl font-bold">Your estimated level</h1>
                </div>

                <div className="mx-auto max-w-md rounded-2xl border border-border bg-card p-8">
                    <div className="text-6xl font-bold text-primary">{level}</div>
                    <p className="mt-4 text-muted-foreground">
                        {levelDescriptions[level]}
                    </p>
                </div>

                {profile.target_exam && (
                    <div className="mx-auto max-w-md rounded-xl border border-border bg-muted/30 p-6">
                        <p className="text-sm text-muted-foreground">Preparing for</p>
                        <p className="mt-1 text-lg font-semibold">
                            {profile.target_exam.language.flag} {profile.target_exam.name}
                        </p>
                        {profile.target_score && (
                            <p className="mt-1 text-sm text-muted-foreground">
                                Target score: {profile.target_score}
                            </p>
                        )}
                        {profile.exam_date && (
                            <p className="mt-1 text-sm text-muted-foreground">
                                Exam date: {new Date(profile.exam_date).toLocaleDateString()}
                            </p>
                        )}
                    </div>
                )}

                <div>
                    <Button size="lg" onClick={handleComplete} className="gap-2">
                        Go to Dashboard
                    </Button>
                </div>
            </div>
        </OnboardingLayout>
    );
}
