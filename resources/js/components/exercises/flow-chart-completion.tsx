import { useState } from 'react';

interface FlowChartCompletionProps {
    question: {
        id: string;
        title?: string;
        steps: { text: string; blank: boolean; answer?: string }[];
    };
    onAnswer: (questionId: string, answer: Record<string, string>) => void;
    selectedAnswer?: Record<string, string>;
    disabled?: boolean;
}

export function FlowChartCompletion({ question, onAnswer, selectedAnswer, disabled }: FlowChartCompletionProps) {
    const [values, setValues] = useState<Record<string, string>>(selectedAnswer ?? {});

    const blankIndices = question.steps
        .map((s, i) => (s.blank ? String(i) : null))
        .filter(Boolean) as string[];

    const handleChange = (key: string, val: string) => {
        const next = { ...values, [key]: val };
        setValues(next);
    };

    const handleSubmit = () => {
        if (blankIndices.every((k) => values[k]?.trim())) {
            onAnswer(question.id, values);
        }
    };

    return (
        <div className="space-y-4">
            {question.title && <p className="text-lg font-bold">{question.title}</p>}

            <div className="flex flex-col items-center gap-0">
                {question.steps.map((step, i) => (
                    <div key={i} className="flex flex-col items-center">
                        {/* Arrow connector */}
                        {i > 0 && (
                            <div className="flex flex-col items-center">
                                <div className="h-6 w-0.5 bg-primary/40" />
                                <div className="h-0 w-0 border-l-[6px] border-r-[6px] border-t-[8px] border-l-transparent border-r-transparent border-t-primary/40" />
                            </div>
                        )}

                        {/* Step card */}
                        {step.blank ? (
                            <div className="w-full max-w-md rounded-xl border-2 border-dashed border-primary/40 bg-primary/5 p-3">
                                <div className="mb-1 text-center text-[10px] font-bold uppercase tracking-wider text-primary/60">
                                    Étape {i + 1}
                                </div>
                                <input
                                    type="text"
                                    value={values[String(i)] ?? ''}
                                    onChange={(e) => handleChange(String(i), e.target.value)}
                                    disabled={disabled}
                                    className="w-full rounded border border-border bg-background px-3 py-2 text-center text-sm focus:border-primary focus:outline-none disabled:opacity-50"
                                    placeholder="Complétez cette étape..."
                                />
                            </div>
                        ) : (
                            <div className="w-full max-w-md rounded-xl border-2 border-border bg-background p-3 text-center text-sm font-medium shadow-sm">
                                {step.text}
                            </div>
                        )}
                    </div>
                ))}
            </div>

            {!disabled && (
                <div className="flex justify-center pt-2">
                    <button
                        onClick={handleSubmit}
                        disabled={!blankIndices.every((k) => values[k]?.trim())}
                        className="rounded-lg bg-primary px-6 py-3 text-sm font-medium text-primary-foreground disabled:opacity-50"
                    >
                        Valider
                    </button>
                </div>
            )}
        </div>
    );
}
