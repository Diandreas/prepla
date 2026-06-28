import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent } from '@/components/ui/card';

interface Center {
    id: number;
    name: string;
    seats_limit: number;
    seats_used: number;
    classrooms_count: number;
}

function Stat({ label, value, href }: { label: string; value: React.ReactNode; href?: string }) {
    const inner = (
        <Card className="transition-colors hover:bg-muted/30">
            <CardContent className="p-4">
                <p className="text-2xl font-bold">{value}</p>
                <p className="text-sm text-muted-foreground">{label}</p>
            </CardContent>
        </Card>
    );
    return href ? <Link href={href}>{inner}</Link> : inner;
}

export default function CenterDashboard({ center }: { center: Center }) {
    return (
        <AppLayout>
            <Head title={center.name} />
            <div className="mx-auto w-full max-w-4xl space-y-5 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">{center.name}</h1>
                    <p className="text-sm text-muted-foreground">Tableau de bord du centre</p>
                </div>

                <div className="grid gap-3 sm:grid-cols-3">
                    <Stat label="Classes" value={center.classrooms_count} href={route('center.classes.index')} />
                    <Stat label="Élèves" value={`${center.seats_used} / ${center.seats_limit}`} href={route('center.students.index')} />
                    <Stat label="Sièges restants" value={Math.max(0, center.seats_limit - center.seats_used)} />
                </div>
            </div>
        </AppLayout>
    );
}
