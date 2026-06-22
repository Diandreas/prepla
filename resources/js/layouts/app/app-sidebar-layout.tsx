import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { MobileTabBar } from '@/components/mobile-tab-bar';
import { PwaInstallPrompt } from '@/components/pwa-install-prompt';
import { useIsMobile } from '@/hooks/use-mobile';
import { type BreadcrumbItem } from '@/types';

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
    focusMode = false,
}: {
    children: React.ReactNode;
    breadcrumbs?: BreadcrumbItem[];
    // Focus mode hides the sidebar, top header and mobile tab bar — used inside
    // lessons and exercises so nothing distracts from the learning task.
    focusMode?: boolean;
}) {
    const isMobile = useIsMobile();

    return (
        <AppShell variant="sidebar">
            {/* Sidebar only on desktop, and never in focus mode */}
            {!isMobile && !focusMode && <AppSidebar />}

            <AppContent variant="sidebar">
                {!focusMode && <AppSidebarHeader breadcrumbs={breadcrumbs} />}
                <div className={focusMode ? '' : 'pb-24 md:pb-6'}>
                    {children}
                </div>
            </AppContent>

            {/* Mobile Navigation — hidden in focus mode */}
            {!focusMode && <MobileTabBar />}

            <PwaInstallPrompt />
        </AppShell>
    );
}
