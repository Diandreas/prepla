import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import OnboardingLayout from '@/layouts/onboarding-layout';
import { useState } from 'react';
import type { ExamWithLanguage } from '@/types';

interface Props {
    exam: ExamWithLanguage | null;
}

const placementQuestions = [
    {
        id: 1,
        text: 'Choose the correct form:',
        sentence: '"She ___ to the store yesterday."',
        options: ['go', 'goes', 'went', 'going'],
        correct: 2,
        level: 'A1',
    },
    {
        id: 2,
        text: 'Complete the sentence:',
        sentence: '"If I ___ more time, I would travel the world."',
        options: ['have', 'had', 'has', 'having'],
        correct: 1,
        level: 'B1',
    },
    {
        id: 3,
        text: 'Choose the best word:',
        sentence: '"The project was ___ due to budget constraints."',
        options: ['postponed', 'delayed', 'abandoned', 'suspended'],
        correct: 2,
        level: 'B2',
    },
    {
        id: 4,
        text: 'Select the correct option:',
        sentence: '"Not only ___ the exam, but she also got the highest score."',
        options: ['she passed', 'did she pass', 'passed she', 'she did pass'],
        correct: 1,
        level: 'C1',
    },
    {
        id: 5,
        text: 'Choose the appropriate word:',
        sentence: '"The politician\'s speech was full of ___ promises."',
        options: ['empty', 'hollow', 'void', 'blank'],
        correct: 1,
        level: 'C1',
    },
];

export default function PlacementTest({ exam }: Props) {
    const [currentQ, setCurrentQ] = useState(0);
    const [answers, setAnswers] = useState<Record<number, number>>({});
    const [selectedOption, setSelectedOption] = useState<number | null>(null);

    const question = placementQuestions[currentQ];
    const isLast = currentQ === placementQuestions.length - 1;

    function handleNext() {
        if (selectedOption === null) return;

        const newAnswers = { ...answers, [question.id]: selectedOption };
        setAnswers(newAnswers);
        setSelectedOption(null);

        if (isLast) {
            // Calculate estimated level
            let correct = 0;
            placementQuestions.forEach((q) => {
                if (newAnswers[q.id] === q.correct) correct++;
            });
            const ratio = correct / placementQuestions.length;
            let level = 'A1';
            if (ratio >= 0.9) level = 'C2';
            else if (ratio >= 0.8) level = 'C1';
            else if (ratio >= 0.6) level = 'B2';
            else if (ratio >= 0.4) level = 'B1';
            else if (ratio >= 0.2) level = 'A2';

            router.post(route('onboarding.placement.store'), {
                answers: newAnswers,
                estimated_level: level,
            });
        } else {
            setCurrentQ(currentQ + 1);
        }
    }

    return (
        <OnboardingLayout title="Placement Test" step={3}>
            <div className="space-y-8">
                <div className="text-center">
                    <h1 className="text-3xl font-bold">Quick Placement Test</h1>
                    <p className="mt-2 text-muted-foreground">
                        Answer these questions so we can estimate your current level
                    </p>
                </div>

                {/* Progress */}
                <div className="text-center text-sm text-muted-foreground">
                    Question {currentQ + 1} of {placementQuestions.length}
                </div>

                {/* Question */}
                <div className="mx-auto max-w-lg space-y-6">
                    <div className="rounded-xl border border-border bg-card p-6">
                        <p className="font-medium">{question.text}</p>
                        <p className="mt-2 text-lg italic text-muted-foreground">
                            {question.sentence}
                        </p>
                    </div>

                    <div className="grid gap-3">
                        {question.options.map((option, i) => (
                            <button
                                key={i}
                                onClick={() => setSelectedOption(i)}
                                className={`rounded-lg border p-4 text-left transition-all ${
                                    selectedOption === i
                                        ? 'border-primary bg-primary/5 ring-1 ring-primary'
                                        : 'border-border hover:border-primary/50'
                                }`}
                            >
                                <span className="mr-3 inline-flex h-6 w-6 items-center justify-center rounded-full bg-muted text-xs font-medium">
                                    {String.fromCharCode(65 + i)}
                                </span>
                                {option}
                            </button>
                        ))}
                    </div>
                </div>

                <div className="flex justify-end">
                    <Button
                        size="lg"
                        disabled={selectedOption === null}
                        onClick={handleNext}
                    >
                        {isLast ? 'See Results' : 'Next'}
                    </Button>
                </div>
            </div>
        </OnboardingLayout>
    );
}
