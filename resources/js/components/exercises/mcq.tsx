import { useState } from 'react';
import { normalizeOptions } from './normalize-options';

interface McqProps {
    question: {
        id: string;
        text: string;
        options: unknown;
        correct_answer?: unknown;
        correct?: unknown;
    };
    onAnswer: (questionId: string, answer: string) => void;
    selectedAnswer?: string;
    disabled?: boolean;
}

export function Mcq({ question, onAnswer, selectedAnswer, disabled }: McqProps) {
    // correct_answer peut arriver sous forme de string, tableau ou objet selon le
    // générateur IA. On coerce en string de façon défensive pour éviter un crash
    // (.trim is not a function) qui blanchirait toute la page d'exercice.
    const rawCorrect = question.correct_answer ?? question.correct ?? '';
    const correctAnswer = (Array.isArray(rawCorrect) ? rawCorrect[0] ?? '' : String(rawCorrect)).trim().toUpperCase();

    const options = normalizeOptions(question.options);

    return (
        <div className="space-y-4">
            <p className="text-lg font-medium">{question.text}</p>
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
                            className={`rounded-lg border-2 p-4 text-left ${!disabled ? 'duo-press' : 'transition-all'} ${
                                isCorrect
                                    ? 'border-emerald-400 bg-emerald-50 ring-1 ring-emerald-400 dark:bg-emerald-950/20'
                                    : isWrong
                                    ? 'border-red-400 bg-red-50 ring-1 ring-red-400 dark:bg-red-950/20'
                                    : isSelected
                                    ? 'border-primary bg-primary/5'
                                    : 'border-border disabled:opacity-50'
                            }`}
                            style={!disabled ? { boxShadow: isSelected ? '0 4px 0 0 var(--primary, #4A90E2)' : '0 3px 0 0 #e5e7eb' } : undefined}
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
