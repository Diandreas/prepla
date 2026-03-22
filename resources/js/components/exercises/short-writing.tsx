import { useState } from 'react';

interface ShortWritingProps {
    question: {
        id: string;
        text: string;
        context?: string;
        min_words?: number;
        max_words?: number;
    };
    onAnswer: (questionId: string, answer: string) => void;
    selectedAnswer?: string;
    disabled?: boolean;
}

export function ShortWriting({ question, onAnswer, selectedAnswer, disabled }: ShortWritingProps) {
    const [value, setValue] = useState(selectedAnswer ?? '');
    const minWords = question.min_words ?? 30;
    const maxWords = question.max_words ?? 80;
    const wordCount = value.trim() ? value.trim().split(/\s+/).length : 0;
    const inRange = wordCount >= minWords && wordCount <= maxWords;

    const handleChange = (text: string) => {
        setValue(text);
        onAnswer(question.id, text);
    };

    return (
        <div className="space-y-4">
            <p className="text-lg font-medium">{question.text}</p>

            {question.context && (
                <div className="rounded-xl border border-primary/20 bg-primary/5 p-4 text-sm italic text-muted-foreground">
                    {question.context}
                </div>
            )}

            <textarea
                className="min-h-[120px] w-full rounded-lg border border-border bg-background p-4 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary disabled:opacity-50"
                placeholder="Rédigez votre texte ici..."
                value={value}
                onChange={(e) => handleChange(e.target.value)}
                disabled={disabled}
            />

            <div className="flex justify-between text-sm">
                <span className={inRange ? 'font-medium text-green-600' : wordCount > maxWords ? 'font-medium text-red-500' : 'text-muted-foreground'}>
                    {wordCount} mot{wordCount !== 1 ? 's' : ''}
                </span>
                <span className="text-muted-foreground">
                    {minWords}–{maxWords} mots
                </span>
            </div>
        </div>
    );
}
