import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Badge } from '@/components/ui/badge';
import type { ExamRecord } from '@/types';

interface Props {
    exams: ExamRecord[];
    targetExamId: number | null;
}

export default function PracticeIndex({ exams, targetExamId }: Props) {
    // Group exams by language
    const examsByLanguage = exams.reduce<Record<string, ExamRecord[]>>((acc, exam) => {
        const langName = exam.language?.name ?? 'Other';
        if (!acc[langName]) acc[langName] = [];
        acc[langName].push(exam);
        return acc;
    }, {});

    return (
        <AppLayout>
            <Head title="Practice" />
            <div className="space-y-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold">Practice</h1>
                    <p className="text-muted-foreground">Choose an exam to start practicing</p>
                </div>

                {Object.entries(examsByLanguage).map(([langName, langExams]) => (
                    <div key={langName} className="space-y-3">
                        <h2 className="text-lg font-semibold">{langName}</h2>
                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            {langExams.map((exam) => (
                                <Link
                                    key={exam.id}
                                    href={route('practice.exam', exam.id)}
                                    className={`rounded-xl border p-4 transition-all hover:border-primary/50 hover:shadow-md ${
                                        exam.id === targetExamId
                                            ? 'border-primary bg-primary/5'
                                            : 'border-border'
                                    }`}
                                >
                                    <div className="flex items-center justify-between">
                                        <h3 className="font-semibold">{exam.name}</h3>
                                        {exam.id === targetExamId && (
                                            <Badge>Your Exam</Badge>
                                        )}
                                    </div>
                                    {exam.levels && (
                                        <div className="mt-2 flex flex-wrap gap-1">
                                            {exam.levels.slice(0, 3).map((level) => (
                                                <Badge key={level} variant="secondary" className="text-xs">
                                                    {level}
                                                </Badge>
                                            ))}
                                            {exam.levels.length > 3 && (
                                                <Badge variant="secondary" className="text-xs">
                                                    +{exam.levels.length - 3}
                                                </Badge>
                                            )}
                                        </div>
                                    )}
                                </Link>
                            ))}
                        </div>
                    </div>
                ))}
            </div>
        </AppLayout>
    );
}
