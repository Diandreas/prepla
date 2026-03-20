import { SidebarTrigger } from '@/components/ui/sidebar';
import { usePage } from '@inertiajs/react';
import type { SharedData } from '@/types';
import { Flame, Zap } from 'lucide-react';

const OXFORD = '#1A2B48';
const SKY    = '#4A90E2';
const GOLD   = '#F5A623';

const PAGE_TITLES: Record<string, string> = {
    '/dashboard':        'Mon Parcours',
    '/practice':         'Pratiquer',
    '/ai-tools':         'Outils IA',
    '/results':          'Résultats',
    '/settings/profile': 'Profil',
    '/leaderboard':      'Classement',
};

export function AppSidebarHeader() {
    const page = usePage<SharedData & { userProfile?: any }>();
    const { auth, userProfile } = page.props;
    const url = page.url ?? '';

    // Find current page title
    const pageTitle = Object.entries(PAGE_TITLES).find(([path]) => url === path || url.startsWith(path + '/'))?.[1] ?? 'PrePla';

    const streak = (userProfile as any)?.streak_current ?? 0;
    const xp     = (userProfile as any)?.xp_total ?? 0;

    return (
        <header
            className="sticky top-0 z-40 flex h-14 shrink-0 items-center justify-between border-b px-4 md:static md:h-14"
            style={{
                background: '#ffffff',
                borderColor: 'rgba(26,43,72,0.08)',
                boxShadow: '0 1px 8px rgba(26,43,72,0.06)',
            }}
        >
            {/* Left: sidebar toggle (desktop only) + page title */}
            <div className="flex items-center gap-3">
                <div className="hidden md:block">
                    <SidebarTrigger className="-ml-1" />
                </div>
                <span
                    className="text-base font-bold tracking-tight md:text-lg"
                    style={{ color: OXFORD }}
                >
                    {pageTitle}
                </span>
            </div>

            {/* Right: streak + XP */}
            <div className="flex items-center gap-2">
                <div
                    className="flex items-center gap-1.5 rounded-full px-3 py-1 text-[11px] font-black uppercase tracking-wider"
                    style={{ background: 'rgba(249,115,22,0.08)', color: '#F97316' }}
                >
                    <Flame size={14} strokeWidth={2.5} />
                    {streak}
                </div>
                <div
                    className="flex items-center gap-1.5 rounded-full px-3 py-1 text-[11px] font-black uppercase tracking-wider"
                    style={{ background: 'rgba(245,166,35,0.1)', color: GOLD }}
                >
                    <Zap size={14} strokeWidth={2.5} />
                    {xp}
                </div>
            </div>
        </header>
    );
}
