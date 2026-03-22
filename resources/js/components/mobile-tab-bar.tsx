import { Link, usePage } from '@inertiajs/react';
import { useAppearance } from '@/hooks/use-appearance';

const tabs = [
    { label: 'Accueil', href: '/dashboard', icon: 'home' },
    { label: 'Pratiquer', href: '/practice', icon: 'puzzle' },
    { label: 'IA', href: '/ai-tools', icon: 'sparkles', isCenter: true },
    { label: 'Résultats', href: '/results', icon: 'statistics' },
    { label: 'Profil', href: '/settings/profile', icon: 'profile' },
];

export function MobileTabBar() {
    const { url } = usePage();
    const { appearance } = useAppearance();
    const isDark = appearance === 'dark' || (appearance === 'system' && typeof window !== 'undefined' && window.matchMedia('(prefers-color-scheme: dark)').matches);

    const bg = isDark ? '#0f1623' : '#ffffff';
    const borderColor = isDark ? 'rgba(255,255,255,0.07)' : 'rgba(26,43,72,0.09)';
    const shadow = isDark ? '0 -6px 24px rgba(0,0,0,0.4)' : '0 -4px 20px rgba(26,43,72,0.09)';
    const activeLabelColor = isDark ? '#4A90E2' : '#1A2B48';
    const inactiveLabelColor = isDark ? 'rgba(255,255,255,0.38)' : '#94a3b8';

    return (
        <nav
            className="fixed bottom-0 left-0 right-0 z-50 md:hidden"
            style={{ background: bg, borderTop: `1px solid ${borderColor}`, boxShadow: shadow }}
        >
            <div className="flex h-[72px] items-end justify-around px-2 pb-2">
                {tabs.map((tab) => {
                    const isActive = url === tab.href || url.startsWith(tab.href + '/');

                    if (tab.isCenter) {
                        return (
                            <Link
                                key={tab.href}
                                href={tab.href}
                                className="flex -translate-y-4 flex-col items-center gap-1"
                            >
                                <div
                                    className="flex items-center justify-center rounded-full transition-all duration-200 active:scale-90"
                                    style={{
                                        width: 62,
                                        height: 62,
                                        background: isActive
                                            ? 'linear-gradient(135deg, #4A90E2 0%, #3478c8 100%)'
                                            : isDark
                                                ? 'linear-gradient(135deg, #1e2d4a 0%, #2a3f6a 100%)'
                                                : 'linear-gradient(135deg, #1A2B48 0%, #2a3f6a 100%)',
                                        boxShadow: isActive
                                            ? '0 6px 24px rgba(74,144,226,0.55)'
                                            : '0 4px 18px rgba(26,43,72,0.4)',
                                    }}
                                >
                                    {/* Sur fond sombre — invert pour blanc */}
                                    <img
                                        src="/icons/sparkles.png"
                                        alt="IA"
                                        width={34}
                                        height={34}
                                        style={{
                                            objectFit: 'contain',
                                            filter: 'brightness(0) invert(1)',
                                        }}
                                    />
                                </div>
                                <span className="text-[10px] font-bold tracking-wide" style={{ color: isActive ? activeLabelColor : inactiveLabelColor }}>
                                    {tab.label}
                                </span>
                            </Link>
                        );
                    }

                    return (
                        <Link
                            key={tab.href}
                            href={tab.href}
                            className="flex flex-1 flex-col items-center gap-0.5 pb-1 transition-all"
                        >
                            {/* Indicateur actif en haut */}
                            <div
                                className="mb-1 rounded-full transition-all duration-300"
                                style={{
                                    height: 3,
                                    width: isActive ? 24 : 0,
                                    background: '#4A90E2',
                                    opacity: isActive ? 1 : 0,
                                }}
                            />
                            {/* Icône en couleur naturelle — pas de filtre */}
                            <img
                                src={`/icons/${tab.icon}.png`}
                                alt={tab.label}
                                width={36}
                                height={36}
                                style={{
                                    objectFit: 'contain',
                                    opacity: isActive ? 1 : 0.45,
                                    transition: 'opacity 0.2s ease',
                                }}
                            />
                            <span
                                className="text-[10px] font-semibold tracking-wide transition-colors"
                                style={{ color: isActive ? activeLabelColor : inactiveLabelColor }}
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
