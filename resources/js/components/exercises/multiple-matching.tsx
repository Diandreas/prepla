import { useState } from 'react';
import { coerceOption } from './normalize-options';

interface MultipleMatchingProps {
    question: {
        id: string;
        texts: { id: string; title: string; content: string }[];
        statements: { id: string; text: string; correct_text_id?: string }[];
    };
    onAnswer: (questionId: string, answer: Record<string, string>) => void;
    selectedAnswer?: Record<string, string>;
    disabled?: boolean;
}

export function MultipleMatching({ question, onAnswer, selectedAnswer, disabled }: MultipleMatchingProps) {
    const [values, setValues] = useState<Record<string, string>>(selectedAnswer ?? {});

    const handleSelect = (statementId: string, textId: string) => {
        if (disabled) return;
        const next = { ...values, [statementId]: textId };
        setValues(next);
    };

    // Coerce every IA-generated field defensively — a malformed object here
    // (instead of a plain string) would otherwise crash the whole session with
    // React error #31, the same class of bug already fixed on mcq/matching/ordering.
    const texts = (Array.isArray(question.texts) ? question.texts : []).map((t) => ({
        id: coerceOption(t?.id),
        title: coerceOption(t?.title),
        content: coerceOption(t?.content),
    }));
    const statements = (Array.isArray(question.statements) ? question.statements : []).map((s) => ({
        id: coerceOption(s?.id),
        text: coerceOption(s?.text),
    }));
    const textIds = texts.map((t) => t.id);

    const handleSubmit = () => {
        if (Object.keys(values).length === statements.length) {
            onAnswer(question.id, values);
        }
    };

    return (
        <div className="space-y-5">
            {/* Texts */}
            <div className="grid gap-3 sm:grid-cols-2">
                {texts.map((text) => (
                    <div key={text.id} className="rounded-xl border bg-muted/30 p-4">
                        <div className="mb-2 flex items-center gap-2">
                            <span className="flex h-7 w-7 items-center justify-center rounded-lg bg-primary text-xs font-bold text-primary-foreground">
                                {text.id}
                            </span>
                            <span className="text-sm font-bold">{text.title}</span>
                        </div>
                        <p className="text-sm leading-relaxed text-muted-foreground">{text.content}</p>
                    </div>
                ))}
            </div>

            {/* Statements */}
            <div className="space-y-2">
                <p className="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                    Associez chaque affirmation au texte correspondant
                </p>
                {statements.map((stmt, i) => {
                    const selected = values[stmt.id];
                    return (
                        <div key={stmt.id} className="flex items-center gap-3 rounded-xl border bg-background p-3">
                            <span className="min-w-[24px] text-sm font-bold text-muted-foreground">{i + 1}.</span>
                            <p className="flex-1 text-sm">{stmt.text}</p>
                            <div className="flex gap-1">
                                {textIds.map((tid) => (
                                    <button
                                        key={tid}
                                        onClick={() => handleSelect(stmt.id, tid)}
                                        disabled={disabled}
                                        className={`flex h-8 w-8 items-center justify-center rounded-lg text-xs font-bold transition-all ${
                                            selected === tid
                                                ? 'bg-primary text-primary-foreground shadow-sm'
                                                : 'border border-border bg-muted/30 text-foreground hover:border-primary/50'
                                        } disabled:opacity-50`}
                                    >
                                        {tid}
                                    </button>
                                ))}
                            </div>
                        </div>
                    );
                })}
            </div>

            {!disabled && (
                <button
                    onClick={handleSubmit}
                    disabled={Object.keys(values).length < statements.length}
                    className="rounded-lg bg-primary px-6 py-3 text-sm font-medium text-primary-foreground disabled:opacity-50"
                >
                    Valider
                </button>
            )}
        </div>
    );
}
