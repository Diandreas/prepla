import { Link, usePage } from '@inertiajs/react';

const tabs = [
    {
        label: 'Accueil',
        href: '/dashboard',
        icon: (active: boolean) => (
            <svg width="22" height="22" viewBox="0 0 24 24" fill={active ? '#1A2B48' : 'none'} stroke={active ? '#1A2B48' : '#94a3b8'} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
        ),
    },
    {
        label: 'Pratiquer',
        href: '/practice',
        icon: (active: boolean) => (
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke={active ? '#1A2B48' : '#94a3b8'} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z" fill={active ? 'rgba(26,43,72,0.12)' : 'none'}/>
                <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z" fill={active ? 'rgba(26,43,72,0.12)' : 'none'}/>
            </svg>
        ),
    },
    {
        label: 'IA',
        href: '/ai-tools',
        isCenter: true,
        icon: (active: boolean) => (
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <path d="M12 2a4 4 0 0 1 4 4c0 1.5-.8 2.8-2 3.5V11h1a2 2 0 0 1 2 2v1h1a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v1a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1v-1H6a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h1v-1a2 2 0 0 1 2-2h1V9.5A4 4 0 0 1 8 6a4 4 0 0 1 4-4z"/>
                <circle cx="10" cy="15" r="1" fill="white"/>
                <circle cx="14" cy="15" r="1" fill="white"/>
            </svg>
        ),
    },
    {
        label: 'Résultats',
        href: '/results',
        icon: (active: boolean) => (
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke={active ? '#1A2B48' : '#94a3b8'} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <line x1="18" y1="20" x2="18" y2="10"/>
                <line x1="12" y1="20" x2="12" y2="4"/>
                <line x1="6" y1="20" x2="6" y2="14"/>
                {active && <rect x="4.5" y="13.5" width="3" height="7" rx="0.5" fill="rgba(26,43,72,0.15)" stroke="none"/>}
                {active && <rect x="10.5" y="3.5" width="3" height="17" rx="0.5" fill="rgba(26,43,72,0.15)" stroke="none"/>}
                {active && <rect x="16.5" y="9.5" width="3" height="11" rx="0.5" fill="rgba(26,43,72,0.15)" stroke="none"/>}
            </svg>
        ),
    },
    {
        label: 'Profil',
        href: '/settings/profile',
        icon: (active: boolean) => (
            <svg width="22" height="22" viewBox="0 0 24 24" fill={active ? 'rgba(26,43,72,0.12)' : 'none'} stroke={active ? '#1A2B48' : '#94a3b8'} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
        ),
    },
];

export function MobileTabBar() {
    const { url } = usePage();

    return (
        <nav
            className="fixed bottom-0 left-0 right-0 z-50 md:hidden"
            style={{
                background: '#ffffff',
                borderTop: '1px solid rgba(26,43,72,0.08)',
                boxShadow: '0 -4px 20px rgba(26,43,72,0.08)',
            }}
        >
            <div className="flex h-[62px] items-end justify-around px-1 pb-2">
                {tabs.map((tab) => {
                    const isActive = url === tab.href || url.startsWith(tab.href + '/');

                    if (tab.isCenter) {
                        return (
                            <Link
                                key={tab.href}
                                href={tab.href}
                                className="flex -translate-y-3 flex-col items-center gap-1"
                            >
                                <div
                                    className="flex h-14 w-14 items-center justify-center rounded-full shadow-lg transition-transform active:scale-95"
                                    style={{
                                        background: isActive
                                            ? 'linear-gradient(135deg, #4A90E2 0%, #3478c8 100%)'
                                            : 'linear-gradient(135deg, #1A2B48 0%, #2a3f6a 100%)',
                                        boxShadow: '0 4px 16px rgba(26,43,72,0.35)',
                                    }}
                                >
                                    {tab.icon(isActive)}
                                </div>
                                <span
                                    className="text-[10px] font-semibold"
                                    style={{ color: isActive ? '#4A90E2' : 'rgba(26,43,72,0.45)' }}
                                >
                                    {tab.label}
                                </span>
                            </Link>
                        );
                    }

                    return (
                        <Link
                            key={tab.href}
                            href={tab.href}
                            className="flex flex-1 flex-col items-center gap-1 py-1 transition-all"
                        >
                            {/* Active indicator dot */}
                            <div
                                className="mb-0.5 h-1 w-1 rounded-full transition-all duration-200"
                                style={{ background: isActive ? '#4A90E2' : 'transparent' }}
                            />
                            {tab.icon(isActive)}
                            <span
                                className="text-[10px] font-semibold transition-colors"
                                style={{ color: isActive ? '#1A2B48' : '#94a3b8' }}
                            >
                                {tab.label}
                            </span>
                        </Link>
                    );
                })}
            </div>
        </nav>
    );
}
