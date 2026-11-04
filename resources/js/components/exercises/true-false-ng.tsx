import { ChoiceOption, verdictDuChoix } from './choice-option';

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
    /** Correction montree ; par defaut `disabled`, comme avant (voir mcq.tsx). */
    revealed?: boolean;
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
export function TrueFalseNg({ question, onAnswer, selectedAnswer, disabled, revealed = disabled }: TrueFalseNgProps) {
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
            <p data-enonce className="lecteur-enonce whitespace-pre-line">{question.text}</p>
            <div className={`grid gap-2.5 ${options.length === 2 ? 'grid-cols-2' : 'grid-cols-1 sm:grid-cols-3'}`}>
                {options.map(({ value, label }, i) => {
                    const isSelected = selectedAnswer === value;

                    return (
                        <ChoiceOption
                            key={value}
                            index={i}
                            align="centre"
                            selected={isSelected}
                            verdict={verdictDuChoix(!!revealed, expected, value.toLowerCase(), isSelected)}
                            disabled={disabled}
                            onSelect={() => onAnswer(question.id, value)}
                        >
                            {label}
                        </ChoiceOption>
                    );
                })}
            </div>
        </div>
    );
}
