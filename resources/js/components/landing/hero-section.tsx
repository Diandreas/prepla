import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { ArrowRight, Sparkles } from 'lucide-react';

export function HeroSection() {
    return (
        <section className="relative overflow-hidden py-24 sm:py-32">
            {/* Background gradient */}
            <div className="absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-primary/20 via-background to-background" />

            <div className="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
                <div className="mx-auto max-w-3xl">
                    <div className="mb-6 inline-flex items-center gap-2 rounded-full border border-border bg-muted/50 px-4 py-1.5 text-sm">
                        <Sparkles className="h-4 w-4 text-primary" />
                        <span>AI-powered language test preparation</span>
                    </div>

                    <h1 className="text-4xl font-bold tracking-tight sm:text-6xl lg:text-7xl">
                        Ace your language exam with{' '}
                        <span className="bg-gradient-to-r from-primary to-primary/60 bg-clip-text text-transparent">
                            AI-powered practice
                        </span>
                    </h1>

                    <p className="mt-6 text-lg leading-8 text-muted-foreground sm:text-xl">
                        Prepare for IELTS, TOEFL, DELF, HSK, and 20+ language exams with personalized AI exercises,
                        instant feedback, and adaptive learning paths.
                    </p>

                    <div className="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row">
                        <Button size="lg" asChild className="gap-2">
                            <Link href="/register">
                                Start Free Practice
                                <ArrowRight className="h-4 w-4" />
                            </Link>
                        </Button>
                        <Button size="lg" variant="outline" asChild>
                            <a href="#pricing">View Pricing</a>
                        </Button>
                    </div>

                    <p className="mt-4 text-sm text-muted-foreground">
                        No credit card required · Free plan available
                    </p>
                </div>
            </div>
        </section>
    );
}
