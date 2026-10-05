import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import { type BreadcrumbItem } from '@/types';
import { usePage } from '@inertiajs/react';

interface AppLayoutProps {
    children: React.ReactNode;
    breadcrumbs?: BreadcrumbItem[];
    focusMode?: boolean;
}

export default function AppLayout({ children, breadcrumbs, ...props }: AppLayoutProps) {
    const page = usePage();
    const flash = page.props.flash as { error?: string; success?: string } | undefined;
    const hasOwnErrorNotice = ['practice/exam-dashboard', 'practice/exam-simulator', 'practice/section-drills', 'settings/subscription'].includes(page.component);
    return (
    <AppLayoutTemplate breadcrumbs={breadcrumbs} {...props}>
        {flash?.error && !hasOwnErrorNotice && <p role="alert" className="mx-4 mt-4 rounded-xl border border-red-300 bg-red-50 p-4 text-sm text-red-900 dark:border-red-800 dark:bg-red-950 dark:text-red-100">{flash.error}</p>}
        {children}
    </AppLayoutTemplate>
    );
}
