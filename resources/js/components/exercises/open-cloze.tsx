import { useState } from 'react';

interface OpenClozeProps {
    question: {
        id: string;
        text: string;
        gap_count?: number;
        correct_answers?: Record<string, string>;
    };
    // Send a map keyed by gap number ("1","2",…) to match the generator's
    // correct_answers ({"1":ans,"2":ans}). Also writes 0-based keys as a fallback.
    onAnswer: (questionId: string, answer: Record<string, string>) => void;
    selectedAnswer?: Record<string, string>;
    disabled?: boolean;
}

export function OpenCloze({ question, onAnswer, selectedAnswer, disabled }: OpenClozeProps) {
    // Support both numbered gaps "(1)___" and plain "___".
    const text = question.text ?? '';
    const parts = text.split(/\(\d+\)___|___/);
    const gapCount = Math.max(parts.length - 1, 0);

    const [values, setValues] = useState<Record<string, string>>(selectedAnswer ?? {});

    const handleChange = (zeroIdx: number, val: string) => {
        setValues((prev) => {
            // gap number is 1-based to align with generator keys; also write 0-based.
            const next = { ...prev, [String(zeroIdx + 1)]: val, [String(zeroIdx)]: val };
            const allFilled = Array.from({ length: gapCount }).every((_, i) => (next[String(i + 1)] ?? '').trim() !== '');
            if (gapCount > 0 && allFilled) onAnswer(question.id, next);
            return next;
        });
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
                                value={values[String(i + 1)] ?? ''}
                                onChange={(e) => handleChange(i, e.target.value)}
                                className="mx-1 inline-block w-24 rounded border-b-2 border-primary bg-background px-2 py-0.5 text-center text-sm focus:outline-none disabled:opacity-50"
                                disabled={disabled}
                                placeholder={`(${i + 1})`}
                            />
                        )}
                    </span>
                ))}
            </div>
        </div>
    );
}
