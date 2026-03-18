import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { ExerciseTimer } from './exercise-timer';
import { ExerciseProgress } from './exercise-progress';
import { Mcq } from './mcq';
import { TrueFalseNg } from './true-false-ng';
import { GapFill } from './gap-fill';
import { Matching } from './matching';
import { EssayEditor } from './essay-editor';
import { useCallback, useRef, useState } from 'react';
import type { ExerciseRecord, QuestionItem } from '@/types';

interface ExercisePlayerProps {
    exercise: ExerciseRecord;
}

const componentMap: Record<string, React.ComponentType<any>> = {
    'mcq': Mcq,
    'true-false-ng': TrueFalseNg,
    'gap-fill': GapFill,
    'matching': Matching,
    'essay-editor': EssayEditor,
};

export function ExercisePlayer({ exercise }: ExercisePlayerProps) {
    const [currentIndex, setCurrentIndex] = useState(0);
    const [answers, setAnswers] = useState<Record<string, string>>({});
    const timeRef = useRef(0);
    const [submitting, setSubmitting] = useState(false);

    const questions = exercise.questions ?? [];
    const question = questions[currentIndex];
    const componentKey = exercise.exercise_type?.component_key ?? 'mcq';
    const Component = componentMap[componentKey] ?? Mcq;
    const isLast = currentIndex === questions.length - 1;

    const handleAnswer = useCallback((questionId: string, answer: string) => {
        setAnswers((prev) => ({ ...prev, [questionId]: answer }));
    }, []);

    const handleTimeUpdate = useCallback((seconds: number) => {
        timeRef.current = seconds;
    }, []);

    function handleNext() {
        if (isLast) {
            handleSubmit();
        } else {
            setCurrentIndex((prev) => prev + 1);
        }
    }

    function handleSubmit() {
        setSubmitting(true);
        router.post(route('exercise.submit', exercise.id), {
            answers,
            time_spent: timeRef.current,
        });
    }

    if (!question) {
        return <p className="text-center text-muted-foreground">No questions in this exercise.</p>;
    }

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <ExerciseProgress current={currentIndex + 1} total={questions.length} />
                <ExerciseTimer onTimeUpdate={handleTimeUpdate} />
            </div>

            {/* Passage / Content */}
            {typeof exercise.content?.passage === 'string' && (
                <div className="rounded-lg border border-border bg-muted/30 p-4 text-sm leading-relaxed">
                    {exercise.content.passage}
                </div>
            )}

            <Component
                question={question}
                onAnswer={handleAnswer}
                selectedAnswer={answers[question.id]}
            />

            <div className="flex justify-between pt-4">
                <Button
                    variant="outline"
                    disabled={currentIndex === 0}
                    onClick={() => setCurrentIndex((prev) => prev - 1)}
                >
                    Previous
                </Button>
                <Button
                    onClick={handleNext}
                    disabled={!answers[question.id] || submitting}
                >
                    {isLast ? (submitting ? 'Submitting...' : 'Submit') : 'Next'}
                </Button>
            </div>
        </div>
    );
}
