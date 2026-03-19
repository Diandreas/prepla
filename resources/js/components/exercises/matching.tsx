import { useState } from 'react';

interface MatchingProps {
    question: {
        id: string;
        text: string;
        options: string[];
        items?: string[];
    };
    onAnswer: (questionId: string, answer: string) => void;
    selectedAnswer?: string;
}

export function Matching({ question, onAnswer, selectedAnswer }: MatchingProps) {
    const options = question.options ?? [];

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
                    return (
                        <button
                            key={i}
                            onClick={() => onAnswer(question.id, letter)}
                            className={`w-full rounded-lg border p-4 text-left text-sm transition-all ${
                                selectedAnswer === letter
                                    ? 'border-primary bg-primary/5 ring-1 ring-primary'
                                    : 'border-border hover:border-primary/50'
                            }`}
                        >
                            <span className="mr-3 inline-flex h-6 w-6 items-center justify-center rounded-full bg-muted text-xs font-bold">
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
