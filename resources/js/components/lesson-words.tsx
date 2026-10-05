import axios from 'axios';
import { useState } from 'react';

export interface LessonWord { id: number; word: string; translation: string; example: string; saved: boolean }
export function LessonWords({ words }: { words: LessonWord[] }) {
    const [saved, setSaved] = useState(() => new Set(words.filter(w => w.saved).map(w => w.id)));
    const [pending, setPending] = useState<number | null>(null);
    const [error, setError] = useState('');
    if (!words.length) return null;
    return <details className="mb-5 rounded-2xl border border-primary/15 bg-primary/5 p-4">
        <summary className="cursor-pointer text-sm font-bold">Les mots utiles de cette mission <span className="text-muted-foreground">· {words.length} mots</span></summary>
        <p className="mt-2 text-xs text-muted-foreground">Observe leur utilisation, puis garde ceux que tu veux retrouver dans « Mes mots ».</p>
        <div className="mt-3 space-y-3">{words.map(word => <div key={word.id} className="rounded-xl bg-card p-3">
            <div className="flex items-start justify-between gap-3"><p className="font-bold">{word.word} <span className="font-normal text-muted-foreground">· {word.translation}</span></p>
            <button disabled={saved.has(word.id) || pending !== null} className="shrink-0 text-xs font-semibold text-primary disabled:text-muted-foreground" onClick={async () => {
                setPending(word.id); setError('');
                try { await axios.post(route('dictionary.save'), { dictionary_word_id: word.id }); setSaved(previous => new Set([...previous, word.id])); }
                catch { setError('Ce mot n’a pas été enregistré. Réessaie.'); } finally { setPending(null); }
            }}>{saved.has(word.id) ? '✓ Gardé' : pending === word.id ? '…' : '+ Garder'}</button></div>
            <p className="mt-1 text-sm leading-relaxed text-muted-foreground">{word.example}</p>
        </div>)}</div>
        {error && <p role="alert" className="mt-3 text-sm text-destructive">{error}</p>}
    </details>;
}
