import { useState } from 'react';

interface FlowStep {
    text?: string;
    // The generator emits `is_blank`; older data used `blank`. Support both.
    is_blank?: boolean;
    blank?: boolean;
    answer?: string;
}

interface FlowChartCompletionProps {
    question: {
        id: string;
        title?: string;
        text?: string;
        steps: FlowStep[];
        correct_answers?: Record<string, string>;
    };
    onAnswer: (questionId: string, answer: Record<string, string>) => void;
    selectedAnswer?: Record<string, string>;
    disabled?: boolean;
}

function isBlankStep(s: FlowStep): boolean {
    if (typeof s.is_blank === 'boolean') return s.is_blank;
    if (typeof s.blank === 'boolean') return s.blank;
    // New shape sometimes marks a blank by an empty text.
    return (s.text ?? '') === '';
}

export function FlowChartCompletion({ question, onAnswer, selectedAnswer, disabled }: FlowChartCompletionProps) {
    const [values, setValues] = useState<Record<string, string>>(selectedAnswer ?? {});

    const steps = question.steps || [];
    const blankIndices = steps
        .map((s, i) => (isBlankStep(s) ? String(i) : null))
        .filter(Boolean) as string[];

    // Write BOTH the absolute step index and the blank-relative index so scoring
    // matches whichever key the generator's correct_answers map uses.
    const blankRel = new Map<string, string>();
    let rel = 0;
    steps.forEach((s, i) => { if (isBlankStep(s)) blankRel.set(String(i), String(rel++)); });

    const setBoth = (absKey: string, val: string) => {
        setValues((prev) => {
            const next = { ...prev, [absKey]: val };
            const relKey = blankRel.get(absKey);
            if (relKey != null) next[relKey] = val;
            const allFilled = blankIndices.every((k) => (next[k] ?? '').trim() !== '');
            if (blankIndices.length > 0 && allFilled) onAnswer(question.id, next);
            return next;
        });
    };

    return (
        <div className="space-y-4">
            {(question.title || question.text) && <p className="text-lg font-bold">{question.title ?? question.text}</p>}

            <div className="flex flex-col items-center gap-0">
                {steps.map((step, i) => {
                    const blank = isBlankStep(step);
                    return (
                        <div key={i} className="flex flex-col items-center w-full">
                            {/* Arrow connector */}
                            {i > 0 && (
                                <div className="flex flex-col items-center">
                                    <div className="h-6 w-0.5 bg-primary/40" />
                                    <div className="h-0 w-0 border-l-[6px] border-r-[6px] border-t-[8px] border-l-transparent border-r-transparent border-t-primary/40" />
                                </div>
                            )}

                            {/* Step card */}
                            {blank ? (
                                <div className="w-full max-w-md rounded-xl border-2 border-dashed border-primary/40 bg-primary/5 p-3">
                                    <div className="mb-1 text-center text-[10px] font-bold uppercase tracking-wider text-primary/60">
                                        Étape {i + 1}
                                    </div>
                                    <input
                                        type="text"
                                        value={values[String(i)] ?? ''}
                                        onChange={(e) => setBoth(String(i), e.target.value)}
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
                    );
                })}
            </div>
        </div>
    );
}
