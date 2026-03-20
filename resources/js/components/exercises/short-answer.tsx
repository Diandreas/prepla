import { useState } from 'react';

interface ShortAnswerProps {
    question: { id: string; text: string; max_words?: number };
    onAnswer: (questionId: string, answer: string) => void;
    selectedAnswer?: string;
}

export function ShortAnswer({ question, onAnswer, selectedAnswer }: ShortAnswerProps) {
    const [value, setValue] = useState(selectedAnswer ?? '');

    return (
        <div className="space-y-4">
            <p className="text-lg font-medium">{question.text}</p>
            {question.max_words && (
                <p className="text-xs text-muted-foreground">
                    Maximum {question.max_words} words
                </p>
            )}
            <textarea
                value={value}
                onChange={(e) => setValue(e.target.value)}
                className="w-full rounded-lg border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                rows={3}
                placeholder="Type your answer..."
                disabled={!!selectedAnswer}
            />
            {!selectedAnswer && (
                <button
                    onClick={() => value.trim() && onAnswer(question.id, value.trim())}
                    disabled={!value.trim()}
                    className="rounded-lg bg-primary px-6 py-3 text-sm font-medium text-primary-foreground disabled:opacity-50"
                >
                    Submit
                </button>
            )}
        </div>
    );
}
