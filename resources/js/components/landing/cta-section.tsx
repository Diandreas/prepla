import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { ArrowRight } from 'lucide-react';

export function CtaSection() {
    return (
        <section className="py-24">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="relative overflow-hidden rounded-3xl bg-primary px-6 py-16 text-center text-primary-foreground sm:px-16 sm:py-24">
                    <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">
                        Ready to ace your exam?
                    </h2>
                    <p className="mx-auto mt-4 max-w-xl text-lg text-primary-foreground/80">
                        Join thousands of students preparing smarter with AI. Start your free practice today.
                    </p>
                    <div className="mt-8">
                        <Button size="lg" variant="secondary" asChild className="gap-2">
                            <Link href="/register">
                                Get Started for Free
                                <ArrowRight className="h-4 w-4" />
                            </Link>
                        </Button>
                    </div>
                </div>
            </div>
        </section>
    );
}
