import { useState } from 'react';

interface FormField {
    label: string;
    type?: 'text' | 'date' | 'select';
    blank: boolean;
    answer?: string;
    options?: string[];
}

interface FormCompletionProps {
    question: {
        id: string;
        title?: string;
        fields: FormField[];
    };
    onAnswer: (questionId: string, answer: Record<string, string>) => void;
    selectedAnswer?: Record<string, string>;
    disabled?: boolean;
}

export function FormCompletion({ question, onAnswer, selectedAnswer, disabled }: FormCompletionProps) {
    const [values, setValues] = useState<Record<string, string>>(selectedAnswer ?? {});
    const fields = question.fields || [];
    const blanks = fields
        .map((f, i) => ({ ...f, originalIndex: i }))
        .filter((f) => f.blank);

    const handleChange = (index: string, val: string) => {
        const next = { ...values, [index]: val };
        setValues(next);
    };

    const handleSubmit = () => {
        if (Object.keys(values).length >= blanks.length) {
            onAnswer(question.id, values);
        }
    };

    let blankIdx = 0;

    return (
        <div className="space-y-4">
            {question.title && (
                <p className="text-lg font-bold">{question.title}</p>
            )}

            <div className="overflow-hidden rounded-xl border">
                {fields.map((field, i) => {
                    const isBlank = field.blank;
                    const currentBlankIdx = isBlank ? blankIdx++ : -1;

                    return (
                        <div
                            key={i}
                            className={`flex items-center gap-3 border-b last:border-b-0 px-4 py-3 ${isBlank ? 'bg-primary/5' : 'bg-muted/30'}`}
                        >
                            <span className="min-w-[140px] text-sm font-semibold text-foreground">
                                {field.label}
                            </span>
                            {isBlank ? (
                                field.type === 'select' && field.options ? (
                                    <select
                                        value={values[String(currentBlankIdx)] ?? ''}
                                        onChange={(e) => handleChange(String(currentBlankIdx), e.target.value)}
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
                                        value={values[String(currentBlankIdx)] ?? ''}
                                        onChange={(e) => handleChange(String(currentBlankIdx), e.target.value)}
                                        disabled={disabled}
                                        className="flex-1 rounded border border-border bg-background px-3 py-1.5 text-sm focus:border-primary focus:outline-none disabled:opacity-50"
                                        placeholder="..."
                                    />
                                )
                            ) : (
                                <span className="flex-1 text-sm text-muted-foreground">{field.answer ?? '—'}</span>
                            )}
                        </div>
                    );
                })}
            </div>

            {!disabled && (
                <button
                    onClick={handleSubmit}
                    disabled={Object.keys(values).length < blanks.length || Object.values(values).some((v) => !v.trim())}
                    className="rounded-lg bg-primary px-6 py-3 text-sm font-medium text-primary-foreground disabled:opacity-50"
                >
                    Valider
                </button>
            )}
        </div>
    );
}
