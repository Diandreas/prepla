import { Head, Link } from '@inertiajs/react';
import { PropsWithChildren } from 'react';

interface OnboardingLayoutProps {
    title: string;
    step: number;
    totalSteps?: number;
}

const steps = ['Choose Exam', 'Set Goal', 'Placement Test', 'Results'];

export default function OnboardingLayout({
    title,
    step,
    totalSteps = 4,
    children,
}: PropsWithChildren<OnboardingLayoutProps>) {
    return (
        <>
            <Head title={`${title} - PrePla`} />
            <div className="flex min-h-screen flex-col bg-background">
                {/* Header */}
                <header className="border-b border-border px-4 py-4">
                    <div className="mx-auto flex max-w-3xl items-center justify-between">
                        <Link href="/" className="text-xl font-bold">
                            Pre<span className="text-primary">Pla</span>
                        </Link>
                        <span className="text-sm text-muted-foreground">
                            Step {step} of {totalSteps}
                        </span>
                    </div>
                </header>

                {/* Progress bar */}
                <div className="mx-auto w-full max-w-3xl px-4 pt-6">
                    <div className="flex gap-2">
                        {steps.map((label, i) => (
                            <div key={label} className="flex-1">
                                <div
                                    className={`h-2 rounded-full transition-colors ${
                                        i + 1 <= step ? 'bg-primary' : 'bg-muted'
                                    }`}
                                />
                                <p
                                    className={`mt-1 text-xs ${
                                        i + 1 === step
                                            ? 'font-medium text-foreground'
                                            : 'text-muted-foreground'
                                    }`}
                                >
                                    {label}
                                </p>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Content */}
                <main className="mx-auto flex w-full max-w-3xl flex-1 flex-col px-4 py-8">
                    {children}
                </main>
            </div>
        </>
    );
}
