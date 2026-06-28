import { useState } from 'react';

interface SummaryCompletionProps {
    question: {
        id: string;
        // Generator emits `text`; legacy used `summary_text`.
        text?: string;
        summary_text?: string;
        word_list: string[];
        gap_count?: number;
        correct_answers?: Record<string, string>;
    };
    // Send a map {"0":word,"1":word,...} so the scorer's correct_answers branch matches.
    onAnswer: (questionId: string, answer: Record<string, string>) => void;
    selectedAnswer?: Record<string, string>;
    disabled?: boolean;
}

export function SummaryCompletion({ question, onAnswer, selectedAnswer, disabled }: SummaryCompletionProps) {
    const wordList = question.word_list || [];
    const summaryText = question.text ?? question.summary_text ?? '';
    // Split on ___ (with optional numbering like (1)___ or ___1___).
    const parts = summaryText.split(/\(\d+\)___|___\d*___?|___/);
    const gapCount = Math.max(parts.length - 1, 0);

    const [values, setValues] = useState<Record<string, string>>(selectedAnswer ?? {});

    const handleChange = (index: number, val: string) => {
        setValues((prev) => {
            const next = { ...prev, [String(index)]: val };
            const allFilled = Array.from({ length: gapCount }).every((_, i) => (next[String(i)] ?? '').trim() !== '');
            if (gapCount > 0 && allFilled) onAnswer(question.id, next);
            return next;
        });
    };

    const usedWords = new Set(Object.values(values).filter(Boolean));

    return (
        <div className="space-y-4">
            {/* Word list */}
            <div className="rounded-xl border border-primary/20 bg-primary/5 p-4">
                <p className="mb-2 text-xs font-bold uppercase tracking-wider text-primary">Mots disponibles</p>
                <div className="flex flex-wrap gap-2">
                    {wordList.map((word, i) => (
                        <span
                            key={i}
                            className={`rounded-lg border px-3 py-1 text-sm font-medium transition-all ${
                                usedWords.has(word)
                                    ? 'border-muted bg-muted/50 text-muted-foreground line-through'
                                    : 'border-primary/30 bg-background text-foreground'
                            }`}
                        >
                            {word}
                        </span>
                    ))}
                </div>
            </div>

            {/* Summary with dropdowns */}
            <div className="rounded-xl border bg-muted/30 p-6 leading-relaxed text-sm">
                {parts.map((part, i) => (
                    <span key={i}>
                        {part}
                        {i < parts.length - 1 && (
                            <select
                                value={values[String(i)] ?? ''}
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
        </div>
    );
}
