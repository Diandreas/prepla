import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import OnboardingLayout from '@/layouts/onboarding-layout';
import { useState, useEffect } from 'react';
import type { LanguageWithExams } from '@/types';

import * as Flags from 'country-flag-icons/react/3x2';

function flagEmojiToCode(flag: string): string {
    const points = [...flag].map(c => c.codePointAt(0)! - 0x1F1E6);
    if (points.length === 2 && points[0] >= 0 && points[0] <= 25) {
        return String.fromCharCode(65 + points[0], 65 + points[1]);
    }
    return '';
}

function FlagImage({ flag }: { flag: string }) {
    const code = flagEmojiToCode(flag);
    const FlagComponent = code ? (Flags as Record<string, React.ComponentType<{ style?: React.CSSProperties }>>)[code] : null;
    if (FlagComponent) {
        return <FlagComponent style={{ width: 48, borderRadius: 4 }} />;
    }
    return <span style={{ fontSize: '2rem' }}>{flag}</span>;
}

interface Props {
    languages: LanguageWithExams[];
}

export default function ExamSelect({ languages }: Props) {
    const [selectedLanguage, setSelectedLanguage] = useState<LanguageWithExams | null>(null);
    const [selectedExamId, setSelectedExamId] = useState<number | null>(null);
    const [mounted, setMounted] = useState(false);
    const [examsVisible, setExamsVisible] = useState(false);

    useEffect(() => {
        setMounted(true);
    }, []);

    useEffect(() => {
        if (selectedLanguage) {
            setExamsVisible(false);
            const timer = setTimeout(() => setExamsVisible(true), 50);
            return () => clearTimeout(timer);
        }
    }, [selectedLanguage]);

    function handleSubmit() {
        if (!selectedExamId) return;
        router.post(route('onboarding.exam.store'), {
            exam_id: selectedExamId,
        });
    }

    return (
        <OnboardingLayout title="Choisissez votre examen" step={2}>
            {/* Floating background decoration */}
            <div className="pointer-events-none absolute inset-0 -z-10 overflow-hidden">
                <div className="animate-float absolute -top-32 right-1/4 h-[400px] w-[400px] rounded-full bg-primary/10 blur-3xl" />
                <div className="animate-float delay-300 absolute -bottom-24 left-1/3 h-[300px] w-[300px] rounded-full bg-violet-500/8 blur-3xl" />
            </div>

            <div className="space-y-8">
                <div className="text-center">
                    <h1 className="text-3xl font-bold tracking-tight">Choisissez votre langue</h1>
                    <p className="mt-2 text-muted-foreground">
                        Sélectionnez la langue que vous souhaitez préparer
                    </p>
                </div>

                {/* Language grid */}
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-5">
                    {languages.map((lang, index) => {
                        const isSelected = selectedLanguage?.id === lang.id;
                        return (
                            <button
                                key={lang.id}
                                onClick={() => {
                                    setSelectedLanguage(lang);
                                    setSelectedExamId(null);
                                }}
                                className={`relative rounded-xl border p-4 text-center transition-all duration-300 hover:border-primary/50 hover:shadow-md ${
                                    isSelected
                                        ? 'border-primary bg-primary/5 ring-2 ring-primary shadow-md'
                                        : 'border-border'
                                }`}
                                style={{
                                    opacity: mounted ? 1 : 0,
                                    transform: mounted
                                        ? isSelected
                                            ? 'translateY(0) scale(1.03)'
                                            : 'translateY(0) scale(1)'
                                        : 'translateY(12px) scale(1)',
                                    transition: `opacity 0.4s ease ${index * 0.07}s, transform 0.4s ease ${index * 0.07}s, border-color 0.2s, box-shadow 0.2s, background-color 0.2s`,
                                }}
                            >
                                {/* Checkmark overlay */}
                                {isSelected && (
                                    <span className="absolute top-1.5 right-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-sm">
                                        <img src="/icons/check.png" alt="" width={12} height={12} style={{ objectFit: 'contain', filter: 'brightness(0) invert(1)' }} />
                                    </span>
                                )}
                                <div className="flex justify-center mb-1"><FlagImage flag={lang.flag} /></div>
                                <p className="mt-2 text-sm font-medium">{lang.name}</p>
                            </button>
                        );
                    })}
                </div>

                {/* Exam selection */}
                {selectedLanguage && (
                    <div className="space-y-4">
                        <h2 className="text-xl font-semibold">
                            Choisissez votre examen de {selectedLanguage.name}
                        </h2>
                        <div className="grid gap-3 sm:grid-cols-2">
                            {selectedLanguage.exams.map((exam, index) => {
                                const isSelected = selectedExamId === exam.id;
                                return (
                                    <button
                                        key={exam.id}
                                        onClick={() => setSelectedExamId(exam.id)}
                                        className={`rounded-xl border p-4 text-left transition-all duration-300 hover:border-primary/50 hover:shadow-md ${
                                            isSelected
                                                ? 'border-primary bg-primary/5 ring-2 ring-primary shadow-md'
                                                : 'border-border'
                                        }`}
                                        style={{
                                            opacity: examsVisible ? 1 : 0,
                                            transform: examsVisible ? 'translateY(0)' : 'translateY(16px)',
                                            transition: `opacity 0.4s ease ${index * 0.08}s, transform 0.4s ease ${index * 0.08}s, border-color 0.2s, box-shadow 0.2s, background-color 0.2s`,
                                            borderLeft: isSelected
                                                ? '3px solid transparent'
                                                : undefined,
                                            borderImage: isSelected
                                                ? 'linear-gradient(to bottom, hsl(var(--primary)), hsl(var(--primary) / 0.5)) 1'
                                                : undefined,
                                            borderImageSlice: isSelected ? '1' : undefined,
                                        }}
                                    >
                                        <div className="flex items-center justify-between">
                                            <p className="font-semibold">{exam.name}</p>
                                            {exam.levels && (
                                                <Badge variant="outline" className="text-[10px] tabular-nums">
                                                    {exam.levels.length} niveau{exam.levels.length > 1 ? 'x' : ''}
                                                </Badge>
                                            )}
                                        </div>
                                        {exam.levels && (
                                            <div className="mt-2 flex flex-wrap gap-1">
                                                {exam.levels.map((level: string) => (
                                                    <Badge key={level} variant="secondary" className="text-xs">
                                                        {level}
                                                    </Badge>
                                                ))}
                                            </div>
                                        )}
                                    </button>
                                );
                            })}
                        </div>
                    </div>
                )}

                <div className="flex justify-end">
                    <Button
                        size="lg"
                        disabled={!selectedExamId}
                        onClick={handleSubmit}
                        className="bg-gradient-to-r from-primary to-primary/85 font-semibold shadow-md transition-all hover:-translate-y-0.5 hover:shadow-lg disabled:opacity-50"
                    >
                        Continuer →
                    </Button>
                </div>
            </div>
        </OnboardingLayout>
    );
}
