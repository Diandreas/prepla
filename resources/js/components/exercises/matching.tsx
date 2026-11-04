import { ChoiceOption, lettreDuChoix, verdictDuChoix } from './choice-option';
import { normalizeOptions } from './normalize-options';

interface MatchingProps {
    question: {
        id: string;
        text: string;
        options: unknown;
        items?: string[];
        correct_answer?: unknown;
        correct?: unknown;
    };
    onAnswer: (questionId: string, answer: string) => void;
    selectedAnswer?: string;
    disabled?: boolean;
    /** Correction montree ; par defaut `disabled`, comme avant (voir mcq.tsx). */
    revealed?: boolean;
}

export function Matching({ question, onAnswer, selectedAnswer, disabled, revealed = disabled }: MatchingProps) {
    const options = normalizeOptions(question.options);
    // correct_answer peut être string/tableau/objet selon le générateur IA :
    // coerce défensivement pour éviter un crash (.trim is not a function).
    const rawCorrect = question.correct_answer ?? question.correct ?? '';
    const correctAnswer = (Array.isArray(rawCorrect) ? String(rawCorrect[0] ?? '') : typeof rawCorrect === 'object' ? '' : String(rawCorrect)).trim().toUpperCase();

    return (
        <div className="space-y-4">
            {/* Ce qu'il faut associer : souvent une situation, pas un « terme ».
                Le libelle disait « Associez ce terme », au vouvoiement, quand tout
                le reste de l'interface tutoie. */}
            <div className="rounded-2xl border-[1.5px] border-primary/25 bg-primary/5 px-5 py-4">
                <p className="mb-1 text-[0.8125rem] font-semibold text-primary">Choisis ce qui correspond</p>
                <p data-enonce className="lecteur-enonce whitespace-pre-line">{question.text}</p>
            </div>
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
