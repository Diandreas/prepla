import { useTts } from '@/hooks/use-tts';

interface SpeakButtonProps {
    /** Texte à lire à voix haute (TTS Deepgram + fallback navigateur). */
    text: string;
    /** Langue : slug DB ("english"/"french"/"german") ou code court ("en"/"fr"/"de"). */
    lang?: string;
    /** Libellé optionnel à afficher à côté de l'icône. */
    label?: string;
    className?: string;
    /** Variante compacte (icône seule) pour intégration dans une bulle de dialogue. */
    compact?: boolean;
}

/**
 * Bouton "Écouter" réutilisable. Utilise Deepgram côté serveur avec repli
 * automatique sur la synthèse vocale du navigateur, dans la bonne langue.
 */
export function SpeakButton({ text, lang = 'en', label, className = '', compact = false }: SpeakButtonProps) {
    const { speak, stop, isSpeaking } = useTts();

    if (!text || !text.trim()) return null;

    const handleClick = () => (isSpeaking ? stop() : speak(text, lang));

    const Icon = isSpeaking ? (
        // Pause / stop
        <svg viewBox="0 0 20 20" fill="currentColor" className="h-4 w-4">
            <path d="M5.75 3a.75.75 0 00-.75.75v12.5c0 .414.336.75.75.75h1.5a.75.75 0 00.75-.75V3.75A.75.75 0 007.25 3h-1.5zM12.75 3a.75.75 0 00-.75.75v12.5c0 .414.336.75.75.75h1.5a.75.75 0 00.75-.75V3.75a.75.75 0 00-.75-.75h-1.5z" />
        </svg>
    ) : (
        // Speaker
        <svg viewBox="0 0 20 20" fill="currentColor" className="h-4 w-4">
            <path d="M10 3.75a.75.75 0 00-1.264-.546L5.203 6.5H3.667A1.667 1.667 0 002 8.167v3.666A1.667 1.667 0 003.667 13.5h1.536l3.533 3.296A.75.75 0 0010 16.25V3.75zM13.78 7.22a.75.75 0 10-1.06 1.06 2.5 2.5 0 010 3.54.75.75 0 101.06 1.06 4 4 0 000-5.66zM15.9 5.1a.75.75 0 10-1.06 1.06 5.5 5.5 0 010 7.78.75.75 0 101.06 1.06 7 7 0 000-9.9z" />
        </svg>
    );

    if (compact) {
        return (
            <button
                type="button"
                onClick={handleClick}
                title={isSpeaking ? 'Arrêter' : 'Écouter'}
                className={`inline-flex items-center justify-center rounded-full p-1.5 text-primary transition hover:bg-primary/10 ${isSpeaking ? 'animate-pulse' : ''} ${className}`}
            >
                {Icon}
            </button>
        );
    }

    return (
        <button
            type="button"
            onClick={handleClick}
            className={`inline-flex items-center gap-1.5 rounded-lg border border-primary/40 bg-primary/5 px-3 py-1.5 text-xs font-medium text-primary transition hover:bg-primary/10 ${isSpeaking ? 'animate-pulse' : ''} ${className}`}
        >
            {Icon}
            <span>{label ?? (isSpeaking ? 'Arrêter' : 'Écouter')}</span>
        </button>
    );
}
