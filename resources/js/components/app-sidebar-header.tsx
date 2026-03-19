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
            className="flex h-14 shrink-0 items-center justify-between border-b px-4"
            style={{
                background: '#ffffff',
                borderColor: 'rgba(26,43,72,0.08)',
                boxShadow: '0 1px 8px rgba(26,43,72,0.06)',
            }}
        >
            {/* Left: sidebar toggle + page title */}
            <div className="flex items-center gap-3">
                <SidebarTrigger className="-ml-1" />
                <span
                    className="text-base font-bold"
                    style={{ color: OXFORD }}
                >
                    {pageTitle}
                </span>
            </div>

            {/* Right: streak + XP */}
            <div className="flex items-center gap-2">
                <div
                    className="flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-bold"
                    style={{ background: 'rgba(249,115,22,0.08)', color: '#F97316' }}
                >
                    <Flame size={13} />
                    {streak}
                </div>
                <div
                    className="flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-bold"
                    style={{ background: 'rgba(245,166,35,0.1)', color: GOLD }}
                >
                    <Zap size={13} />
                    {xp}
                </div>
            </div>
        </header>
    );
}
