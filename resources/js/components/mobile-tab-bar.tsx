import { Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { playSound } from '@/hooks/use-sound';

interface Tab { labelKey: string; href: string; icon: string; isCenter?: boolean }

const LEARNER_TABS: Tab[] = [
    { labelKey: 'sidebar.home', href: '/dashboard', icon: 'home' },
    { labelKey: 'sidebar.practice', href: '/practice', icon: 'puzzle' },
    { labelKey: 'sidebar.ai_short', href: '/ai-tools', icon: 'sparkles', isCenter: true },
    { labelKey: 'sidebar.results', href: '/results', icon: 'statistics' },
    { labelKey: 'sidebar.profile', href: '/settings/profile', icon: 'profile' },
];

const CENTER_TABS: Tab[] = [
    { labelKey: 'sidebar.home', href: '/center', icon: 'home' },
    { labelKey: 'sidebar.classes', href: '/center/classes', icon: 'layout-grid' },
    { labelKey: 'sidebar.assignments', href: '/center/assignments', icon: 'tasks', isCenter: true },
    { labelKey: 'sidebar.students', href: '/center/students', icon: 'profile' },
    { labelKey: 'sidebar.content', href: '/center/exercises', icon: 'puzzle' },
];

const ADMIN_TABS: Tab[] = [
    { labelKey: 'sidebar.centers', href: '/admin/centers', icon: 'layout-grid' },
    { labelKey: 'sidebar.profile', href: '/settings/profile', icon: 'profile' },
];

export function MobileTabBar() {
    const { t } = useTranslation();
    const { url, props } = usePage();
    const auth = (props as any)?.auth;
    const role: string | undefined = auth?.role;
    const centerRole: string | undefined = auth?.center?.role;

    const tabs =
        role === 'super_admin'
            ? ADMIN_TABS
            : centerRole === 'center_admin' || centerRole === 'teacher'
              ? CENTER_TABS
              : LEARNER_TABS;

    return (
        <nav className="fixed bottom-0 left-0 right-0 z-50 border-t border-border/60 bg-background/85 backdrop-blur-xl md:hidden">
            <div className="flex h-[68px] items-center justify-around px-2 pb-[env(safe-area-inset-bottom)]">
                {tabs.map((tab) => {
                    const isActive = url === tab.href || url.startsWith(tab.href + '/');

                    if (tab.isCenter) {
                        return (
                            <Link key={tab.href} href={tab.href} prefetch={['mount', 'hover']} cacheFor="1m" onClick={() => playSound('click')} className="flex -translate-y-3 flex-col items-center gap-1">
                                <div
                                    className="flex items-center justify-center rounded-2xl transition-transform duration-200 active:scale-90"
                                    style={{
                                        width: 54, height: 54,
                                        background: 'linear-gradient(135deg, #4A90E2 0%, #3478c8 100%)',
                                        boxShadow: isActive ? '0 8px 22px rgba(74,144,226,0.5)' : '0 4px 14px rgba(74,144,226,0.35)',
                                    }}
                                >
                                    <img src="/icons/sparkles.png" alt={t(tab.labelKey)} width={28} height={28} style={{ objectFit: 'contain', filter: 'brightness(0) invert(1)' }} />
                                </div>
                            </Link>
                        );
                    }

                    return (
                        <Link
                            key={tab.href}
                            href={tab.href}
                            prefetch={['mount', 'hover']}
                            cacheFor="1m"
                            onClick={() => playSound('click')}
                            className="group flex flex-1 flex-col items-center gap-1"
                        >
                            {/* Active pill behind the icon */}
                            <div
                                className={`flex items-center justify-center rounded-2xl transition-all duration-200 ${isActive ? 'bg-primary/10' : 'bg-transparent'}`}
                                style={{ width: 44, height: 30 }}
                            >
                                <img
                                    src={`/icons/${tab.icon}.png`}
                                    alt={t(tab.labelKey)}
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
                                {t(tab.labelKey)}
                            </span>
                        </Link>
                    );
                })}
            </div>
        </nav>
    );
}
