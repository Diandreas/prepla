import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { ConfettiBurst } from '@/components/confetti-burst';
import { playSound } from '@/hooks/use-sound';
import { motion } from 'framer-motion';

function Icon({ name, size = 20, className, style }: { name: string; size?: number; className?: string; style?: React.CSSProperties }) {
    return <img src={`/icons/${name}.png`} alt="" width={size} height={size} className={className} style={{ objectFit: 'contain', ...style }} />;
}

// AI-evaluated question types (essay/speaking/short-answer) return `explanation`
// as an object ({concept,evidence,hint,french_translation}), not a string.
// Rendering it directly as a React child crashes the whole page (error #31) —
// same class of bug already fixed on exercise/result.tsx via asText().
function asText(v: any): string {
    if (v == null) return '';
    if (typeof v === 'string') return v;
    if (typeof v === 'number' || typeof v === 'boolean') return String(v);
    if (Array.isArray(v)) return v.map(asText).filter(Boolean).join(' ');
    if (typeof v === 'object') return asText(v.concept ?? v.text ?? v.message ?? v.hint ?? v.value ?? Object.values(v)[0]);
    return '';
}

interface ReportDetail {
    exercise_id: number;
    type?: string;
    title?: string;
    accuracy?: number;
    feedback?: any[];
    questions?: any[];
}

interface Props {
    node: {
        id: number;
        title: string;
        exam?: {
            name: string;
            language?: { name: string }
        }
    };
    report: {
        node_title?: string;
        accuracy?: number;
        xp_earned?: number;
        time_spent?: number;
        details?: ReportDetail[];
    };
    userLevel: string;
}

export default function SessionReport({ node, report, userLevel }: Props) {
    const { t } = useTranslation();

    // Defensive fallbacks: a stale/partial report (old session shape left over
    // from before a deploy) must never crash this page to a blank screen.
    const accuracy = report?.accuracy ?? 0;
    const details = Array.isArray(report?.details) ? report.details : [];
    const xpEarned = report?.xp_earned ?? 0;
    const timeSpent = report?.time_spent ?? 0;
    const nodeTitle = report?.node_title ?? node?.title ?? '';

    // Victory sound + haptics on arrival (xp gained when the concept is mastered).
    useEffect(() => {
        playSound(accuracy >= 80 ? 'complete' : 'xp');
    }, [accuracy]);

    const container = {
        hidden: { opacity: 0 },
        show: {
            opacity: 1,
            transition: { staggerChildren: 0.1 }
        }
    };

    const item = {
        hidden: { opacity: 0, y: 20 },
        show: { opacity: 1, y: 0 }
    };

    return (
        <AppLayout>
            <Head title={`Résultats : ${nodeTitle}`} />
            {accuracy >= 80 && <ConfettiBurst />}

            <div className="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
                <motion.div
                    variants={container}
                    initial="hidden"
                    animate="show"
                    className="space-y-8"
                >
                    {/* Header Card */}
                    <motion.div variants={item} className="relative overflow-hidden bg-white dark:bg-slate-900 rounded-3xl p-8 shadow-xl border border-slate-200 dark:border-slate-800">
                        <div className="absolute top-0 right-0 -mt-8 -mr-8 w-64 h-64 bg-indigo-500/5 rounded-full blur-3xl" />
                        <div className="relative flex flex-col md:flex-row items-center gap-8">
                            {/* Animated trophy / encouragement based on accuracy */}
                            <div className="flex-shrink-0">
                                <img
                                    src={accuracy >= 80 ? '/animation/winner.gif' : accuracy >= 60 ? '/animation/big-trophy.gif' : '/animation/Fire.gif'}
                                    alt=""
                                    width={150}
                                    height={150}
                                    className="drop-shadow-xl"
                                />
                            </div>
                            <div className="flex-1 text-center md:text-left">
                                <span className={`inline-block px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider mb-2 ${
                                    accuracy >= 60
                                        ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'
                                        : 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300'
                                }`}>
                                    {accuracy >= 60 ? 'Concept maîtrisé' : 'Maîtrise insuffisante'}
                                </span>
                                <h1 className="text-3xl font-bold text-slate-900 dark:text-white mb-2">
                                    {nodeTitle}
                                </h1>
                                <p className="text-slate-500 dark:text-slate-400">
                                    {accuracy >= 60
                                        ? `Concept validé (≥60%). Tu peux passer au suivant.`
                                        : `Il te faut ≥60% pour valider ce concept. Tu es à ${Math.round(accuracy)}% — refais une session ou revois la leçon.`}
                                </p>
                            </div>

                            <div className="flex gap-4">
                                <div className="bg-slate-50 dark:bg-slate-800/50 p-6 rounded-2xl border border-slate-100 dark:border-slate-800 text-center min-w-[120px]">
                                    <div className="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{Math.round(accuracy)}%</div>
                                    <div className="text-xs font-medium text-slate-400 uppercase mt-1">Précision</div>
                                </div>
                                <div className="bg-slate-50 dark:bg-slate-800/50 p-6 rounded-2xl border border-slate-100 dark:border-slate-800 text-center min-w-[120px]">
                                    <div className="text-2xl font-bold text-yellow-500">+{xpEarned}</div>
                                    <div className="text-xs font-medium text-slate-400 uppercase mt-1">XP Gagnés</div>
                                </div>
                            </div>
                        </div>
                    </motion.div>

                    {/* Stats Grid */}
                    <motion.div variants={item} className="grid grid-cols-1 md:grid-cols-3 gap-6">
                         <div className="bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 flex items-center gap-4">
                             <div className="w-12 h-12 rounded-xl bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center text-orange-600">
                                 <Icon name="clock" size={24} />
                             </div>
                             <div>
                                 <div className="text-sm text-slate-400 font-medium">Temps total</div>
                                 <div className="text-lg font-bold text-slate-900 dark:text-white">
                                     {Math.floor(timeSpent / 60)}m {timeSpent % 60}s
                                 </div>
                             </div>
                         </div>
                         <div className="bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 flex items-center gap-4">
                             <div className="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center text-green-600">
                                 <Icon name="check-circle" size={24} />
                             </div>
                             <div>
                                 <div className="text-sm text-slate-400 font-medium">Réussite</div>
                                 <div className="text-lg font-bold text-slate-900 dark:text-white">
                                     {details.filter(d => (d.accuracy ?? 0) >= 60).length} / {details.length} Exercices
                                 </div>
                             </div>
                         </div>
                         <div className="bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 flex items-center gap-4">
                             <div className="w-12 h-12 rounded-xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-purple-600">
                                 <Icon name="trending-up" size={24} />
                             </div>
                             <div>
                                 <div className="text-sm text-slate-400 font-medium">Niveau</div>
                                 <div className="text-lg font-bold text-slate-900 dark:text-white">
                                     {node?.exam?.language?.name ?? ''} · {userLevel}
                                 </div>
                             </div>
                         </div>
                    </motion.div>

                    {/* Detailed Analysis */}
                    <motion.div variants={item} className="space-y-4">
                        <h2 className="text-xl font-bold text-slate-900 dark:text-white px-2">Analyse par exercice</h2>
                        {details.map((detail, idx) => (
                            <div key={idx} className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                                <div className="p-4 flex items-center justify-between border-b border-slate-100 dark:border-slate-800">
                                    <div className="flex items-center gap-3">
                                        <div className="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center text-indigo-600">
                                            {idx + 1}
                                        </div>
                                        <div>
                                            <div className="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-tight">{detail.type ?? detail.title ?? ''}</div>
                                        </div>
                                    </div>
                                    <div className={`text-sm font-bold px-3 py-1 rounded-full ${(detail.accuracy ?? 0) >= 60 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}>
                                        {Math.round(detail.accuracy ?? 0)}%
                                    </div>
                                </div>
                                <div className="p-6 space-y-4">
                                    {(Array.isArray(detail.feedback) ? detail.feedback : []).map((f, fIdx) => (
                                        <div key={fIdx} className={`p-4 rounded-xl border ${f.correct ? 'bg-green-50/30 border-green-100' : 'bg-red-50/30 border-red-100'}`}>
                                            <div className="flex items-start gap-3">
                                                <div className={`mt-0.5 w-5 h-5 rounded-full flex items-center justify-center shrink-0 ${f.correct ? 'bg-green-500 text-white' : 'bg-red-500 text-white'}`}>
                                                    <Icon name={f.correct ? "check" : "x"} size={12} style={{ filter: 'brightness(0) invert(1)' }} />
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <p className="text-sm font-medium text-slate-800 dark:text-slate-200 truncate">
                                                        {f.question_text || `Question ${fIdx + 1}`}
                                                    </p>
                                                    {!f.correct && f.explanation && (
                                                        <p className="mt-2 text-sm text-slate-600 dark:text-slate-400 bg-white/50 dark:bg-slate-950/30 p-3 rounded-lg border border-slate-100 dark:border-slate-800 italic">
                                                            {asText(f.explanation)}
                                                        </p>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        ))}
                    </motion.div>

                    {/* Actions — adapt CTA to whether user mastered the concept */}
                    <motion.div variants={item} className="flex flex-col sm:flex-row items-center justify-center gap-4 pt-8">
                        {accuracy >= 60 ? (
                            <>
                                <Link
                                    href="/lessons/next"
                                    className="w-full sm:w-auto px-8 py-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl font-bold shadow-lg transition-all transform hover:-translate-y-1 text-center"
                                >
                                    Concept suivant →
                                </Link>
                                <Link
                                    href="/dashboard"
                                    className="w-full sm:w-auto px-8 py-4 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-900 dark:text-white rounded-2xl font-bold border border-slate-200 dark:border-slate-700 transition-all text-center"
                                >
                                    Retour au parcours
                                </Link>
                            </>
                        ) : (
                            <>
                                <Link
                                    href={route('node.start', node.id)}
                                    className="w-full sm:w-auto px-8 py-4 bg-amber-500 hover:bg-amber-600 text-white rounded-2xl font-bold shadow-lg transition-all transform hover:-translate-y-1 text-center"
                                >
                                    ↻ Refaire pour valider (≥60%)
                                </Link>
                                <Link
                                    href={`/lessons/${node.id}`}
                                    className="w-full sm:w-auto px-8 py-4 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-900 dark:text-white rounded-2xl font-bold border border-slate-200 dark:border-slate-700 transition-all text-center"
                                >
                                    Revoir la leçon
                                </Link>
                            </>
                        )}
                    </motion.div>
                </motion.div>
            </div>
        </AppLayout>
    );
}
