import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useState } from 'react';

function Icon({ name, size = 20, className, style }: { name: string; size?: number; className?: string; style?: React.CSSProperties }) {
    return <img src={`/icons/${name}.png`} alt="" width={size} height={size} className={className} style={{ objectFit: 'contain', ...style }} />;
}

interface UserError {
    id: number;
    skill_type: string;
    prompt: string;
    user_answer: string;
    correct_answer: string;
    explanation: string | null;
    mastered: boolean;
    review_count: number;
    created_at: string;
}

interface Props {
    errors: UserError[];
}

const skillColors: Record<string, string> = {
    reading: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    listening: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    writing: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    speaking: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
};

const skillIcons: Record<string, string> = {
    reading: 'book',
    listening: 'headphones',
    writing: 'writing',
    speaking: 'speaking',
};

export default function ErrorsPractice({ errors }: Props) {
    const { t } = useTranslation();
    const [reviewed, setReviewed] = useState<Set<number>>(new Set());
    const [loading, setLoading] = useState<number | null>(null);
    // Active recall: the learner must re-answer BEFORE seeing the solution.
    const [typed, setTyped] = useState<Record<number, string>>({});
    const [revealed, setRevealed] = useState<Record<number, boolean>>({});
    const [wasCorrect, setWasCorrect] = useState<Record<number, boolean>>({});

    const sm2Schedule = async (error: UserError, correct: boolean) => {
        const xsrf = document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN='));
        const token = xsrf ? decodeURIComponent(xsrf.split('=')[1]) : '';
        await fetch(`/errors/${error.id}/review`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-XSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ correct }),
        });
    };

    // Step 1: learner submits their recall attempt → grade it, reveal solution,
    // and feed SM-2 with the real result (no more self-assessment).
    const attemptRecall = async (error: UserError) => {
        const ans = (typed[error.id] ?? '').trim();
        if (!ans) return;
        setLoading(error.id);
        const ok = ans.toLowerCase() === (error.correct_answer ?? '').trim().toLowerCase();
        setWasCorrect(prev => ({ ...prev, [error.id]: ok }));
        setRevealed(prev => ({ ...prev, [error.id]: true }));
        try {
            await sm2Schedule(error, ok);
        } catch { /* non-blocking */ }
        finally { setLoading(null); }
    };

    // Step 2: after seeing the solution, move the card out of the pending list.
    const dismiss = (error: UserError) => {
        setReviewed(prev => new Set([...prev, error.id]));
    };

    const pending = errors.filter(e => !reviewed.has(e.id));
    const done = errors.filter(e => reviewed.has(e.id));

    return (
        <AppLayout>
            <Head title={t('errors.practice_title', 'Révision des erreurs')} />

            <div className="mx-auto max-w-3xl px-4 py-10 space-y-8">
                {/* Header */}
                <div className="flex items-center gap-4">
                    <div className="h-12 w-12 bg-red-100 dark:bg-red-900/30 rounded-2xl flex items-center justify-center">
                        <Icon name="x-circle" size={28} style={{ }} />
                    </div>
                    <div>
                        <h1 className="text-2xl font-black text-slate-900 dark:text-white">
                            {t('errors.practice_title', 'Révision des erreurs')}
                        </h1>
                        <p className="text-slate-500 dark:text-slate-400 text-sm font-medium">
                            {pending.length} {t('errors.errors_remaining', 'erreur(s) à revoir')}
                        </p>
                    </div>
                </div>

                {/* Empty state */}
                {errors.length === 0 && (
                    <div className="text-center py-20 space-y-4">
                        <div className="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-2xl flex items-center justify-center mx-auto">
                            <Icon name="check-circle" size={32} style={{ }} />
                        </div>
                        <p className="text-slate-500 font-medium">{t('errors.no_errors', 'Aucune erreur à réviser. Excellent travail !')}</p>
                    </div>
                )}

                {/* Pending errors */}
                {pending.map((error) => (
                    <div key={error.id} className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                        {/* Skill badge + header */}
                        <div className="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                            <span className={`px-2.5 py-1 rounded-lg text-xs font-black uppercase tracking-wide flex items-center gap-1.5 ${skillColors[error.skill_type] ?? 'bg-slate-100 text-slate-600'}`}>
                                <Icon name={skillIcons[error.skill_type] ?? 'book'} size={12} />
                                {error.skill_type}
                            </span>
                            <span className="text-xs text-slate-400">{new Date(error.created_at).toLocaleDateString()}</span>
                        </div>

                        <div className="p-5 space-y-4">
                            {/* Prompt */}
                            {error.prompt && (
                                <div className="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                                    <p className="text-xs font-bold text-slate-400 uppercase tracking-wide mb-1">{t('errors.question', 'Question')}</p>
                                    <p className="text-sm text-slate-700 dark:text-slate-300 leading-relaxed">{error.prompt}</p>
                                </div>
                            )}

                            {!revealed[error.id] ? (
                                <>
                                    {/* Active recall: re-answer BEFORE seeing the solution */}
                                    <p className="text-xs font-bold text-slate-400 uppercase tracking-wide">
                                        {t('errors.try_again', 'Réessaie de répondre')}
                                    </p>
                                    <input
                                        value={typed[error.id] ?? ''}
                                        onChange={(e) => setTyped(prev => ({ ...prev, [error.id]: e.target.value }))}
                                        onKeyDown={(e) => e.key === 'Enter' && attemptRecall(error)}
                                        placeholder={t('errors.your_recall', 'Ta réponse…')}
                                        className="w-full rounded-xl border-2 border-slate-200 px-4 py-3 font-semibold focus:border-blue-400 focus:outline-none"
                                    />
                                    <button
                                        onClick={() => attemptRecall(error)}
                                        disabled={loading === error.id || !(typed[error.id] ?? '').trim()}
                                        className="duo-press w-full py-2.5 rounded-xl bg-blue-600 text-white font-bold text-sm disabled:opacity-40"
                                        style={{ boxShadow: '0 4px 0 0 #1e4fa0' }}
                                    >
                                        {loading === error.id ? '...' : t('errors.check', 'Vérifier')}
                                    </button>
                                </>
                            ) : (
                                <>
                                    {/* Result of the recall attempt */}
                                    <div className={`rounded-xl p-3 text-center font-black ${wasCorrect[error.id] ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'}`}>
                                        {wasCorrect[error.id]
                                            ? t('errors.recall_ok', '✓ Bravo, tu as retenu !')
                                            : t('errors.recall_ko', '✗ Pas encore — revois ci-dessous')}
                                    </div>

                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div className="p-3 bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-100 dark:border-red-800/30">
                                            <p className="text-xs font-bold text-red-400 uppercase tracking-wide mb-1">{t('errors.your_answer', 'Ta réponse')}</p>
                                            <p className="text-sm text-red-700 dark:text-red-300 font-medium">{typed[error.id] || error.user_answer}</p>
                                        </div>
                                        <div className="p-3 bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-100 dark:border-green-800/30">
                                            <p className="text-xs font-bold text-green-400 uppercase tracking-wide mb-1">{t('errors.correct_answer', 'Bonne réponse')}</p>
                                            <p className="text-sm text-green-700 dark:text-green-300 font-medium">{error.correct_answer}</p>
                                        </div>
                                    </div>

                                    {error.explanation && (
                                        <div className="p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl border border-indigo-100 dark:border-indigo-800/30">
                                            <p className="text-xs font-bold text-indigo-400 uppercase tracking-wide mb-1 flex items-center gap-1">
                                                <Icon name="sparkles" size={11} />
                                                {t('errors.explanation', 'Explication')}
                                            </p>
                                            <p className="text-sm text-indigo-700 dark:text-indigo-300 leading-relaxed">{error.explanation}</p>
                                        </div>
                                    )}

                                    <button
                                        onClick={() => dismiss(error)}
                                        className="duo-press w-full py-2.5 rounded-xl bg-green-500 text-white font-bold text-sm"
                                        style={{ boxShadow: '0 4px 0 0 #2d7d52' }}
                                    >
                                        {t('errors.continue', 'Continuer')}
                                    </button>
                                </>
                            )}
                        </div>
                    </div>
                ))}

                {/* Done section */}
                {done.length > 0 && (
                    <div className="space-y-3">
                        <p className="text-xs font-bold uppercase tracking-wide text-slate-400">{t('errors.reviewed', 'Révisé')}</p>
                        {done.map((error) => (
                            <div key={error.id} className="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-700 opacity-60 flex items-center gap-3">
                                <Icon name="check-circle" size={18} style={{ }} />
                                <p className="text-sm font-medium text-slate-600 dark:text-slate-400 truncate">{error.correct_answer}</p>
                            </div>
                        ))}
                    </div>
                )}

                {/* Completion message */}
                {errors.length > 0 && pending.length === 0 && (
                    <div className="text-center py-10 space-y-3">
                        <div className="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-2xl flex items-center justify-center mx-auto animate-bounce">
                            <Icon name="trophy" size={32} style={{ }} />
                        </div>
                        <p className="font-black text-slate-900 dark:text-white text-lg">{t('errors.all_done', 'Session terminée !')}</p>
                        <p className="text-slate-500 text-sm">{t('errors.keep_practicing', 'Continuez à pratiquer pour consolider vos acquis.')}</p>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
