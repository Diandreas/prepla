import AppLayout from '@/layouts/app-layout';
import { Head, Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { VocabReviewSession, type ReviewResult } from '@/components/vocab-review-session';
import { ConfettiBurst } from '@/components/confetti-burst';

const SKY = '#4A90E2';

export default function VocabReviewPage() {
    const preferences = usePage().props.learningPreferences as { audio_enabled?: boolean } | undefined;
    const [saveError, setSaveError] = useState(false);
    const [saving, setSaving] = useState(false);
    const [earnedXp, setEarnedXp] = useState(0);
    const [words, setWords] = useState<any[] | null>(null);
    const [distractors, setDistractors] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const [empty, setEmpty] = useState(false);
    const [loadError, setLoadError] = useState(false);
    const [finished, setFinished] = useState(false);
    const [results, setResults] = useState<ReviewResult[]>([]);

    useEffect(() => {
        (async () => {
            try {
                const res = await fetch(`/dictionary/review-session?limit=5`);
                if (res.status === 404) { setEmpty(true); return; }
                if (!res.ok) throw new Error('load failed');
                const data = await res.json();
                const w = data.words ?? data;
                if (!w || w.length === 0) { setEmpty(true); return; }
                setWords(w);
                setDistractors(data.distractors ?? []);
            } catch {
                setLoadError(true);
            } finally {
                setLoading(false);
            }
        })();
    }, []);

    const playAudio = async (wordId: number) => {
        try {
            const res = await fetch(`/dictionary/audio/${wordId}`);
            const d = await res.json();
            if (!res.ok || !d.url) return false;
            await new Audio(d.url).play();
            return true;
        } catch { return false; }
    };

    const onFinish = async (finalResults: ReviewResult[]) => {
        setResults(finalResults);
        setSaving(true);
        setSaveError(false);
        try {
            const xsrf = document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN='));
            const token = xsrf ? decodeURIComponent(xsrf.split('=')[1]) : '';
            const response = await fetch('/dictionary/review-batch/submit', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-XSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ results: finalResults }),
            });
            if (!response.ok) throw new Error('save failed');
            const data = await response.json();
            setEarnedXp(data.xp_earned);
            setFinished(true);
        } catch { setSaveError(true); } finally { setSaving(false); }
    };

    return (
        <AppLayout focusMode>
            <Head title="Révision du vocabulaire" />
            <div className="min-h-[calc(100vh-2rem)] flex flex-col">
                {/* Minimal focus header: just a way out */}
                <div className="flex items-center justify-between px-4 py-3">
                    <Link href="/dictionary" className="text-sm font-bold" style={{ color: SKY }}>
                        ← Quitter
                    </Link>
                    <span className="text-xs font-black uppercase tracking-widest text-slate-400">Révision vocabulaire</span>
                    <span className="w-12" />
                </div>

                <div className="flex-1 flex items-center justify-center">
                    {loading && (
                        <div className="mx-auto w-full max-w-lg px-4 py-8 space-y-4">
                            <div className="skeleton-shimmer h-2 w-1/3 rounded-full" />
                            <div className="skeleton-shimmer h-24 w-full rounded-2xl" />
                            <div className="grid gap-2.5">
                                <div className="skeleton-shimmer h-12 w-full rounded-xl" />
                                <div className="skeleton-shimmer h-12 w-full rounded-xl" />
                                <div className="skeleton-shimmer h-12 w-full rounded-xl" />
                            </div>
                        </div>
                    )}

                    {empty && (
                        <div className="text-center px-6 py-16 space-y-4">
                            <p className="text-5xl"></p>
                            <h1 className="text-2xl font-black" style={{ color: '#1A2B48' }}>Rien à réviser</h1>
                            <p className="text-sm text-slate-500">Découvre de nouveaux mots dans le dictionnaire pour les réviser ensuite.</p>
                            <Link href="/dictionary" className="duo-press inline-block rounded-xl px-6 py-3 text-sm font-black text-white" style={{ background: SKY, boxShadow: '0 4px 0 0 #2a6fc0' }}>
                                Retour au dictionnaire
                            </Link>
                        </div>
                    )}
                    {loadError && <div role="alert" className="p-6 text-center"><p>La révision ne peut pas être chargée pour le moment. Tes mots sont conservés.</p><Link href="/dictionary" className="mt-3 inline-block text-primary">Retour à Mes mots</Link></div>}

                    {finished && <ConfettiBurst />}
                    {finished && (
                        <div className="text-center px-6 py-16 space-y-5">
                            <div className="h-24 w-24 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto text-4xl">✓</div>
                            <h1 className="text-3xl font-black" style={{ color: '#1A2B48' }}>Session terminée !</h1>
                            <p className="text-lg font-bold text-orange-500">+{earnedXp} XP</p>
                            <div className="flex justify-center gap-2 flex-wrap">
                                {results.map((r, i) => (
                                    <div key={i} className={`h-3 w-3 rounded-full ${r.is_correct ? 'bg-green-500' : 'bg-red-500'}`} />
                                ))}
                            </div>
                            <Link href="/dictionary" className="duo-press inline-block rounded-xl px-6 py-3 text-sm font-black text-white" style={{ background: SKY, boxShadow: '0 4px 0 0 #2a6fc0' }}>
                                Retour au dictionnaire
                            </Link>
                        </div>
                    )}

                    {saving && <p role="status" className="p-6">Enregistrement de ta révision…</p>}
                    {saveError && <div role="alert" className="p-6 text-center"><p>La révision n’a pas pu être enregistrée. Tes réponses sont conservées ici.</p><button onClick={() => onFinish(results)} className="mt-3 rounded-xl bg-primary px-5 py-3 text-primary-foreground">Réessayer</button></div>}
                    {!loading && !empty && !finished && !saving && !saveError && words && (
                        <div className="w-full">
                            <VocabReviewSession
                                words={words}
                                distractors={distractors}
                                onPlayAudio={playAudio}
                                onFinish={onFinish}
                                audioEnabled={preferences?.audio_enabled !== false}
                            />
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
