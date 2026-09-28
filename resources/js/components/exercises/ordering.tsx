import { useState } from 'react';
import { coerceOption } from './normalize-options';

interface OrderingProps {
    question: { id: string; text: string; items: unknown };
    onAnswer: (questionId: string, answer: string[]) => void;
    selectedAnswer?: string[];
    disabled?: boolean;
}

/**
 * Remise en ordre.
 *
 * Deux pièges corrigés. L'ordre n'était transmis qu'une fois tous les éléments
 * placés : une remise en ordre partielle ne pouvait pas être validée du tout. Et le
 * retrait d'un élément était bloqué dès qu'une réponse existait — c'est-à-dire dès le
 * dernier élément posé : un clic de travers figeait l'ordre définitivement, sans
 * moyen de le reprendre avant de vérifier.
 *
 * L'ordre part maintenant à chaque changement, et reste modifiable jusqu'à la
 * correction.
 */
export function Ordering({ question, onAnswer, selectedAnswer, disabled }: OrderingProps) {
    const [ordered, setOrdered] = useState<string[]>(selectedAnswer ?? []);
    // Le générateur IA renvoie parfois les items sous forme d'objets {id, text}
    // au lieu de strings. Rendre un objet comme enfant React provoque l'erreur #31
    // (page d'exercice blanche). On normalise donc systématiquement en string[].
    const items = (Array.isArray(question.items) ? question.items : []).map(coerceOption);
    const remaining = items.filter((item) => !ordered.includes(item));

    const apply = (next: string[]) => {
        setOrdered(next);
        onAnswer(question.id, next);
    };

    const addItem = (item: string) => {
        if (disabled) return;
        apply([...ordered, item]);
    };

    const removeItem = (index: number) => {
        if (disabled) return;
        apply(ordered.filter((_, i) => i !== index));
    };

    return (
        <div className="space-y-4">
            <p className="text-lg font-medium">{question.text}</p>

            {/* Ordre en cours de construction */}
            <div className="border-border min-h-[60px] space-y-2 rounded-xl border-2 border-dashed p-3">
                {ordered.length === 0 && (
                    <p className="text-muted-foreground py-2 text-center text-sm">
                        Clique les éléments ci-dessous pour les mettre dans l'ordre.
                    </p>
                )}
                {ordered.map((item, i) => (
                    <button
                        key={i}
                        onClick={() => removeItem(i)}
                        disabled={disabled}
                        aria-label={`Retirer « ${item} » de la position ${i + 1}`}
                        className="bg-primary/5 border-primary hover:bg-primary/10 flex w-full items-center gap-3 rounded-lg border p-3 text-left text-sm transition disabled:cursor-default"
                    >
                        <span className="bg-primary text-primary-foreground flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold">
                            {i + 1}
                        </span>
                        {item}
                    </button>
                ))}
            </div>

            {ordered.length > 0 && !disabled && (
                <p className="text-muted-foreground text-xs">
                    Clique un élément déjà placé pour le retirer et le remettre ailleurs.
                </p>
            )}

            {/* Éléments restants */}
            {remaining.length > 0 && (
                <div className="grid gap-2">
                    {remaining.map((item, i) => (
                        <button
                            key={i}
                            onClick={() => addItem(item)}
                            disabled={disabled}
                            className="border-border hover:border-primary hover:bg-primary/5 rounded-lg border p-3 text-left text-sm transition disabled:opacity-60"
                        >
                            {item}
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}
