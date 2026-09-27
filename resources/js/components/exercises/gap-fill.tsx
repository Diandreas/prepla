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
}

/** Champ inline dont la largeur suit ce qui est tapé, pour rester fluide dans la phrase. */
function gapWidth(value: string) {
    return Math.max(6, (value.length || 8) + 1);
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
export function GapFill({ question, onAnswer, selectedAnswer, disabled }: GapFillProps) {
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
            <div className="space-y-3">
                <p className="text-lg font-medium leading-relaxed">
                    {parts[0]}
                    <span className="mx-1 inline-block min-w-[80px] border-b-2 border-primary px-1 text-center font-bold text-primary">
                        {selIdx >= 0 && selIdx < options.length ? options[selIdx] : ' '}
                    </span>
                    {parts.slice(1).join('___')}
                </p>
                <div className="grid gap-2">
                    {options.map((opt, i) => {
                        const letter = String.fromCharCode(65 + i);
                        const isSel = selLetter === letter;
                        const isCorrect = disabled && correct === letter;
                        const isWrong = disabled && isSel && correct !== letter;
                        return (
                            <button
                                key={i}
                                type="button"
                                onClick={() => !disabled && onAnswer(question.id, letter)}
                                disabled={disabled}
                                aria-pressed={isSel}
                                className={`rounded-xl border-2 p-3 text-left text-sm transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background ${
                                    isCorrect ? 'border-emerald-400 bg-emerald-50 text-emerald-950 dark:bg-emerald-950/40 dark:text-emerald-100'
                                    : isWrong ? 'border-red-400 bg-red-50 text-red-950 dark:bg-red-950/40 dark:text-red-100'
                                    : isSel ? 'border-primary bg-primary/5'
                                    : 'border-border enabled:hover:border-primary/50 disabled:opacity-50'}`}
                            >
                                <span className={`mr-2.5 inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold ${
                                    isCorrect ? 'bg-emerald-700 text-white' : isWrong ? 'bg-red-700 text-white' : 'bg-muted text-muted-foreground'
                                }`}>{letter}</span>
                                {opt}
                                {isCorrect && <span className="sr-only"> — Bonne réponse</span>}
                                {isWrong && <span className="sr-only"> — Réponse incorrecte</span>}
                            </button>
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
                <p className="text-lg font-medium leading-relaxed">
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
                        className="mx-1 inline-block max-w-full border-b-2 border-primary bg-primary/5 px-1.5 py-0.5 text-center font-bold text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:opacity-60"
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
            <p className="text-lg font-medium leading-relaxed">
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
                                    className="mx-1 inline-block max-w-full border-b-2 border-primary bg-primary/5 px-1.5 py-0.5 text-center font-bold text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:opacity-60"
                                />
                            )}
                        </span>
                    );
                })}
            </p>
            <p className="text-xs text-muted-foreground">
                Cette phrase compte {gapCount} blancs : remplis-les tous avant de vérifier.
            </p>
        </div>
    );
}
