import { useState } from 'react';

interface McqProps {
    question: {
        id: string;
        text: string;
        options: string[];
    };
    onAnswer: (questionId: string, answer: string) => void;
    selectedAnswer?: string;
}

export function Mcq({ question, onAnswer, selectedAnswer }: McqProps) {
    return (
        <div className="space-y-4">
            <p className="text-lg font-medium">{question.text}</p>
            <div className="grid gap-2">
                {question.options.map((option, i) => (
                    <button
                        key={i}
                        onClick={() => onAnswer(question.id, option)}
                        className={`rounded-lg border p-4 text-left transition-all ${
                            selectedAnswer === option
                                ? 'border-primary bg-primary/5 ring-1 ring-primary'
                                : 'border-border hover:border-primary/50'
                        }`}
                    >
                        <span className="mr-3 inline-flex h-6 w-6 items-center justify-center rounded-full bg-muted text-xs font-medium">
                            {String.fromCharCode(65 + i)}
                        </span>
                        {option}
                    </button>
                ))}
            </div>
        </div>
    );
}
