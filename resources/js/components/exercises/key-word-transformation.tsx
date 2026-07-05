import { useState } from 'react';

interface KeyWordTransformationProps {
    question: {
        id: string;
        original_sentence: string;
        key_word?: string;
        keyword?: string;
        start_of_answer?: string;
    };
    onAnswer: (questionId: string, answer: string) => void;
    selectedAnswer?: string;
}

export function KeyWordTransformation({ question, onAnswer, selectedAnswer }: KeyWordTransformationProps) {
    const [value, setValue] = useState(selectedAnswer ?? '');

    return (
        <div className="space-y-4">
            <p className="text-sm text-muted-foreground">
                Complete the second sentence so that it has a similar meaning to the first. Use the word given.
                You must use between 2 and 5 words including the word given.
            </p>

            <div className="rounded-xl border bg-muted/30 p-4 space-y-3">
                <p className="text-sm font-medium">{question.original_sentence}</p>
                <div className="flex items-center gap-2">
                    <span className="text-sm text-muted-foreground">Key word:</span>
                    <span className="rounded bg-primary/10 px-3 py-1 font-mono text-sm font-bold uppercase text-primary">
                        {question.key_word ?? question.keyword}
                    </span>
                </div>
            </div>

            <div className="flex items-center gap-1 text-sm">
                {question.start_of_answer && (
                    <span className="font-medium">{question.start_of_answer}</span>
                )}
                <input
                    type="text"
                    value={value}
                    onChange={(e) => setValue(e.target.value)}
                    className="flex-1 rounded-lg border border-border bg-background px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    placeholder="Type the missing words..."
                    disabled={!!selectedAnswer}
                />
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
