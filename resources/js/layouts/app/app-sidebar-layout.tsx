import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { MobileTabBar } from '@/components/mobile-tab-bar';
import { useIsMobile } from '@/hooks/use-mobile';
import { type BreadcrumbItem } from '@/types';

export default function AppSidebarLayout({ children, breadcrumbs = [] }: { children: React.ReactNode; breadcrumbs?: BreadcrumbItem[] }) {
    const isMobile = useIsMobile();

    return (
        <AppShell variant="sidebar">
            {/* Sidebar only on desktop */}
            {!isMobile && <AppSidebar />}
            
            <AppContent variant="sidebar">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                <div className="pb-24 md:pb-6">
                    {children}
                </div>
            </AppContent>
            
            {/* Mobile Navigation */}
            <MobileTabBar />
        </AppShell>
    );
}
