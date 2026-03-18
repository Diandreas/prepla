import { useState } from 'react';

interface EssayEditorProps {
    question: {
        id: string;
        text: string;
    };
    onAnswer: (questionId: string, answer: string) => void;
    selectedAnswer?: string;
    minWords?: number;
    maxWords?: number;
}

export function EssayEditor({ question, onAnswer, selectedAnswer, minWords = 150, maxWords = 300 }: EssayEditorProps) {
    const text = selectedAnswer ?? '';
    const wordCount = text.trim() ? text.trim().split(/\s+/).length : 0;

    return (
        <div className="space-y-4">
            <p className="text-lg font-medium">{question.text}</p>
            <textarea
                className="min-h-[200px] w-full rounded-lg border border-border bg-background p-4 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                placeholder="Write your essay here..."
                value={text}
                onChange={(e) => onAnswer(question.id, e.target.value)}
            />
            <div className="flex justify-between text-sm text-muted-foreground">
                <span>{wordCount} words</span>
                <span>Target: {minWords}–{maxWords} words</span>
            </div>
        </div>
    );
}
