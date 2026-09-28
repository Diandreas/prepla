interface WordFormationProps {
    question: { id: string; text: string; root_word: string };
    onAnswer: (questionId: string, answer: string) => void;
    selectedAnswer?: string;
    disabled?: boolean;
}

/**
 * Formation de mots : dériver la bonne forme à partir d'un mot racine.
 *
 * La phrase est rendue en entier. Elle était coupée au deuxième blanc — seuls le
 * début et le segment suivant s'affichaient, la fin disparaissait sans trace, et
 * l'apprenant devait deviner une forme sans voir le contexte qui la détermine.
 */
export function WordFormation({ question, onAnswer, selectedAnswer, disabled }: WordFormationProps) {
    const value = selectedAnswer ?? '';
    const parts = (question.text ?? '').split('___');

    return (
        <div className="space-y-4">
            <div className="rounded-xl border bg-muted/30 p-6 text-sm leading-relaxed">
                {parts.map((part, index) => (
                    <span key={index}>
                        {part}
                        {index < parts.length - 1 && (
                            <input
                                type="text"
                                value={value}
                                onChange={(e) => onAnswer(question.id, e.target.value)}
                                aria-label="Forme dérivée à écrire"
                                className="border-primary bg-background mx-1 inline-block w-32 rounded border-b-2 px-2 py-0.5 text-center text-sm focus:outline-none disabled:opacity-60"
                                disabled={disabled}
                            />
                        )}
                    </span>
                ))}
            </div>

            <div className="flex items-center gap-2">
                <span className="text-muted-foreground text-sm">Mot racine :</span>
                <span className="bg-primary/10 text-primary rounded px-3 py-1 font-mono text-sm font-bold">
                    {question.root_word}
                </span>
            </div>
        </div>
    );
}
