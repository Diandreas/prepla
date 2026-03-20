import { useState } from 'react';

interface SentenceCompletionProps {
    question: { id: string; text: string; blank_position?: string };
    onAnswer: (questionId: string, answer: string) => void;
    selectedAnswer?: string;
}

export function SentenceCompletion({ question, onAnswer, selectedAnswer }: SentenceCompletionProps) {
    const [value, setValue] = useState(selectedAnswer ?? '');
    const parts = question.text.split('___');

    const handleSubmit = () => {
        if (value.trim()) onAnswer(question.id, value.trim());
    };

    return (
        <div className="space-y-4">
            <p className="text-lg font-medium leading-relaxed">
                {parts[0]}
                <span className="inline-block min-w-[120px] border-b-2 border-primary mx-1 px-1">
                    {selectedAnswer || value || '\u00A0'}
                </span>
                {parts[1] ?? ''}
            </p>
            <div className="flex gap-2">
                <input
                    type="text"
                    value={value}
                    onChange={(e) => setValue(e.target.value)}
                    onKeyDown={(e) => e.key === 'Enter' && handleSubmit()}
                    className="flex-1 rounded-lg border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    placeholder="Type your answer..."
                    disabled={!!selectedAnswer}
                />
                {!selectedAnswer && (
                    <button
                        onClick={handleSubmit}
                        disabled={!value.trim()}
                        className="rounded-lg bg-primary px-6 py-3 text-sm font-medium text-primary-foreground disabled:opacity-50"
                    >
                        Confirm
                    </button>
                )}
            </div>
        </div>
    );
}
