import { Link, usePage } from '@inertiajs/react';

const tabs = [
    { label: 'Accueil', href: '/dashboard', icon: 'home' },
    { label: 'Pratiquer', href: '/practice', icon: 'puzzle' },
    { label: 'IA', href: '/ai-tools', icon: 'sparkles', isCenter: true },
    { label: 'Résultats', href: '/results', icon: 'statistics' },
    { label: 'Profil', href: '/settings/profile', icon: 'profile' },
];

export function MobileTabBar() {
    const { url } = usePage();

    return (
        <nav className="fixed bottom-0 left-0 right-0 z-50 border-t border-border/60 bg-background/85 backdrop-blur-xl md:hidden">
            <div className="flex h-[68px] items-center justify-around px-2 pb-[env(safe-area-inset-bottom)]">
                {tabs.map((tab) => {
                    const isActive = url === tab.href || url.startsWith(tab.href + '/');

                    if (tab.isCenter) {
                        return (
                            <Link key={tab.href} href={tab.href} prefetch cacheFor="30s" className="flex -translate-y-3 flex-col items-center gap-1">
                                <div
                                    className="flex items-center justify-center rounded-2xl transition-transform duration-200 active:scale-90"
                                    style={{
                                        width: 54, height: 54,
                                        background: 'linear-gradient(135deg, #4A90E2 0%, #3478c8 100%)',
                                        boxShadow: isActive ? '0 8px 22px rgba(74,144,226,0.5)' : '0 4px 14px rgba(74,144,226,0.35)',
                                    }}
                                >
                                    <img src="/icons/sparkles.png" alt="IA" width={28} height={28} style={{ objectFit: 'contain', filter: 'brightness(0) invert(1)' }} />
                                </div>
                            </Link>
                        );
                    }

                    return (
                        <Link
                            key={tab.href}
                            href={tab.href}
                            prefetch
                            cacheFor="30s"
                            className="group flex flex-1 flex-col items-center gap-1"
                        >
                            {/* Active pill behind the icon */}
                            <div
                                className={`flex items-center justify-center rounded-2xl transition-all duration-200 ${isActive ? 'bg-primary/10' : 'bg-transparent'}`}
                                style={{ width: 44, height: 30 }}
                            >
                                <img
                                    src={`/icons/${tab.icon}.png`}
                                    alt={tab.label}
                                    width={24}
                                    height={24}
                                    style={{
                                        objectFit: 'contain',
                                        opacity: isActive ? 1 : 0.5,
                                        transition: 'opacity 0.2s ease',
                                    }}
                                />
                            </div>
                            <span
                                className={`text-[10px] font-bold tracking-wide transition-colors ${isActive ? 'text-primary' : 'text-muted-foreground'}`}
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
