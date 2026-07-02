import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import type { ExerciseRecord, ExamRecord } from '@/types';

interface Props {
    exercises: ExerciseRecord[];
    exam: ExamRecord;
    difficulty: string;
}

export default function BatchResults({ exercises, exam, difficulty }: Props) {
    return (
        <AppLayout>
            <Head title="Résultats de la génération" />
            <div className="mx-auto max-w-5xl space-y-8 p-4 md:p-8">
                <div className="relative overflow-hidden rounded-[40px] bg-indigo-600 p-8 text-white shadow-2xl shadow-indigo-600/30 md:p-12">
                    <div className="relative z-10 space-y-4">
                        <div className="flex items-center gap-3">
                            <Badge className="bg-white/20 text-white backdrop-blur-md">
                                {difficulty}
                            </Badge>
                            <span className="text-sm font-bold uppercase tracking-wider opacity-60">
                                {exam.name}
                            </span>
                        </div>
                        <h1 className="text-4xl font-black md:text-5xl">Génération Terminée !</h1>
                        <p className="max-w-md text-lg font-medium text-indigo-100">
                            L'IA a concocté {exercises.length} exercices personnalisés pour toi. Prêt à relever le défi ?
                        </p>
                    </div>
                    {/* Decorative blobs */}
                    <div className="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-white/10 blur-3xl" />
                    <div className="absolute -bottom-20 -left-20 h-64 w-64 rounded-full bg-indigo-400/20 blur-3xl" />
                </div>

                <div className="grid gap-6">
                    {exercises.map((ex, i) => (
                        <Card 
                            key={ex.id} 
                            className="group overflow-hidden rounded-[32px] border-none bg-white shadow-xl shadow-slate-200/50 transition-all hover:-translate-y-1 hover:shadow-2xl hover:shadow-indigo-600/10"
                        >
                            <CardContent className="flex items-center gap-6 p-6 md:p-8">
                                <div className="flex h-16 w-16 items-center justify-center rounded-3xl bg-slate-50 font-black text-slate-300 transition-colors group-hover:bg-indigo-50 group-hover:text-indigo-600 md:h-20 md:w-20 md:text-2xl">
                                    {String(i + 1).padStart(2, '0')}
                                </div>
                                <div className="flex-1 space-y-1">
                                    <h3 className="text-xl font-black text-slate-900 md:text-2xl">
                                        {ex.exercise_type?.name || 'Exercice'}
                                    </h3>
                                    <div className="flex items-center gap-4">
                                        <Badge variant="secondary" className="bg-slate-100 text-slate-600">
                                            {ex.exercise_type?.skill_type}
                                        </Badge>
                                        <span className="text-sm font-bold text-slate-400">
                                            {ex.questions?.length || 0} Questions
                                        </span>
                                    </div>
                                </div>
                                <Link href={route('exercise.show', ex.id)}>
                                    <Button size="lg" className="h-14 rounded-2xl bg-indigo-600 px-8 font-black uppercase tracking-wider text-white shadow-lg shadow-indigo-600/20 hover:bg-indigo-700">
                                        Commencer
                                    </Button>
                                </Link>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <div className="flex justify-center pt-4">
                    <Link href={route('ai-tools.generator')}>
                        <Button variant="ghost" className="font-bold text-slate-500 hover:text-indigo-600">
                            ← Retour au générateur
                        </Button>
                    </Link>
                </div>
            </div>
        </AppLayout>
    );
}
