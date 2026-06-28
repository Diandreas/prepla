import { useState } from 'react';
import { coerceOption } from './normalize-options';

interface OrderingProps {
    question: { id: string; text: string; items: unknown };
    onAnswer: (questionId: string, answer: string[]) => void;
    selectedAnswer?: string[];
}

export function Ordering({ question, onAnswer, selectedAnswer }: OrderingProps) {
    const [ordered, setOrdered] = useState<string[]>(selectedAnswer ?? []);
    // Le générateur IA renvoie parfois les items sous forme d'objets {id, text}
    // au lieu de strings. Rendre un objet comme enfant React provoque l'erreur #31
    // (page d'exercice blanche). On normalise donc systématiquement en string[].
    const items = (Array.isArray(question.items) ? question.items : []).map(coerceOption);
    const remaining = items.filter((item) => !ordered.includes(item));

    const addItem = (item: string) => {
        const next = [...ordered, item];
        setOrdered(next);
        if (next.length === items.length) {
            onAnswer(question.id, next);
        }
    };

    const removeItem = (index: number) => {
        if (selectedAnswer) return;
        setOrdered(ordered.filter((_, i) => i !== index));
    };

    return (
        <div className="space-y-4">
            <p className="text-lg font-medium">{question.text}</p>

            {/* Ordered items */}
            <div className="min-h-[60px] rounded-xl border-2 border-dashed border-border p-3 space-y-2">
                {ordered.length === 0 && (
                    <p className="text-sm text-muted-foreground text-center py-2">
                        Click items below to put them in order
                    </p>
                )}
                {ordered.map((item, i) => (
                    <button
                        key={i}
                        onClick={() => removeItem(i)}
                        disabled={!!selectedAnswer}
                        className="flex w-full items-center gap-3 rounded-lg border bg-primary/5 border-primary p-3 text-left text-sm transition hover:bg-primary/10 disabled:cursor-default"
                    >
                        <span className="flex h-6 w-6 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground">
                            {i + 1}
                        </span>
                        {item}
                    </button>
                ))}
            </div>

            {/* Remaining items */}
            {remaining.length > 0 && (
                <div className="grid gap-2">
                    {remaining.map((item, i) => (
                        <button
                            key={i}
                            onClick={() => addItem(item)}
                            className="rounded-lg border border-border p-3 text-left text-sm transition hover:border-primary hover:bg-primary/5"
                        >
                            {item}
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}
