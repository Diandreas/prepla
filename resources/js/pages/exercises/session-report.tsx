import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { motion } from 'framer-motion';

function Icon({ name, size = 20, className, style }: { name: string; size?: number; className?: string; style?: React.CSSProperties }) {
    return <img src={`/icons/${name}.png`} alt="" width={size} height={size} className={className} style={{ objectFit: 'contain', ...style }} />;
}

interface ReportDetail {
    exercise_id: number;
    type: string;
    accuracy: number;
    feedback: any[];
    questions: any[];
}

interface Props {
    node: {
        id: number;
        title: string;
        exam: {
            name: string;
            language: { name: string }
        }
    };
    report: {
        node_title: string;
        accuracy: number;
        xp_earned: number;
        time_spent: number;
        details: ReportDetail[];
    };
    userLevel: string;
}

export default function SessionReport({ node, report, userLevel }: Props) {
    const { t } = useTranslation();

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
            <Head title={`Résultats : ${report.node_title}`} />
            
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
                                    src={report.accuracy >= 60 ? '/animation/Trophy.gif' : '/animation/Fire.gif'}
                                    alt=""
                                    width={130}
                                    height={130}
                                    className="drop-shadow-xl"
                                />
                            </div>
                            <div className="flex-1 text-center md:text-left">
                                <span className="inline-block px-3 py-1 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 text-xs font-bold uppercase tracking-wider mb-2">
                                    Session Terminée
                                </span>
                                <h1 className="text-3xl font-bold text-slate-900 dark:text-white mb-2">
                                    {report.node_title}
                                </h1>
                                <p className="text-slate-500 dark:text-slate-400">
                                    {report.accuracy >= 80
                                        ? `Excellent travail ! Vous progressez vers votre objectif ${node.exam.name}.`
                                        : report.accuracy >= 50
                                          ? `Bonne session — continuez ainsi pour progresser vers ${node.exam.name}.`
                                          : `Pas de panique : c'est en s'entraînant qu'on s'améliore. On recommence ?`}
                                </p>
                            </div>

                            <div className="flex gap-4">
                                <div className="bg-slate-50 dark:bg-slate-800/50 p-6 rounded-2xl border border-slate-100 dark:border-slate-800 text-center min-w-[120px]">
                                    <div className="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{Math.round(report.accuracy)}%</div>
                                    <div className="text-xs font-medium text-slate-400 uppercase mt-1">Précision</div>
                                </div>
                                <div className="bg-slate-50 dark:bg-slate-800/50 p-6 rounded-2xl border border-slate-100 dark:border-slate-800 text-center min-w-[120px]">
                                    <div className="text-2xl font-bold text-yellow-500">+{report.xp_earned}</div>
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
                                     {Math.floor(report.time_spent / 60)}m {report.time_spent % 60}s
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
                                     {report.details.filter(d => d.accuracy >= 80).length} / {report.details.length} Exercices
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
                                     {node.exam.language.name} · {userLevel}
                                 </div>
                             </div>
                         </div>
                    </motion.div>

                    {/* Detailed Analysis */}
                    <motion.div variants={item} className="space-y-4">
                        <h2 className="text-xl font-bold text-slate-900 dark:text-white px-2">Analyse par exercice</h2>
                        {report.details.map((detail, idx) => (
                            <div key={idx} className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                                <div className="p-4 flex items-center justify-between border-b border-slate-100 dark:border-slate-800">
                                    <div className="flex items-center gap-3">
                                        <div className="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center text-indigo-600">
                                            {idx + 1}
                                        </div>
                                        <div>
                                            <div className="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-tight">{detail.type}</div>
                                        </div>
                                    </div>
                                    <div className={`text-sm font-bold px-3 py-1 rounded-full ${detail.accuracy >= 80 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}>
                                        {Math.round(detail.accuracy)}%
                                    </div>
                                </div>
                                <div className="p-6 space-y-4">
                                    {detail.feedback.map((f, fIdx) => (
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
                                                            {f.explanation}
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

                    {/* Actions */}
                    <motion.div variants={item} className="flex flex-col sm:flex-row items-center justify-center gap-4 pt-8">
                        <Link 
                            href={route('practice.index')}
                            className="w-full sm:w-auto px-8 py-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl font-bold shadow-lg shadow-indigo-200 dark:shadow-none transition-all transform hover:-translate-y-1 text-center"
                        >
                            Retour au Dashboard
                        </Link>
                        <Link 
                            href={route('node.start', node.id)}
                            className="w-full sm:w-auto px-8 py-4 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-900 dark:text-white rounded-2xl font-bold border border-slate-200 dark:border-slate-700 transition-all text-center"
                        >
                            Refaire la session
                        </Link>
                    </motion.div>
                </motion.div>
            </div>
        </AppLayout>
    );
}
