import { useState } from 'react';

interface SummaryCompletionProps {
    question: {
        id: string;
        summary_text: string;
        word_list: string[];
        gap_count: number;
    };
    onAnswer: (questionId: string, answer: string[]) => void;
    selectedAnswer?: string[];
    disabled?: boolean;
}

export function SummaryCompletion({ question, onAnswer, selectedAnswer, disabled }: SummaryCompletionProps) {
    const wordList = question.word_list || [];
    const summaryText = question.summary_text || '';
    const [values, setValues] = useState<string[]>(selectedAnswer ?? Array(question.gap_count || 1).fill(''));

    const parts = summaryText.split(/___\d*___?|___/);

    const handleChange = (index: number, val: string) => {
        const next = [...values];
        next[index] = val;
        setValues(next);
    };

    const handleSubmit = () => {
        if (values.every((v) => v)) {
            onAnswer(question.id, values);
        }
    };

    return (
        <div className="space-y-4">
            {/* Word list */}
            <div className="rounded-xl border border-primary/20 bg-primary/5 p-4">
                <p className="mb-2 text-xs font-bold uppercase tracking-wider text-primary">Mots disponibles</p>
                <div className="flex flex-wrap gap-2">
                    {wordList.map((word, i) => {
                        const used = values.includes(word);
                        return (
                            <span
                                key={i}
                                className={`rounded-lg border px-3 py-1 text-sm font-medium transition-all ${
                                    used
                                        ? 'border-muted bg-muted/50 text-muted-foreground line-through'
                                        : 'border-primary/30 bg-background text-foreground'
                                }`}
                            >
                                {word}
                            </span>
                        );
                    })}
                </div>
            </div>

            {/* Summary with dropdowns */}
            <div className="rounded-xl border bg-muted/30 p-6 leading-relaxed text-sm">
                {parts.map((part, i) => (
                    <span key={i}>
                        {part}
                        {i < parts.length - 1 && (
                            <select
                                value={values[i] ?? ''}
                                onChange={(e) => handleChange(i, e.target.value)}
                                disabled={disabled}
                                className="mx-1 inline-block min-w-[120px] rounded border-2 border-primary/40 bg-background px-2 py-1 text-sm font-medium focus:border-primary focus:outline-none disabled:opacity-50"
                            >
                                <option value="">({i + 1})</option>
                                {wordList.map((word, j) => (
                                    <option key={j} value={word}>{word}</option>
                                ))}
                            </select>
                        )}
                    </span>
                ))}
            </div>

            {!disabled && (
                <button
                    onClick={handleSubmit}
                    disabled={!values.every((v) => v)}
                    className="rounded-lg bg-primary px-6 py-3 text-sm font-medium text-primary-foreground disabled:opacity-50"
                >
                    Valider
                </button>
            )}
        </div>
    );
}
