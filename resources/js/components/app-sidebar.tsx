import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { BarChart3, BookOpen, LayoutGrid, Settings, Sparkles, Trophy } from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'Accueil',
        url: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Pratiquer',
        url: '/practice',
        icon: BookOpen,
    },
    {
        title: 'Outils IA',
        url: '/ai-tools',
        icon: Sparkles,
    },
    {
        title: 'Résultats',
        url: '/results',
        icon: BarChart3,
    },
    {
        title: 'Classement',
        url: '/leaderboard',
        icon: Trophy,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Settings',
        url: '/settings/profile',
        icon: Settings,
    },
];

export function AppSidebar() {
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
