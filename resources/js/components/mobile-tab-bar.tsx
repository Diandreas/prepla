import { Link, usePage } from '@inertiajs/react';

const tabs = [
    {
        label: 'Accueil',
        href: '/dashboard',
        icon: (active: boolean) => (
            <img
                src="/icons/home.png"
                alt="Accueil"
                width={26}
                height={26}
                style={{
                    filter: active ? 'brightness(0) saturate(100%) invert(16%) sepia(9%) saturate(1749%) hue-rotate(181deg) brightness(95%) contrast(92%)' : 'brightness(0) saturate(100%) invert(84%) sepia(5%) saturate(392%) hue-rotate(177deg) brightness(94%) contrast(86%)',
                }}
            />
        ),
    },
    {
        label: 'Pratiquer',
        href: '/practice',
        icon: (active: boolean) => (
            <img
                src="/icons/puzzle.png"
                alt="Pratiquer"
                width={26}
                height={26}
                style={{
                    filter: active ? 'brightness(0) saturate(100%) invert(16%) sepia(9%) saturate(1749%) hue-rotate(181deg) brightness(95%) contrast(92%)' : 'brightness(0) saturate(100%) invert(84%) sepia(5%) saturate(392%) hue-rotate(177deg) brightness(94%) contrast(86%)',
                }}
            />
        ),
    },
    {
        label: 'IA',
        href: '/ai-tools',
        isCenter: true,
        icon: (_active: boolean) => (
            <img
                src="/icons/sparkles.png"
                alt="IA"
                width={26}
                height={26}
                style={{ filter: 'brightness(0) saturate(100%) invert(100%)' }}
            />
        ),
    },
    {
        label: 'Résultats',
        href: '/results',
        icon: (active: boolean) => (
            <img
                src="/icons/statistics.png"
                alt="Résultats"
                width={26}
                height={26}
                style={{
                    filter: active ? 'brightness(0) saturate(100%) invert(16%) sepia(9%) saturate(1749%) hue-rotate(181deg) brightness(95%) contrast(92%)' : 'brightness(0) saturate(100%) invert(84%) sepia(5%) saturate(392%) hue-rotate(177deg) brightness(94%) contrast(86%)',
                }}
            />
        ),
    },
    {
        label: 'Profil',
        href: '/settings/profile',
        icon: (active: boolean) => (
            <img
                src="/icons/profile.png"
                alt="Profil"
                width={26}
                height={26}
                style={{
                    filter: active ? 'brightness(0) saturate(100%) invert(16%) sepia(9%) saturate(1749%) hue-rotate(181deg) brightness(95%) contrast(92%)' : 'brightness(0) saturate(100%) invert(84%) sepia(5%) saturate(392%) hue-rotate(177deg) brightness(94%) contrast(86%)',
                }}
            />
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
