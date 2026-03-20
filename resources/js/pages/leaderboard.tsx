import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import type { LeaderboardEntry } from '@/types';
import { useEffect, useState } from 'react';

interface Props {
    entries: LeaderboardEntry[];
    currentUserId: number;
}

const OXFORD = '#1A2B48';
const SKY = '#4A90E2';
const GOLD = '#F5A623';

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

const podiumColors = [
    { bg: GOLD, shadow: '#d48b10', ring: 'rgba(245,166,35,0.25)' },      // 1st
    { bg: '#C0C0C0', shadow: '#8a8a8a', ring: 'rgba(192,192,192,0.25)' }, // 2nd
    { bg: '#CD7F32', shadow: '#9a5e20', ring: 'rgba(205,127,50,0.25)' },  // 3rd
];

export default function Leaderboard({ entries, currentUserId }: Props) {
    const [mounted, setMounted] = useState(false);
    useEffect(() => setMounted(true), []);

    return (
        <AppLayout>
            <Head title="Classement" />
            <div className="mx-auto max-w-2xl px-4 py-8">
                {/* Header */}
                <div className="mb-8 text-center">
                    <h1 className="text-2xl font-black tracking-tight" style={{ color: OXFORD }}>
                        Classement
                    </h1>
                    <p className="mt-1 text-sm font-bold text-muted-foreground">
                        Classement hebdomadaire par XP gagnés
                    </p>
                </div>

                {/* Top 3 Podium */}
                {entries.length >= 3 && (
                    <div className="mb-10 flex items-end justify-center gap-3 sm:gap-6">
                        {[1, 0, 2].map((idx) => {
                            const entry = entries[idx];
                            if (!entry) return null;
                            const isFirst = idx === 0;
                            const colors = podiumColors[idx];
                            const size = isFirst ? 80 : 64;
                            const barH = isFirst ? 'h-28' : idx === 1 ? 'h-20' : 'h-14';

                            return (
                                <div
                                    key={entry.id}
                                    className={`flex flex-col items-center ${isFirst ? 'order-2' : idx === 1 ? 'order-1' : 'order-3'}`}
                                    style={{
                                        opacity: mounted ? 1 : 0,
                                        transform: mounted ? 'translateY(0)' : 'translateY(20px)',
                                        transition: `all 0.5s cubic-bezier(0.22, 1, 0.36, 1) ${idx * 150}ms`,
                                    }}
                                >
                                    {/* Crown for 1st */}
                                    {isFirst && (
                                        <div className="mb-1 animate-bounce-soft text-2xl">👑</div>
                                    )}
                                    {/* Avatar circle */}
                                    <div
                                        className="relative flex items-center justify-center rounded-full font-black text-white"
                                        style={{
                                            width: size,
                                            height: size,
                                            background: colors.bg,
                                            boxShadow: `0 6px 0 0 ${colors.shadow}`,
                                            border: `4px solid ${colors.bg}`,
                                            fontSize: isFirst ? 28 : 22,
                                        }}
                                    >
                                        {(entry.user?.name ?? 'U').charAt(0).toUpperCase()}
                                        {/* Rank badge */}
                                        <div
                                            className="absolute -bottom-2 -right-1 flex h-7 w-7 items-center justify-center rounded-full text-xs font-black text-white"
                                            style={{
                                                background: colors.bg,
                                                boxShadow: `0 3px 0 0 ${colors.shadow}`,
                                                border: '3px solid white',
                                            }}
                                        >
                                            {idx + 1}
                                        </div>
                                    </div>
                                    <p
                                        className={`mt-3 text-sm font-black tracking-tight ${entry.user_id === currentUserId ? 'text-primary' : ''}`}
                                        style={{ color: entry.user_id === currentUserId ? SKY : OXFORD }}
                                    >
                                        {entry.user?.name ?? 'User'}
                                    </p>
                                    <p className="text-xs font-bold text-muted-foreground">
                                        {entry.xp} XP
                                    </p>
                                    {/* Podium bar */}
                                    <div
                                        className={`mt-2 w-20 rounded-t-2xl sm:w-24 ${barH}`}
                                        style={{
                                            background: `linear-gradient(180deg, ${colors.bg}33 0%, ${colors.bg}18 100%)`,
                                            border: `2px solid ${colors.bg}33`,
                                            borderBottom: 'none',
                                        }}
                                    />
                                </div>
                            );
                        })}
                    </div>
                )}

                {/* Full List */}
                <div className="duo-card overflow-hidden p-0">
                    <div className="border-b-2 border-gray-100 px-5 py-3">
                        <p className="text-xs font-black uppercase tracking-widest" style={{ color: OXFORD, opacity: 0.5 }}>
                            Cette semaine
                        </p>
                    </div>

                    {entries.length === 0 ? (
                        <p className="py-10 text-center text-sm font-bold text-muted-foreground">
                            Aucune entrée pour l'instant. Complétez des exercices pour gagner des XP !
                        </p>
                    ) : (
                        <div className="divide-y divide-gray-100">
                            {entries.map((entry, i) => (
                                <div
                                    key={entry.id}
                                    className={`flex items-center gap-3 px-5 py-3 transition-all ${entry.user_id === currentUserId
                                        ? 'bg-primary/5'
                                        : ''
                                        }`}
                                    style={{
                                        opacity: mounted ? 1 : 0,
                                        transform: mounted ? 'translateX(0)' : 'translateX(-10px)',
                                        transition: `all 0.4s ease ${300 + i * 50}ms`,
                                    }}
                                >
                                    {/* Rank */}
                                    <span
                                        className="w-8 text-center text-sm font-black"
                                        style={{ color: i < 3 ? podiumColors[i]?.bg : 'rgba(26,43,72,0.3)' }}
                                    >
                                        {i + 1}
                                    </span>
                                    {/* Avatar */}
                                    <div
                                        className="flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold text-white"
                                        style={{
                                            background: i < 3 ? podiumColors[i]?.bg : '#e5e7eb',
                                            color: i < 3 ? '#fff' : OXFORD,
                                            boxShadow: i < 3 ? `0 3px 0 0 ${podiumColors[i]?.shadow}` : '0 2px 0 0 #d1d5db',
                                        }}
                                    >
                                        {(entry.user?.name ?? 'U').charAt(0).toUpperCase()}
                                    </div>
                                    {/* Name */}
                                    <span className="flex-1 font-bold" style={{ color: OXFORD }}>
                                        {entry.user?.name ?? 'User'}
                                        {entry.user_id === currentUserId && (
                                            <span className="ml-2 text-xs font-black" style={{ color: SKY }}>(vous)</span>
                                        )}
                                    </span>
                                    {/* XP */}
                                    <div className="flex items-center gap-1">
                                        <CustomIcon name="trophy" className="h-3.5 w-3.5" style={{ filter: 'brightness(0) saturate(100%) invert(84%) sepia(40%) saturate(1734%) hue-rotate(353deg) brightness(94%) contrast(86%)' }} />
                                        <span className="text-sm font-black" style={{ color: GOLD }}>
                                            {entry.xp}
                                        </span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
