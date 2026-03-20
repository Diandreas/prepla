import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { useState } from 'react';
import type { ExamRecord, ExamSection, ExerciseTypeRecord } from '@/types';

interface Props {
    exams: (ExamRecord & { sections: (ExamSection & { exercise_types: ExerciseTypeRecord[] })[] })[];
    targetExamId?: number | null;
    userLevel?: string | null;
}

const difficulties = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

export default function Generator({ exams, targetExamId, userLevel }: Props) {
    const [selectedExamId, setSelectedExamId] = useState<number | null>(targetExamId ?? null);
    const [selectedTypeId, setSelectedTypeId] = useState<number | null>(null);
    const [difficulty, setDifficulty] = useState(userLevel ?? 'B1');
    const [generating, setGenerating] = useState(false);

    const selectedExam = exams.find((e) => e.id === selectedExamId);
    const allTypes = selectedExam?.sections.flatMap((s) => s.exercise_types ?? []) ?? [];

    function handleGenerate() {
        if (!selectedExamId || !selectedTypeId) return;
        setGenerating(true);
        router.post(route('ai-tools.generator.store'), {
            exam_id: selectedExamId,
            exercise_type_id: selectedTypeId,
            difficulty,
        });
    }

    return (
        <AppLayout>
            <Head title="Générateur d'exercices IA" />
            <div className="space-y-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold">Générateur d'exercices</h1>
                    <p className="text-muted-foreground">
                        Générez des exercices personnalisés adaptés à vos besoins
                    </p>
                </div>

                {targetExamId && selectedExam && (
                    <div className="flex items-center gap-4 rounded-xl border bg-card p-4 shadow-sm">
                        <div className="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-2xl">
                            {selectedExam.language?.flag}
                        </div>
                        <div>
                            <p className="text-xs font-bold uppercase tracking-wider text-muted-foreground">Examen cible</p>
                            <p className="text-lg font-bold">{selectedExam.name}</p>
                        </div>
                    </div>
                )}

                {/* Step 1: Choose Exam (Only if not pre-selected) */}
                {!targetExamId && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">1. Choisir un examen</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                {exams.map((exam) => (
                                    <button
                                        key={exam.id}
                                        onClick={() => {
                                            setSelectedExamId(exam.id);
                                            setSelectedTypeId(null);
                                        }}
                                        className={`rounded-lg border p-3 text-left transition-all ${
                                            selectedExamId === exam.id
                                                ? 'border-primary bg-primary/5 ring-1 ring-primary'
                                                : 'border-border hover:border-primary/50'
                                        }`}
                                    >
                                        <span className="mr-2">{exam.language?.flag}</span>
                                        <span className="font-medium">{exam.name}</span>
                                    </button>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Step 2: Choose Exercise Type */}
                {selectedExam && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">{targetExamId ? '1' : '2'}. Choisir un type d'exercice</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-2 sm:grid-cols-2">
                                {allTypes.map((type) => (
                                    <button
                                        key={type.id}
                                        onClick={() => setSelectedTypeId(type.id)}
                                        className={`rounded-lg border p-3 text-left transition-all ${
                                            selectedTypeId === type.id
                                                ? 'border-primary bg-primary/5 ring-1 ring-primary'
                                                : 'border-border hover:border-primary/50'
                                        }`}
                                    >
                                        <p className="font-medium">{type.name}</p>
                                        <Badge variant="secondary" className="mt-1 text-xs">{type.skill_type}</Badge>
                                    </button>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Step 3: Choose Difficulty */}
                {selectedTypeId && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">{targetExamId ? '2' : '3'}. Choisir la difficulté</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex flex-wrap gap-2">
                                {difficulties.map((d) => (
                                    <button
                                        key={d}
                                        onClick={() => setDifficulty(d)}
                                        className={`rounded-lg border px-4 py-2 font-medium transition-all ${
                                            difficulty === d
                                                ? 'border-primary bg-primary/5 ring-1 ring-primary'
                                                : 'border-border hover:border-primary/50'
                                        }`}
                                    >
                                        {d}
                                    </button>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Generate button */}
                {selectedTypeId && (
                    <Button
                        size="lg"
                        onClick={handleGenerate}
                        disabled={generating}
                        className="gap-2"
                    >
                        <img src="/icons/sparkles.png" alt="" width={16} height={16} style={{ objectFit: 'contain' }} />
                        {generating ? 'Génération en cours...' : "Générer l'exercice"}
                    </Button>
                )}
            </div>
        </AppLayout>
    );
}
