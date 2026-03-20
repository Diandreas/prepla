import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { BarChart3, BookOpen, LayoutGrid, Settings, Sparkles, Trophy } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import AppLogo from './app-logo';

export function AppSidebar() {
    const { t } = useTranslation();

    const mainNavItems: NavItem[] = [
        {
            title: t('sidebar.home', 'Accueil'),
            url: '/dashboard',
            icon: LayoutGrid,
        },
        {
            title: t('sidebar.practice', 'Pratiquer'),
            url: '/practice',
            icon: BookOpen,
        },
        {
            title: t('sidebar.ai_tools', 'Outils IA'),
            url: '/ai-tools',
            icon: Sparkles,
        },
        {
            title: t('sidebar.results', 'Résultats'),
            url: '/results',
            icon: BarChart3,
        },
        {
            title: t('sidebar.leaderboard', 'Classement'),
            url: '/leaderboard',
            icon: Trophy,
        },
    ];

    const footerNavItems: NavItem[] = [
        {
            title: t('sidebar.settings', 'Paramètres'),
            url: '/settings/profile',
            icon: Settings,
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
