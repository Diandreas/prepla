import { ClipboardCheck, GraduationCap, UserPlus } from 'lucide-react';

const steps = [
    {
        step: 1,
        icon: UserPlus,
        title: 'Choose Your Exam',
        description: 'Sign up and select your target language exam. Tell us your goal score and exam date.',
    },
    {
        step: 2,
        icon: ClipboardCheck,
        title: 'Practice with AI',
        description: 'Complete AI-generated exercises that match your exam format. Get instant feedback and corrections.',
    },
    {
        step: 3,
        icon: GraduationCap,
        title: 'Track & Improve',
        description: 'Monitor your progress with detailed analytics. Focus on weak areas and watch your scores climb.',
    },
];

export function HowItWorks() {
    return (
        <section className="py-24">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="text-center">
                    <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">
                        How it works
                    </h2>
                    <p className="mt-4 text-lg text-muted-foreground">
                        Start improving in three simple steps
                    </p>
                </div>

                <div className="mt-16 grid gap-8 md:grid-cols-3">
                    {steps.map((step) => (
                        <div key={step.step} className="relative text-center">
                            <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-primary text-2xl font-bold text-primary-foreground">
                                {step.step}
                            </div>
                            <h3 className="mt-6 text-lg font-semibold">{step.title}</h3>
                            <p className="mt-2 text-muted-foreground">{step.description}</p>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
