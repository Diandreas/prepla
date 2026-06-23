import { SidebarTrigger } from '@/components/ui/sidebar';
import { usePage } from '@inertiajs/react';
import type { SharedData } from '@/types';
import { useAppearance } from '@/hooks/use-appearance';

const GOLD = '#F5A623';

const PAGE_TITLES: Record<string, string> = {
    '/dashboard': 'Mon Parcours',
    '/practice': 'Pratiquer',
    '/ai-tools': 'Outils IA',
    '/results': 'Résultats',
    '/settings/profile': 'Profil',
    '/leaderboard': 'Classement',
};

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
                        filter: 'brightness(0) saturate(100%) invert(84%) sepia(40%) saturate(1734%) hue-rotate(353deg) brightness(94%) contrast(86%)',
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
                        filter: 'brightness(0) saturate(100%) invert(10%) sepia(50%) saturate(1400%) hue-rotate(195deg) brightness(75%) contrast(95%)',
                    }}
                />
            )}
        </button>
    );
}

export function AppSidebarHeader() {
    const page = usePage<SharedData & { userProfile?: any }>();
    const { userProfile } = page.props;
    const url = page.url ?? '';

    // Find current page title
    const pageTitle = Object.entries(PAGE_TITLES).find(([path]) => url === path || url.startsWith(path + '/'))?.[1] ?? 'PrePla';

    const streak = (userProfile as any)?.streak_current ?? 0;
    const xp = (userProfile as any)?.xp_total ?? 0;

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
                    <span className="flex items-center gap-1 text-xs font-black tabular-nums" style={{ color: '#F97316' }}>
                        <img src="/animation/Fire.gif" alt="Série" className="h-4 w-4 object-contain" />
                        {streak}
                    </span>
                    <span className="h-3 w-px bg-border" />
                    <span className="flex items-center gap-1 text-xs font-black tabular-nums" style={{ color: GOLD }}>
                        <CustomIcon name="trophy" className="h-3.5 w-3.5" style={{ filter: 'brightness(0) saturate(100%) invert(84%) sepia(40%) saturate(1734%) hue-rotate(353deg) brightness(94%) contrast(86%)' }} />
                        {xp}
                    </span>
                </div>
                <ThemeToggleButton />
            </div>
        </header>
    );
}
