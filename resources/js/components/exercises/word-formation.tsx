import { CHAMP_DANS_LA_PHRASE } from './choice-option';

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
            <div data-enonce className="rounded-2xl border-[1.5px] bg-muted/30 p-4 text-base leading-[3.25rem] sm:p-6">
                {parts.map((part, index) => (
                    <span key={index}>
                        {part}
                        {index < parts.length - 1 && (
                            <input
                                type="text"
                                value={value}
                                onChange={(e) => onAnswer(question.id, e.target.value)}
                                aria-label="Forme dérivée à écrire"
                                autoComplete="off"
                                autoCapitalize="off"
                                spellCheck={false}
                                className={`${CHAMP_DANS_LA_PHRASE} w-40`}
                                disabled={disabled}
                            />
                        )}
                    </span>
                ))}
            </div>

            <div className="flex items-center gap-2">
                <span className="text-muted-foreground text-[0.9375rem]">Mot racine :</span>
                <span className="bg-primary/10 text-primary rounded-lg px-3 py-1 font-mono text-base font-bold">
                    {question.root_word}
                </span>
            </div>
        </div>
    );
}
