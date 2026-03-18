import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import OnboardingLayout from '@/layouts/onboarding-layout';
import { useState } from 'react';
import type { LanguageWithExams } from '@/types';

interface Props {
    languages: LanguageWithExams[];
}

export default function ExamSelect({ languages }: Props) {
    const [selectedLanguage, setSelectedLanguage] = useState<LanguageWithExams | null>(null);
    const [selectedExamId, setSelectedExamId] = useState<number | null>(null);

    function handleSubmit() {
        if (!selectedExamId) return;
        router.post(route('onboarding.exam.store'), {
            exam_id: selectedExamId,
        });
    }

    return (
        <OnboardingLayout title="Choose Your Exam" step={1}>
            <div className="space-y-8">
                <div className="text-center">
                    <h1 className="text-3xl font-bold">Choose your language</h1>
                    <p className="mt-2 text-muted-foreground">
                        Select the language you want to prepare for
                    </p>
                </div>

                {/* Language grid */}
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-5">
                    {languages.map((lang) => (
                        <button
                            key={lang.id}
                            onClick={() => {
                                setSelectedLanguage(lang);
                                setSelectedExamId(null);
                            }}
                            className={`rounded-xl border p-4 text-center transition-all hover:border-primary/50 ${
                                selectedLanguage?.id === lang.id
                                    ? 'border-primary bg-primary/5 ring-1 ring-primary'
                                    : 'border-border'
                            }`}
                        >
                            <span className="text-3xl">{lang.flag}</span>
                            <p className="mt-2 text-sm font-medium">{lang.name}</p>
                        </button>
                    ))}
                </div>

                {/* Exam selection */}
                {selectedLanguage && (
                    <div className="space-y-4">
                        <h2 className="text-xl font-semibold">
                            Choose your {selectedLanguage.name} exam
                        </h2>
                        <div className="grid gap-3 sm:grid-cols-2">
                            {selectedLanguage.exams.map((exam) => (
                                <button
                                    key={exam.id}
                                    onClick={() => setSelectedExamId(exam.id)}
                                    className={`rounded-xl border p-4 text-left transition-all hover:border-primary/50 ${
                                        selectedExamId === exam.id
                                            ? 'border-primary bg-primary/5 ring-1 ring-primary'
                                            : 'border-border'
                                    }`}
                                >
                                    <p className="font-semibold">{exam.name}</p>
                                    {exam.levels && (
                                        <div className="mt-2 flex flex-wrap gap-1">
                                            {exam.levels.map((level: string) => (
                                                <Badge key={level} variant="secondary" className="text-xs">
                                                    {level}
                                                </Badge>
                                            ))}
                                        </div>
                                    )}
                                </button>
                            ))}
                        </div>
                    </div>
                )}

                <div className="flex justify-end">
                    <Button
                        size="lg"
                        disabled={!selectedExamId}
                        onClick={handleSubmit}
                    >
                        Continue
                    </Button>
                </div>
            </div>
        </OnboardingLayout>
    );
}
