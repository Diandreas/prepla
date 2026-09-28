interface ShortAnswerProps {
    question: { id: string; text: string; max_words?: number };
    onAnswer: (questionId: string, answer: string) => void;
    selectedAnswer?: string;
    disabled?: boolean;
}

/**
 * Réponse courte libre.
 *
 * La réponse est enregistrée au fil de la frappe. Un bouton « Submit » propre au
 * composant faisait doublon avec « Vérifier » de la barre du bas, et surtout piégeait :
 * une réponse tapée mais non confirmée n'existait pas, « Vérifier » restait éteint et
 * rien n'expliquait pourquoi.
 */
export function ShortAnswer({ question, onAnswer, selectedAnswer, disabled }: ShortAnswerProps) {
    const value = selectedAnswer ?? '';

    return (
        <div className="space-y-4">
            <p className="text-lg font-medium">{question.text}</p>
            {question.max_words && (
                <p className="text-muted-foreground text-xs">{question.max_words} mots maximum</p>
            )}
            <textarea
                value={value}
                onChange={(e) => onAnswer(question.id, e.target.value)}
                className="border-border bg-background focus:border-primary focus:ring-primary w-full rounded-lg border px-4 py-3 text-sm focus:ring-1 focus:outline-none disabled:opacity-60"
                rows={3}
                placeholder="Écris ta réponse…"
                aria-label="Ta réponse"
                disabled={disabled}
            />
        </div>
    );
}
