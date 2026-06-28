import { useState } from 'react';

interface Note {
    label: string;
    // Generator emits {label, value:""} where ""=blank. Older data used blank/answer.
    value?: string;
    blank?: boolean;
    answer?: string;
}

interface NoteCompletionProps {
    question: {
        id: string;
        notes: Note[];
        title?: string;
        text?: string;
        correct_answers?: Record<string, string>;
    };
    onAnswer: (questionId: string, answer: Record<string, string>) => void;
    selectedAnswer?: Record<string, string>;
    disabled?: boolean;
}

function isBlankNote(n: Note): boolean {
    if (typeof n.blank === 'boolean') return n.blank;
    return (n.value ?? '') === '';
}

export function NoteCompletion({ question, onAnswer, selectedAnswer, disabled }: NoteCompletionProps) {
    const [values, setValues] = useState<Record<string, string>>(selectedAnswer ?? {});
    const notes = question.notes || [];
    const blankAbsIndices = notes.map((n, i) => (isBlankNote(n) ? String(i) : null)).filter(Boolean) as string[];

    // Map absolute index → blank-relative index so scoring matches either key form.
    const relOf = new Map<string, string>();
    let rel = 0;
    notes.forEach((n, i) => { if (isBlankNote(n)) relOf.set(String(i), String(rel++)); });

    const setBoth = (absKey: string, val: string) => {
        setValues((prev) => {
            const next = { ...prev, [absKey]: val };
            const relKey = relOf.get(absKey);
            if (relKey != null) next[relKey] = val;
            const allFilled = blankAbsIndices.every((k) => (next[k] ?? '').trim() !== '');
            if (blankAbsIndices.length > 0 && allFilled) onAnswer(question.id, next);
            return next;
        });
    };

    return (
        <div className="space-y-4">
            {(question.title || question.text) && <p className="text-lg font-bold">{question.title ?? question.text}</p>}

            {blankAbsIndices.length === 0 ? (
                <div className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                    Ces notes n'ont aucun champ à compléter.
                </div>
            ) : (
                <div className="rounded-xl border bg-muted/30 p-4 space-y-2">
                    {notes.map((note, i) => {
                        if (!isBlankNote(note)) {
                            return (
                                <div key={i} className="flex items-center gap-2">
                                    <span className="text-sm font-medium">{note.label}{note.value ? `: ${note.value}` : ''}</span>
                                </div>
                            );
                        }
                        return (
                            <div key={i} className="flex items-center gap-2">
                                <span className="text-sm font-medium min-w-[120px]">{note.label}:</span>
                                <input
                                    type="text"
                                    value={values[String(i)] ?? ''}
                                    onChange={(e) => setBoth(String(i), e.target.value)}
                                    className="flex-1 rounded border border-border bg-background px-3 py-1.5 text-sm focus:border-primary focus:outline-none disabled:opacity-50"
                                    disabled={disabled}
                                    placeholder="À compléter…"
                                />
                            </div>
                        );
                    })}
                </div>
            )}
        </div>
    );
}
