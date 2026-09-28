import { useState } from 'react';
import { normalizeOptions } from './normalize-options';

interface SentenceCompletionProps {
    question: {
        id: string;
        text: string;
        blank_position?: string;
        // The generator emits options[4] + a LETTER correct_answer (MCQ-style).
        // Some exams instead want a free typed answer (no options).
        options?: unknown;
        correct_answer?: unknown;
    };
    onAnswer: (questionId: string, answer: string) => void;
    selectedAnswer?: string;
    disabled?: boolean;
}

export function SentenceCompletion({ question, onAnswer, selectedAnswer, disabled }: SentenceCompletionProps) {
    const [value, setValue] = useState(selectedAnswer ?? '');
    const parts = (question.text ?? '').split('___');
    const options = normalizeOptions(question.options);
    const hasOptions = options.length > 0;

    // ─── Choice mode: the generator gave options → pick a letter (A/B/C/D) ───
    if (hasOptions) {
        const correct = String(question.correct_answer ?? '').trim().toUpperCase();
        return (
            <div className="space-y-4">
                <p className="text-lg font-medium leading-relaxed">
                    {parts[0]}
                    <span className="inline-block min-w-[120px] border-b-2 border-primary mx-1 px-1 text-center font-bold">
                        {(() => {
                            const sel = selectedAnswer ?? '';
                            const idx = sel.length === 1 ? sel.toUpperCase().charCodeAt(0) - 65 : -1;
                            return idx >= 0 && idx < options.length ? options[idx] : ' ';
                        })()}
                    </span>
                    {parts.slice(1).join(' ___ ')}
                </p>
                <div className="grid gap-2">
                    {options.map((opt, i) => {
                        const letter = String.fromCharCode(65 + i);
                        const isSelected = (selectedAnswer ?? '').toUpperCase() === letter;
                        const isCorrect = disabled && correct === letter;
                        const isWrong = disabled && isSelected && correct !== letter;
                        return (
                            <button
                                key={i}
                                onClick={() => !disabled && onAnswer(question.id, letter)}
                                disabled={disabled}
                                className={`rounded-xl border-2 p-3 text-left text-sm transition-all ${
                                    isCorrect
                                        ? 'border-emerald-400 bg-emerald-50 dark:bg-emerald-950/20'
                                        : isWrong
                                        ? 'border-red-400 bg-red-50 dark:bg-red-950/20'
                                        : isSelected
                                        ? 'border-primary bg-primary/5'
                                        : 'border-border disabled:opacity-50'
                                }`}
                            >
                                <span className="mr-2.5 inline-flex h-6 w-6 items-center justify-center rounded-full bg-muted text-xs font-bold">
                                    {letter}
                                </span>
                                {opt}
                            </button>
                        );
                    })}
                </div>
            </div>
        );
    }

    // ─── Free-text mode: no options → type the missing word(s) ───
    return (
        <div className="space-y-4">
            <p className="text-lg font-medium leading-relaxed">
                {parts[0]}
                <span className="inline-block min-w-[120px] border-b-2 border-primary mx-1 px-1">
                    {selectedAnswer || value || ' '}
                </span>
                {parts.slice(1).join(' ___ ')}
            </p>
            <input
                type="text"
                value={value}
                onChange={(e) => {
                    setValue(e.target.value);
                    onAnswer(question.id, e.target.value);
                }}
                aria-label="Ta réponse"
                className="border-border bg-background focus:border-primary focus:ring-primary w-full rounded-lg border px-4 py-3 text-sm focus:ring-1 focus:outline-none disabled:opacity-60"
                placeholder="Écris ta réponse…"
                disabled={disabled}
            />
        </div>
    );
}
