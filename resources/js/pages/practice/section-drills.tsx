import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { ExamRecord, ExamSection, ExerciseRecord } from '@/types';

interface Props {
    exam: ExamRecord;
    section: ExamSection;
    exercisesByDifficulty: Record<string, ExerciseRecord[]>;
}

const difficultyColors: Record<string, string> = {
    A1: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    A2: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
    B1: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    B2: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
    C1: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
    C2: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
};

export default function SectionDrills({ exam, section, exercisesByDifficulty }: Props) {
    const difficulties = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];
    const hasExercises = Object.keys(exercisesByDifficulty).length > 0;

    return (
        <AppLayout>
            <Head title={`${section.name} - ${exam.name}`} />
            <div className="space-y-6 p-4 md:p-6">
                <div>
                    <div className="flex items-center gap-2">
                        <span className="text-2xl">{exam.language?.flag}</span>
                        <h1 className="text-2xl font-bold">{section.name}</h1>
                    </div>
                    <p className="text-muted-foreground">{exam.name} · {section.skill_type}</p>
                </div>

                {!hasExercises ? (
                    <div className="rounded-xl border border-dashed border-border p-12 text-center">
                        <p className="text-lg font-medium">Aucun exercice pour l'instant</p>
                        <p className="mt-2 text-muted-foreground">
                            Utilisez le générateur IA pour créer des exercices pour cette section
                        </p>
                        <Button className="mt-4" asChild>
                            <Link href="/ai-tools">Accéder aux outils IA</Link>
                        </Button>
                    </div>
                ) : (
                    <div className="space-y-6">
                        {difficulties.map((difficulty) => {
                            const exercises = exercisesByDifficulty[difficulty];
                            if (!exercises || exercises.length === 0) return null;
                            return (
                                <div key={difficulty} className="space-y-3">
                                    <div className="flex items-center gap-2">
                                        <Badge className={difficultyColors[difficulty]}>
                                            {difficulty}
                                        </Badge>
                                        <span className="text-sm text-muted-foreground">
                                            {exercises.length} exercices
                                        </span>
                                    </div>
                                    <div className="grid gap-2">
                                        {exercises.map((exercise) => (
                                            <Link
                                                key={exercise.id}
                                                href={route('exercise.show', exercise.id)}
                                                className="flex items-center justify-between rounded-lg border border-border p-4 transition-all hover:border-primary/50"
                                            >
                                                <div>
                                                    <p className="font-medium">
                                                        {exercise.exercise_type?.name ?? 'Exercice'}
                                                    </p>
                                                    <p className="text-sm text-muted-foreground">
                                                        {exercise.xp_reward} XP
                                                    </p>
                                                </div>
                                                <Badge variant="outline">{exercise.exercise_type?.component_key}</Badge>
                                            </Link>
                                        ))}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
