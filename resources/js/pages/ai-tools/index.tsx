import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent } from '@/components/ui/card';
import { Brain, FileEdit, HelpCircle, Lightbulb, Sparkles } from 'lucide-react';

const tools = [
    {
        title: 'Générateur d\'exercices',
        description: 'Générez des exercices illimités adaptés à votre examen et votre niveau',
        icon: Sparkles,
        href: '/ai-tools/generator',
        color: 'text-purple-500',
    },
    {
        title: 'Correcteur de rédaction',
        description: 'Soumettez vos essais et obtenez des corrections détaillées avec scores',
        icon: FileEdit,
        href: '/ai-tools/writing-corrector',
        color: 'text-blue-500',
    },
    {
        title: 'Explicateur IA',
        description: 'Posez des questions sur la grammaire, le vocabulaire ou les stratégies',
        icon: HelpCircle,
        href: '/ai-tools/explainer',
        color: 'text-green-500',
    },
    {
        title: 'Recommandations',
        description: 'Obtenez des recommandations personnalisées basées sur vos performances',
        icon: Lightbulb,
        href: '/ai-tools/recommendations',
        color: 'text-orange-500',
    },
];

export default function AiToolsIndex() {
    return (
        <AppLayout>
            <Head title="Outils IA" />
            <div className="space-y-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold">Outils IA</h1>
                    <p className="text-muted-foreground">
                        Boostez votre préparation avec des outils alimentés par l'IA
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
