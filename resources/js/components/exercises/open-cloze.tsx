import { useState } from 'react';

interface OpenClozeProps {
    question: { id: string; text: string; gap_count: number };
    onAnswer: (questionId: string, answer: string[]) => void;
    selectedAnswer?: string[];
}

export function OpenCloze({ question, onAnswer, selectedAnswer }: OpenClozeProps) {
    const [values, setValues] = useState<string[]>(selectedAnswer ?? Array(question.gap_count).fill(''));
    const parts = question.text.split('___');

    const handleChange = (index: number, val: string) => {
        const next = [...values];
        next[index] = val;
        setValues(next);
    };

    const handleSubmit = () => {
        if (values.every((v) => v.trim())) {
            onAnswer(question.id, values.map((v) => v.trim()));
        }
    };

    return (
        <div className="space-y-4">
            <div className="rounded-xl border bg-muted/30 p-6 leading-relaxed text-sm">
                {parts.map((part, i) => (
                    <span key={i}>
                        {part}
                        {i < parts.length - 1 && (
                            <input
                                type="text"
                                value={values[i] ?? ''}
                                onChange={(e) => handleChange(i, e.target.value)}
                                className="mx-1 inline-block w-24 rounded border-b-2 border-primary bg-background px-2 py-0.5 text-center text-sm focus:outline-none"
                                disabled={!!selectedAnswer}
                                placeholder={`(${i + 1})`}
                            />
                        )}
                    </span>
                ))}
            </div>
            {!selectedAnswer && (
                <button
                    onClick={handleSubmit}
                    disabled={!values.every((v) => v.trim())}
                    className="rounded-lg bg-primary px-6 py-3 text-sm font-medium text-primary-foreground disabled:opacity-50"
                >
                    Submit
                </button>
            )}
        </div>
    );
}
