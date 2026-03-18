import { Brain, LineChart, Target, Zap } from 'lucide-react';

const features = [
    {
        icon: Brain,
        title: 'AI-Generated Exercises',
        description: 'Unlimited practice with exercises created by AI, tailored to your level and target exam format.',
    },
    {
        icon: Zap,
        title: 'Instant Feedback',
        description: 'Get detailed corrections and explanations for writing, speaking, and all question types.',
    },
    {
        icon: Target,
        title: 'Adaptive Learning',
        description: 'Smart algorithms identify your weaknesses and focus practice where you need it most.',
    },
    {
        icon: LineChart,
        title: 'Progress Analytics',
        description: 'Track your scores, streaks, and improvement over time with detailed skill breakdowns.',
    },
];

export function FeaturesSection() {
    return (
        <section id="features" className="border-t border-border bg-muted/30 py-24">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="text-center">
                    <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">
                        Everything you need to succeed
                    </h2>
                    <p className="mt-4 text-lg text-muted-foreground">
                        Powerful tools designed to maximize your exam score
                    </p>
                </div>

                <div className="mt-16 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                    {features.map((feature) => (
                        <div key={feature.title} className="rounded-xl border border-border bg-card p-6">
                            <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10">
                                <feature.icon className="h-6 w-6 text-primary" />
                            </div>
                            <h3 className="mt-4 font-semibold">{feature.title}</h3>
                            <p className="mt-2 text-sm leading-6 text-muted-foreground">
                                {feature.description}
                            </p>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
