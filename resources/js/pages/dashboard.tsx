import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import * as Flags from 'country-flag-icons/react/3x2';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import type { SharedData, UserProfile } from '@/types';

function Icon({ name, size = 20, style }: { name: string; size?: number; style?: React.CSSProperties }) {
    return <img src={`/icons/${name}.png`} alt="" width={size} height={size} style={{ objectFit: 'contain', ...style }} />;
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

// Custom icon component using icons from /public/icons
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
    id: number; title: string; description: string;
    icon: string; skill_type: string; level: string; xp_reward: number;
    status: 'locked' | 'available' | 'in_progress' | 'completed';
    scheduled_for?: string;
}

interface Chapter {
    name: string;
    order: number;
    nodes: RoadmapNode[];
}

interface PageProps {
    profile: UserProfile | null;
    chapters: Chapter[];
    stats: {
        total_nodes: number;
        completed_nodes: number;
        progress_percent: number;
    }
}

/* ─── Brand colors ─── */
const OXFORD = '#1A2B48';
const SKY = '#4A90E2';
const GOLD = '#F5A623';
const PEARL = '#F4F7F6';

/* ─── Custom node icons ─── */
function NodeIcon({ name, size, color = 'white' }: { name: string; size: number; color?: string }) {
    const s = size;
    switch (name) {
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
            <path d={`M${x1},0 C${x1},30 ${x2},58 ${x2},88`} fill="none" stroke={color} strokeWidth="5" strokeLinecap="round" strokeDasharray="1 10" opacity="0.35" />
        </svg>
    );
}

function RoadmapNodeItem({ node, index, mounted, isNext, pathColor }: { node: RoadmapNode; index: number; mounted: boolean; isNext: boolean; pathColor: string }) {
    const { t } = useTranslation();
    const isCompleted = node.status === 'completed';
    const isAvailable = node.status === 'available' || node.status === 'in_progress';
    const isLocked = node.status === 'locked';
    const offsetX = zigzagX[index % zigzagX.length] ?? 0;
    const size = 72;

    return (
        <div className="flex flex-col items-center" style={{ transform: `translateX(${offsetX}px)`, opacity: mounted ? 1 : 0, transition: `opacity 0.5s ease ${index * 50}ms` }}>
            <div className="relative">
                <button
                    disabled={isLocked}
                    onClick={() => isAvailable && router.visit(route('node.start', node.id))}
                    className={`relative flex items-center justify-center rounded-full border-[3px] ${isCompleted ? 'duo-node-completed' : isAvailable ? 'duo-node-available' : 'duo-node-locked'
                        }`}
                    style={{
                        width: size, height: size,
                        background: isCompleted ? OXFORD : isAvailable ? SKY : '#e0e4ea',
                        animation: isAvailable && !isCompleted ? 'nodeBounce 2.2s ease-in-out infinite' : undefined
                    }}
                >
                    {isCompleted ? <Icon name="check" size={20} style={{ filter: 'brightness(0) invert(1)' }} /> : isLocked ? <Icon name="shield" size={20} style={{ opacity: 0.25 }} /> : <NodeIcon name={node.icon} size={24} />}
                </button>
                {isNext && isAvailable && (
                    <div className="absolute -top-14 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-2xl px-5 py-2.5 text-[12px] font-black text-white tracking-widest animate-bounce"
                        style={{ background: SKY, boxShadow: `0 5px 0 0 #2563a0`, border: '2px solid #3a82cc', borderBottom: 'none' }}>
                        {t('common.start').toUpperCase()}
                        <div className="absolute -bottom-1.5 left-1/2 -translate-x-1/2 h-3 w-3 rotate-45" style={{ background: SKY }} />
                    </div>
                )}
            </div>
            <p className="mt-2 max-w-[100px] text-center text-[10px] font-bold uppercase tracking-tight" style={{ color: isLocked ? 'rgba(26,43,72,0.3)' : OXFORD }}>
                {node.title}
            </p>
        </div>
    );
}

export default function Dashboard() {
    const { t } = useTranslation();
    const { auth, profile, chapters, stats } = usePage<SharedData & PageProps>().props;
    const [mounted, setMounted] = useState(false);
    useEffect(() => setMounted(true), []);

    const examName = (profile as any)?.target_exam?.name;
    const examFlag = (profile as any)?.target_exam?.language?.flag;

    return (
        <AppLayout>
            <Head title="Dashboard" />
            <style>{`
                @keyframes nodeBounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-5px); } }

                /* ── Duolingo-style thick block shadows ── */

                /* Cards */
                .duo-card {
                    box-shadow: 0 4px 0 0 #d1d5db !important;
                    border: 2px solid #e5e7eb !important;
                    border-bottom: none !important;
                    transition: transform 0.12s cubic-bezier(.2,.8,.4,1), box-shadow 0.12s cubic-bezier(.2,.8,.4,1) !important;
                }

                /* Badges */
                .duo-badge {
                    box-shadow: 0 3px 0 0 #d1d5db !important;
                    border: 2px solid #e5e7eb !important;
                    border-bottom: none !important;
                    transition: transform 0.1s ease, box-shadow 0.1s ease !important;
                }

                /* Chapter banners */
                .duo-chapter {
                    box-shadow: 0 8px 0 0 rgba(0,0,0,0.3) !important;
                    border: none !important;
                    transition: transform 0.12s ease, box-shadow 0.12s ease !important;
                }

                /* ── Available node: thick colored shadow ── */
                .duo-node-available {
                    box-shadow: 0 8px 0 0 #2563a0 !important;
                    border-color: #3a82cc !important;
                    border-width: 4px !important;
                    transition: transform 0.1s cubic-bezier(.2,.8,.4,1), box-shadow 0.1s cubic-bezier(.2,.8,.4,1), filter 0.1s ease !important;
                    cursor: pointer;
                }
                .duo-node-available:hover {
                    transform: translateY(-4px) scale(1.08) !important;
                    box-shadow: 0 12px 0 0 #2563a0 !important;
                    filter: brightness(1.12);
                }
                .duo-node-available:active {
                    transform: translateY(6px) scale(0.96) !important;
                    box-shadow: 0 2px 0 0 #2563a0 !important;
                    filter: brightness(0.92);
                    animation: none !important;
                }

                /* ── Completed node ── */
                .duo-node-completed {
                    box-shadow: 0 8px 0 0 #0e1a2e !important;
                    border-color: #15253f !important;
                    border-width: 4px !important;
                    transition: transform 0.1s cubic-bezier(.2,.8,.4,1), box-shadow 0.1s cubic-bezier(.2,.8,.4,1) !important;
                    cursor: pointer;
                }
                .duo-node-completed:hover {
                    transform: translateY(-3px) scale(1.05) !important;
                    box-shadow: 0 11px 0 0 #0e1a2e !important;
                }
                .duo-node-completed:active {
                    transform: translateY(6px) scale(0.96) !important;
                    box-shadow: 0 2px 0 0 #0e1a2e !important;
                }

                /* ── Locked node ── */
                .duo-node-locked {
                    box-shadow: 0 6px 0 0 #c8cdd4 !important;
                    border-color: #d5d9e0 !important;
                    border-width: 4px !important;
                    background: #e8ebef !important;
                    cursor: not-allowed;
                }

                /* ── Bottom nav links ── */
                .duo-link {
                    box-shadow: 0 5px 0 0 #d1d5db !important;
                    border: 2px solid #e5e7eb !important;
                    border-bottom: none !important;
                    transition: transform 0.12s cubic-bezier(.2,.8,.4,1), box-shadow 0.12s cubic-bezier(.2,.8,.4,1) !important;
                    cursor: pointer;
                    font-weight: 800 !important;
                    text-transform: uppercase;
                    letter-spacing: 0.02em;
                    color: ${OXFORD};
                }
                .duo-link:hover {
                    transform: translateY(-3px) !important;
                    box-shadow: 0 8px 0 0 #d1d5db !important;
                    background: #f8fafc !important;
                }
                .duo-link:active {
                    transform: translateY(4px) !important;
                    box-shadow: 0 1px 0 0 #d1d5db !important;
                }
            `}</style>

            <div className="mx-auto max-w-2xl px-4 py-8">
                {/* Header Stats */}
                <div className="mb-8 flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-black tracking-tight" style={{ color: OXFORD }}>
                            {auth.user.name.split(' ')[0]}
                            {examName && <span className="ml-2 text-sm font-medium text-muted-foreground inline-flex items-center gap-1">{examFlag && <FlagImg flag={examFlag} size={18} />} {examName}</span>}
                        </h1>
                    </div>
                    <div className="flex gap-2">
                        <div className="duo-badge flex items-center gap-1.5 rounded-xl border bg-card px-3 py-2 text-sm font-bold">
                            <CustomIcon name="medal" className="h-4 w-4" style={{ filter: 'brightness(0) saturate(100%) invert(50%) sepia(96%) saturate(1762%) hue-rotate(332deg) brightness(102%) contrast(96%)' }} />
                            <span>{profile?.streak_current ?? 0}</span>
                        </div>
                        <div className="duo-badge flex items-center gap-1.5 rounded-xl border bg-card px-3 py-2 text-sm font-bold">
                            <CustomIcon name="trophy" className="h-4 w-4" style={{ filter: 'brightness(0) saturate(100%) invert(84%) sepia(40%) saturate(1734%) hue-rotate(353deg) brightness(94%) contrast(86%)' }} />
                            <span>{(profile?.xp_total ?? 0).toLocaleString()}</span>
                        </div>
                    </div>
                </div>

                {/* Progress Card */}
                <div className="duo-card mb-10 rounded-2xl border bg-card p-5">
                    <div className="mb-3 flex items-center justify-between text-xs font-bold uppercase tracking-wider text-muted-foreground">
                        <span>{t('exercise.progress', { current: stats.completed_nodes, total: stats.total_nodes })}</span>
                        <span>{stats.progress_percent}%</span>
                    </div>
                    <div className="h-5 w-full overflow-hidden rounded-full" style={{ background: '#e5e7eb', boxShadow: 'inset 0 3px 0 rgba(0,0,0,0.08)' }}>
                        <div className="h-full rounded-full transition-all duration-1000" style={{
                            width: `${Math.max(stats.progress_percent, 2)}%`,
                            background: `linear-gradient(180deg, #5BA0F0 0%, ${SKY} 40%, #3A80D2 100%)`,
                            boxShadow: 'inset 0 -3px 0 rgba(0,0,0,0.15), inset 0 2px 0 rgba(255,255,255,0.3)'
                        }} />
                    </div>
                </div>

                {/* Chapters & Nodes */}
                <div className="space-y-12">
                    {chapters.map((chapter, cIdx) => {
                        const theme = chapterThemes[cIdx % chapterThemes.length];
                        let globalNodeIdx = chapters.slice(0, cIdx).reduce((acc, c) => acc + c.nodes.length, 0);

                        return (
                            <div key={chapter.name} className="space-y-6">
                                <div className="duo-chapter rounded-2xl p-5 text-white" style={{ background: theme.bg }}>
                                    <p className="text-[10px] font-black uppercase tracking-[0.2em] opacity-60">{t('common.chapter', { order: chapter.order })}</p>
                                    <h2 className="text-xl font-black">{chapter.name}</h2>
                                </div>

                                <div className="flex flex-col items-center py-4">
                                    {chapter.nodes.map((node, nIdx) => {
                                        const currentIdx = globalNodeIdx + nIdx;
                                        const nextNode = chapter.nodes[nIdx + 1] || chapters[cIdx + 1]?.nodes[0];
                                        const isNext = node.status === 'available' || node.status === 'in_progress';

                                        return (
                                            <div key={node.id} className="flex flex-col items-center w-full">
                                                <RoadmapNodeItem
                                                    node={node}
                                                    index={currentIdx}
                                                    mounted={mounted}
                                                    isNext={isNext && !chapter.nodes.slice(0, nIdx).some(n => n.status === 'available')}
                                                    pathColor={theme.pathColor}
                                                />
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
                            </div>
                        );
                    })}
                </div>

                {/* Bottom Navigation Quick Access */}
                <div className="mt-16 grid grid-cols-2 gap-4">
                    <Link href={route('practice.index')} className="duo-link flex items-center justify-between rounded-2xl border bg-card p-4 font-bold">
                        <span className="flex items-center gap-2">
                            <CustomIcon name="puzzle" className="h-5 w-5" />
                            {t('common.start')}
                        </span>
                        <Icon name="chevron-right" size={20} />
                    </Link>
                    <Link href={route('ai-tools.index')} className="duo-link flex items-center justify-between rounded-2xl border bg-card p-4 font-bold">
                        <span className="flex items-center gap-2">
                            <CustomIcon name="video" className="h-5 w-5" />
                            {t('common.ai_tools')}
                        </span>
                        <Icon name="chevron-right" size={20} />
                    </Link>
                </div>
            </div>
        </AppLayout>
    );
}
