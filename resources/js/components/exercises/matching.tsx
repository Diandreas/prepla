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
    const items = question.items ?? [];
    const options = question.options ?? [];

    return (
        <div className="space-y-4">
            <p className="text-lg font-medium">{question.text}</p>
            <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                    <p className="text-sm font-medium text-muted-foreground">Items</p>
                    {items.map((item, i) => (
                        <div key={i} className="rounded-lg border border-border bg-muted/30 p-3 text-sm">
                            {i + 1}. {item}
                        </div>
                    ))}
                </div>
                <div className="space-y-2">
                    <p className="text-sm font-medium text-muted-foreground">Options</p>
                    {options.map((option, i) => (
                        <button
                            key={i}
                            onClick={() => onAnswer(question.id, option)}
                            className={`w-full rounded-lg border p-3 text-left text-sm transition-all ${
                                selectedAnswer === option
                                    ? 'border-primary bg-primary/5 ring-1 ring-primary'
                                    : 'border-border hover:border-primary/50'
                            }`}
                        >
                            {String.fromCharCode(65 + i)}. {option}
                        </button>
                    ))}
                </div>
            </div>
        </div>
    );
}
