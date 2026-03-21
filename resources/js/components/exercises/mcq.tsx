import { useState } from 'react';

interface McqProps {
    question: {
        id: string;
        text: string;
        options: string[];
        correct_answer?: string;
        correct?: string;
    };
    onAnswer: (questionId: string, answer: string) => void;
    selectedAnswer?: string;
    disabled?: boolean;
}

export function Mcq({ question, onAnswer, selectedAnswer, disabled }: McqProps) {
    const correctAnswer = (question.correct_answer ?? question.correct ?? '').trim().toUpperCase();

    return (
        <div className="space-y-4">
            <p className="text-lg font-medium">{question.text}</p>
            <div className="grid gap-2">
                {question.options.map((option, i) => {
                    const letter = String.fromCharCode(65 + i);
                    const isSelected = selectedAnswer === letter;
                    const isCorrect = disabled && correctAnswer === letter;
                    const isWrong = disabled && isSelected && correctAnswer !== letter;

                    return (
                        <button
                            key={i}
                            onClick={() => !disabled && onAnswer(question.id, letter)}
                            disabled={disabled}
                            className={`rounded-lg border p-4 text-left transition-all ${
                                isCorrect
                                    ? 'border-emerald-400 bg-emerald-50 ring-1 ring-emerald-400 dark:bg-emerald-950/20'
                                    : isWrong
                                    ? 'border-red-400 bg-red-50 ring-1 ring-red-400 dark:bg-red-950/20'
                                    : isSelected
                                    ? 'border-primary bg-primary/5 ring-1 ring-primary'
                                    : 'border-border hover:border-primary/50 disabled:opacity-50'
                            }`}
                        >
                            <span className={`mr-3 inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-medium ${
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
