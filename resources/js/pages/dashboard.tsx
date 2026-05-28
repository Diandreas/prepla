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

function CustomIcon({ name, className, style }: { name: string; className?: string; style?: React.CSSProperties }) {
    return (
        <img
            src={`/icons/${name}.png`}
            alt={name}
            className={className || 'h-5 w-5'}
            style={{ objectFit: 'contain', ...style }}
        />
    );
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

/* ─── Custom node icons ─── */
function NodeIcon({ name, size, color = 'white' }: { name: string; size: number; color?: string }) {
    const s = size;
    switch (name) {
        case 'trophy': return <svg width={s} height={s} viewBox="0 0 24 24" fill="none"><path d="M6 9V2h12v7c0 2.5-2 4.5-4.5 4.5h-3C8 13.5 6 11.5 6 9z" fill={color} opacity="0.9" /><path d="M6 5H2v2c0 2 2 2 2 2h2V5zM18 5h4v2c0 2-2 2-2 2h-2V5z" fill={color} opacity="0.6" /><path d="M12 14v5M8 22h8" stroke={color} strokeWidth="2" strokeLinecap="round" /></svg>;
        case 'book': return <svg width={s} height={s} viewBox="0 0 24 24" fill="none"><path d="M4 4h7a1 1 0 0 1 1 1v14a1 1 0 0 0-1-1H4V4z" fill={color} opacity="0.9" /><path d="M20 4h-7a1 1 0 0 0-1 1v14a1 1 0 0 1 1-1h7V4z" fill={color} opacity="0.5" /><line x1="12" y1="5" x2="12" y2="19" stroke={color} strokeWidth="1.5" /></svg>;
        case 'headphones': return <svg width={s} height={s} viewBox="0 0 24 24" fill="none"><path d="M12 3a9 9 0 0 0-9 9v3h3a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-7a10 10 0 0 1 20 0v7a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2v-2a2 2 0 0 1 2-2h3v-3a9 9 0 0 0-9-9z" fill={color} opacity="0.85" /></svg>;
        case 'pen': return <svg width={s} height={s} viewBox="0 0 24 24" fill="none"><path d="M17 3l4 4-12 12H5v-4L17 3z" fill={color} opacity="0.9" /><path d="M15 5l4 4" stroke={color} strokeWidth="1.5" opacity="0.5" /><line x1="5" y1="20" x2="19" y2="20" stroke={color} strokeWidth="2" strokeLinecap="round" opacity="0.4" /></svg>;
        case 'mic': return <svg width={s} height={s} viewBox="0 0 24 24" fill="none"><rect x="9" y="2" width="6" height="12" rx="3" fill={color} opacity="0.9" /><path d="M5 10a7 7 0 0 0 14 0" stroke={color} strokeWidth="2" strokeLinecap="round" fill="none" opacity="0.7" /><line x1="12" y1="17" x2="12" y2="21" stroke={color} strokeWidth="2" strokeLinecap="round" opacity="0.7" /><line x1="9" y1="21" x2="15" y2="21" stroke={color} strokeWidth="2" strokeLinecap="round" opacity="0.7" /></svg>;
        case 'brain': return <svg width={s} height={s} viewBox="0 0 24 24" fill="none"><path d="M12 3a6 6 0 0 1 4 10.5V16a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1v-2.5A6 6 0 0 1 12 3z" fill={color} opacity="0.9" /><rect x="9" y="17" width="6" height="1.5" rx="0.75" fill={color} opacity="0.6" /><rect x="10" y="19.5" width="4" height="1.5" rx="0.75" fill={color} opacity="0.4" /></svg>;
        case 'target': return <svg width={s} height={s} viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke={color} strokeWidth="2" opacity="0.4" /><circle cx="12" cy="12" r="5.5" stroke={color} strokeWidth="2" opacity="0.7" /><circle cx="12" cy="12" r="2.5" fill={color} /></svg>;
        default: return <img src="/icons/zap.png" alt="" width={s} height={s} style={{ objectFit: 'contain' }} />;
    }
}

const chapterThemes = [
    { bg: `linear-gradient(135deg, ${OXFORD} 0%, #2a3f6a 100%)`, pathColor: OXFORD },
    { bg: `linear-gradient(135deg, ${SKY} 0%, #2a6fc0 100%)`, pathColor: SKY },
    { bg: `linear-gradient(135deg, ${OXFORD} 0%, ${SKY} 100%)`, pathColor: '#3070b0' },
    { bg: `linear-gradient(135deg, #2a6fc0 0%, ${OXFORD} 100%)`, pathColor: '#2a6fc0' },
    { bg: `linear-gradient(135deg, ${GOLD} 0%, #e08c10 100%)`, pathColor: GOLD },
];

const zigzagX = [0, 55, 90, 55, 0, -55, -90, -55];

function NodePath({ fromX, toX, color }: { fromX: number; toX: number; color: string }) {
    const w = 260; const cx = w / 2;
    const x1 = cx + fromX; const x2 = cx + toX;
    return (
        <svg width={w} height={88} className="mx-auto block" style={{ overflow: 'visible' }}>
            <path d={`M${x1},0 C${x1},30 ${x2},58 ${x2},88`} fill="none" stroke={color} strokeWidth="5" strokeLinecap="round" className="roadmap-path" opacity="0.35" />
        </svg>
    );
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
        // Find the first node that is either available, in_progress, or just not completed
        return allNodes.find(n => n.status === 'in_progress')?.id
            ?? allNodes.find(n => n.status === 'available')?.id
            ?? allNodes.find(n => n.status === 'locked')?.id;
    }, [allNodes]);

    const lastNodeId = allNodes.length > 0 ? allNodes[allNodes.length - 1].id : null;

    // Find the chapter index that contains the currently active node — that's the
    // ONE chapter we display by default. The user can navigate to other chapters
    // via the prev/next buttons. No more 10-chapter infinite scroll.
    const activeChapterIdx = useMemo(() => {
        if (!firstIncompleteNodeId) return 0;
        const idx = chapters.findIndex(c => c.nodes.some(n => n.id === firstIncompleteNodeId));
        return idx >= 0 ? idx : 0;
    }, [chapters, firstIncompleteNodeId]);

    const [viewedChapterIdx, setViewedChapterIdx] = useState(activeChapterIdx);
    // Re-sync if the active chapter changes (e.g. after completing one)
    useEffect(() => { setViewedChapterIdx(activeChapterIdx); }, [activeChapterIdx]);

    const viewedChapter = chapters[viewedChapterIdx];
    const isViewingActive = viewedChapterIdx === activeChapterIdx;
    const completedInChapter = viewedChapter ? viewedChapter.nodes.filter(n => n.status === 'completed').length : 0;
    const totalInChapter = viewedChapter ? viewedChapter.nodes.length : 0;
    const chapterPct = totalInChapter > 0 ? Math.round((completedInChapter / totalInChapter) * 100) : 0;

    const examName = (profile as any)?.target_exam?.name;
    const examFlag = (profile as any)?.target_exam?.language?.flag;

    return (
        <AppLayout>
            <Head title="Dashboard" />

            {/* Loading overlay */}
            {loadingNode && (
                <div className="fixed inset-0 z-50 flex flex-col items-center justify-center gap-6 bg-white/90 backdrop-blur-md">
                    <style>{`
                        @keyframes spinRing { to { transform: rotate(360deg); } }
                        @keyframes pulseGlow { 0%,100% { opacity:0.6; transform:scale(1); } 50% { opacity:1; transform:scale(1.08); } }
                        @keyframes dotBounce { 0%,80%,100% { transform:translateY(0); opacity:0.4; } 40% { transform:translateY(-8px); opacity:1; } }
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
                        <p className="text-base font-bold" style={{ color: OXFORD }}>{t('dashboard.preparing_session', 'Préparation en cours')}</p>
                        <p className="mt-1 text-sm font-medium" style={{ color: '#6b7280' }}>{loadingNode.title}</p>
                    </div>
                </div>
            )}
            
            <style>{`
                @keyframes nodeFloating { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-3px); } }
                @keyframes nodePulsing { 0%, 100% { box-shadow: 0 8px 0 0 rgba(74, 144, 226, 1); } 50% { box-shadow: 0 8px 0 0 rgba(74, 144, 226, 0.4), 0 0 15px rgba(74, 144, 226, 0.4); } }
                @keyframes floatingBadge { 0%, 100% { transform: translate(-50%, 0); } 50% { transform: translate(-50%, -6px); } }

                @keyframes dashMove { from { stroke-dashoffset: 20; } to { stroke-dashoffset: 0; } }
                .roadmap-path { stroke-dasharray: 10 12; animation: dashMove 3s linear infinite; }

                .duo-sidebar-box {
                    border: 2px solid #e5e7eb;
                    border-radius: 20px;
                    padding: 20px;
                    background: white;
                }
                .duo-sidebar-box h3 {
                    font-weight: 900;
                    font-size: 1.05rem;
                    color: ${OXFORD};
                    margin-bottom: 12px;
                }

                .duo-node-btn {
                    position: relative;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 50%;
                    transition: transform 0.1s ease, box-shadow 0.1s ease, filter 0.1s ease;
                    cursor: pointer;
                    margin-bottom: 6px;
                }

                .duo-node-available {
                    background: ${SKY};
                    border: 4px solid #3a82cc;
                    box-shadow: 0 8px 0 0 #2563a0;
                }
                .duo-node-available:active {
                    transform: translateY(8px) scale(0.98);
                    box-shadow: 0 0 0 0 #2563a0;
                }

                .duo-node-completed {
                    background: ${GOLD};
                    border: 4px solid #e08c10;
                    box-shadow: 0 8px 0 0 #b36e05;
                }
                .duo-node-completed:active {
                    transform: translateY(8px) scale(0.98);
                    box-shadow: 0 0 0 0 #b36e05;
                }

                .duo-node-locked {
                    background: #e5e7eb;
                    border: 4px solid #d1d5db;
                    box-shadow: 0 8px 0 0 #9ca3af;
                    cursor: not-allowed;
                }

                .node-badge {
                    position: absolute;
                    bottom: -5px;
                    right: -5px;
                    width: 26px;
                    height: 26px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border: 3px solid white;
                }

                .node-active-ring {
                    position: absolute;
                    inset: -8px;
                    border: 2px dashed ${SKY};
                    border-radius: 50%;
                    animation: spin 8s linear infinite;
                    opacity: 0.3;
                    pointer-events: none;
                }
                
                @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
            `}</style>

            <div className="mx-auto max-w-[1000px] px-4 py-8">
                <div className="grid grid-cols-1 md:grid-cols-[1fr_320px] gap-10">
                    
                    {/* ROADMAP — single active chapter view */}
                    <div className="flex flex-col">
                        {/* Chapter navigator */}
                        <div className="mb-6 flex items-center justify-between gap-3 rounded-2xl bg-white border-2 border-gray-100 p-3 shadow-sm">
                            <button
                                onClick={() => setViewedChapterIdx(i => Math.max(0, i - 1))}
                                disabled={viewedChapterIdx === 0}
                                className="rounded-xl px-3 py-2 text-sm font-black text-slate-600 hover:bg-slate-50 disabled:opacity-30"
                                aria-label="Chapitre précédent"
                            >
                                ←
                            </button>
                            <div className="flex-1 text-center">
                                <p className="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">
                                    Chapitre {viewedChapterIdx + 1} / {chapters.length}
                                    {isViewingActive && <span className="ml-2 inline-block rounded-full bg-emerald-100 px-2 py-0.5 text-emerald-700">EN COURS</span>}
                                    {!isViewingActive && viewedChapterIdx < activeChapterIdx && <span className="ml-2 inline-block rounded-full bg-amber-100 px-2 py-0.5 text-amber-700">TERMINÉ</span>}
                                    {!isViewingActive && viewedChapterIdx > activeChapterIdx && <span className="ml-2 inline-block rounded-full bg-slate-100 px-2 py-0.5 text-slate-500">VERROUILLÉ</span>}
                                </p>
                                <p className="mt-0.5 text-sm font-black truncate" style={{ color: OXFORD }}>{viewedChapter?.name}</p>
                                <div className="mt-1.5 mx-auto h-1.5 max-w-[200px] rounded-full bg-slate-100 overflow-hidden">
                                    <div className="h-full rounded-full transition-all duration-500" style={{ width: `${chapterPct}%`, background: SKY }} />
                                </div>
                                <p className="mt-1 text-[10px] font-bold text-slate-400">{completedInChapter} / {totalInChapter} étapes</p>
                            </div>
                            <button
                                onClick={() => setViewedChapterIdx(i => Math.min(chapters.length - 1, i + 1))}
                                disabled={viewedChapterIdx >= chapters.length - 1 || viewedChapterIdx >= activeChapterIdx}
                                className="rounded-xl px-3 py-2 text-sm font-black text-slate-600 hover:bg-slate-50 disabled:opacity-30"
                                aria-label="Chapitre suivant"
                            >
                                →
                            </button>
                        </div>

                        <div>
                            {viewedChapter && (() => {
                                const cIdx = viewedChapterIdx;
                                const chapter = viewedChapter;
                                const theme = chapterThemes[cIdx % chapterThemes.length];
                                let globalNodeSum = chapters.slice(0, cIdx).reduce((acc, c) => acc + c.nodes.length, 0);

                                return (
                                    <div key={chapter.name} className="space-y-6">
                                        <div className="rounded-2xl p-5 text-white" style={{ background: theme.bg, boxShadow: '0 8px 0 0 rgba(0,0,0,0.15)' }}>
                                            <p className="text-[10px] font-black uppercase tracking-[0.2em] opacity-60">{t('common.chapter', { order: chapter.order, defaultValue: `Chapitre ${chapter.order}` })}</p>
                                            <h2 className="text-xl font-black">{chapter.name}</h2>
                                        </div>

                                        <div className="flex flex-col items-center py-4">
                                            {chapter.nodes.map((node, nIdx) => {
                                                const currentIdx = globalNodeSum + nIdx;
                                                const nextNode = chapter.nodes[nIdx + 1];
                                                
                                                const isActiveNode = node.id === firstIncompleteNodeId;
                                                const isCompleted = node.status === 'completed';
                                                const isAvailable = node.status === 'available' || node.status === 'in_progress';
                                                const isLocked = node.status === 'locked';
                                                const isFinalNode = node.id === lastNodeId;
                                                const offsetX = zigzagX[currentIdx % zigzagX.length] ?? 0;

                                                return (
                                                    <div key={node.id} className="flex flex-col items-center w-full">
                                                        <div className="flex flex-col items-center" style={{ transform: `translateX(${offsetX}px)` }}>
                                                            <div className="relative">
                                                                {isActiveNode && <div className="node-active-ring" />}
                                                                <button
                                                                    disabled={isLocked}
                                                                    onClick={() => (isAvailable || isCompleted) && handleStartNode(node)}
                                                                    className={`w-[74px] h-[74px] duo-node-btn ${isCompleted ? 'duo-node-completed' : isAvailable ? 'duo-node-available' : 'duo-node-locked'}`}
                                                                    style={{ 
                                                                        animation: isActiveNode ? 'nodeFloating 2.5s ease-in-out infinite, nodePulsing 2.5s ease-in-out infinite' : undefined,
                                                                        transform: isActiveNode ? 'scale(1.05)' : undefined
                                                                    }}
                                                                >
                                                                    <NodeIcon 
                                                                        name={isFinalNode && isCompleted ? 'trophy' : (isFinalNode ? 'target' : node.icon)} 
                                                                        size={30} 
                                                                        color={isLocked ? '#9ca3af' : 'white'} 
                                                                    />
                                                                    {isCompleted && (
                                                                        <div className="node-badge" style={{ background: GOLD }}>
                                                                            <Icon name="check" size={14} style={{ filter: 'brightness(0) invert(1)' }} />
                                                                        </div>
                                                                    )}
                                                                    {isLocked && (
                                                                        <div className="node-badge" style={{ background: '#d1d5db' }}>
                                                                            <Icon name="lock" size={12} style={{ filter: 'brightness(0) invert(1)', opacity: 0.8 }} />
                                                                        </div>
                                                                    )}
                                                                </button>
                                                                
                                                                {isActiveNode && (
                                                                    <div className="absolute -top-[52px] left-1/2 -translate-x-1/2 whitespace-nowrap rounded-2xl px-4 py-2.5 text-[12px] font-black text-white tracking-widest z-10"
                                                                        style={{ 
                                                                            background: SKY, 
                                                                            boxShadow: `0 4px 0 0 #2563a0`, 
                                                                            border: '2px solid #3a82cc',
                                                                            animation: 'floatingBadge 2s ease-in-out infinite'
                                                                        }}>
                                                                        {t('common.start', 'COMMENCER').toUpperCase()}
                                                                        <div className="absolute -bottom-1.5 left-1/2 -translate-x-1/2 h-2.5 w-2.5 rotate-45" style={{ background: SKY, borderRight: '2px solid #3a82cc', borderBottom: '2px solid #3a82cc' }} />
                                                                    </div>
                                                                )}
                                                            </div>
                                                            <p className="mt-4 max-w-[110px] text-center text-[12px] font-black uppercase tracking-tight" style={{ color: (isLocked && !isActiveNode) ? 'rgba(26,43,72,0.3)' : OXFORD }}>
                                                                {node.title}
                                                            </p>
                                                        </div>

                                                        {nextNode && (
                                                            <NodePath
                                                                fromX={zigzagX[currentIdx % zigzagX.length]}
                                                                toX={zigzagX[(currentIdx + 1) % zigzagX.length]}
                                                                color={theme.pathColor}
                                                            />
                                                        )}
                                                    </div>
                                                );
                                            })}
                                        </div>

                                        {/* Chapter footer: boss / synthesis */}
                                        {completedInChapter === totalInChapter && totalInChapter > 0 && (
                                            <Link
                                                href={route('chapter.synthesis', { chapterOrder: chapter.order })}
                                                className="mt-4 block rounded-2xl border-2 border-amber-300 bg-gradient-to-br from-amber-50 to-amber-100 p-5 text-center transition hover:from-amber-100 hover:to-amber-200 hover:-translate-y-0.5"
                                                style={{ boxShadow: '0 4px 0 0 #e08c10' }}
                                            >
                                                <p className="text-xs font-black uppercase tracking-widest text-amber-700">🏆 Boss du chapitre</p>
                                                <p className="mt-1 text-lg font-black text-amber-900">Synthèse — Tous les concepts</p>
                                                <p className="mt-1 text-xs text-amber-700/80">≥80% pour valider définitivement le chapitre</p>
                                            </Link>
                                        )}
                                    </div>
                                );
                            })()}
                        </div>
                    </div>

                    {/* SIDEBAR — visible on mobile too (stacks below the roadmap) */}
                    <div className="flex flex-col gap-6">
                        {examName && (
                            <div className="flex items-center gap-3 p-2">
                                {examFlag && <FlagImg flag={examFlag} size={32} />}
                                <div>
                                    <h2 className="text-lg font-black" style={{ color: OXFORD }}>{examName}</h2>
                                </div>
                            </div>
                        )}

                        <div className="duo-sidebar-box grid grid-cols-2 gap-4">
                            <div className="flex flex-col items-center group cursor-help">
                                <div className="relative">
                                    <CustomIcon name="medal" className="h-7 w-7 transition-transform group-hover:scale-110" />
                                    {(profile?.streak_current ?? 0) > 0 && (
                                        <div className="absolute -top-1 -right-1">
                                            <div className="relative">
                                                <div className="absolute inset-0 bg-orange-500 blur-md animate-pulse rounded-full opacity-50" />
                                                <div className="relative h-4 w-4 bg-gradient-to-t from-red-600 to-orange-400 rounded-full flex items-center justify-center border border-white shadow-lg">
                                                    <div className="h-1.5 w-1.5 bg-yellow-200 rounded-full animate-bounce" />
                                                </div>
                                            </div>
                                        </div>
                                    )}
                                </div>
                                <span className="font-black text-[#ef4444] text-xl mt-1">{profile?.streak_current ?? 0}</span>
                                <span className="text-[10px] uppercase font-black opacity-40">Jours</span>
                            </div>
                            <div className="flex flex-col items-center group">
                                <CustomIcon name="trophy" className="h-7 w-7 transition-transform group-hover:rotate-12" />
                                <span className="font-black text-[#F5A623] text-xl mt-1">{profile?.xp_total ?? 0}</span>
                                <span className="text-[10px] uppercase font-black opacity-40">Total XP</span>
                            </div>
                        </div>

                        {/* Pilier 9: Next Lesson CTA */}
                        {curriculum && (
                            <Link
                                href="/lessons/next"
                                className="duo-sidebar-box group relative overflow-hidden p-5 text-white"
                                style={{ background: `linear-gradient(135deg, ${SKY}, #3478c8)`, border: `2px solid #3a82cc`, boxShadow: '0 4px 0 0 #2563a0' }}
                            >
                                <p className="text-[9px] font-black uppercase tracking-[0.2em] opacity-70">▶ Prochaine leçon</p>
                                <p className="text-sm font-black mt-1">{curriculum.current_objective?.title || 'Commencer'}</p>
                                <p className="text-[10px] mt-0.5 opacity-70">
                                    Objectif {curriculum.current_index + 1} / {curriculum.total_objectives}
                                </p>
                                {curriculum.consecutive_failures >= 2 && (
                                    <p className="mt-2 text-[9px] font-bold bg-white/20 rounded-lg px-2 py-1 inline-block">🔄 Consolidation</p>
                                )}
                                <div className="absolute -right-4 -top-4 h-20 w-20 rounded-full bg-white/10" />
                            </Link>
                        )}

                        {/* Quick Practice CTA — generates a fresh adaptive practice session */}
                        <Link
                            href={route('practice.index')}
                            className="duo-sidebar-box group relative overflow-hidden p-5 text-white"
                            style={{ background: `linear-gradient(135deg, #48b77b, #3a9d68)`, border: `2px solid #2d8a55`, boxShadow: '0 4px 0 0 #1f6e42' }}
                        >
                            <p className="text-[9px] font-black uppercase tracking-[0.2em] opacity-70">⚡ Pratique rapide</p>
                            <p className="text-sm font-black mt-1">5 minutes d'exercices adaptés</p>
                            <p className="text-[10px] mt-0.5 opacity-70">Mix de types · niveau ajusté</p>
                            <div className="absolute -right-4 -top-4 h-20 w-20 rounded-full bg-white/10" />
                        </Link>

                        <div className="duo-sidebar-box">
                            <h3>Outils de Révision</h3>
                            <div className="space-y-3">
                                {/* Pilier 9: Mon Parcours */}
                                <Link 
                                    href="/lessons"
                                    className="flex items-center gap-3 p-3 rounded-xl hover:bg-blue-50 transition-colors border-2 border-transparent hover:border-blue-100"
                                >
                                    <div className="h-10 w-10 rounded-xl flex items-center justify-center" style={{ background: 'rgba(74,144,226,0.1)' }}>
                                        <span className="text-lg">📘</span>
                                    </div>
                                    <div className="flex-1">
                                        <p className="text-sm font-black" style={{ color: OXFORD }}>Mon Parcours</p>
                                        <p className="text-[10px] uppercase font-bold opacity-50">Leçons & Programme</p>
                                    </div>
                                </Link>

                                {/* Pilier 3: Due errors review */}
                                <Link 
                                    href="/errors"
                                    className="flex items-center gap-3 p-3 rounded-xl hover:bg-red-50 transition-colors border-2 border-transparent hover:border-red-100"
                                >
                                    <div className="relative h-10 w-10 rounded-xl flex items-center justify-center bg-red-50">
                                        <span className="text-lg">🔄</span>
                                        {(dueErrorsCount ?? 0) > 0 && (
                                            <div className="absolute -top-1 -right-1 h-5 w-5 rounded-full bg-red-500 flex items-center justify-center">
                                                <span className="text-[9px] font-black text-white">{dueErrorsCount}</span>
                                            </div>
                                        )}
                                    </div>
                                    <div className="flex-1">
                                        <p className="text-sm font-black" style={{ color: OXFORD }}>Révision SM-2</p>
                                        <p className="text-[10px] uppercase font-bold opacity-50">
                                            {(dueErrorsCount ?? 0) > 0 ? `${dueErrorsCount} erreur${(dueErrorsCount ?? 0) > 1 ? 's' : ''} à revoir` : 'À jour !'}
                                        </p>
                                    </div>
                                </Link>

                                <Link 
                                    href={route('dictionary.index')} 
                                    className="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-50 transition-colors border-2 border-transparent hover:border-slate-100"
                                >
                                    <div className="h-10 w-10 bg-blue-50 rounded-xl flex items-center justify-center">
                                        <Icon name="book" size={20} />
                                    </div>
                                    <div className="flex-1">
                                        <p className="text-sm font-black" style={{ color: OXFORD }}>Mon Dictionnaire</p>
                                        <p className="text-[10px] uppercase font-bold opacity-50">Exploration & Vocabulaire</p>
                                    </div>
                                </Link>

                                <button 
                                    onClick={() => router.post(route('dictionary.discover'))}
                                    className="w-full flex items-center gap-3 p-3 rounded-xl bg-orange-50 hover:bg-orange-100 transition-colors border-2 border-orange-100 group"
                                >
                                    <div className="h-10 w-10 bg-orange-400 rounded-xl flex items-center justify-center transition-transform group-hover:scale-110">
                                        <Icon name="sparkles" size={20} style={{ filter: 'brightness(0) invert(1)' }} />
                                    </div>
                                    <div className="text-left">
                                        <p className="text-sm font-black text-orange-900">Découvrir un mot</p>
                                        <p className="text-[10px] uppercase font-bold text-orange-700/60">+5 XP Bonus</p>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <div className="duo-sidebar-box">
                            <h3>Progression</h3>
                            <div className="mb-3 flex items-center justify-between text-xs font-black uppercase tracking-wider">
                                <span className="opacity-60">{stats.completed_nodes} / {stats.total_nodes}</span>
                                <span className="text-blue-600">{stats.progress_percent}%</span>
                            </div>
                            <div className="h-4 w-full bg-slate-100 rounded-full overflow-hidden p-1 shadow-inner">
                                <div className="h-full bg-gradient-to-r from-blue-400 to-blue-600 rounded-full transition-all duration-1000 relative" style={{ width: `${stats.progress_percent}%` }}>
                                    <div className="absolute inset-0 bg-white/20 animate-[pulse_2s_infinite]" />
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
