import { useState } from 'react';
import { useAudioRecorder } from '@/hooks/use-audio-recorder';
import { useTts } from '@/hooks/use-tts';

function csrfToken(): string {
    const cookie = document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN='));
    if (cookie) return decodeURIComponent(cookie.split('=')[1]);
    return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? '';
}

interface ListenRepeatProps {
    question: {
        id: string;
        audio_text?: string;
        correct_answer?: string;
        audio_url?: string;
    };
    onAnswer: (questionId: string, answer: string) => void;
    selectedAnswer?: string;
    disabled?: boolean;
    lang?: string;
}

/**
 * Listen & Repeat (TOEFL 2026 / pronunciation) : on joue une phrase cible, l'élève
 * la répète, on transcrit son audio et on compare à la cible (score de fidélité).
 */
export function ListenRepeat({ question, onAnswer, selectedAnswer, disabled, lang = 'en' }: ListenRepeatProps) {
    const { speak, stop, isSpeaking } = useTts();
    const { isRecording, audioUrl, audioBlob, startRecording, stopRecording } = useAudioRecorder();
    const [result, setResult] = useState<{ score: number; heard: string } | null>(null);
    const [evaluating, setEvaluating] = useState(false);

    const target = question.audio_text || question.correct_answer || '';

    const playTarget = () => {
        if (isSpeaking) { stop(); return; }
        if (question.audio_url) {
            const a = new Audio(question.audio_url);
            a.play().catch(() => speak(target, lang));
        } else {
            speak(target, lang);
        }
    };

    const submit = async () => {
        if (!audioBlob || evaluating) return;
        setEvaluating(true);
        try {
            const fd = new FormData();
            fd.append('audio', audioBlob, `repeat-${question.id}.webm`);
            fd.append('prompt', `Repeat exactly this sentence: "${target}". Score how faithfully it was repeated.`);
            fd.append('lang', lang);
            const res = await fetch(route('api.exercise.evaluate-turn'), {
                method: 'POST', headers: { 'X-XSRF-TOKEN': csrfToken() }, body: fd,
            });
            const data = await res.json();
            const heard = (data.transcription ?? '').toString();
            // Fidelity = word overlap with the target (simple, robust, language-agnostic).
            const norm = (s: string) => s.toLowerCase().replace(/[^\p{L}\s]/gu, '').split(/\s+/).filter(Boolean);
            const t = norm(target), h = norm(heard);
            const hit = t.filter(w => h.includes(w)).length;
            const score = t.length ? Math.round((hit / t.length) * 100) : (data.accuracy ?? 0);
            setResult({ score, heard });
            onAnswer(question.id, `repeat:${score}`);
        } catch {
            setResult({ score: 0, heard: '' });
            onAnswer(question.id, 'repeat:0');
        } finally {
            setEvaluating(false);
        }
    };

    return (
        <div className="space-y-4">
            <p className="text-sm font-bold text-muted-foreground">Écoute la phrase, puis répète-la le plus fidèlement possible.</p>

            <button
                onClick={playTarget}
                className="flex w-full items-center justify-center gap-3 rounded-xl border-2 border-primary bg-primary/5 px-4 py-3.5 text-primary"
            >
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>
                <span className="text-sm font-bold">{isSpeaking ? 'Lecture…' : 'Écouter la phrase'}</span>
            </button>

            {!result && !evaluating && !disabled && (
                <div className="flex flex-wrap items-center gap-2">
                    <button
                        onClick={() => (isRecording ? stopRecording() : startRecording())}
                        className={`flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold text-white ${isRecording ? 'bg-red-500 animate-pulse' : 'bg-primary'}`}
                    >
                        <div className={`h-3 w-3 rounded-full ${isRecording ? 'bg-white' : 'bg-white/60'}`} />
                        {isRecording ? 'Stop' : 'Répéter (enregistrer)'}
                    </button>
                    {audioUrl && !isRecording && (
                        <button onClick={submit} className="rounded-xl bg-emerald-500 px-4 py-2.5 text-sm font-bold text-white">Valider</button>
                    )}
                </div>
            )}
            {audioUrl && !isRecording && !result && <audio controls src={audioUrl} className="h-8 w-full" />}

            {evaluating && (
                <div className="flex items-center gap-2 text-xs font-bold text-primary">
                    <img src="/animation/loading.gif" alt="" width={20} height={20} /> Analyse de ta prononciation…
                </div>
            )}

            {result && (
                <div className="space-y-2 rounded-xl border border-border bg-background p-3">
                    <div className="flex items-center gap-2">
                        <span className={`rounded-full px-2 py-0.5 text-[11px] font-black ${result.score >= 60 ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'}`}>{result.score}% fidélité</span>
                    </div>
                    {result.heard && <p className="text-xs italic text-slate-600">Entendu : « {result.heard} »</p>}
                    <p className="text-xs text-slate-500">Cible : {target}</p>
                </div>
            )}
        </div>
    );
}
