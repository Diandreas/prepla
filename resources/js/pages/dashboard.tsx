import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import * as Flags from 'country-flag-icons/react/3x2';
import { useEffect, useState, useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { LoadingAnimation } from '@/components/loading-animation';
import type { SharedData, UserProfile } from '@/types';

function Icon({ name, size = 20, style, className }: { name: string; size?: number; style?: React.CSSProperties; className?: string }) {
    return <img src={`/icons/${name}.png`} alt="" width={size} height={size} className={className} style={{ objectFit: 'contain', ...style }} />;
}

function flagEmojiToCode(flag: string): string {
    const points = [...flag].map(c => c.codePointAt(0)! - 0x1F1E6);
    if (points.length === 2 && points[0] >= 0 && points[0] <= 25) {
        return String.fromCharCode(65 + points[0], 65 + points[1]);
    }
    return '';
}

function FlagImg({ flag, size = 20 }: { flag: string; size?: number }) {
    const code = flagEmojiToCode(flag);
    const FlagComponent = code ? (Flags as Record<string, React.ComponentType<{ style?: React.CSSProperties }>>)[code] : null;
    if (FlagComponent) return <FlagComponent style={{ width: size, borderRadius: 2, display: 'inline-block', verticalAlign: 'middle' }} />;
    return <span>{flag}</span>;
}

/* ─── Types ─── */
interface RoadmapNode {
    id: string | number; title: string; description: string;
    icon: string; skill_type: string; level: string; xp_reward: number;
    status: 'locked' | 'available' | 'in_progress' | 'completed' | 'attempted';
    scheduled_for?: string;
    type?: 'lesson' | 'practice';
    action_url?: string;
}

interface Chapter {
    name: string;
    order: number;
    nodes: RoadmapNode[];
}

interface CurriculumObjective {
    title: string;
    concept: string;
    status: string;
}

interface CurriculumData {
    current_objective: CurriculumObjective | null;
    current_index: number;
    total_objectives: number;
    progress_percent: number;
    consecutive_failures: number;
}

interface ErrorDiag {
    error_category: string;
    subcategory: string | null;
    count: number;
}

interface PageProps {
    profile: UserProfile | null;
    chapters: Chapter[];
    stats: {
        total_nodes: number;
        completed_nodes: number;
        progress_percent: number;
    };
    curriculum?: CurriculumData | null;
    nextLesson?: { id: number; title: string; status: string } | null;
    errorDiagnostic?: ErrorDiag[];
    dueErrorsCount?: number;
}

/* ─── Brand colors ─── */
const OXFORD = '#1A2B48';
const SKY = '#4A90E2';
const GOLD = '#F5A623';

/* ─── Step icon per node type ─── */
function StepCircleIcon({ icon, status, size = 48 }: { icon: string; status: RoadmapNode['status']; size?: number }) {
    const isCompleted = status === 'completed';
    const isLocked = status === 'locked';
    const isActive = status === 'available' || status === 'in_progress' || status === 'attempted';

    const bg = isCompleted ? SKY : isActive ? SKY : '#e5e7eb';
    const iconFilter = isLocked ? 'grayscale(1) opacity(0.4)' : 'brightness(0) invert(1)';

    const iconMap: Record<string, string> = {
        book: 'book', headphones: 'headphones', pen: 'writing', mic: 'mic',
        brain: 'lightbulb', target: 'target', trophy: 'trophy',
    };
    const iconName = iconMap[icon] ?? icon;

    return (
        <div
            className="flex items-center justify-center rounded-full flex-shrink-0"
            style={{
                width: size, height: size,
                background: isActive ? `linear-gradient(135deg, ${SKY}, #3478c8)` : isCompleted ? `linear-gradient(135deg, ${SKY} 0%, #2a6fc0 100%)` : '#e5e7eb',
                boxShadow: isActive ? `0 4px 12px rgba(74,144,226,0.4)` : isCompleted ? '0 2px 8px rgba(74,144,226,0.25)' : 'none',
                border: isActive ? '3px solid #3a82cc' : isCompleted ? '3px solid #3a82cc' : '3px solid #d1d5db',
                position: 'relative',
            }}
        >
            {isLocked
                ? <svg width={size * 0.38} height={size * 0.38} viewBox="0 0 24 24" fill="none"><rect x="6" y="11" width="12" height="9" rx="2" fill="#9ca3af" /><path d="M8 11V7a4 4 0 0 1 8 0v4" stroke="#9ca3af" strokeWidth="2" /></svg>
                : <img src={`/icons/${iconName}.png`} alt="" width={size * 0.42} height={size * 0.42} style={{ objectFit: 'contain', filter: iconFilter }} />
            }
            {isCompleted && (
                <div className="absolute -bottom-1 -right-1 w-5 h-5 rounded-full flex items-center justify-center border-2 border-white" style={{ background: GOLD }}>
                    <svg width="10" height="10" viewBox="0 0 10 10"><path d="M2 5l2.5 2.5L8 3" stroke="white" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" fill="none" /></svg>
                </div>
            )}
        </div>
    );
}

/* ─── Step label → display name ─── */
function stepLabel(node: RoadmapNode, idx: number): string {
    const labels = ['Théorie', 'Pratique', 'Quiz', 'Exercice', 'Révision', 'Examen'];
    return labels[idx] ?? node.title;
}

function stepDescription(node: RoadmapNode, idx: number): string {
    const descs = [
        'Comprends les bases essentielles.',
        'Mets en pratique ce que tu as appris.',
        'Teste tes connaissances.',
        'Entraîne-toi avec des exercices ciblés.',
        'Révise et consolide tes acquis.',
        'Évalue ton niveau global.',
    ];
    if (node.description && node.description.length > 3) return node.description;
    return descs[idx] ?? 'Continue ta progression.';
}

/* ─── Status badge ─── */
function StatusBadge({ status }: { status: RoadmapNode['status'] }) {
    if (status === 'completed') {
        return <span className="inline-flex items-center gap-1 rounded-full px-3 py-1 text-[11px] font-black" style={{ background: '#dcfce7', color: '#16a34a' }}>Terminé</span>;
    }
    if (status === 'attempted') {
        return <span className="inline-flex items-center gap-1 rounded-full px-3 py-1 text-[11px] font-black" style={{ background: '#fef9c3', color: '#ca8a04' }}>À améliorer</span>;
    }
    if (status === 'available' || status === 'in_progress') {
        return <span className="inline-flex items-center gap-1 rounded-full px-3 py-1 text-[11px] font-black" style={{ background: '#dcfce7', color: '#16a34a' }}>À faire</span>;
    }
    return <span className="inline-flex items-center gap-1 rounded-full px-3 py-1 text-[11px] font-black" style={{ background: '#f3f4f6', color: '#9ca3af' }}>À venir</span>;
}

export default function Dashboard() {
    const { t } = useTranslation();
    const { auth, profile, chapters, stats, curriculum, nextLesson, errorDiagnostic, dueErrorsCount } = usePage<SharedData & PageProps>().props;

    const [loadingNode, setLoadingNode] = useState<{ id: string | number; title: string } | null>(null);

    const handleStartNode = (node: RoadmapNode) => {
        setLoadingNode({ id: node.id, title: node.title });
        if (node.action_url) {
            router.visit(node.action_url);
        } else {
            router.visit(route('node.start', node.id));
        }
    };

    const allNodes = useMemo(() => chapters.flatMap(c => c.nodes), [chapters]);
    const firstIncompleteNodeId = useMemo(() => {
        return allNodes.find(n => n.status === 'in_progress')?.id
            ?? allNodes.find(n => n.status === 'attempted')?.id
            ?? allNodes.find(n => n.status === 'available')?.id
            ?? allNodes.find(n => n.status === 'locked')?.id;
    }, [allNodes]);

    const activeChapterIdx = useMemo(() => {
        // No incomplete node left = whole journey done → point to the last chapter
        // (otherwise we'd snap back to chapter 0 and lock navigation forward).
        if (!firstIncompleteNodeId) return Math.max(0, chapters.length - 1);
        const idx = chapters.findIndex(c => c.nodes.some(n => n.id === firstIncompleteNodeId));
        return idx >= 0 ? idx : 0;
    }, [chapters, firstIncompleteNodeId]);

    const [viewedChapterIdx, setViewedChapterIdx] = useState(activeChapterIdx);
    useEffect(() => { setViewedChapterIdx(activeChapterIdx); }, [activeChapterIdx]);

    const viewedChapter = chapters[viewedChapterIdx];
    const isViewingActive = viewedChapterIdx === activeChapterIdx;
    const completedInChapter = viewedChapter ? viewedChapter.nodes.filter(n => n.status === 'completed').length : 0;
    const totalInChapter = viewedChapter ? viewedChapter.nodes.length : 0;
    const chapterPct = totalInChapter > 0 ? Math.round((completedInChapter / totalInChapter) * 100) : 0;

    const examName = (profile as any)?.target_exam?.name;
    const examFlag = (profile as any)?.target_exam?.language?.flag;

    /* First active (or first available) node in viewed chapter */
    const firstActiveInChapter = viewedChapter?.nodes.find(
        n => n.status === 'in_progress' || n.status === 'attempted' || n.status === 'available'
    ) ?? viewedChapter?.nodes[0];

    return (
        <AppLayout>
            <Head title="Mon Parcours" />

            {/* Loading overlay */}
            {loadingNode && (
                <div className="fixed inset-0 z-50 flex flex-col items-center justify-center gap-4 bg-background/90 backdrop-blur-md">
                    <LoadingAnimation size={140} />
                    <div className="text-center">
                        <p className="text-base font-bold text-foreground">Préparation en cours</p>
                        <p className="mt-1 text-sm font-medium text-muted-foreground">{loadingNode.title}</p>
                    </div>
                </div>
            )}

            <style>{`
                @keyframes floatUp { 0%,100% { transform:translateY(0); } 50% { transform:translateY(-5px); } }
                .chapter-hero-img { animation: floatUp 3.5s ease-in-out infinite; }
                .step-card-active { box-shadow: 0 2px 16px rgba(74,144,226,0.12); }
            `}</style>

            <div className="mx-auto max-w-lg lg:max-w-5xl px-4 py-6 lg:grid lg:grid-cols-3 lg:gap-6 lg:items-start">

                {/* ─── Left column: chapter + steps ─── */}
                <div className="lg:col-span-2">

                {/* ── Chapter card (compact): navigation + progress + Commencer ── */}
                {viewedChapter && (
                    <div className="mb-4 rounded-2xl bg-card border border-border shadow-sm p-3.5">
                        <div className="flex items-center gap-2">
                            {/* Prev */}
                            <button
                                onClick={() => setViewedChapterIdx(i => Math.max(0, i - 1))}
                                disabled={viewedChapterIdx === 0}
                                className="w-7 h-7 flex items-center justify-center rounded-full hover:bg-muted disabled:opacity-20 transition flex-shrink-0"
                                aria-label="Chapitre précédent"
                            >
                                <svg width="15" height="15" viewBox="0 0 16 16" fill="none"><path d="M10 3L5 8l5 5" stroke={OXFORD} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" /></svg>
                            </button>

                            <div className="flex-1 min-w-0">
                                <div className="flex items-center gap-2">
                                    <span className="text-[9px] font-black uppercase tracking-widest text-muted-foreground truncate">
                                        Ch. {viewedChapterIdx + 1}/{chapters.length}
                                    </span>
                                    {isViewingActive && (
                                        <span className="rounded-full px-1.5 py-0.5 text-[9px] font-black" style={{ background: '#dcfce7', color: '#16a34a' }}>EN COURS</span>
                                    )}
                                    {!isViewingActive && viewedChapterIdx < activeChapterIdx && (
                                        <span className="rounded-full px-1.5 py-0.5 text-[9px] font-black" style={{ background: '#fef3c7', color: '#d97706' }}>TERMINÉ</span>
                                    )}
                                    {!isViewingActive && viewedChapterIdx > activeChapterIdx && (
                                        <span className="rounded-full px-1.5 py-0.5 text-[9px] font-black" style={{ background: '#f3f4f6', color: '#9ca3af' }}>VERROUILLÉ</span>
                                    )}
                                </div>
                                <h2 className="text-base font-black truncate text-foreground">{viewedChapter.name}</h2>
                            </div>

                            {/* Next */}
                            <button
                                onClick={() => setViewedChapterIdx(i => Math.min(chapters.length - 1, i + 1))}
                                disabled={viewedChapterIdx >= chapters.length - 1 || viewedChapterIdx >= activeChapterIdx}
                                className="w-7 h-7 flex items-center justify-center rounded-full hover:bg-muted disabled:opacity-20 transition flex-shrink-0"
                                aria-label="Chapitre suivant"
                            >
                                <svg width="15" height="15" viewBox="0 0 16 16" fill="none"><path d="M6 3l5 5-5 5" stroke={OXFORD} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" /></svg>
                            </button>
                        </div>

                        {/* Progress + Commencer on one compact row */}
                        <div className="mt-3 flex items-center gap-3">
                            <div className="flex-1 min-w-0">
                                <div className="h-1.5 w-full rounded-full bg-muted overflow-hidden">
                                    <div className="h-full rounded-full transition-all duration-700" style={{ width: `${chapterPct}%`, background: SKY }} />
                                </div>
                                <p className="mt-1 text-[10px] font-bold text-muted-foreground">{completedInChapter} / {totalInChapter} étapes</p>
                            </div>
                            {firstActiveInChapter && (isViewingActive || viewedChapterIdx <= activeChapterIdx) && (
                                <button
                                    onClick={() => handleStartNode(firstActiveInChapter)}
                                    className="duo-press flex-shrink-0 rounded-xl px-4 py-2.5 font-black text-xs text-white flex items-center gap-1.5"
                                    style={{ background: SKY, boxShadow: '0 4px 0 0 #2563a0' }}
                                >
                                    COMMENCER
                                    <svg width="13" height="13" viewBox="0 0 14 14" fill="none"><path d="M3 7h8M8 4l3 3-3 3" stroke="white" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" /></svg>
                                </button>
                            )}
                        </div>
                    </div>
                )}

                {/* ── Quick actions (compact, MOBILE) — right under the chapter card
                    so the learner sees what to do without scrolling. On desktop they
                    move to the right column. ── */}
                <div className="mb-5 grid grid-cols-3 gap-2 lg:hidden">
                    <Link
                        href="/lessons/next"
                        className="duo-press flex flex-col items-center gap-1.5 rounded-2xl p-3 text-white text-center"
                        style={{ background: `linear-gradient(135deg, ${SKY}, #3478c8)`, boxShadow: '0 4px 0 0 #2563a0' }}
                    >
                        <Icon name="lightbulb" size={22} style={{ filter: 'brightness(0) invert(1)' }} />
                        <span className="text-[10px] font-black leading-tight">Prochaine leçon</span>
                    </Link>
                    <Link
                        href={route('practice.index')}
                        className="duo-press flex flex-col items-center gap-1.5 rounded-2xl p-3 text-white text-center"
                        style={{ background: 'linear-gradient(135deg, #48b77b, #3a9d68)', boxShadow: '0 4px 0 0 #1f6e42' }}
                    >
                        <Icon name="zap" size={22} style={{ filter: 'brightness(0) invert(1)' }} />
                        <span className="text-[10px] font-black leading-tight">Pratique rapide</span>
                    </Link>
                    <Link
                        href="/errors"
                        className="duo-press relative flex flex-col items-center gap-1.5 rounded-2xl border-2 border-border bg-card p-3 text-center"
                        style={{ boxShadow: '0 4px 0 0 var(--border)' }}
                    >
                        <Icon name="review" size={22} style={{ }} />
                        <span className="text-[10px] font-black leading-tight text-foreground">Révision{(dueErrorsCount ?? 0) > 0 ? ` (${dueErrorsCount})` : ''}</span>
                    </Link>
                </div>

                {/* ── Steps List ── */}
                {viewedChapter && (
                    <div>
                        <div className="flex items-center justify-between mb-4">
                            <div>
                                <h3 className="text-base font-black text-foreground">Ton parcours</h3>
                                <p className="text-xs text-muted-foreground font-medium">Apprends pas à pas</p>
                            </div>
                            <Link
                                href="/lessons"
                                className="flex items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-black border border-border text-muted-foreground hover:bg-accent transition"
                            >
                                Voir le chapitre
                                <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><rect x="1" y="2" width="10" height="1.5" rx="0.75" fill="currentColor" /><rect x="1" y="5.25" width="10" height="1.5" rx="0.75" fill="currentColor" /><rect x="1" y="8.5" width="10" height="1.5" rx="0.75" fill="currentColor" /></svg>
                            </Link>
                        </div>

                        {/* Roadmap — cercles décalés + ligne pointillée DERRIÈRE les cards (z-index) */}
                        <div className="relative py-2">
                            {(() => {
                                const offsets = [0, 40, 70, 50, 20, 50, 70, 40];
                                const CIRCLE_SIZE = 54;
                                const ROW_HEIGHT = 84; // hauteur estimée d'une row (card)
                                const GAP = 16;        // gap entre rows

                                // Only ONE step should read as "the next thing to do".
                                // Steps unlocked *after* it are shown as waiting (locked-looking)
                                // until the current one is done, so the UI isn't confusing.
                                const firstActionableIdx = viewedChapter.nodes.findIndex(
                                    n => n.status === 'available' || n.status === 'in_progress' || n.status === 'attempted'
                                );

                                return viewedChapter.nodes.map((node, idx) => {
                                    const rawActive = node.status === 'available' || node.status === 'in_progress' || node.status === 'attempted';
                                    // Active only if it's the first actionable step; later unlocked steps wait.
                                    const isActive = rawActive && idx === firstActionableIdx;
                                    const isWaiting = rawActive && idx !== firstActionableIdx;
                                    const isCompleted = node.status === 'completed';
                                    const isLocked = node.status === 'locked' || isWaiting;
                                    const canClick = isActive || isCompleted;
                                    const hasNext = idx < viewedChapter.nodes.length - 1;
                                    const circleOffsetX = offsets[idx % offsets.length];
                                    const nextOffsetX = offsets[(idx + 1) % offsets.length];

                                    return (
                                        <div key={node.id} style={{ marginBottom: hasNext ? GAP : 0, position: 'relative' }}>

                                            {/* Ligne pointillée en absolute, z=0, derrière tout */}
                                            {hasNext && (
                                                <div
                                                    className="pointer-events-none"
                                                    style={{
                                                        position: 'absolute',
                                                        left: 0, right: 0,
                                                        top: CIRCLE_SIZE,
                                                        height: ROW_HEIGHT + GAP,
                                                        zIndex: 0,
                                                    }}
                                                >
                                                    <svg width="100%" height="100%" preserveAspectRatio="none" viewBox={`0 0 300 ${ROW_HEIGHT + GAP}`}>
                                                        <path
                                                            d={`M ${circleOffsetX + CIRCLE_SIZE / 2} 0 C ${circleOffsetX + CIRCLE_SIZE / 2} ${(ROW_HEIGHT + GAP) * 0.6}, ${nextOffsetX + CIRCLE_SIZE / 2} ${(ROW_HEIGHT + GAP) * 0.4}, ${nextOffsetX + CIRCLE_SIZE / 2} ${ROW_HEIGHT + GAP}`}
                                                            fill="none"
                                                            stroke="#cbd5e1"
                                                            strokeWidth="2.5"
                                                            strokeDasharray="7 6"
                                                            strokeLinecap="round"
                                                        />
                                                    </svg>
                                                </div>
                                            )}

                                            {/* Row : cercle + card — au dessus de la ligne (z=1) */}
                                            <div className="flex items-center gap-3" style={{ position: 'relative', zIndex: 1 }}>
                                                <div className="flex-shrink-0" style={{ marginLeft: circleOffsetX }}>
                                                    <StepCircleIcon icon={node.icon} status={isWaiting ? 'locked' : node.status} size={CIRCLE_SIZE} />
                                                </div>

                                                <button
                                                    disabled={isLocked}
                                                    onClick={() => canClick && handleStartNode(node)}
                                                    className={`flex-1 flex items-center justify-between rounded-2xl p-4 text-left border-2 ${canClick ? 'duo-press' : 'transition'} ${
                                                        isActive
                                                            ? 'bg-card border-blue-200 dark:border-blue-900'
                                                            : isCompleted
                                                            ? 'bg-card border-border'
                                                            : 'bg-muted/50 border-border cursor-not-allowed'
                                                    }`}
                                                    style={canClick ? { boxShadow: isActive ? '0 4px 0 0 var(--border)' : '0 3px 0 0 var(--border)' } : undefined}
                                                >
                                                    <div className="min-w-0 flex-1">
                                                        <p className="text-[10px] font-black uppercase tracking-widest mb-0.5" style={{ color: isActive ? SKY : undefined }}>
                                                            <span className={isActive ? '' : 'text-muted-foreground'}>Étape {idx + 1}</span>
                                                        </p>
                                                        <p className={`text-sm font-black ${isLocked ? 'text-muted-foreground' : 'text-foreground'}`}>
                                                            {stepLabel(node, idx)}
                                                        </p>
                                                        <p className="text-xs mt-0.5 text-muted-foreground">
                                                            {stepDescription(node, idx)}
                                                        </p>
                                                    </div>
                                                    <div className="flex items-center gap-2 flex-shrink-0 ml-2">
                                                        <StatusBadge status={isWaiting ? 'locked' : node.status} />
                                                        {isLocked
                                                            ? <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><rect x="4" y="7" width="8" height="6" rx="1.5" fill="#d1d5db" /><path d="M6 7V5a2 2 0 0 1 4 0v2" stroke="#d1d5db" strokeWidth="1.5" /></svg>
                                                            : <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M6 4l4 4-4 4" stroke={isActive ? SKY : '#d1d5db'} strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" /></svg>
                                                        }
                                                    </div>
                                                </button>
                                            </div>

                                        </div>
                                    );
                                });
                            })()}
                        </div>

                        {/* Chapter boss */}
                        {completedInChapter === totalInChapter && totalInChapter > 0 && (
                            <Link
                                href={route('chapter.synthesis', { chapterOrder: viewedChapter.order })}
                                className="mt-6 flex items-center gap-4 rounded-2xl p-4 border-2 border-amber-200 transition hover:border-amber-300 hover:scale-[1.01]"
                                style={{ background: 'linear-gradient(135deg, #fffbeb, #fef3c7)', boxShadow: '0 4px 0 0 #e08c10' }}
                            >
                                <div className="h-12 w-12 rounded-2xl flex items-center justify-center flex-shrink-0" style={{ background: 'rgba(245,166,35,0.15)' }}>
                                    <Icon name="trophy" size={26} style={{ }} />
                                </div>
                                <div className="flex-1">
                                    <p className="text-[10px] font-black uppercase tracking-widest text-amber-600">Boss du chapitre</p>
                                    <p className="text-sm font-black text-amber-900">Synthèse — Tous les concepts</p>
                                    <p className="text-xs text-amber-700/70 mt-0.5">≥80% pour valider définitivement</p>
                                </div>
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M7 5l5 5-5 5" stroke="#d97706" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" /></svg>
                            </Link>
                        )}
                    </div>
                )}

                {/* ── Journey complete banner (replaces actions when finished) ── */}
                {curriculum && (curriculum as any).journey_complete && (
                    <Link
                        href="/dictionary/review"
                        className="duo-press mt-6 block rounded-2xl p-5 text-white relative overflow-hidden"
                        style={{ background: 'linear-gradient(135deg, #48b77b, #2d7d52)', boxShadow: '0 5px 0 0 #1f6e42' }}
                    >
                        <div className="flex items-center gap-3">
                            <div className="flex-1 min-w-0">
                                <p className="text-[9px] font-black uppercase tracking-widest opacity-80">Objectif atteint</p>
                                <p className="text-sm font-black">Bravo, ton parcours est terminé !</p>
                                <p className="text-[10px] opacity-80">Continue en mode entretien : révise ton vocabulaire et tes erreurs.</p>
                            </div>
                        </div>
                    </Link>
                )}

                </div>{/* /left column */}

                {/* ─── Right column (desktop): actions + progress, sticky ─── */}
                <aside className="lg:col-span-1 lg:sticky lg:top-20 space-y-4">
                    {/* Desktop quick actions */}
                    <div className="hidden lg:grid grid-cols-1 gap-2">
                        <Link href="/lessons/next" className="duo-press flex items-center gap-3 rounded-2xl p-3.5 text-white"
                            style={{ background: `linear-gradient(135deg, ${SKY}, #3478c8)`, boxShadow: '0 4px 0 0 #2563a0' }}>
                            <Icon name="lightbulb" size={22} style={{ filter: 'brightness(0) invert(1)' }} />
                            <span className="text-sm font-black">Prochaine leçon</span>
                        </Link>
                        <Link href={route('practice.index')} className="duo-press flex items-center gap-3 rounded-2xl p-3.5 text-white"
                            style={{ background: 'linear-gradient(135deg, #48b77b, #3a9d68)', boxShadow: '0 4px 0 0 #1f6e42' }}>
                            <Icon name="zap" size={22} style={{ filter: 'brightness(0) invert(1)' }} />
                            <span className="text-sm font-black">Pratique rapide</span>
                        </Link>
                        <Link href="/errors" className="duo-press flex items-center gap-3 rounded-2xl border-2 border-border bg-card p-3.5"
                            style={{ boxShadow: '0 4px 0 0 var(--border)' }}>
                            <Icon name="review" size={22} style={{ }} />
                            <span className="text-sm font-black text-foreground">Révision{(dueErrorsCount ?? 0) > 0 ? ` (${dueErrorsCount})` : ''}</span>
                        </Link>
                    </div>

                    {/* Overall progress */}
                    <div className="mt-4 lg:mt-0 rounded-2xl bg-card border border-border p-4">
                        <div className="flex items-center justify-between mb-2 text-xs font-black uppercase tracking-wider">
                            <span className="text-muted-foreground">Progression globale</span>
                            <span style={{ color: SKY }}>{stats.progress_percent}%</span>
                        </div>
                        <div className="h-3 w-full bg-muted rounded-full overflow-hidden">
                            <div className="h-full rounded-full transition-all duration-1000 relative" style={{ width: `${stats.progress_percent}%`, background: `linear-gradient(to right, ${SKY}, #3478c8)` }}>
                                <div className="absolute inset-0 bg-white/20" style={{ animation: 'pulse 2s infinite' }} />
                            </div>
                        </div>
                        <p className="mt-1.5 text-[10px] font-bold text-muted-foreground">{stats.completed_nodes} / {stats.total_nodes} étapes complétées</p>
                    </div>
                </aside>

            </div>
        </AppLayout>
    );
}
