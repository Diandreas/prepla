import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import {
    BookOpen, Brain, Crown, Flame, Globe, Headphones,
    Lock, Mic, Pen, Sparkles, Star, Target, Trophy, Zap,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { SharedData, UserProfile } from '@/types';

/* ─── Types ─── */
interface RoadmapNode {
    id: number; sort_order: number; title: string; description: string;
    icon: string; skill_type: string; level: string; xp_reward: number;
    node_type: string; status: 'locked' | 'available' | 'in_progress' | 'completed';
}
interface PageProps { profile: UserProfile | null; roadmap: RoadmapNode[]; }

/* ─── Brand colors ─── */
const OXFORD = '#1A2B48';
const SKY    = '#4A90E2';
const GOLD   = '#F5A623';
const PEARL  = '#F4F7F6';

/* ─── Icon map ─── */
const iconMap: Record<string, typeof BookOpen> = {
    book: BookOpen, headphones: Headphones, pen: Pen, mic: Mic,
    trophy: Trophy, star: Star, zap: Zap, target: Target, brain: Brain, globe: Globe,
};

/* ─── Chapter themes ─── */
const chapterThemes = [
    { label: 'Fondamentaux',            bg: `linear-gradient(135deg, ${OXFORD} 0%, #2a3f6a 100%)`, pathColor: OXFORD },
    { label: 'Vocabulaire & Grammaire', bg: `linear-gradient(135deg, ${SKY} 0%, #2a6fc0 100%)`,   pathColor: SKY    },
    { label: 'Compréhension',           bg: `linear-gradient(135deg, ${OXFORD} 0%, ${SKY} 100%)`, pathColor: '#3070b0' },
    { label: 'Expression',              bg: `linear-gradient(135deg, #2a6fc0 0%, ${OXFORD} 100%)`, pathColor: '#2a6fc0' },
    { label: 'Examen Final',            bg: `linear-gradient(135deg, ${GOLD} 0%, #e08c10 100%)`,  pathColor: GOLD   },
];

/* ─── Zigzag offsets ─── */
const zigzagX = [0, 55, 90, 55, 0, -55, -90, -55, 0, 55, 90, 55, 0, -55, -90];

/* ─── Animated counter ─── */
function useAnimatedCounter(end: number, duration = 1000, delay = 0) {
    const [value, setValue] = useState(0);
    const rafRef = useRef<number | null>(null);
    useEffect(() => {
        if (end === 0) return;
        const t = setTimeout(() => {
            const start = performance.now();
            const step = (now: number) => {
                const p = Math.min((now - start) / duration, 1);
                setValue(Math.round((1 - (1 - p) * (1 - p)) * end));
                if (p < 1) rafRef.current = requestAnimationFrame(step);
            };
            rafRef.current = requestAnimationFrame(step);
        }, delay);
        return () => { clearTimeout(t); if (rafRef.current) cancelAnimationFrame(rafRef.current); };
    }, [end, duration, delay]);
    return value;
}

/* ─── SVG connector path between nodes ─── */
function NodePath({ fromX, toX, color }: { fromX: number; toX: number; color: string }) {
    const w = 260;
    const cx = w / 2;
    const x1 = cx + fromX;
    const x2 = cx + toX;
    const y1 = 0;
    const y2 = 88; // gap between node centers
    return (
        <svg
            width={w} height={y2}
            className="mx-auto block"
            style={{ overflow: 'visible' }}
        >
            <path
                d={`M${x1},${y1} C${x1},${y1 + 30} ${x2},${y2 - 30} ${x2},${y2}`}
                fill="none"
                stroke={color}
                strokeWidth="5"
                strokeLinecap="round"
                strokeDasharray="1 10"
                opacity="0.35"
            />
        </svg>
    );
}

/* ─── Chapter Banner ─── */
function ChapterBanner({ chapter, title, theme, mounted, delay }: {
    chapter: number; title: string; theme: (typeof chapterThemes)[0]; mounted: boolean; delay: number;
}) {
    return (
        <div
            className="relative overflow-hidden rounded-2xl px-6 py-5 text-white shadow-lg"
            style={{
                background: theme.bg,
                opacity: mounted ? 1 : 0,
                transform: mounted ? 'translateY(0)' : 'translateY(16px)',
                transition: `all 0.5s cubic-bezier(0.22,1,0.36,1) ${delay}ms`,
            }}
        >
            {/* Decorative circle */}
            <div className="pointer-events-none absolute -right-6 -top-6 h-24 w-24 rounded-full bg-white/10" />
            <div className="pointer-events-none absolute -bottom-4 -left-4 h-16 w-16 rounded-full bg-white/6" />
            <p className="text-[11px] font-bold uppercase tracking-[0.18em] text-white/60">
                Chapitre {chapter}
            </p>
            <p className="mt-0.5 text-lg font-bold">{title}</p>
        </div>
    );
}

/* ─── Single Roadmap Node ─── */
function RoadmapNodeItem({ node, index, mounted, isNext, pathColor }: {
    node: RoadmapNode; index: number; mounted: boolean; isNext: boolean; pathColor: string;
}) {
    // Popup is always visible for the next available node (like Duolingo)
    const popupOpen = isNext && isAvailable;
    const Icon = iconMap[node.icon] ?? BookOpen;
    const isCompleted  = node.status === 'completed';
    const isAvailable  = node.status === 'available' || node.status === 'in_progress';
    const isLocked     = node.status === 'locked';
    const isBoss       = node.node_type === 'boss';
    const isPractice   = node.node_type === 'practice';
    const offsetX      = zigzagX[index % zigzagX.length] ?? 0;

    // Sizes
    const size  = isBoss ? 96 : isPractice ? 80 : 72;
    const iSize = isBoss ? 32 : isPractice ? 28 : 24;

    // Colors
    const bg = isCompleted
        ? `linear-gradient(135deg, ${OXFORD} 0%, #2a3f6a 100%)`
        : isAvailable
          ? isBoss
            ? `linear-gradient(135deg, ${GOLD} 0%, #e08c10 100%)`
            : `linear-gradient(135deg, ${SKY} 0%, #3478c8 100%)`
          : 'none';
    const borderColor = isCompleted ? OXFORD
        : isAvailable ? (isBoss ? GOLD : SKY)
        : 'rgba(26,43,72,0.12)';
    const shadowColor = isCompleted ? 'rgba(26,43,72,0.25)'
        : isAvailable ? (isBoss ? 'rgba(245,166,35,0.4)' : 'rgba(74,144,226,0.4)')
        : 'transparent';

    function handleClick() {
        if (isAvailable) {
            router.visit(`/node/${node.id}/start`);
        }
    }

    // Stars for completed (based on xp_reward tier)
    const starCount = isCompleted ? (node.xp_reward >= 20 ? 3 : node.xp_reward >= 10 ? 2 : 1) : 0;

    return (
        <div
            className="flex flex-col items-center"
            style={{
                transform: `translateX(${offsetX}px)`,
                opacity: mounted ? 1 : 0,
                transition: `opacity 0.5s ease ${300 + index * 90}ms`,
            }}
        >
            {/* Node button */}
            <div className="relative">
                <button
                    disabled={isLocked}
                    onClick={handleClick}
                    className="relative flex items-center justify-center rounded-full border-[3px] transition-all duration-200"
                    style={{
                        width: size, height: size,
                        background: bg,
                        borderColor,
                        boxShadow: isAvailable ? `0 6px 20px ${shadowColor}, 0 0 0 6px ${shadowColor.replace('0.4', '0.12')}` : isCompleted ? `0 4px 12px ${shadowColor}` : 'none',
                        backgroundColor: isLocked ? 'rgba(26,43,72,0.06)' : undefined,
                        animation: isAvailable && !isLocked ? 'nodeBounce 2.2s ease-in-out infinite' : undefined,
                    }}
                >
                    {isCompleted
                        ? <svg width={iSize} height={iSize} viewBox="0 0 24 24" fill="none" stroke="white" strokeWidth={2.5}><polyline points="20 6 9 17 4 12"/></svg>
                        : isLocked
                          ? <Lock size={20} color="rgba(26,43,72,0.3)" />
                          : isBoss
                            ? <Crown size={iSize} color="white" />
                            : <Icon size={iSize} color="white" />
                    }

                    {/* Pulse rings for available */}
                    {isAvailable && (
                        <>
                            <span
                                className="absolute inset-0 rounded-full"
                                style={{ animation: 'pulseRing 2.2s ease-out infinite', border: `3px solid ${isBoss ? GOLD : SKY}`, opacity: 0 }}
                            />
                            <span
                                className="absolute inset-0 rounded-full"
                                style={{ animation: 'pulseRing 2.2s ease-out 0.7s infinite', border: `3px solid ${isBoss ? GOLD : SKY}`, opacity: 0 }}
                            />
                        </>
                    )}
                </button>

                {/* XP badge */}
                {!isLocked && (
                    <span
                        className="absolute -bottom-1.5 -right-1.5 flex min-w-[22px] items-center justify-center rounded-full px-1 text-[9px] font-bold text-white shadow-md"
                        style={{
                            background: isBoss ? GOLD : isCompleted ? OXFORD : SKY,
                            height: 22,
                            border: `2px solid ${PEARL}`,
                        }}
                    >
                        {node.xp_reward >= 100 ? '★' : `+${node.xp_reward}`}
                    </span>
                )}
            </div>

            {/* Stars for completed nodes */}
            {isCompleted && (
                <div className="mt-1.5 flex gap-0.5">
                    {[0, 1, 2].map(i => (
                        <Star
                            key={i}
                            size={10}
                            style={{
                                fill: i < starCount ? GOLD : 'transparent',
                                color: i < starCount ? GOLD : 'rgba(26,43,72,0.2)',
                            }}
                        />
                    ))}
                </div>
            )}

            {/* Node label */}
            <p
                className="mt-1.5 max-w-[100px] text-center text-[11px] font-semibold leading-snug"
                style={{ color: isLocked ? 'rgba(26,43,72,0.3)' : isCompleted ? OXFORD : isAvailable ? SKY : 'rgba(26,43,72,0.5)' }}
            >
                {node.title}
            </p>

            {/* Popup below the node — Duolingo style */}
            {isNext && isAvailable && popupOpen && (
                <div
                    className="relative mt-3 w-52 rounded-2xl p-4 text-center"
                    style={{
                        background: '#fff',
                        boxShadow: `0 12px 40px rgba(74,144,226,0.22), 0 2px 8px rgba(0,0,0,0.06)`,
                        border: `2px solid rgba(74,144,226,0.15)`,
                        animation: 'fadeSlideUp 0.25s cubic-bezier(0.22,1,0.36,1) both',
                    }}
                >
                    {/* Arrow up */}
                    <div
                        className="absolute -top-2.5 left-1/2 -translate-x-1/2 h-5 w-5 rotate-45"
                        style={{ background: '#fff', border: `2px solid rgba(74,144,226,0.15)`, borderBottom: 'none', borderRight: 'none' }}
                    />
                    <p className="text-sm font-bold" style={{ color: OXFORD }}>{node.title}</p>
                    <div className="mt-1.5 flex items-center justify-center gap-1.5">
                        {node.level && (
                            <span className="rounded-full px-2 py-0.5 text-[10px] font-semibold text-white" style={{ background: SKY }}>
                                {node.level}
                            </span>
                        )}
                        <span className="rounded-full px-2 py-0.5 text-[10px] font-semibold" style={{ background: `rgba(245,166,35,0.15)`, color: GOLD }}>
                            +{node.xp_reward} XP
                        </span>
                    </div>
                    <button
                        onClick={(e) => { e.stopPropagation(); router.visit(`/node/${node.id}/start`); }}
                        className="mt-3 w-full rounded-xl py-2.5 text-sm font-bold text-white transition-all active:scale-95"
                        style={{
                            background: `linear-gradient(135deg, ${SKY} 0%, #3478c8 100%)`,
                            boxShadow: `0 4px 14px rgba(74,144,226,0.4)`,
                        }}
                    >
                        COMMENCER
                    </button>
                </div>
            )}
        </div>
    );
}

/* ─── Main Dashboard ─── */
export default function Dashboard() {
    const { auth, userProfile } = usePage<SharedData>().props;
    const { profile, roadmap } = usePage<SharedData & PageProps>().props;
    const [mounted, setMounted] = useState(false);
    useEffect(() => setMounted(true), []);

    const effectiveProfile = profile ?? userProfile;
    const streak   = effectiveProfile?.streak_current ?? 0;
    const xp       = effectiveProfile?.xp_total ?? 0;
    const level    = effectiveProfile?.current_level ?? null;
    const examName = (effectiveProfile as any)?.target_exam?.name ?? null;
    const examFlag = (effectiveProfile as any)?.target_exam?.language?.flag ?? '';

    const streakCount = useAnimatedCounter(streak, 1000, 300);
    const xpCount     = useAnimatedCounter(xp, 1400, 400);

    const completedNodes   = roadmap?.filter(n => n.status === 'completed').length ?? 0;
    const totalNodes       = roadmap?.length ?? 0;
    const progressPercent  = totalNodes > 0 ? Math.round((completedNodes / totalNodes) * 100) : 0;
    const nextNodeIndex    = roadmap?.findIndex(n => n.status === 'available' || n.status === 'in_progress') ?? -1;

    const CHAPTER_SIZE = 3;
    const chapters: { chapterIdx: number; nodes: RoadmapNode[] }[] = [];
    if (roadmap) {
        for (let i = 0; i < roadmap.length; i += CHAPTER_SIZE) {
            chapters.push({ chapterIdx: Math.floor(i / CHAPTER_SIZE), nodes: roadmap.slice(i, i + CHAPTER_SIZE) });
        }
    }

    const greeting = () => {
        const h = new Date().getHours();
        return h < 12 ? 'Bonjour' : h < 18 ? 'Bon après-midi' : 'Bonsoir';
    };

    return (
        <AppLayout>
            <Head title="Tableau de bord" />

            <style>{`
                @keyframes nodeBounce {
                    0%, 100% { transform: translateY(0px); }
                    50%       { transform: translateY(-5px); }
                }
                @keyframes pulseRing {
                    0%   { transform: scale(1);    opacity: 0.7; }
                    80%  { transform: scale(1.55); opacity: 0; }
                    100% { transform: scale(1.55); opacity: 0; }
                }
                @keyframes shimmer {
                    0%   { background-position: -200% center; }
                    100% { background-position:  200% center; }
                }
                @keyframes fadeSlideUp {
                    from { opacity: 0; transform: translateY(12px); }
                    to   { opacity: 1; transform: translateY(0); }
                }
            `}</style>

            <div className="mx-auto max-w-2xl px-4 py-5">

                {/* ── TOP BAR ── */}
                <div
                    className="mb-5 flex items-center justify-between"
                    style={{ opacity: mounted ? 1 : 0, transition: 'opacity 0.4s ease 100ms' }}
                >
                    <div>
                        <p className="text-xs font-medium" style={{ color: 'rgba(26,43,72,0.5)' }}>{greeting()}</p>
                        <h1 className="text-xl font-extrabold" style={{ color: OXFORD }}>
                            {auth.user.name.split(' ')[0]}
                            {examName && (
                                <span className="ml-2 text-sm font-semibold" style={{ color: SKY }}>
                                    {examFlag} {examName}
                                </span>
                            )}
                        </h1>
                    </div>

                    {/* Stat pills */}
                    <div className="flex items-center gap-2">
                        {[
                            { icon: <Flame size={15} color="#F97316" />, value: streakCount },
                            { icon: <Zap size={15} color={GOLD} />, value: xpCount.toLocaleString() },
                        ].map((s, i) => (
                            <div
                                key={i}
                                className="flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-sm font-bold"
                                style={{ borderColor: 'rgba(26,43,72,0.12)', background: '#fff', color: OXFORD, boxShadow: '0 1px 4px rgba(26,43,72,0.07)' }}
                            >
                                {s.icon} {s.value}
                            </div>
                        ))}
                        {level && (
                            <div
                                className="rounded-full px-3 py-1.5 text-sm font-bold text-white"
                                style={{ background: `linear-gradient(135deg, ${SKY} 0%, #3478c8 100%)` }}
                            >
                                {level}
                            </div>
                        )}
                    </div>
                </div>

                {/* ── PROGRESS BAR ── */}
                {totalNodes > 0 && (
                    <div
                        className="mb-6 overflow-hidden rounded-2xl border bg-white p-4"
                        style={{
                            borderColor: 'rgba(26,43,72,0.08)',
                            boxShadow: '0 1px 6px rgba(26,43,72,0.06)',
                            opacity: mounted ? 1 : 0,
                            transition: 'opacity 0.4s ease 200ms',
                        }}
                    >
                        <div className="mb-2 flex items-center justify-between">
                            <span className="text-xs font-semibold" style={{ color: 'rgba(26,43,72,0.5)' }}>Progression du parcours</span>
                            <span className="text-xs font-bold" style={{ color: SKY }}>{completedNodes}/{totalNodes} · {progressPercent}%</span>
                        </div>
                        <div className="h-3 overflow-hidden rounded-full" style={{ background: 'rgba(26,43,72,0.06)' }}>
                            <div
                                className="h-full rounded-full"
                                style={{
                                    width: mounted ? `${progressPercent}%` : '0%',
                                    background: `linear-gradient(90deg, ${SKY} 0%, #3478c8 50%, ${GOLD} 100%)`,
                                    backgroundSize: '200% 100%',
                                    animation: mounted ? 'shimmer 3s linear infinite' : 'none',
                                    transition: 'width 1.4s cubic-bezier(0.4,0,0.2,1) 600ms',
                                    boxShadow: `0 0 10px rgba(74,144,226,0.4)`,
                                }}
                            />
                        </div>
                    </div>
                )}

                {/* ── ROADMAP ── */}
                {roadmap && roadmap.length > 0 ? (
                    <div className="space-y-8">
                        {chapters.map(({ chapterIdx, nodes }, ci) => {
                            const theme = chapterThemes[chapterIdx % chapterThemes.length];
                            const firstNodeInChapter = chapterIdx * CHAPTER_SIZE;

                            return (
                                <div key={chapterIdx} className="space-y-0">
                                    <ChapterBanner
                                        chapter={chapterIdx + 1}
                                        title={theme.label}
                                        theme={theme}
                                        mounted={mounted}
                                        delay={80 + ci * 40}
                                    />

                                    {/* Nodes with connecting paths */}
                                    <div className="relative flex flex-col items-center pt-5 pb-2">
                                        {nodes.map((node, ni) => {
                                            const globalIndex = firstNodeInChapter + ni;
                                            const nextOffsetX = ni < nodes.length - 1 ? (zigzagX[(globalIndex + 1) % zigzagX.length] ?? 0) : 0;
                                            const curOffsetX  = zigzagX[globalIndex % zigzagX.length] ?? 0;

                                            return (
                                                <div key={node.id} className="flex flex-col items-center w-full">
                                                    <RoadmapNodeItem
                                                        node={node}
                                                        index={globalIndex}
                                                        mounted={mounted}
                                                        isNext={globalIndex === nextNodeIndex}
                                                        pathColor={theme.pathColor}
                                                    />
                                                    {/* Connecting path to next node */}
                                                    {ni < nodes.length - 1 && (
                                                        <NodePath
                                                            fromX={curOffsetX}
                                                            toX={nextOffsetX}
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

                        {/* Completion */}
                        {completedNodes === totalNodes && totalNodes > 0 && (
                            <div
                                className="rounded-2xl p-8 text-center text-white"
                                style={{ background: `linear-gradient(135deg, ${GOLD} 0%, #e08c10 100%)`, boxShadow: `0 8px 32px rgba(245,166,35,0.4)` }}
                            >
                                <Trophy size={48} className="mx-auto" />
                                <h3 className="mt-3 text-2xl font-black">Parcours complété !</h3>
                                <p className="mt-1 text-sm text-white/80">Vous avez maîtrisé tout le programme.</p>
                            </div>
                        )}
                    </div>
                ) : (
                    /* ── NO ROADMAP ── */
                    <div
                        className="rounded-2xl border-2 border-dashed p-10 text-center"
                        style={{ borderColor: `${SKY}30`, background: `${SKY}08` }}
                    >
                        <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full" style={{ background: `${SKY}15` }}>
                            <Sparkles size={32} color={SKY} />
                        </div>
                        <h2 className="mt-4 text-xl font-bold" style={{ color: OXFORD }}>Votre parcours vous attend !</h2>
                        <p className="mt-2 text-sm" style={{ color: 'rgba(26,43,72,0.55)' }}>
                            Complétez l'onboarding pour débloquer votre feuille de route personnalisée.
                        </p>
                        <Link
                            href="/onboarding/exam"
                            className="mt-5 inline-flex items-center gap-2 rounded-xl px-6 py-3 text-sm font-semibold text-white shadow-md transition-all hover:-translate-y-0.5"
                            style={{ background: `linear-gradient(135deg, ${SKY} 0%, #3478c8 100%)` }}
                        >
                            Commencer
                        </Link>
                    </div>
                )}

                {/* ── QUICK ACTIONS ── */}
                <div
                    className="mt-7 grid gap-3 sm:grid-cols-3"
                    style={{ opacity: mounted ? 1 : 0, transition: 'opacity 0.4s ease 900ms' }}
                >
                    {[
                        { title: 'Pratiquer',    icon: BookOpen, href: '/practice',    bg: `linear-gradient(135deg, ${SKY} 0%, #3478c8 100%)` },
                        { title: 'Outils IA',   icon: Sparkles, href: '/ai-tools',    bg: `linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%)` },
                        { title: 'Classement',  icon: Trophy,   href: '/leaderboard', bg: `linear-gradient(135deg, ${GOLD} 0%, #e08c10 100%)` },
                    ].map((action) => {
                        const AIcon = action.icon;
                        return (
                            <Link
                                key={action.title}
                                href={action.href}
                                className="group flex items-center gap-3 rounded-2xl border bg-white p-4 transition-all duration-200 hover:-translate-y-1 hover:shadow-md"
                                style={{ borderColor: 'rgba(26,43,72,0.08)', boxShadow: '0 1px 4px rgba(26,43,72,0.06)' }}
                            >
                                <div
                                    className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-white shadow-sm"
                                    style={{ background: action.bg }}
                                >
                                    <AIcon size={18} />
                                </div>
                                <span className="text-sm font-semibold" style={{ color: OXFORD }}>{action.title}</span>
                            </Link>
                        );
                    })}
                </div>
            </div>
        </AppLayout>
    );
}
