import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Check } from 'lucide-react';
import type { PricingPlan } from '@/data/languages';

interface PricingSectionProps {
    pricing: PricingPlan[];
}

export function PricingSection({ pricing }: PricingSectionProps) {
    return (
        <section id="pricing" className="border-t border-border bg-muted/30 py-24">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="text-center">
                    <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">
                        Simple, transparent pricing
                    </h2>
                    <p className="mt-4 text-lg text-muted-foreground">
                        Start free, upgrade when you're ready
                    </p>
                </div>

                <div className="mt-16 grid gap-8 md:grid-cols-3">
                    {pricing.map((plan) => (
                        <div
                            key={plan.slug}
                            className={`relative rounded-2xl border bg-card p-8 ${
                                plan.highlighted
                                    ? 'border-primary shadow-lg ring-1 ring-primary'
                                    : 'border-border'
                            }`}
                        >
                            {plan.highlighted && (
                                <div className="absolute -top-4 left-1/2 -translate-x-1/2 rounded-full bg-primary px-4 py-1 text-sm font-medium text-primary-foreground">
                                    Most Popular
                                </div>
                            )}
                            <h3 className="text-lg font-semibold">{plan.name}</h3>
                            <div className="mt-4 flex items-baseline gap-1">
                                <span className="text-4xl font-bold">
                                    {plan.price === 0 ? 'Free' : `$${plan.price}`}
                                </span>
                                {plan.period && (
                                    <span className="text-muted-foreground">/{plan.period}</span>
                                )}
                            </div>
                            <ul className="mt-8 space-y-3">
                                {plan.features.map((feature) => (
                                    <li key={feature} className="flex items-start gap-3">
                                        <Check className="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                                        <span className="text-sm">{feature}</span>
                                    </li>
                                ))}
                            </ul>
                            <Button
                                className="mt-8 w-full"
                                variant={plan.highlighted ? 'default' : 'outline'}
                                asChild
                            >
                                <Link href="/register">{plan.cta}</Link>
                            </Button>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
