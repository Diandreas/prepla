interface KeyWordTransformationProps {
    question: {
        id: string;
        original_sentence: string;
        key_word?: string;
        keyword?: string;
        start_of_answer?: string;
    };
    onAnswer: (questionId: string, answer: string) => void;
    selectedAnswer?: string;
    disabled?: boolean;
}

/**
 * Transformation : réécrire une phrase en réutilisant un mot imposé.
 *
 * Consigne et libellés en français : l'exercice s'adressait à l'apprenant en anglais,
 * y compris quand la langue étudiée est l'allemand.
 */
export function KeyWordTransformation({ question, onAnswer, selectedAnswer, disabled }: KeyWordTransformationProps) {
    const value = selectedAnswer ?? '';

    return (
        <div className="space-y-4">
            <p className="text-muted-foreground text-sm">
                Complète la seconde phrase pour qu'elle ait le même sens que la première, en utilisant le mot
                imposé. Entre 2 et 5 mots, mot imposé compris, sans le modifier.
            </p>

            <div className="space-y-3 rounded-xl border bg-muted/30 p-4">
                <p className="text-sm font-medium">{question.original_sentence}</p>
                <div className="flex items-center gap-2">
                    <span className="text-muted-foreground text-sm">Mot imposé :</span>
                    <span className="bg-primary/10 text-primary rounded px-3 py-1 font-mono text-sm font-bold uppercase">
                        {question.key_word ?? question.keyword}
                    </span>
                </div>
            </div>

            <div className="flex items-center gap-1 text-sm">
                {question.start_of_answer && <span className="font-medium">{question.start_of_answer}</span>}
                <input
                    type="text"
                    value={value}
                    onChange={(e) => onAnswer(question.id, e.target.value)}
                    aria-label="Fin de la phrase à écrire"
                    className="border-border bg-background focus:border-primary focus:ring-primary flex-1 rounded-lg border px-4 py-3 text-sm focus:ring-1 focus:outline-none disabled:opacity-60"
                    placeholder="Écris les mots manquants…"
                    disabled={disabled}
                />
            </div>
        </div>
    );
}
