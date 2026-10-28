interface TrueFalseNgProps {
    question: {
        id: string;
        text: string;
        correct_answer?: unknown;
        /** Les choix réellement offerts par la partie d'examen, quand elle n'en offre pas trois. */
        answer_options?: unknown;
    };
    onAnswer: (questionId: string, answer: string) => void;
    selectedAnswer?: string;
    disabled?: boolean;
}

const LIBELLES: Record<string, string> = {
    true: 'Vrai',
    false: 'Faux',
    'not given': 'Non mentionné',
    richtig: 'Richtig',
    falsch: 'Falsch',
    ja: 'Ja',
    nein: 'Nein',
};

const PAR_DEFAUT = [
    { value: 'True', label: 'Vrai' },
    { value: 'False', label: 'Faux' },
    { value: 'Not Given', label: 'Non mentionné' },
];

/**
 * Vrai / Faux / Non mentionné — ou seulement deux choix quand l'épreuve n'en
 * offre que deux.
 *
 * Le composant affichait toujours les TROIS boutons. Or les parties Ja/Nein du
 * Goethe et de telc, et les Richtig/Falsch, n'en proposent que deux : on
 * entraînait l'apprenant devant un « Non mentionné » qui n'existe pas le jour
 * de l'examen, et qu'il pouvait cocher. Une question peut donc déclarer ses
 * propres choix via `answer_options` ; sans cette clé, les trois restent, pour
 * les exercices déjà en base.
 *
 * Le composant ignorait aussi `disabled` : après correction les trois choix
 * restaient cliquables, sans effet — le joueur refuse la réponse une fois
 * vérifiée —, et surtout rien ne montrait lequel était le bon. Un apprenant
 * lisait « Incorrect » sans savoir quoi retenir, alors que le QCM, lui,
 * l'affiche.
 */
export function TrueFalseNg({ question, onAnswer, selectedAnswer, disabled }: TrueFalseNgProps) {
    const expected = String(question.correct_answer ?? '').trim().toLowerCase();

    const declares = Array.isArray(question.answer_options)
        ? question.answer_options.filter((v): v is string => typeof v === 'string' && v.trim() !== '')
        : [];
    const options = declares.length >= 2
        ? declares.map((value) => ({ value, label: LIBELLES[value.trim().toLowerCase()] ?? value }))
        : PAR_DEFAUT;

    return (
        <div className="space-y-4">
            {/* Les sauts de ligne comptent : un enonce porte souvent le panneau
                puis, en dessous, l'affirmation a juger. */}
            <p className="text-lg font-medium whitespace-pre-line">{question.text}</p>
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
