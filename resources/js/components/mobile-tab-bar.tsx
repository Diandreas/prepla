import { Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { playSound } from '@/hooks/use-sound';
import { ArtIcon } from '@/components/art-icon';

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
        <nav className="app-mobile-nav fixed bottom-0 left-0 right-0 z-50 border-t border-border/60 bg-background/85 backdrop-blur-xl md:hidden">
            <div className="flex min-h-[76px] items-center justify-around gap-1 px-2 pt-2 pb-[max(0.5rem,env(safe-area-inset-bottom))]">
                {tabs.map((tab) => {
                    const isActive = url === tab.href || url.startsWith(tab.href + '/');

                    return (
                        <Link
                            key={tab.href}
                            href={tab.href}
                            prefetch={['mount', 'hover']}
                            cacheFor="1m"
                            onClick={() => playSound('click')}
                            aria-current={isActive ? 'page' : undefined}
                            className="app-mobile-link group flex flex-1 flex-col items-center justify-center gap-1.5 rounded-xl text-muted-foreground"
                        >
                            <ArtIcon name={tab.icon} size={32} tone={tab.isCenter ? 'amber' : 'blue'} />
                            <span
                                className={`text-[10px] font-bold tracking-wide transition-colors ${isActive ? 'text-foreground' : 'text-muted-foreground'}`}
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
