import { useState } from 'react';
import { normalizeOptions } from './normalize-options';

interface MatchingProps {
    question: {
        id: string;
        text: string;
        options: unknown;
        items?: string[];
        correct_answer?: unknown;
        correct?: unknown;
    };
    onAnswer: (questionId: string, answer: string) => void;
    selectedAnswer?: string;
    disabled?: boolean;
}

export function Matching({ question, onAnswer, selectedAnswer, disabled }: MatchingProps) {
    const options = normalizeOptions(question.options);
    // correct_answer peut être string/tableau/objet selon le générateur IA :
    // coerce défensivement pour éviter un crash (.trim is not a function).
    const rawCorrect = question.correct_answer ?? question.correct ?? '';
    const correctAnswer = (Array.isArray(rawCorrect) ? rawCorrect[0] ?? '' : typeof rawCorrect === 'object' ? '' : String(rawCorrect)).trim().toUpperCase();

    return (
        <div className="space-y-4">
            {/* Term to match */}
            <div className="rounded-xl border-2 border-primary/20 bg-primary/5 px-5 py-4">
                <p className="text-xs font-semibold uppercase tracking-wide text-primary mb-1">Associez ce terme</p>
                <p className="text-lg font-semibold">{question.text}</p>
            </div>
            {/* Options */}
            <div className="grid gap-2">
                {options.map((option, i) => {
                    const letter = String.fromCharCode(65 + i);
                    const isSelected = selectedAnswer === letter;
                    const isCorrect = disabled && correctAnswer === letter;
                    const isWrong = disabled && isSelected && correctAnswer !== letter;

                    return (
                        <button
                            key={i}
                            onClick={() => !disabled && onAnswer(question.id, letter)}
                            disabled={disabled}
                            className={`w-full rounded-lg border p-4 text-left text-sm transition-all ${
                                isCorrect
                                    ? 'border-emerald-400 bg-emerald-50 ring-1 ring-emerald-400 dark:bg-emerald-950/20'
                                    : isWrong
                                    ? 'border-red-400 bg-red-50 ring-1 ring-red-400 dark:bg-red-950/20'
                                    : isSelected
                                    ? 'border-primary bg-primary/5 ring-1 ring-primary'
                                    : 'border-border hover:border-primary/50 disabled:opacity-50'
                            }`}
                        >
                            <span className={`mr-3 inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold ${
                                isCorrect ? 'bg-emerald-500 text-white' : isWrong ? 'bg-red-500 text-white' : 'bg-muted'
                            }`}>
                                {letter}
                            </span>
                            {option}
                        </button>
                    );
                })}
            </div>
        </div>
    );
}
