import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { useState } from 'react';
import * as Flags from 'country-flag-icons/react/3x2';
import type { ExamRecord, ExamSection, ExerciseTypeRecord } from '@/types';

function flagEmojiToCode(flag: string): string {
    const points = [...flag].map(c => c.codePointAt(0)! - 0x1F1E6);
    if (points.length === 2 && points[0] >= 0 && points[0] <= 25) {
        return String.fromCharCode(65 + points[0], 65 + points[1]);
    }
    return '';
}

function FlagImg({ flag, size = 20 }: { flag: string; size?: number }) {
    const code = flagEmojiToCode(flag);
    const FlagComponent = code ? (Flags as Record<string, React.ComponentType<{ style?: React.CSSProperties }>>)[code] : null;
    if (FlagComponent) return <FlagComponent style={{ width: size, borderRadius: 2, display: 'inline-block', verticalAlign: 'middle' }} />;
    return <span>{flag}</span>;
}

interface Props {
    exams: (ExamRecord & { sections: (ExamSection & { exercise_types: ExerciseTypeRecord[] })[] })[];
    targetExamId?: number | null;
    userLevel?: string | null;
}

const difficulties = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

export default function Generator({ exams, targetExamId, userLevel }: Props) {
    const [selectedExamId, setSelectedExamId] = useState<number | null>(targetExamId ?? null);
    const [selectedTypeIds, setSelectedTypeIds] = useState<number[]>([]);
    const [difficulty, setDifficulty] = useState(userLevel ?? 'B1');
    const [generating, setGenerating] = useState(false);

    const selectedExam = exams.find((e) => e.id === selectedExamId);
    const allTypes = selectedExam?.sections.flatMap((s) => s.exercise_types ?? []) ?? [];

    function toggleType(id: number) {
        setSelectedTypeIds(prev => 
            prev.includes(id) ? prev.filter(t => t !== id) : [...prev, id]
        );
    }

    function handleGenerate() {
        if (!selectedExamId || selectedTypeIds.length === 0) return;
        setGenerating(true);
        router.post(route('ai-tools.generator.store'), {
            exam_id: selectedExamId,
            exercise_type_ids: selectedTypeIds,
            difficulty,
        });
    }

    return (
        <AppLayout>
            <Head title="Générateur d'exercices IA" />
            <div className="space-y-6 p-4 md:p-6 pb-32">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-black tracking-tight">Générateur d'exercices</h1>
                        <p className="text-slate-500">
                            Crée des exercices sur-mesure pour ta préparation
                        </p>
                    </div>
                    {selectedTypeIds.length > 0 && (
                        <Badge variant="outline" className="px-4 py-2 rounded-full border-indigo-200 bg-indigo-50 text-indigo-700 font-bold">
                            {selectedTypeIds.length} sélectionné{selectedTypeIds.length > 1 ? 's' : ''}
                        </Badge>
                    )}
                </div>

                {/* Step 2: Choose Exercise Type */}
                {selectedExam && (
                    <Card className={`rounded-[32px] border-none shadow-2xl shadow-slate-200/50 transition-opacity duration-300 ${generating ? 'opacity-50 pointer-events-none' : ''}`}>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-lg font-black uppercase tracking-wider text-slate-400">1. Sélectionnez les types d'exercices</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                {allTypes.map((type) => {
                                    const isSelected = selectedTypeIds.includes(type.id);
                                    return (
                                        <button
                                            key={type.id}
                                            onClick={() => toggleType(type.id)}
                                            disabled={generating}
                                            className={`group relative flex flex-col items-start rounded-3xl border-2 p-5 text-left transition-all ${
                                                isSelected
                                                    ? 'border-indigo-600 bg-indigo-50 ring-4 ring-indigo-600/10'
                                                    : 'border-slate-100 bg-white hover:border-indigo-200 hover:bg-slate-50'
                                            } ${generating ? 'cursor-not-allowed' : ''}`}
                                        >
                                            <div className={`mb-3 flex h-10 w-10 items-center justify-center rounded-2xl transition-colors ${
                                                isSelected ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-400 group-hover:bg-indigo-100 group-hover:text-indigo-600'
                                            }`}>
                                                <div className="font-black text-xs">{type.skill_type.substring(0, 3).toUpperCase()}</div>
                                            </div>
                                            <p className={`font-black text-sm tracking-tight ${isSelected ? 'text-indigo-900' : 'text-slate-700'}`}>
                                                {type.name}
                                            </p>
                                            
                                            {isSelected && (
                                                <div className="absolute top-4 right-4 text-indigo-600">
                                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3"><path d="M20 6L9 17l-5-5" /></svg>
                                                </div>
                                            )}
                                        </button>
                                    );
                                })}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Step 3: Choose Difficulty */}
                {selectedTypeIds.length > 0 && (
                    <Card className={`rounded-[32px] border-none shadow-2xl shadow-slate-200/50 transition-opacity duration-300 ${generating ? 'opacity-50 pointer-events-none' : ''}`}>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-lg font-black uppercase tracking-wider text-slate-400">2. Niveau CEFR</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex flex-wrap gap-3">
                                {difficulties.map((d) => (
                                    <button
                                        key={d}
                                        onClick={() => setDifficulty(d)}
                                        disabled={generating}
                                        className={`rounded-2xl border-2 px-6 py-3 font-black transition-all ${
                                            difficulty === d
                                                ? 'border-indigo-600 bg-indigo-600 text-white shadow-lg shadow-indigo-600/20'
                                                : 'border-slate-100 bg-white text-slate-500 hover:border-indigo-200'
                                        } ${generating ? 'cursor-not-allowed' : ''}`}
                                    >
                                        {d}
                                    </button>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Sticky Action Bar — bottom-24 sur mobile pour passer au-dessus de la bottom-nav, bottom-8 sur desktop */}
                {selectedTypeIds.length > 0 && (
                    <div
                        className="fixed bottom-24 md:bottom-8 left-1/2 -translate-x-1/2 w-full max-w-sm px-4 animate-in slide-in-from-bottom-10 duration-500"
                        style={{ paddingBottom: 'env(safe-area-inset-bottom, 0px)' }}
                    >
                        <Button
                            size="lg"
                            onClick={handleGenerate}
                            disabled={generating}
                            className="h-16 w-full rounded-3xl bg-indigo-600 font-black uppercase tracking-widest text-white shadow-2xl shadow-indigo-600/40 hover:bg-indigo-700 hover:shadow-indigo-600/50 disabled:bg-slate-400 disabled:shadow-none"
                        >
                            {generating ? (
                                <div className="flex items-center gap-3">
                                    <div className="h-5 w-5 animate-spin rounded-full border-4 border-white/30 border-t-white" />
                                    <span>Génération...</span>
                                </div>
                            ) : (
                                <span>Générer {selectedTypeIds.length} Exercice{selectedTypeIds.length > 1 ? 's' : ''}</span>
                            )}
                        </Button>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
