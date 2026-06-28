import { useState } from 'react';

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
    // Counter for the blank-relative key (must be declared before the .map below;
    // its absence was crashing the component with "blankIdx is not defined").
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
                        // Key answers by BOTH the absolute field index (i) and the
                        // blank-relative index. The generator's correct_answers map is
                        // sometimes keyed by absolute position, sometimes by blank order
                        // — writing both keys (same value) makes scoring match either way
                        // and fixes correct answers being marked 0%.
                        const relKey = blank ? String(blankIdx++) : '';
                        const absKey = String(i);
                        const display = field.answer ?? field.value ?? '—';
                        const setBoth = (val: string) => {
                            setValues((prev) => {
                                const next = { ...prev, [absKey]: val };
                                if (relKey) next[relKey] = val;
                                // Count distinct blanks filled (abs keys only, to avoid double-count).
                                const blanksFilled = fields.filter((f, fi) => isBlankField(f) && (next[String(fi)] ?? '').trim() !== '').length;
                                if (blankCount > 0 && blanksFilled >= blankCount) {
                                    onAnswer(question.id, next);
                                }
                                return next;
                            });
                        };

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
                                            value={values[absKey] ?? ''}
                                            onChange={(e) => setBoth(e.target.value)}
                                            disabled={disabled}
                                            className="flex-1 rounded border border-border bg-background px-3 py-1.5 text-sm focus:border-primary focus:outline-none disabled:opacity-50"
                                        >
                                            <option value="">—</option>
                                            {field.options.map((opt, j) => (
                                                <option key={j} value={opt}>{opt}</option>
                                            ))}
                                        </select>
                                    ) : (
                                        // Always a plain text input. We deliberately do NOT use
                                        // <input type="date">: the AI mislabels many text fields as
                                        // "date", and a native date picker forces a locale format
                                        // that never matches the expected answer string → 0%.
                                        <input
                                            type="text"
                                            value={values[absKey] ?? ''}
                                            onChange={(e) => setBoth(e.target.value)}
                                            disabled={disabled}
                                            className="flex-1 rounded border border-border bg-background px-3 py-1.5 text-sm focus:border-primary focus:outline-none disabled:opacity-50"
                                            placeholder={field.type === 'date' ? 'JJ/MM/AAAA…' : 'À compléter…'}
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
