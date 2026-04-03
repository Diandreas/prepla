import { useState } from 'react';

interface NoteCompletionProps {
    question: {
        id: string;
        notes: { label: string; blank: boolean; answer?: string }[];
        title?: string;
    };
    onAnswer: (questionId: string, answer: Record<string, string>) => void;
    selectedAnswer?: Record<string, string>;
}

export function NoteCompletion({ question, onAnswer, selectedAnswer }: NoteCompletionProps) {
    const [values, setValues] = useState<Record<string, string>>(selectedAnswer ?? {});
    const notes = question.notes || [];
    const blanks = notes.filter((n) => n.blank);

    const handleChange = (index: number, val: string) => {
        const newValues = { ...values, [String(index)]: val };
        setValues(newValues);
    };

    const handleSubmit = () => {
        if (Object.keys(values).length === blanks.length) {
            onAnswer(question.id, values);
        }
    };

    let blankIndex = 0;
    return (
        <div className="space-y-4">
            {question.title && <p className="text-lg font-bold">{question.title}</p>}
            <div className="rounded-xl border bg-muted/30 p-4 space-y-2">
                {notes.map((note, i) => {
                    if (!note.blank) {
                        return (
                            <div key={i} className="flex items-center gap-2">
                                <span className="text-sm font-medium">{note.label}</span>
                            </div>
                        );
                    }
                    const idx = blankIndex++;
                    return (
                        <div key={i} className="flex items-center gap-2">
                            <span className="text-sm font-medium min-w-[120px]">{note.label}:</span>
                            <input
                                type="text"
                                value={values[String(idx)] ?? ''}
                                onChange={(e) => handleChange(idx, e.target.value)}
                                className="flex-1 rounded border border-border bg-background px-3 py-1.5 text-sm focus:border-primary focus:outline-none"
                                disabled={!!selectedAnswer}
                            />
                        </div>
                    );
                })}
            </div>
            {!selectedAnswer && (
                <button
                    onClick={handleSubmit}
                    className="rounded-lg bg-primary px-6 py-3 text-sm font-medium text-primary-foreground disabled:opacity-50"
                >
                    Submit
                </button>
            )}
        </div>
    );
}
