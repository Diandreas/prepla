import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Sparkles } from 'lucide-react';
import { useState } from 'react';
import type { ExamRecord, ExamSection, ExerciseTypeRecord } from '@/types';

interface Props {
    exams: (ExamRecord & { sections: (ExamSection & { exercise_types: ExerciseTypeRecord[] })[] })[];
}

const difficulties = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

export default function Generator({ exams }: Props) {
    const [selectedExamId, setSelectedExamId] = useState<number | null>(null);
    const [selectedTypeId, setSelectedTypeId] = useState<number | null>(null);
    const [difficulty, setDifficulty] = useState('B1');
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
            <Head title="AI Exercise Generator" />
            <div className="space-y-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold">Exercise Generator</h1>
                    <p className="text-muted-foreground">
                        Generate custom exercises tailored to your needs
                    </p>
                </div>

                {/* Step 1: Choose Exam */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">1. Choose Exam</CardTitle>
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

                {/* Step 2: Choose Exercise Type */}
                {selectedExam && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">2. Choose Exercise Type</CardTitle>
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
                            <CardTitle className="text-base">3. Choose Difficulty</CardTitle>
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
                        <Sparkles className="h-4 w-4" />
                        {generating ? 'Generating...' : 'Generate Exercise'}
                    </Button>
                )}
            </div>
        </AppLayout>
    );
}
