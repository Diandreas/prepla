import { ChoiceOption, lettreDuChoix, verdictDuChoix } from './choice-option';
import { normalizeOptions } from './normalize-options';

interface McqProps {
    question: {
        id: string;
        text: string;
        options: unknown;
        correct_answer?: unknown;
        correct?: unknown;
    };
    onAnswer: (questionId: string, answer: string) => void;
    selectedAnswer?: string;
    disabled?: boolean;
    /**
     * La correction est-elle montree ? Par defaut, comme avant, `disabled` la
     * montre. Le lecteur d'exercices verrouille pendant l'envoi SANS reveler :
     * il passe donc `revealed={false}`.
     */
    revealed?: boolean;
}

export function Mcq({ question, onAnswer, selectedAnswer, disabled, revealed = disabled }: McqProps) {
    // correct_answer peut arriver sous forme de string, tableau ou objet selon le
    // générateur IA. On coerce en string de façon défensive pour éviter un crash
    // (.trim is not a function) qui blanchirait toute la page d'exercice.
    const rawCorrect = question.correct_answer ?? question.correct ?? '';
    const correctAnswer = (Array.isArray(rawCorrect) ? String(rawCorrect[0] ?? '') : typeof rawCorrect === 'object' ? '' : String(rawCorrect)).trim().toUpperCase();

    const options = normalizeOptions(question.options);

    return (
        <div className="space-y-4">
            <p data-enonce className="lecteur-enonce whitespace-pre-line">{question.text}</p>
            <div className="grid gap-2.5">
                {options.map((option, i) => {
                    const letter = lettreDuChoix(i);
                    const isSelected = selectedAnswer === letter;

                    return (
                        <ChoiceOption
                            key={i}
                            index={i}
                            letter={letter}
                            selected={isSelected}
                            verdict={verdictDuChoix(!!revealed, correctAnswer, letter, isSelected)}
                            disabled={disabled}
                            onSelect={() => onAnswer(question.id, letter)}
                        >
                            {option}
                        </ChoiceOption>
                    );
                })}
            </div>
        </div>
    );
}
