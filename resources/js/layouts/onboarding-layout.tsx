import { Head, Link } from '@inertiajs/react';
import { PropsWithChildren, useEffect, useState } from 'react';

interface OnboardingLayoutProps {
    title: string;
    step: number;
    totalSteps?: number;
}

const steps = [
    { label: 'Langue', sublabel: 'Maternelle' },
    { label: 'Examen', sublabel: 'Choisir' },
    { label: 'Objectif', sublabel: 'Définir' },
    { label: 'Test', sublabel: 'Évaluer' },
    { label: 'Programme', sublabel: 'Démarrer' },
];

export default function OnboardingLayout({
    title,
    step,
    totalSteps = 5,
    children,
}: PropsWithChildren<OnboardingLayoutProps>) {
    const [mounted, setMounted] = useState(false);
    useEffect(() => setMounted(true), []);

    return (
        <>
            <Head title={`${title} - PrePla`} />
            <div className="flex min-h-screen flex-col bg-background">
                {/* Decorative background blobs */}
                <div className="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
                    <div className="animate-float absolute -top-32 left-1/4 h-[400px] w-[400px] rounded-full bg-primary/6 blur-3xl" />
                    <div className="animate-float absolute -bottom-32 right-1/4 h-[300px] w-[300px] rounded-full bg-violet-500/5 blur-3xl" style={{ animationDelay: '3s' }} />
                </div>

                {/* Header */}
                <header className="sticky top-0 z-10 border-b border-border/50 bg-background/80 px-4 py-3 backdrop-blur-xl">
                    <div className="mx-auto flex max-w-2xl items-center justify-between">
                        <Link href="/" className="flex items-center gap-1.5 text-xl font-black tracking-tight">
                            <img src="/icons/sparkles.png" alt="" width={20} height={20} style={{ objectFit: 'contain', filter: 'brightness(0) saturate(100%) invert(47%) sepia(68%) saturate(600%) hue-rotate(195deg)' }} />
                            Pre<span className="text-primary">Pla</span>
                        </Link>
                        <span className="rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                            Étape {step}/{totalSteps}
                        </span>
                    </div>
                </header>

                {/* Step indicators */}
                <div className="mx-auto w-full max-w-2xl px-4 pt-5">
                    <div className="flex items-start gap-1.5 sm:gap-2.5">
                        {steps.map((s, i) => {
                            const stepNum = i + 1;
                            const done = stepNum < step;
                            const active = stepNum === step;
                            return (
                                <div
                                    key={s.label}
                                    className="flex flex-1 flex-col items-center gap-1.5"
                                    style={{
                                        opacity: mounted ? 1 : 0,
                                        transform: mounted ? 'translateY(0)' : 'translateY(8px)',
                                        transition: 'all 0.5s ease',
                                        transitionDelay: `${i * 80}ms`,
                                    }}
                                >
                                    {/* Bar */}
                                    <div className="relative h-2 w-full overflow-hidden rounded-full bg-muted">
                                        <div
                                            className="absolute inset-y-0 left-0 rounded-full bg-gradient-to-r from-primary to-primary/70 transition-all duration-1000 ease-out"
                                            style={{
                                                width: mounted ? (done ? '100%' : active ? '60%' : '0%') : '0%',
                                                transitionDelay: `${300 + i * 100}ms`,
                                            }}
                                        />
                                        {/* Shimmer on active bar */}
                                        {active && (
                                            <div className="absolute inset-0 overflow-hidden rounded-full">
                                                <div className="absolute inset-0 w-1/2 bg-gradient-to-r from-transparent via-white/30 to-transparent animate-shimmer" style={{ backgroundSize: '200% 100%' }} />
                                            </div>
                                        )}
                                    </div>
                                    {/* Label */}
                                    <div className="flex items-center gap-1">
                                        {done ? (
                                            <img src="/icons/check-circle.png" alt="" width={14} height={14} style={{ objectFit: 'contain', filter: 'brightness(0) saturate(100%) invert(47%) sepia(68%) saturate(600%) hue-rotate(195deg)' }} />
                                        ) : (
                                            <span
                                                className={`flex h-4 w-4 items-center justify-center rounded-full text-[9px] font-bold transition-all duration-300 ${
                                                    active
                                                        ? 'bg-primary text-primary-foreground shadow-md shadow-primary/30'
                                                        : 'bg-muted text-muted-foreground'
                                                }`}
                                            >
                                                {stepNum}
                                            </span>
                                        )}
                                        <span
                                            className={`hidden text-xs font-medium sm:block ${
                                                active ? 'text-foreground' : done ? 'text-primary' : 'text-muted-foreground'
                                            }`}
                                        >
                                            {s.label}
                                        </span>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>

                {/* Content */}
                <main className="mx-auto flex w-full max-w-2xl flex-1 flex-col px-4 py-7">
                    {children}
                </main>
            </div>
        </>
    );
}
