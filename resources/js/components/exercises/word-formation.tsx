import { useState } from 'react';

interface WordFormationProps {
    question: { id: string; text: string; root_word: string };
    onAnswer: (questionId: string, answer: string) => void;
    selectedAnswer?: string;
}

export function WordFormation({ question, onAnswer, selectedAnswer }: WordFormationProps) {
    const [value, setValue] = useState(selectedAnswer ?? '');
    const parts = question.text.split('___');

    return (
        <div className="space-y-4">
            <div className="rounded-xl border bg-muted/30 p-6 leading-relaxed text-sm">
                {parts[0]}
                <input
                    type="text"
                    value={value}
                    onChange={(e) => setValue(e.target.value)}
                    className="mx-1 inline-block w-32 rounded border-b-2 border-primary bg-background px-2 py-0.5 text-center text-sm focus:outline-none"
                    disabled={!!selectedAnswer}
                />
                {parts[1] ?? ''}
            </div>

            <div className="flex items-center gap-2">
                <span className="text-sm text-muted-foreground">Root word:</span>
                <span className="rounded bg-primary/10 px-3 py-1 font-mono text-sm font-bold text-primary">
                    {question.root_word}
                </span>
            </div>

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
