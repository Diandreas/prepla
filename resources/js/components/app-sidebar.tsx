import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AppLogo from './app-logo';

// Custom icon component using icons from /public/icons
// Uses CSS filter to colorize PNGs based on active state and dark/light mode
function SidebarIcon({ name }: { name: string }) {
    return (
        <img
            src={`/icons/${name}.png`}
            alt={name}
            width={32}
            height={32}
            style={{ objectFit: 'contain' }}
        />
    );
}

export function AppSidebar() {
    const { t } = useTranslation();

    const mainNavItems: NavItem[] = [
        {
            title: t('sidebar.home', 'Accueil'),
            url: '/dashboard',
            icon: () => <SidebarIcon name="home" />,
        },
        {
            title: t('sidebar.practice', 'Pratiquer'),
            url: '/practice',
            icon: () => <SidebarIcon name="puzzle" />,
        },
        {
            title: t('sidebar.ai_tools', 'Outils IA'),
            url: '/ai-tools',
            icon: () => <SidebarIcon name="sparkles" />,
        },
        {
            title: t('sidebar.results', 'Résultats'),
            url: '/results',
            icon: () => <SidebarIcon name="statistics" />,
        },
        {
            title: t('sidebar.leaderboard', 'Classement'),
            url: '/leaderboard',
            icon: () => <SidebarIcon name="trophy" />,
        },
    ];

    const footerNavItems: NavItem[] = [
        {
            title: t('sidebar.settings', 'Paramètres'),
            url: '/settings/profile',
            icon: () => <SidebarIcon name="settings" />,
        },
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
