import { SidebarTrigger } from '@/components/ui/sidebar';
import { usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import type { SharedData } from '@/types';
import { useAppearance } from '@/hooks/use-appearance';
import { useState, useEffect, useRef } from 'react';

const GOLD = '#F5A623';

// Path → i18n key under "page_titles". Order matters: more specific paths must
// come before their parent prefix so the first match wins (e.g.
// /ai-tools/writing-corrector before /ai-tools).
const PAGE_TITLE_KEYS: [string, string][] = [
    ['/dashboard', 'dashboard'],
    ['/practice', 'practice'],
    ['/dictionary', 'dictionary'],
    ['/ai-tools/writing-corrector', 'writing_corrector'],
    ['/ai-tools/explainer', 'explainer'],
    ['/ai-tools/generator', 'generator'],
    ['/ai-tools/recommendations', 'recommendations'],
    ['/ai-tools', 'ai_tools'],
    ['/errors', 'errors'],
    ['/results', 'results'],
    ['/settings/profile', 'profile'],
    ['/leaderboard', 'leaderboard'],
    // B2B — back-office & espace centre
    ['/admin/centers', 'centers'],
    ['/center/classes', 'classes'],
    ['/center/students', 'students'],
    ['/center/exercises', 'content'],
    ['/center/assignments', 'assignments'],
    ['/center', 'center'],
    ['/join', 'join'],
];

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

function ThemeToggleButton() {
    const { appearance, updateAppearance } = useAppearance();

    const isDark = appearance === 'dark' || (appearance === 'system' && typeof window !== 'undefined' && window.matchMedia('(prefers-color-scheme: dark)').matches);

    const toggle = () => {
        updateAppearance(isDark ? 'light' : 'dark');
    };

    return (
        <button
            onClick={toggle}
            title={isDark ? 'Passer en mode clair' : 'Passer en mode sombre'}
            className="flex items-center justify-center rounded-full transition-all duration-200 hover:scale-105 active:scale-95"
            style={{
                width: 34,
                height: 34,
                border: `1px solid ${isDark ? 'rgba(255,255,255,0.1)' : 'rgba(26,43,72,0.12)'}`,
                background: isDark ? 'rgba(255,255,255,0.05)' : 'rgba(26,43,72,0.04)',
                cursor: 'pointer',
            }}
        >
            {isDark ? (
                <img
                    src="/icons/sun.png"
                    alt="Mode clair"
                    width={18}
                    height={18}
                    style={{
                        objectFit: 'contain',
                        
                    }}
                />
            ) : (
                <img
                    src="/icons/moon.png"
                    alt="Mode sombre"
                    width={18}
                    height={18}
                    style={{
                        objectFit: 'contain',
                        
                    }}
                />
            )}
        </button>
    );
}

export function AppSidebarHeader() {
    const { t } = useTranslation();
    const page = usePage<SharedData & { userProfile?: any }>();
    const { userProfile } = page.props;
    const url = page.url ?? '';

    // Find current page title (resolve its i18n key; fall back to the brand name)
    const titleKey = PAGE_TITLE_KEYS.find(([path]) => url === path || url.startsWith(path + '/'))?.[1];
    const pageTitle = titleKey ? t(`page_titles.${titleKey}`) : 'PrePla';

    const streak = (userProfile as any)?.streak_current ?? 0;
    const xp = (userProfile as any)?.xp_total ?? 0;

    // Bump the XP / streak chips briefly when their value increases.
    const [xpBump, setXpBump] = useState(false);
    const [streakBump, setStreakBump] = useState(false);
    const prevXp = useRef(xp);
    const prevStreak = useRef(streak);
    useEffect(() => {
        if (xp > prevXp.current) { setXpBump(true); const t = setTimeout(() => setXpBump(false), 450); prevXp.current = xp; return () => clearTimeout(t); }
        prevXp.current = xp;
    }, [xp]);
    useEffect(() => {
        if (streak > prevStreak.current) { setStreakBump(true); const t = setTimeout(() => setStreakBump(false), 450); prevStreak.current = streak; return () => clearTimeout(t); }
        prevStreak.current = streak;
    }, [streak]);

    return (
        <header
            className="sticky top-0 z-40 flex h-14 shrink-0 items-center justify-between border-b border-border/60 bg-background/80 px-4 backdrop-blur-xl md:static md:h-14"
        >
            {/* Left: sidebar toggle (desktop only) + page title */}
            <div className="flex items-center gap-3">
                <div className="hidden md:block">
                    <SidebarTrigger className="-ml-1" />
                </div>
                <span className="text-base font-bold tracking-tight text-foreground md:text-lg">
                    {pageTitle}
                </span>
            </div>

            {/* Right: a single discreet stat group + theme toggle */}
            <div className="flex items-center gap-2">
                <div className="flex items-center gap-3 rounded-full border border-border/60 bg-card/60 px-3 py-1">
                    <span className={`flex items-center gap-1 text-xs font-black tabular-nums ${streakBump ? 'value-bump' : ''}`} style={{ color: '#F97316' }}>
                        <img src="/animation/Fire.gif" alt="Série" className="h-4 w-4 object-contain" />
                        {streak}
                    </span>
                    <span className="h-3 w-px bg-border" />
                    <span className={`flex items-center gap-1 text-xs font-black tabular-nums ${xpBump ? 'value-bump' : ''}`} style={{ color: GOLD }}>
                        <CustomIcon name="trophy" className="h-3.5 w-3.5" style={{ }} />
                        {xp}
                    </span>
                </div>
                <ThemeToggleButton />
            </div>
        </header>
    );
}
