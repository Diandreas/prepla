import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AppLogo from './app-logo';

// Custom icon component using icons from /public/icons
function CustomIcon({ name, className }: { name: string; className?: string }) {
    return (
        <img
            src={`/icons/${name}.png`}
            alt={name}
            className={className || 'h-5 w-5'}
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
            icon: () => <CustomIcon name="home" />,
        },
        {
            title: t('sidebar.practice', 'Pratiquer'),
            url: '/practice',
            icon: () => <CustomIcon name="puzzle" />,
        },
        {
            title: t('sidebar.ai_tools', 'Outils IA'),
            url: '/ai-tools',
            icon: () => <CustomIcon name="video" />,
        },
        {
            title: t('sidebar.results', 'Résultats'),
            url: '/results',
            icon: () => <CustomIcon name="statistics" />,
        },
        {
            title: t('sidebar.leaderboard', 'Classement'),
            url: '/leaderboard',
            icon: () => <CustomIcon name="trophy" />,
        },
    ];

    const footerNavItems: NavItem[] = [
        {
            title: t('sidebar.settings', 'Paramètres'),
            url: '/settings/profile',
            icon: () => <CustomIcon name="settings" />,
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
