import axios from 'axios';
import { useEffect, useRef, useState } from 'react';
import { Mic, ImagePlus, Square } from 'lucide-react';

export function TutorInputTools({ disabled, onText, onBusy }: { disabled: boolean; onText: (text: string) => void; onBusy: (busy: boolean) => void }) {
    const [phase, setPhase] = useState<'idle' | 'permission' | 'recording' | 'transcribing' | 'image'>('idle');
    const [error, setError] = useState('');
    const recorder = useRef<MediaRecorder | null>(null);
    const stream = useRef<MediaStream | null>(null);
    const timer = useRef<ReturnType<typeof setTimeout> | null>(null);
    const alive = useRef(true);
    const discard = useRef(false);
    const fileInput = useRef<HTMLInputElement>(null);
    const abort = useRef<AbortController | null>(null);
    useEffect(() => { onBusy(phase !== 'idle'); }, [phase, onBusy]);
    useEffect(() => {
        alive.current = true;
        return () => {
            alive.current = false;
            discard.current = true;
            if (timer.current) clearTimeout(timer.current);
            if (recorder.current?.state === 'recording') recorder.current.stop();
            stream.current?.getTracks().forEach(track => track.stop());
            abort.current?.abort();
        };
    }, []);

    async function convert(file: Blob, kind: 'audio' | 'image', filename: string) {
        if (!alive.current) return;
        setPhase(kind === 'audio' ? 'transcribing' : 'image');
        setError('');
        const data = new FormData();
        data.append(kind, file, filename);
        abort.current = new AbortController();
        try {
            const response = await axios.post(route(kind === 'audio' ? 'ai-tools.explainer.transcribe' : 'ai-tools.explainer.image'), data, { signal: abort.current.signal });
            const text = typeof response.data.text === 'string' ? response.data.text.trim() : '';
            if (!text) throw new Error('Aucun texte reconnu. Réessaie avec un enregistrement audible ou une photo plus nette.');
            if (alive.current) onText(kind === 'image' ? `Texte extrait de mon image :\n${text}` : text);
        } catch (e) {
            if (alive.current) setError(axios.isAxiosError(e) ? e.response?.data?.error ?? 'Lecture impossible. Vérifie le fichier et ta connexion, puis réessaie.' : e instanceof Error ? e.message : 'Lecture impossible.');
        } finally {
            if (alive.current) setPhase('idle');
        }
    }

    function stop(cancel = false) {
        discard.current = cancel;
        if (timer.current) clearTimeout(timer.current);
        if (recorder.current?.state === 'recording') recorder.current.stop();
        stream.current?.getTracks().forEach(track => track.stop());
        if (cancel) setPhase('idle');
    }

    async function start() {
        if (phase !== 'idle' || disabled) return;
        setError('');
        if (!navigator.mediaDevices?.getUserMedia || typeof MediaRecorder === 'undefined') {
            setError('Le micro n’est pas disponible dans ce navigateur. Tu peux écrire ta question ou joindre une photo.');
            return;
        }
        setPhase('permission');
        try {
            const audio = await navigator.mediaDevices.getUserMedia({ audio: true });
            if (!alive.current) { audio.getTracks().forEach(track => track.stop()); return; }
            stream.current = audio;
            discard.current = false;
            const mime = ['audio/webm;codecs=opus', 'audio/mp4', 'audio/ogg;codecs=opus'].find(type => MediaRecorder.isTypeSupported(type));
            const recording = new MediaRecorder(audio, mime ? { mimeType: mime } : undefined);
            recorder.current = recording;
            const chunks: Blob[] = [];
            recording.ondataavailable = event => { if (event.data.size) chunks.push(event.data); };
            recording.onstop = () => {
                audio.getTracks().forEach(track => track.stop());
                if (!alive.current || discard.current) return;
                const type = recording.mimeType || 'audio/webm';
                const blob = new Blob(chunks, { type });
                if (blob.size > 10 * 1024 * 1024) { setError('Enregistrement trop volumineux. Fais une prise plus courte.'); setPhase('idle'); return; }
                void convert(blob, 'audio', `question.${type.includes('mp4') ? 'm4a' : type.includes('ogg') ? 'ogg' : 'webm'}`);
            };
            recording.onerror = () => { stop(true); setError('Enregistrement interrompu. Réessaie.'); };
            recording.start(250);
            setPhase('recording');
            timer.current = setTimeout(() => stop(), 90000);
        } catch {
            stream.current?.getTracks().forEach(track => track.stop());
            if (alive.current) { setPhase('idle'); setError('Accès au micro refusé ou indisponible. Vérifie les autorisations du navigateur.'); }
        }
    }

    return <div className="mb-3 space-y-2">
        <div className="flex flex-wrap gap-2">
            <button type="button" disabled={disabled || (phase !== 'idle' && phase !== 'recording')} onClick={() => phase === 'recording' ? stop() : void start()} className="inline-flex items-center gap-2 rounded-xl border border-border px-3 py-2 text-xs font-semibold disabled:opacity-50">
                {phase === 'recording' ? <Square size={16} /> : <Mic size={16} />}{phase === 'recording' ? 'Arrêter et transcrire' : 'Dicter ma question'}
            </button>
            {phase === 'recording' && <button type="button" onClick={() => stop(true)} className="text-xs underline">Annuler</button>}
            <button type="button" disabled={disabled || phase !== 'idle'} onClick={() => fileInput.current?.click()} className="inline-flex items-center gap-2 rounded-xl border border-border px-3 py-2 text-xs font-semibold disabled:opacity-50"><ImagePlus size={16} />Lire une photo</button>
            <input ref={fileInput} type="file" accept="image/jpeg,image/png,image/webp" className="hidden" aria-label="Photo du texte ou de l’exercice" onChange={event => {
                const file = event.target.files?.[0]; event.target.value = '';
                if (!file) return;
                if (file.size > 8 * 1024 * 1024) { setError('Choisis une image de moins de 8 Mo.'); return; }
                void convert(file, 'image', file.name);
            }} />
        </div>
        <p role="status" className="text-xs text-muted-foreground">{phase === 'recording' ? 'Le micro enregistre… 90 secondes maximum.' : phase === 'permission' ? 'Autorise le micro dans ton navigateur.' : phase === 'transcribing' ? 'Transcription en cours…' : phase === 'image' ? 'Lecture du texte de la photo…' : 'Audio et photos sont transmis au service IA pour en extraire le texte. Relis-le avant d’envoyer ; les schémas ne sont pas analysés.'}</p>
        {error && <p role="alert" className="text-xs text-red-600 dark:text-red-400">{error}</p>}
    </div>;
}
