interface TrueFalseNgProps {
    question: {
        id: string;
        text: string;
        correct_answer?: unknown;
    };
    onAnswer: (questionId: string, answer: string) => void;
    selectedAnswer?: string;
    disabled?: boolean;
}

const options = [
    { value: 'True', label: 'Vrai' },
    { value: 'False', label: 'Faux' },
    { value: 'Not Given', label: 'Non mentionné' },
];

/**
 * Vrai / Faux / Non mentionné.
 *
 * Le composant ignorait `disabled` : après correction les trois choix restaient
 * cliquables, sans effet — le joueur refuse la réponse une fois vérifiée —, et
 * surtout rien ne montrait lequel était le bon. Un apprenant lisait « Incorrect »
 * sans savoir quoi retenir, alors que le QCM, lui, l'affiche.
 */
export function TrueFalseNg({ question, onAnswer, selectedAnswer, disabled }: TrueFalseNgProps) {
    const expected = String(question.correct_answer ?? '').trim().toLowerCase();

    return (
        <div className="space-y-4">
            <p className="text-lg font-medium">{question.text}</p>
            <div className="flex gap-3">
                {options.map(({ value, label }) => {
                    const isSelected = selectedAnswer === value;
                    const isExpected = disabled && expected !== '' && expected === value.toLowerCase();
                    const isWrongPick = disabled && isSelected && !isExpected;

                    return (
                        <button
                            key={value}
                            onClick={() => !disabled && onAnswer(question.id, value)}
                            disabled={disabled}
                            aria-pressed={isSelected}
                            className={`duo-press flex-1 rounded-lg border-2 p-3 text-center font-medium ${
                                isExpected
                                    ? 'border-emerald-400 bg-emerald-50 text-emerald-950 dark:bg-emerald-950/40 dark:text-emerald-100'
                                    : isWrongPick
                                      ? 'border-red-400 bg-red-50 text-red-950 dark:bg-red-950/40 dark:text-red-100'
                                      : isSelected
                                        ? 'border-primary bg-primary/5'
                                        : 'border-border'
                            }`}
                            style={{
                                boxShadow: isSelected && !disabled ? '0 4px 0 0 var(--primary, #4A90E2)' : '0 3px 0 0 #e5e7eb',
                            }}
                        >
                            {label}
                            {isExpected && <span className="sr-only"> — Bonne réponse</span>}
                            {isWrongPick && <span className="sr-only"> — Réponse incorrecte</span>}
                        </button>
                    );
                })}
            </div>
        </div>
    );
}
