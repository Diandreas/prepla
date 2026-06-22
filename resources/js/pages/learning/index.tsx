import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { useEffect, useState } from 'react';

function Icon({ name, size = 20, style }: { name: string; size?: number; style?: React.CSSProperties }) {
    return <img src={`/icons/${name}.png`} alt="" width={size} height={size} style={{ objectFit: 'contain', ...style }} />;
}

interface Objective {
    order: number;
    title: string;
    concept: string;
    status: 'pending' | 'current' | 'done' | 'skipped';
    priority: string;
}

interface ErrorStat {
    error_category: string;
    subcategory: string | null;
    count: number;
}

interface Lesson {
    id: number;
    title: string;
    concept: string | null;
    status: string;
    created_at: string;
    skeleton_objective_index: number | null;
}

interface Skeleton {
    objectives: Objective[];
    current_index: number;
    current_objective: Objective | null;
    progress_percent: number;
}

interface Props {
    lessons: { data: Lesson[] };
    skeleton: Skeleton | null;
    errorStats: ErrorStat[];
}

const OXFORD = '#1A2B48';
const SKY = '#4A90E2';
const GOLD = '#F5A623';
const GREEN = '#48b77b';

const categoryLabels: Record<string, string> = {
    'grammar.tense': 'Temps verbaux',
    'grammar.agreement': 'Accords',
    'grammar.word-order': 'Ordre des mots',
    'grammar.article': 'Articles',
    'grammar.preposition': 'Prépositions',
    'grammar.modal': 'Modaux',
    'grammar.pronoun': 'Pronoms',
    'vocabulary.lexical': 'Lexique',
    'vocabulary.register': 'Registre',
    'vocabulary.collocation': 'Collocations',
    'spelling': 'Orthographe',
    'punctuation': 'Ponctuation',
    'coherence': 'Cohérence',
    'writing.structure': 'Structure rédactionnelle',
    'writing.cohesion': 'Cohésion',
    'listening.detail': 'Écoute — détails',
    'listening.inference': 'Écoute — inférence',
    'reading.skim': 'Lecture — survol',
    'reading.scan': 'Lecture — repérage',
    'speaking.pronunciation': 'Prononciation',
    'speaking.fluency': 'Fluidité',
    'speaking.accuracy': 'Précision orale',
};

const categoryColors: Record<string, string> = {
    grammar: '#E74C3C',
    vocabulary: '#9B59B6',
    spelling: '#E67E22',
    punctuation: '#F39C12',
    coherence: '#1ABC9C',
    writing: OXFORD,
    listening: GREEN,
    reading: SKY,
    speaking: GOLD,
};

function getCategoryColor(cat: string): string {
    const prefix = cat.split('.')[0];
    return categoryColors[prefix] || OXFORD;
}

function getCategoryLabel(cat: string): string {
    const label = categoryLabels[cat];
    if (label) return label;
    
    // Fallback: format dot notation nicely
    return cat.split('.')
        .map(segment => segment.charAt(0).toUpperCase() + segment.slice(1).replace(/_/g, ' '))
        .join(' › ');
}

export default function LearningIndex({ lessons, skeleton, errorStats }: Props) {
    const [mounted, setMounted] = useState(false);
    useEffect(() => setMounted(true), []);

    const stagger = (i: number) => ({
        opacity: mounted ? 1 : 0,
        transform: mounted ? 'translateY(0)' : 'translateY(16px)',
        transition: `all 0.5s cubic-bezier(0.22, 1, 0.36, 1) ${i * 100}ms`,
    });

    return (
        <AppLayout>
            <Head title="Mon Parcours" />
            <div className="mx-auto max-w-2xl px-4 py-8">
                {/* Header */}
                <div className="mb-8" style={stagger(0)}>
                    <h1 className="text-2xl font-black tracking-tight" style={{ color: OXFORD }}>
                        📘 Mon Parcours
                    </h1>
                    <p className="mt-1 text-sm font-bold text-muted-foreground">
                        Ton programme adaptatif — chaque leçon s'adapte à tes progrès
                    </p>
                </div>

                {/* Next lesson CTA */}
                <div className="mb-6" style={stagger(1)}>
                    <Link
                        href="/lessons/next"
                        className="duo-press group relative block overflow-hidden rounded-2xl p-6 text-white"
                        style={{
                            background: `linear-gradient(135deg, ${SKY}, #3478c8)`,
                            '--duo-shadow': '#2a6fc0',
                            boxShadow: `0 5px 0 0 #2a6fc0, 0 8px 24px rgba(74,144,226,0.3)`,
                        } as React.CSSProperties}
                    >
                        <div className="relative z-10 flex items-center justify-between">
                            <div>
                                <p className="text-xs font-black uppercase tracking-widest opacity-80">
                                    Prochaine leçon
                                </p>
                                <p className="mt-1 text-lg font-black">
                                    {skeleton?.current_objective?.title || 'Commencer mon apprentissage'}
                                </p>
                                {skeleton?.current_objective?.concept && (
                                    <p className="mt-0.5 text-xs font-semibold opacity-70">
                                        {getCategoryLabel(skeleton.current_objective.concept)}
                                    </p>
                                )}
                            </div>
                            <div
                                className="flex h-14 w-14 items-center justify-center rounded-2xl transition-transform group-hover:scale-110"
                                style={{
                                    background: 'rgba(255,255,255,0.2)',
                                    backdropFilter: 'blur(4px)',
                                }}
                            >
                                <span className="text-3xl">▶</span>
                            </div>
                        </div>
                        {/* Decorative */}
                        <div
                            className="absolute -right-6 -top-6 h-32 w-32 rounded-full opacity-10"
                            style={{ background: '#fff' }}
                        />
                    </Link>
                </div>

                {/* Skeleton progress */}
                {skeleton && (
                    <div className="duo-card mb-6 p-5" style={stagger(2)}>
                        <div className="mb-3 flex items-center justify-between">
                            <p className="text-xs font-black uppercase tracking-widest" style={{ color: OXFORD, opacity: 0.5 }}>
                                Progression du programme
                            </p>
                            <span className="text-sm font-black" style={{ color: SKY }}>
                                {skeleton.progress_percent}%
                            </span>
                        </div>
                        <div className="duo-progress mb-4" style={{ height: '0.6rem' }}>
                            <div
                                className="h-full rounded-full transition-all duration-1000 ease-out"
                                style={{
                                    width: mounted ? `${skeleton.progress_percent}%` : '0%',
                                    background: `linear-gradient(90deg, ${SKY}, ${GREEN})`,
                                    boxShadow: 'inset 0 -2px 0 rgba(0,0,0,0.15), inset 0 1px 0 rgba(255,255,255,0.3)',
                                }}
                            />
                        </div>

                        {/* Objectives timeline */}
                        <div className="space-y-1.5 max-h-64 overflow-y-auto pr-1">
                            {skeleton.objectives.map((obj, i) => {
                                const isDone = obj.status === 'done';
                                const isCurrent = obj.status === 'current';
                                const isSkipped = obj.status === 'skipped';
                                return (
                                    <div
                                        key={i}
                                        className="flex items-center gap-3 rounded-xl px-3 py-2 transition-all"
                                        style={{
                                            background: isCurrent ? 'rgba(74,144,226,0.08)' : 'transparent',
                                            borderLeft: isCurrent ? `3px solid ${SKY}` : isDone ? `3px solid ${GREEN}` : '3px solid #e5e7eb',
                                        }}
                                    >
                                        <div
                                            className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[10px] font-black"
                                            style={{
                                                background: isDone ? GREEN : isCurrent ? SKY : '#e5e7eb',
                                                color: isDone || isCurrent ? '#fff' : '#9ca3af',
                                                boxShadow: isCurrent ? `0 0 10px ${SKY}44` : 'none',
                                            }}
                                        >
                                            {isDone ? '✓' : isSkipped ? '—' : (obj.order !== undefined ? obj.order + 1 : i + 1)}
                                        </div>
                                        <p
                                            className="text-xs font-bold truncate"
                                            style={{
                                                color: isDone ? '#9ca3af' : isCurrent ? SKY : OXFORD,
                                                textDecoration: isDone || isSkipped ? 'line-through' : 'none',
                                            }}
                                        >
                                            {obj.title}
                                        </p>
                                        {obj.priority === 'high' && (
                                            <span className="ml-auto shrink-0 rounded bg-red-100 px-1.5 py-0.5 text-[9px] font-black text-red-600">
                                                PRIORITÉ
                                            </span>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                )}

                {/* Error weaknesses (Pilier 4 dashboard) */}
                {errorStats && errorStats.length > 0 && (
                    <div className="duo-card mb-6 overflow-hidden p-0" style={stagger(3)}>
                        <div className="border-b-2 border-gray-100 px-5 py-3 flex items-center justify-between">
                            <p className="text-xs font-black uppercase tracking-widest" style={{ color: OXFORD, opacity: 0.5 }}>
                                ⚠️ Tes points faibles
                            </p>
                            <Link href="/errors" className="text-[10px] font-bold" style={{ color: SKY }}>
                                Voir tout →
                            </Link>
                        </div>
                        <div className="divide-y divide-gray-100">
                            {errorStats.slice(0, 6).map((stat, i) => (
                                <div key={i} className="flex items-center justify-between px-5 py-3">
                                    <div className="flex items-center gap-3">
                                        <div
                                            className="h-3 w-3 rounded-full"
                                            style={{ background: getCategoryColor(stat.error_category) }}
                                        />
                                        <div>
                                            <p className="text-xs font-bold" style={{ color: OXFORD }}>
                                                {getCategoryLabel(stat.error_category)}
                                            </p>
                                            {stat.subcategory && (
                                                <p className="text-[10px] text-muted-foreground">
                                                    {stat.subcategory.replace(/_/g, ' ')}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <span
                                            className="rounded-lg px-2 py-0.5 text-[10px] font-black text-white"
                                            style={{
                                                background: stat.count >= 5 ? '#E74C3C' : stat.count >= 3 ? '#E67E22' : '#9ca3af',
                                            }}
                                        >
                                            {stat.count} erreur{stat.count > 1 ? 's' : ''}
                                        </span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Recent lessons */}
                <div className="duo-card overflow-hidden p-0" style={stagger(4)}>
                    <div className="border-b-2 border-gray-100 px-5 py-3">
                        <p className="text-xs font-black uppercase tracking-widest" style={{ color: OXFORD, opacity: 0.5 }}>
                            Leçons récentes
                        </p>
                    </div>
                    {lessons.data.length === 0 ? (
                        <div className="py-12 text-center">
                            <p className="text-4xl mb-3">📚</p>
                            <p className="text-sm font-bold text-muted-foreground">
                                Aucune leçon encore. Clique sur "Prochaine leçon" pour commencer !
                            </p>
                        </div>
                    ) : (
                        <div className="flex flex-col gap-2.5 p-4">
                            {lessons.data.map((lesson, i) => (
                                <Link
                                    key={lesson.id}
                                    href={`/lessons/${lesson.id}`}
                                    className="duo-row group flex items-center justify-between px-4 py-3"
                                >
                                    <div className="flex items-center gap-3">
                                        <div
                                            className="flex h-9 w-9 items-center justify-center rounded-xl text-sm"
                                            style={{
                                                background: lesson.status === 'consolidation'
                                                    ? 'rgba(231,76,60,0.1)'
                                                    : 'rgba(74,144,226,0.1)',
                                            }}
                                        >
                                            {lesson.status === 'consolidation' ? '🔄' : '📝'}
                                        </div>
                                        <div>
                                            <p className="text-sm font-bold" style={{ color: OXFORD }}>
                                                {lesson.title}
                                            </p>
                                            <p className="text-[10px] text-muted-foreground">
                                                {new Date(lesson.created_at).toLocaleDateString('fr-FR')}
                                                {lesson.status === 'consolidation' && (
                                                    <span className="ml-1.5 rounded bg-orange-100 px-1 py-0.5 text-[9px] font-bold text-orange-600">
                                                        Consolidation
                                                    </span>
                                                )}
                                            </p>
                                        </div>
                                    </div>
                                    <span className="text-base font-black transition-transform group-hover:translate-x-1" style={{ color: SKY }}>→</span>
                                </Link>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
