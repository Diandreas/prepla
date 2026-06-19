import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import * as Flags from 'country-flag-icons/react/3x2';
import { useEffect, useState, useMemo } from 'react';
import { useTranslation } from 'react-i18next';
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
    status: 'locked' | 'available' | 'in_progress' | 'completed';
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
    const isActive = status === 'available' || status === 'in_progress';

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
            ?? allNodes.find(n => n.status === 'available')?.id
            ?? allNodes.find(n => n.status === 'locked')?.id;
    }, [allNodes]);

    const activeChapterIdx = useMemo(() => {
        if (!firstIncompleteNodeId) return 0;
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
        n => n.status === 'in_progress' || n.status === 'available'
    ) ?? viewedChapter?.nodes[0];

    return (
        <AppLayout>
            <Head title="Mon Parcours" />

            {/* Loading overlay */}
            {loadingNode && (
                <div className="fixed inset-0 z-50 flex flex-col items-center justify-center gap-6 bg-white/90 backdrop-blur-md">
                    <style>{`
                        @keyframes spinRing { to { transform: rotate(360deg); } }
                        @keyframes pulseGlow { 0%,100% { opacity:0.6; transform:scale(1); } 50% { opacity:1; transform:scale(1.08); } }
                    `}</style>
                    <div className="relative flex items-center justify-center" style={{ width: 88, height: 88 }}>
                        <svg className="absolute inset-0" style={{ animation: 'spinRing 1.2s linear infinite' }} width="88" height="88" viewBox="0 0 88 88">
                            <circle cx="44" cy="44" r="38" fill="none" stroke="#e5e7eb" strokeWidth="6" />
                            <circle cx="44" cy="44" r="38" fill="none" stroke={SKY} strokeWidth="6"
                                strokeLinecap="round" strokeDasharray="60 180" />
                        </svg>
                        <div className="flex items-center justify-center rounded-2xl" style={{ width: 56, height: 56, background: SKY, animation: 'pulseGlow 1.8s ease-in-out infinite' }}>
                            <Icon name="sparkles" size={28} style={{ filter: 'brightness(0) invert(1)' }} />
                        </div>
                    </div>
                    <div className="text-center">
                        <p className="text-base font-bold" style={{ color: OXFORD }}>Préparation en cours</p>
                        <p className="mt-1 text-sm font-medium text-gray-500">{loadingNode.title}</p>
                    </div>
                </div>
            )}

            <style>{`
                @keyframes floatUp { 0%,100% { transform:translateY(0); } 50% { transform:translateY(-5px); } }
                .chapter-hero-img { animation: floatUp 3.5s ease-in-out infinite; }
                .step-card-active { box-shadow: 0 2px 16px rgba(74,144,226,0.12); }
            `}</style>

            <div className="mx-auto max-w-lg px-4 py-6">

                {/* ── Chapter Hero Card ── */}
                {viewedChapter && (
                    <div className="mb-4 rounded-2xl bg-white border border-gray-100 shadow-sm relative" style={{ minHeight: 130, overflow: 'visible' }}>
                        <div className="flex items-center p-5 pr-[130px] gap-3">
                            {/* Prev */}
                            <button
                                onClick={() => setViewedChapterIdx(i => Math.max(0, i - 1))}
                                disabled={viewedChapterIdx === 0}
                                className="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 disabled:opacity-20 transition flex-shrink-0"
                                aria-label="Chapitre précédent"
                            >
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M10 3L5 8l5 5" stroke={OXFORD} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" /></svg>
                            </button>

                            <div className="flex-1 min-w-0">
                                <div className="flex items-center gap-2 mb-1">
                                    <span className="text-[10px] font-black uppercase tracking-widest text-gray-400">
                                        Chapitre {viewedChapterIdx + 1} / {chapters.length}
                                    </span>
                                    {isViewingActive && (
                                        <span className="rounded-full px-2 py-0.5 text-[10px] font-black" style={{ background: '#dcfce7', color: '#16a34a' }}>EN COURS</span>
                                    )}
                                    {!isViewingActive && viewedChapterIdx < activeChapterIdx && (
                                        <span className="rounded-full px-2 py-0.5 text-[10px] font-black" style={{ background: '#fef3c7', color: '#d97706' }}>TERMINÉ</span>
                                    )}
                                    {!isViewingActive && viewedChapterIdx > activeChapterIdx && (
                                        <span className="rounded-full px-2 py-0.5 text-[10px] font-black" style={{ background: '#f3f4f6', color: '#9ca3af' }}>VERROUILLÉ</span>
                                    )}
                                </div>
                                <h2 className="text-xl font-black" style={{ color: OXFORD }}>{viewedChapter.name}</h2>
                                <div className="mt-2 h-1.5 w-full max-w-[180px] rounded-full bg-gray-100 overflow-hidden">
                                    <div className="h-full rounded-full transition-all duration-700" style={{ width: `${chapterPct}%`, background: SKY }} />
                                </div>
                                <p className="mt-1 text-[11px] font-bold text-gray-400">{completedInChapter} / {totalInChapter} étapes</p>
                            </div>

                            {/* Next */}
                            <button
                                onClick={() => setViewedChapterIdx(i => Math.min(chapters.length - 1, i + 1))}
                                disabled={viewedChapterIdx >= chapters.length - 1 || viewedChapterIdx >= activeChapterIdx}
                                className="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 disabled:opacity-20 transition flex-shrink-0"
                                aria-label="Chapitre suivant"
                            >
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M6 3l5 5-5 5" stroke={OXFORD} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" /></svg>
                            </button>
                        </div>

                        {/* 3D Illustration — déborde en bas à droite */}
                        <div className="absolute bottom-0 right-0 pointer-events-none" style={{ width: 140, height: 140 }}>
                            <img
                                src="/icons/chapter-hero.png"
                                alt=""
                                className="chapter-hero-img"
                                style={{ width: 140, height: 140, objectFit: 'contain', objectPosition: 'bottom right', filter: 'drop-shadow(0 8px 20px rgba(74,144,226,0.3))' }}
                            />
                        </div>
                    </div>
                )}

                {/* ── CTA Card : Start / Continue chapter ── */}
                {firstActiveInChapter && (isViewingActive || viewedChapterIdx <= activeChapterIdx) && (
                    <button
                        onClick={() => handleStartNode(firstActiveInChapter)}
                        className="mb-6 w-full rounded-2xl text-left flex items-center gap-4 p-4 text-white transition hover:brightness-105 active:scale-[0.98]"
                        style={{
                            background: `linear-gradient(135deg, ${OXFORD} 0%, #2a3f6a 100%)`,
                            boxShadow: '0 4px 0 0 rgba(0,0,0,0.25)',
                        }}
                    >
                        <div className="h-12 w-12 rounded-2xl flex items-center justify-center flex-shrink-0" style={{ background: 'rgba(255,255,255,0.12)' }}>
                            <Icon name="book" size={26} style={{ filter: 'brightness(0) invert(1)' }} />
                        </div>
                        <div className="flex-1 min-w-0">
                            <p className="text-[10px] font-black uppercase tracking-widest opacity-60">Chapitre {viewedChapterIdx + 1}</p>
                            <p className="text-base font-black truncate">{viewedChapter?.name}</p>
                        </div>
                        <div className="flex-shrink-0 rounded-xl px-4 py-2 font-black text-sm flex items-center gap-1.5" style={{ background: SKY }}>
                            COMMENCER
                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M3 7h8M8 4l3 3-3 3" stroke="white" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" /></svg>
                        </div>
                    </button>
                )}

                {/* ── Steps List ── */}
                {viewedChapter && (
                    <div>
                        <div className="flex items-center justify-between mb-4">
                            <div>
                                <h3 className="text-base font-black" style={{ color: OXFORD }}>Ton parcours</h3>
                                <p className="text-xs text-gray-400 font-medium">Apprends pas à pas</p>
                            </div>
                            <Link
                                href="/lessons"
                                className="flex items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-black border border-gray-200 text-gray-600 hover:bg-gray-50 transition"
                            >
                                Voir le chapitre
                                <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><rect x="1" y="2" width="10" height="1.5" rx="0.75" fill="currentColor" /><rect x="1" y="5.25" width="10" height="1.5" rx="0.75" fill="currentColor" /><rect x="1" y="8.5" width="10" height="1.5" rx="0.75" fill="currentColor" /></svg>
                            </Link>
                        </div>

                        {/* Zigzag roadmap — cercles qui alternent gauche/droite + ligne pointillée courbe */}
                        <div className="relative py-2">
                            {viewedChapter.nodes.map((node, idx) => {
                                const isActive = node.status === 'available' || node.status === 'in_progress';
                                const isCompleted = node.status === 'completed';
                                const isLocked = node.status === 'locked';
                                const canClick = isActive || isCompleted;
                                const isLeft = idx % 2 === 0; // alterne gauche / droite
                                const hasNext = idx < viewedChapter.nodes.length - 1;
                                const nextIsLeft = (idx + 1) % 2 === 0;

                                // Position X du cercle : gauche ~30px, droite ~calc(100%-78px)
                                const circleLeft = isLeft ? 30 : undefined;
                                const circleRight = !isLeft ? 30 : undefined;

                                // SVG de la ligne pointillée courbe entre ce cercle et le suivant
                                // Le cercle courant est à (isLeft ? 54px : W-54px), le suivant à l'inverse
                                const CIRCLE_R = 27; // rayon du cercle (54/2)
                                const ROW_H = 100; // hauteur estimée d'une row (card ~84px + gap ~16px)

                                return (
                                    <div key={node.id} className="relative" style={{ marginBottom: hasNext ? 16 : 0 }}>
                                        {/* Row : cercle + card */}
                                        <div className="flex items-center gap-3" style={{
                                            flexDirection: isLeft ? 'row' : 'row-reverse',
                                            paddingLeft: isLeft ? 0 : 0,
                                        }}>
                                            {/* Cercle */}
                                            <div className="flex-shrink-0 z-10" style={{ marginLeft: isLeft ? 8 : 0, marginRight: !isLeft ? 8 : 0 }}>
                                                <StepCircleIcon icon={node.icon} status={node.status} size={54} />
                                            </div>

                                            {/* Card */}
                                            <button
                                                disabled={isLocked}
                                                onClick={() => canClick && handleStartNode(node)}
                                                className={`flex-1 flex items-center justify-between rounded-2xl p-4 text-left transition border-2 ${
                                                    isActive
                                                        ? 'bg-white border-blue-100 step-card-active hover:border-blue-200 hover:scale-[1.01]'
                                                        : isCompleted
                                                        ? 'bg-white border-gray-100 hover:bg-gray-50'
                                                        : 'bg-gray-50/80 border-gray-100 cursor-not-allowed'
                                                }`}
                                            >
                                                <div className="min-w-0 flex-1">
                                                    <p className="text-[10px] font-black uppercase tracking-widest mb-0.5" style={{ color: isActive ? SKY : '#9ca3af' }}>
                                                        Étape {idx + 1}
                                                    </p>
                                                    <p className="text-sm font-black" style={{ color: isLocked ? '#9ca3af' : OXFORD }}>
                                                        {stepLabel(node, idx)}
                                                    </p>
                                                    <p className="text-xs mt-0.5" style={{ color: isLocked ? '#d1d5db' : '#6b7280' }}>
                                                        {stepDescription(node, idx)}
                                                    </p>
                                                </div>
                                                <div className="flex items-center gap-2 flex-shrink-0 ml-2">
                                                    <StatusBadge status={node.status} />
                                                    {isLocked
                                                        ? <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><rect x="4" y="7" width="8" height="6" rx="1.5" fill="#d1d5db" /><path d="M6 7V5a2 2 0 0 1 4 0v2" stroke="#d1d5db" strokeWidth="1.5" /></svg>
                                                        : <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M6 4l4 4-4 4" stroke={isActive ? SKY : '#d1d5db'} strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" /></svg>
                                                    }
                                                </div>
                                            </button>
                                        </div>

                                        {/* Ligne pointillée courbe SVG vers le cercle suivant */}
                                        {hasNext && (
                                            <div className="absolute left-0 right-0 pointer-events-none" style={{ top: 54, height: 60, zIndex: 0 }}>
                                                <svg width="100%" height="60" viewBox="0 0 300 60" preserveAspectRatio="none">
                                                    {/* de bas du cercle courant (isLeft→x≈35, sinon x≈265) vers haut du suivant (inverse) */}
                                                    <path
                                                        d={isLeft
                                                            ? "M 35 0 C 35 40, 265 20, 265 60"
                                                            : "M 265 0 C 265 40, 35 20, 35 60"
                                                        }
                                                        fill="none"
                                                        stroke="#cbd5e1"
                                                        strokeWidth="2.5"
                                                        strokeDasharray="7 6"
                                                        strokeLinecap="round"
                                                    />
                                                </svg>
                                            </div>
                                        )}
                                    </div>
                                );
                            })}
                        </div>

                        {/* Chapter boss */}
                        {completedInChapter === totalInChapter && totalInChapter > 0 && (
                            <Link
                                href={route('chapter.synthesis', { chapterOrder: viewedChapter.order })}
                                className="mt-6 flex items-center gap-4 rounded-2xl p-4 border-2 border-amber-200 transition hover:border-amber-300 hover:scale-[1.01]"
                                style={{ background: 'linear-gradient(135deg, #fffbeb, #fef3c7)', boxShadow: '0 4px 0 0 #e08c10' }}
                            >
                                <div className="h-12 w-12 rounded-2xl flex items-center justify-center flex-shrink-0" style={{ background: 'rgba(245,166,35,0.15)' }}>
                                    <Icon name="trophy" size={26} style={{ filter: 'brightness(0) saturate(100%) invert(52%) sepia(72%) saturate(640%) hue-rotate(2deg)' }} />
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

                {/* ── Quick actions ── */}
                <div className="mt-8 grid grid-cols-2 gap-3">
                    {curriculum && (
                        <Link
                            href="/lessons/next"
                            className="col-span-2 rounded-2xl p-4 text-white flex items-center gap-3 relative overflow-hidden transition hover:brightness-105 active:scale-[0.98]"
                            style={{ background: `linear-gradient(135deg, ${SKY}, #3478c8)`, boxShadow: '0 4px 0 0 #2563a0', border: '2px solid #3a82cc' }}
                        >
                            <div className="h-10 w-10 rounded-xl flex items-center justify-center flex-shrink-0" style={{ background: 'rgba(255,255,255,0.15)' }}>
                                <Icon name="lightbulb" size={22} style={{ filter: 'brightness(0) invert(1)' }} />
                            </div>
                            <div className="flex-1 min-w-0">
                                <p className="text-[9px] font-black uppercase tracking-widest opacity-70">▶ Prochaine leçon</p>
                                <p className="text-sm font-black truncate">{curriculum.current_objective?.title || 'Commencer'}</p>
                                <p className="text-[10px] opacity-70">Objectif {curriculum.current_index + 1} / {curriculum.total_objectives}</p>
                            </div>
                            <div className="absolute -right-4 -top-4 h-20 w-20 rounded-full bg-white/10" />
                        </Link>
                    )}

                    <Link
                        href={route('practice.index')}
                        className="rounded-2xl p-4 text-white flex flex-col gap-2 relative overflow-hidden transition hover:brightness-105 active:scale-[0.98]"
                        style={{ background: 'linear-gradient(135deg, #48b77b, #3a9d68)', boxShadow: '0 4px 0 0 #1f6e42', border: '2px solid #2d8a55' }}
                    >
                        <Icon name="zap" size={22} style={{ filter: 'brightness(0) invert(1)' }} />
                        <div>
                            <p className="text-sm font-black">Pratique rapide</p>
                            <p className="text-[10px] opacity-70">5 min · adapté</p>
                        </div>
                    </Link>

                    <Link
                        href="/errors"
                        className="rounded-2xl p-4 flex flex-col gap-2 relative overflow-hidden transition hover:bg-red-50 border-2 border-red-100 bg-white active:scale-[0.98]"
                    >
                        <div className="relative w-fit">
                            <Icon name="review" size={22} style={{ filter: 'brightness(0) saturate(100%) invert(38%) sepia(93%) saturate(1352%) hue-rotate(338deg)' }} />
                            {(dueErrorsCount ?? 0) > 0 && (
                                <div className="absolute -top-1 -right-1 h-4 w-4 rounded-full bg-red-500 flex items-center justify-center">
                                    <span className="text-[8px] font-black text-white">{dueErrorsCount}</span>
                                </div>
                            )}
                        </div>
                        <div>
                            <p className="text-sm font-black" style={{ color: OXFORD }}>Révision SM-2</p>
                            <p className="text-[10px] text-gray-400">{(dueErrorsCount ?? 0) > 0 ? `${dueErrorsCount} à revoir` : 'À jour !'}</p>
                        </div>
                    </Link>
                </div>

                {/* ── Overall progress ── */}
                <div className="mt-4 rounded-2xl bg-white border border-gray-100 p-4">
                    <div className="flex items-center justify-between mb-2 text-xs font-black uppercase tracking-wider">
                        <span className="text-gray-400">Progression globale</span>
                        <span style={{ color: SKY }}>{stats.progress_percent}%</span>
                    </div>
                    <div className="h-3 w-full bg-gray-100 rounded-full overflow-hidden">
                        <div className="h-full rounded-full transition-all duration-1000 relative" style={{ width: `${stats.progress_percent}%`, background: `linear-gradient(to right, ${SKY}, #3478c8)` }}>
                            <div className="absolute inset-0 bg-white/20" style={{ animation: 'pulse 2s infinite' }} />
                        </div>
                    </div>
                    <p className="mt-1.5 text-[10px] font-bold text-gray-400">{stats.completed_nodes} / {stats.total_nodes} étapes complétées</p>
                </div>

            </div>
        </AppLayout>
    );
}
