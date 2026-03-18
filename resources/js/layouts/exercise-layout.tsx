import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { X } from 'lucide-react';
import { PropsWithChildren } from 'react';

interface ExerciseLayoutProps {
    title: string;
    examName?: string;
    backUrl?: string;
}

export default function ExerciseLayout({
    title,
    examName,
    backUrl = '/practice',
    children,
}: PropsWithChildren<ExerciseLayoutProps>) {
    return (
        <>
            <Head title={title} />
            <div className="flex min-h-screen flex-col bg-background">
                {/* Minimal header */}
                <header className="border-b border-border px-4 py-3">
                    <div className="mx-auto flex max-w-4xl items-center justify-between">
                        <div className="flex items-center gap-3">
                            <Button
                                variant="ghost"
                                size="icon"
                                onClick={() => router.visit(backUrl)}
                            >
                                <X className="h-5 w-5" />
                            </Button>
                            <div>
                                <p className="text-sm font-medium">{title}</p>
                                {examName && (
                                    <p className="text-xs text-muted-foreground">{examName}</p>
                                )}
                            </div>
                        </div>
                    </div>
                </header>

                <main className="mx-auto flex w-full max-w-4xl flex-1 flex-col px-4 py-6">
                    {children}
                </main>
            </div>
        </>
    );
}
