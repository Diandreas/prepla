import { CHAMP_DANS_LA_PHRASE, ChoiceOption, lettreDuChoix, verdictDuChoix } from './choice-option';
import { normalizeOptions } from './normalize-options';

type GapAnswer = string | Record<string, string>;

interface GapFillProps {
    question: {
        id: string;
        text: string;
        options?: unknown;
        correct_answer?: unknown;
    };
    onAnswer: (questionId: string, answer: GapAnswer) => void;
    selectedAnswer?: GapAnswer;
    disabled?: boolean;
    /** Correction montree ; par defaut `disabled`, comme avant (voir mcq.tsx). */
    revealed?: boolean;
}

/** Champ inline dont la largeur suit ce qui est tapé, pour rester fluide dans la phrase. */
function gapWidth(value: string) {
    return Math.max(7, (value.length || 8) + 2);
}

/**
 * Gap-fill : on écrit DIRECTEMENT dans le blanc, à l'intérieur de la phrase
 * (champ inline à la place du ___), au lieu d'un input séparé en bas.
 *
 * Une phrase peut porter plusieurs blancs : chacun reçoit son champ. Avant, seul
 * le premier était remplissable et les suivants restaient affichés en « ___ » —
 * l'exercice était alors impossible à terminer. Avec plusieurs blancs, la réponse
 * part sous forme de dictionnaire indexé par blanc ; le serveur en recolle les
 * valeurs dans l'ordre, exactement comme le fait la correction locale.
 */
export function GapFill({ question, onAnswer, selectedAnswer, disabled, revealed = disabled }: GapFillProps) {
    const raw = question.text ?? '';
    const options = normalizeOptions(question.options);
    const parts = raw.split(/_{2,}/);
    const gapCount = Math.max(1, parts.length - 1);

    // mcq-cloze (seeded) maps to this component WITH options → mode QCM inline.
    if (options.length > 0) {
        const correct = String(question.correct_answer ?? '').trim().toUpperCase();
        const selLetter = (typeof selectedAnswer === 'string' ? selectedAnswer : '').toUpperCase();
        const selIdx = selLetter.length === 1 ? selLetter.charCodeAt(0) - 65 : -1;
        return (
            <div className="space-y-4">
                <p data-enonce className="lecteur-enonce leading-relaxed">
                    {parts[0]}
                    <span className="mx-1 inline-block min-w-[80px] border-b-2 border-primary px-1 text-center text-primary">
                        {selIdx >= 0 && selIdx < options.length ? options[selIdx] : ' '}
                    </span>
                    {parts.slice(1).join('___')}
                </p>
                <div className="grid gap-2.5">
                    {options.map((opt, i) => {
                        const letter = lettreDuChoix(i);
                        const isSel = selLetter === letter;
                        return (
                            <ChoiceOption
                                key={i}
                                index={i}
                                letter={letter}
                                selected={isSel}
                                verdict={verdictDuChoix(!!revealed, correct, letter, isSel)}
                                disabled={disabled}
                                onSelect={() => onAnswer(question.id, letter)}
                            >
                                {opt}
                            </ChoiceOption>
                        );
                    })}
                </div>
            </div>
        );
    }

    // Un seul blanc : la réponse reste une chaîne simple, comme avant.
    if (gapCount === 1) {
        const value = typeof selectedAnswer === 'string' ? selectedAnswer : '';
        const before = parts[0] ?? '';
        const after = parts[1] ?? '';

        return (
            <div className="space-y-2">
                <p data-enonce className="lecteur-enonce leading-[3.25rem]">
                    {before}
                    <input
                        type="text"
                        value={value}
                        onChange={(e) => onAnswer(question.id, e.target.value)}
                        disabled={disabled}
                        aria-label={`Compléter le blanc dans la phrase : ${before}…${after}`}
                        autoComplete="off"
                        autoCapitalize="off"
                        spellCheck={false}
                        placeholder="…"
                        style={{ width: `${gapWidth(value)}ch` }}
                        className={CHAMP_DANS_LA_PHRASE}
                    />
                    {after}
                </p>
            </div>
        );
    }

    // Plusieurs blancs : un champ par blanc, réponse indexée par position.
    const values: Record<string, string> =
        selectedAnswer && typeof selectedAnswer === 'object' ? selectedAnswer : {};

    const setGap = (index: number, next: string) => {
        onAnswer(question.id, { ...values, [String(index)]: next });
    };

    return (
        <div className="space-y-2">
            <p data-enonce className="lecteur-enonce leading-[3.25rem]">
                {parts.map((part, index) => {
                    const value = values[String(index)] ?? '';
                    return (
                        <span key={index}>
                            {part}
                            {index < parts.length - 1 && (
                                <input
                                    type="text"
                                    value={value}
                                    onChange={(e) => setGap(index, e.target.value)}
                                    disabled={disabled}
                                    aria-label={`Blanc ${index + 1} sur ${gapCount}`}
                                    autoComplete="off"
                                    autoCapitalize="off"
                                    spellCheck={false}
                                    placeholder="…"
                                    style={{ width: `${gapWidth(value)}ch` }}
                                    className={CHAMP_DANS_LA_PHRASE}
                                />
                            )}
                        </span>
                    );
                })}
            </p>
            <p className="text-[0.8125rem] text-muted-foreground">
                Cette phrase compte {gapCount} blancs : remplis-les tous avant de vérifier.
            </p>
        </div>
    );
}
