import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent } from '@/components/ui/card';
import { Brain, FileEdit, HelpCircle, Lightbulb, Sparkles } from 'lucide-react';

const tools = [
    {
        title: 'Exercise Generator',
        description: 'Generate unlimited practice exercises tailored to your exam and level',
        icon: Sparkles,
        href: '/ai-tools/generator',
        color: 'text-purple-500',
    },
    {
        title: 'Writing Corrector',
        description: 'Submit your essays and get detailed corrections with band scores',
        icon: FileEdit,
        href: '/ai-tools/writing-corrector',
        color: 'text-blue-500',
    },
    {
        title: 'AI Explainer',
        description: 'Ask questions about grammar, vocabulary, or exam strategies',
        icon: HelpCircle,
        href: '/ai-tools/explainer',
        color: 'text-green-500',
    },
    {
        title: 'Recommendations',
        description: 'Get personalized study recommendations based on your performance',
        icon: Lightbulb,
        href: '/ai-tools/recommendations',
        color: 'text-orange-500',
    },
];

export default function AiToolsIndex() {
    return (
        <AppLayout>
            <Head title="AI Tools" />
            <div className="space-y-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold">AI Tools</h1>
                    <p className="text-muted-foreground">
                        Supercharge your preparation with AI-powered tools
                    </p>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    {tools.map((tool) => (
                        <Link key={tool.href} href={tool.href}>
                            <Card className="h-full transition-all hover:border-primary/50 hover:shadow-md">
                                <CardContent className="flex items-start gap-4 p-6">
                                    <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-primary/10">
                                        <tool.icon className={`h-6 w-6 ${tool.color}`} />
                                    </div>
                                    <div>
                                        <h3 className="font-semibold">{tool.title}</h3>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            {tool.description}
                                        </p>
                                    </div>
                                </CardContent>
                            </Card>
                        </Link>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
