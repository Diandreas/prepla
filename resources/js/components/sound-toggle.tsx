import { useEffect, useState } from 'react';
import { setSoundMuted, playSound } from '@/hooks/use-sound';

const STORAGE_KEY = 'prepla-sound-muted';

/**
 * Settings control to mute/unmute the app's UI sound effects. Haptics (vibration)
 * are unaffected. Persists in localStorage (read by use-sound).
 */
export function SoundToggle() {
    const [muted, setMuted] = useState(false);

    useEffect(() => {
        try {
            setMuted(localStorage.getItem(STORAGE_KEY) === '1');
        } catch {
            /* no-op */
        }
    }, []);

    const toggle = () => {
        const next = !muted;
        setMuted(next);
        setSoundMuted(next);
        if (!next) playSound('pop'); // little confirmation when re-enabling
    };

    return (
        <div className="flex items-center justify-between gap-4 rounded-2xl border bg-card p-4">
            <div className="flex items-center gap-3">
                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                    {muted ? (
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><line x1="23" y1="9" x2="17" y2="15"/><line x1="17" y1="9" x2="23" y2="15"/></svg>
                    ) : (
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>
                    )}
                </div>
                <div>
                    <p className="text-sm font-bold">Effets sonores</p>
                    <p className="text-xs text-muted-foreground">
                        {muted ? 'Désactivés' : 'Sons de réussite, erreur et navigation'}
                    </p>
                </div>
            </div>
            <button
                onClick={toggle}
                role="switch"
                aria-checked={!muted}
                className={`relative h-7 w-12 shrink-0 rounded-full transition-colors ${muted ? 'bg-muted' : 'bg-primary'}`}
            >
                <span
                    className={`absolute top-1 h-5 w-5 rounded-full bg-white shadow transition-transform ${muted ? 'left-1' : 'left-6'}`}
                />
            </button>
        </div>
    );
}
