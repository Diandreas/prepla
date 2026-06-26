import { useEffect, useState } from 'react';

interface FormField {
    label: string;
    type?: 'text' | 'date' | 'select';
    // The generator emits `value`: a non-empty string for a pre-filled (display)
    // field, or "" for a blank the student must fill. Older data used `blank`/
    // `answer`; we support both so existing exercises keep working.
    value?: string;
    blank?: boolean;
    answer?: string;
    options?: string[];
}

interface FormCompletionProps {
    question: {
        id: string;
        title?: string;
        text?: string;
        fields: FormField[];
    };
    onAnswer: (questionId: string, answer: Record<string, string>) => void;
    selectedAnswer?: Record<string, string>;
    disabled?: boolean;
}

// A field is a blank if it explicitly says so, or (new shape) its value is empty.
function isBlankField(f: FormField): boolean {
    if (typeof f.blank === 'boolean') return f.blank;
    return (f.value ?? '') === '';
}

export function FormCompletion({ question, onAnswer, selectedAnswer, disabled }: FormCompletionProps) {
    const [values, setValues] = useState<Record<string, string>>(selectedAnswer ?? {});
    const fields = question.fields || [];

    // Blank indices keyed by their position among blanks (0,1,2…) to line up with
    // the generator's `correct_answers` map ({"0": "...", "1": "..."}).
    const blankCount = fields.filter(isBlankField).length;

    const handleChange = (key: string, val: string) => {
        const next = { ...values, [key]: val };
        setValues(next);
        // Report up only once every blank has a non-empty value. This prevents the
        // player's Check button from ever scoring an empty/partial form as a
        // "success" (the previous version auto-submitted {} when no blank was
        // detected, marking nothing-entered as correct).
        const filled = Object.values(next).filter((v) => v.trim() !== '').length;
        if (blankCount > 0 && filled >= blankCount) {
            onAnswer(question.id, next);
        }
    };

    // If the data is malformed (no blanks at all) we must NOT report an answer,
    // otherwise the exercise would be gradable with nothing to fill.
    useEffect(() => {
        if (blankCount === 0) {
            // leave the answer unset so the player keeps the Check button disabled
        }
    }, [blankCount]);

    let blankIdx = 0;

    return (
        <div className="space-y-4">
            {(question.title || question.text) && (
                <p className="text-base font-bold">{question.title ?? question.text}</p>
            )}

            {blankCount === 0 ? (
                <div className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                    Ce formulaire n'a aucun champ à compléter.
                </div>
            ) : (
                <div className="overflow-hidden rounded-xl border">
                    {fields.map((field, i) => {
                        const blank = isBlankField(field);
                        const key = blank ? String(blankIdx++) : '';
                        const display = field.answer ?? field.value ?? '—';

                        return (
                            <div
                                key={i}
                                className={`flex items-center gap-3 border-b last:border-b-0 px-4 py-3 ${blank ? 'bg-primary/5' : 'bg-muted/30'}`}
                            >
                                <span className="min-w-[120px] text-sm font-semibold text-foreground">
                                    {field.label}
                                </span>
                                {blank ? (
                                    field.type === 'select' && field.options ? (
                                        <select
                                            value={values[key] ?? ''}
                                            onChange={(e) => handleChange(key, e.target.value)}
                                            disabled={disabled}
                                            className="flex-1 rounded border border-border bg-background px-3 py-1.5 text-sm focus:border-primary focus:outline-none disabled:opacity-50"
                                        >
                                            <option value="">—</option>
                                            {field.options.map((opt, j) => (
                                                <option key={j} value={opt}>{opt}</option>
                                            ))}
                                        </select>
                                    ) : (
                                        <input
                                            type={field.type === 'date' ? 'date' : 'text'}
                                            value={values[key] ?? ''}
                                            onChange={(e) => handleChange(key, e.target.value)}
                                            disabled={disabled}
                                            className="flex-1 rounded border border-border bg-background px-3 py-1.5 text-sm focus:border-primary focus:outline-none disabled:opacity-50"
                                            placeholder="À compléter…"
                                        />
                                    )
                                ) : (
                                    <span className="flex-1 text-sm text-muted-foreground">{display}</span>
                                )}
                            </div>
                        );
                    })}
                </div>
            )}
        </div>
    );
}
