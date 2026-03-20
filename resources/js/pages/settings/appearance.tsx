import { Head, Link } from '@inertiajs/react';

import AppearanceTabs from '@/components/appearance-tabs';
import { type BreadcrumbItem } from '@/types';

import AppLayout from '@/layouts/app-layout';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profil',
        href: '/settings/profile',
    },
    {
        title: 'Apparence',
        href: '/settings/appearance',
    },
];

export default function Appearance() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Apparence" />

            <div className="mx-auto max-w-2xl space-y-6 py-6 pb-24 md:py-10 px-4">
                {/* Mobile Back Button */}
                <div className="md:hidden mb-6">
                    <Link href={route('profile.edit')} className="inline-flex items-center text-sm font-medium text-muted-foreground hover:text-foreground">
                        <img src="/icons/chevron-right.png" alt="" width={16} height={16} style={{ objectFit: 'contain', transform: 'rotate(180deg)' }} className="mr-1" />
                        Retour au profil
                    </Link>
                </div>

                <div className="flex items-center gap-3 mb-6">
                    <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <img src="/icons/moon.png" alt="" width={20} height={20} style={{ objectFit: 'contain' }} />
                    </div>
                    <div>
                        <h2 className="text-xl font-black">Apparence</h2>
                        <p className="text-sm text-muted-foreground">Personnalisez le thème de l'application</p>
                    </div>
                </div>

                <div className="overflow-hidden rounded-2xl border bg-card shadow-sm p-4 md:p-6">
                    <AppearanceTabs />
                </div>
            </div>
        </AppLayout>
    );
}
