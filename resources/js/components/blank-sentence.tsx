/**
 * Une phrase à trou où l'on écrit DANS la phrase.
 *
 * Ailleurs, l'énoncé s'affichait tel quel — « Gestern _______ ich … » — avec un
 * champ séparé en dessous : l'apprenant tapait un mot dans le vide, sans voir la
 * phrase se construire. Ici le champ prend la place du trou, à sa taille, et la
 * phrase se lit en entier pendant qu'on la complète.
 *
 * Le lecteur d'exercices a déjà cette mécanique dans gap-fill ; ce composant la
 * rend disponible partout ailleurs (révision des erreurs, entraînement ciblé).
 */
interface BlankSentenceProps {
    text: string;
    value: string;
    onChange: (value: string) => void;
    onSubmit?: () => void;
    disabled?: boolean;
    placeholder?: string;
}

export function BlankSentence({ text, value, onChange, onSubmit, disabled, placeholder }: BlankSentenceProps) {
    const parts = (text ?? '').split(/_{2,}/);

    // Pas de trou dans l'énoncé (question ouverte) : on garde la phrase puis un
    // champ pleine largeur, plutôt que d'inventer un emplacement.
    if (parts.length < 2) {
        return (
            <div className="space-y-2">
                <p className="text-base font-medium leading-relaxed text-slate-800 dark:text-slate-200">{text}</p>
                <input
                    value={value}
                    onChange={(event) => onChange(event.target.value)}
                    onKeyDown={(event) => event.key === 'Enter' && onSubmit?.()}
                    disabled={disabled}
                    placeholder={placeholder}
                    className="w-full rounded-xl border-2 border-slate-200 px-4 py-3 font-semibold focus:border-indigo-400 focus:outline-none disabled:opacity-60 dark:border-slate-700 dark:bg-slate-900"
                />
            </div>
        );
    }

    return (
        <p className="text-base font-medium leading-relaxed text-slate-800 dark:text-slate-200">
            {parts[0]}
            <input
                value={value}
                onChange={(event) => onChange(event.target.value)}
                onKeyDown={(event) => event.key === 'Enter' && onSubmit?.()}
                disabled={disabled}
                placeholder={placeholder}
                aria-label="Mot manquant"
                // La largeur suit ce qui est écrit : un champ figé casse la lecture.
                size={Math.max(8, value.length + 2)}
                className="mx-1 inline-block min-w-[6rem] border-0 border-b-2 border-indigo-400 bg-transparent px-1 text-center font-bold text-indigo-700 focus:outline-none focus:border-indigo-600 disabled:opacity-70 dark:text-indigo-300"
            />
            {parts.slice(1).join('___')}
        </p>
    );
}
