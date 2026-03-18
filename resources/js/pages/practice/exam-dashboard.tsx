import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { BookOpen, Headphones, MessageSquare, Mic } from 'lucide-react';
import type { ExamRecord, ExamSection } from '@/types';

interface Props {
    exam: ExamRecord & { sections: ExamSection[] };
    sectionProgress: Record<number, number>;
}

const skillIcons: Record<string, typeof BookOpen> = {
    reading: BookOpen,
    listening: Headphones,
    writing: MessageSquare,
    speaking: Mic,
};

export default function ExamDashboard({ exam, sectionProgress }: Props) {
    return (
        <AppLayout>
            <Head title={`${exam.name} Practice`} />
            <div className="space-y-6 p-4 md:p-6">
                <div>
                    <div className="flex items-center gap-2">
                        <span className="text-2xl">{exam.language?.flag}</span>
                        <h1 className="text-2xl font-bold">{exam.name}</h1>
                    </div>
                    <p className="text-muted-foreground">Practice all sections of the {exam.name} exam</p>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    {exam.sections.map((section) => {
                        const Icon = skillIcons[section.skill_type] ?? BookOpen;
                        const attempts = sectionProgress[section.id] ?? 0;
                        return (
                            <Link
                                key={section.id}
                                href={route('practice.section', [exam.id, section.id])}
                            >
                                <Card className="h-full transition-all hover:border-primary/50 hover:shadow-md">
                                    <CardHeader className="flex flex-row items-center gap-3">
                                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                                            <Icon className="h-5 w-5 text-primary" />
                                        </div>
                                        <div>
                                            <CardTitle className="text-base">{section.name}</CardTitle>
                                            <p className="text-xs text-muted-foreground">
                                                {section.time_limit} min · {section.exercise_types?.length ?? 0} exercise types
                                            </p>
                                        </div>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm text-muted-foreground">
                                                {attempts} attempts
                                            </span>
                                            <Badge variant="secondary">{section.skill_type}</Badge>
                                        </div>
                                    </CardContent>
                                </Card>
                            </Link>
                        );
                    })}
                </div>
            </div>
        </AppLayout>
    );
}
