import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { useState, useCallback, useMemo } from 'react';
import { useTranslation } from 'react-i18next';
function Icon({ name, size = 20, className, style }: { name: string; size?: number; className?: string; style?: React.CSSProperties }) {
    return <img src={`/icons/${name}.png`} alt="" width={size} height={size} className={className} style={{ objectFit: 'contain', ...style }} />;
}
import { Mcq } from '@/components/exercises/mcq';
import { TrueFalseNg } from '@/components/exercises/true-false-ng';
import { GapFill } from '@/components/exercises/gap-fill';
import { Matching } from '@/components/exercises/matching';
import { EssayEditor } from '@/components/exercises/essay-editor';
import { SentenceCompletion } from '@/components/exercises/sentence-completion';
import { ShortAnswer } from '@/components/exercises/short-answer';
import { NoteCompletion } from '@/components/exercises/note-completion';
import { Ordering } from '@/components/exercises/ordering';
import { Dictation } from '@/components/exercises/dictation';
import { OpenCloze } from '@/components/exercises/open-cloze';
import { WordFormation } from '@/components/exercises/word-formation';
import { KeyWordTransformation } from '@/components/exercises/key-word-transformation';
import { Progress } from '@/components/ui/progress';

interface Exercise {
    id: number;
    exercise_type: {
        component_key: string;
        name: string;
    };
    questions: any[];
    content: any;
    difficulty: string;
}

interface Props {
    node: {
        id: number;
        title: string;
        level: string;
        exam: {
            name: string;
            language: {
                name: string;
            }
        }
    };
    exercises: Exercise[];
    progress: {
        id: number;
        status: string;
    };
}

const componentMap: Record<string, React.ComponentType<any>> = {
    'mcq': Mcq,
    'true-false-ng': TrueFalseNg,
    'gap-fill': GapFill,
    'matching': Matching,
    'essay-editor': EssayEditor,
    'sentence-completion': SentenceCompletion,
    'short-answer': ShortAnswer,
    'note-completion': NoteCompletion,
    'ordering': Ordering,
    'dictation': Dictation,
    'open-cloze': OpenCloze,
    'word-formation': WordFormation,
    'key-word-transformation': KeyWordTransformation,
};

export default function SessionPlayer({ node, exercises, progress }: Props) {
    const { t } = useTranslation();
    const [currentExerciseIndex, setCurrentExerciseIndex] = useState(0);
    const [currentQuestionIndex, setCurrentQuestionIndex] = useState(0);
    const [answers, setAnswers] = useState<Record<string, any>>({});
    const [isChecked, setIsChecked] = useState(false);
    const [isCorrect, setIsCorrect] = useState<boolean | null>(null);
    const [submitting, setSubmitting] = useState(false);

    const exercise = exercises[currentExerciseIndex];
    const questions = exercise?.questions ?? [];
    const question = questions[currentQuestionIndex];
    const Component = componentMap[exercise?.exercise_type?.component_key ?? 'mcq'] ?? Mcq;

    const totalQuestionsInSession = useMemo(() => 
        exercises.reduce((acc, ex) => acc + (ex.questions?.length ?? 0), 0)
    , [exercises]);

    const overallProgressCount = useMemo(() => {
        let count = 0;
        for (let i = 0; i < currentExerciseIndex; i++) {
            count += exercises[i].questions?.length ?? 0;
        }
        return count + currentQuestionIndex;
    }, [currentExerciseIndex, currentQuestionIndex, exercises]);

    const progressValue = (overallProgressCount / totalQuestionsInSession) * 100;

    const handleAnswer = useCallback((questionId: string, answer: any) => {
        if (isChecked) return;
        setAnswers(prev => ({ ...prev, [questionId]: answer }));
    }, [isChecked]);

    const checkAnswer = () => {
        if (!question || isChecked) return;
        
        // Simple check for MCQ/TrueFalse (assuming correct index is in question.correct)
        const currentAnswer = answers[question.id];
        const correctAnswer = question.correct;
        
        setIsCorrect(parseInt(currentAnswer) === parseInt(correctAnswer));
        setIsChecked(true);
    };

    const nextStep = () => {
        setIsChecked(false);
        setIsCorrect(null);

        if (currentQuestionIndex < questions.length - 1) {
            setCurrentQuestionIndex(prev => prev + 1);
        } else if (currentExerciseIndex < exercises.length - 1) {
            setCurrentExerciseIndex(prev => prev + 1);
            setCurrentQuestionIndex(0);
        } else {
            // End of session
            setSubmitting(true);
            router.post(route('exercise.submit_session', node.id), {
                answers,
                node_id: node.id
            });
        }
    };

    if (!exercise || !question) {
        return (
            <AppLayout>
                <div className="flex flex-col items-center justify-center py-20">
                    <Icon name="alert-circle" size={48} className="text-muted-foreground" />
                    <p className="mt-4 text-xl font-medium">Session non disponible</p>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout>
            <Head title={`${node.title} - PrePla`} />
            
            <div className="mx-auto max-w-3xl space-y-8 py-6">
                {/* Header & Global Progress */}
                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <Icon name="sparkles" size={20} />
                            </span>
                            <div>
                                <h1 className="text-lg font-bold leading-none">{node.title}</h1>
                                <p className="text-xs text-muted-foreground mt-1">
                                    {node.exam.language.name} • {node.exam.name}
                                </p>
                            </div>
                        </div>
                        <div className="text-right">
                            <span className="text-sm font-bold tabular-nums">
                                {overallProgressCount + 1} / {totalQuestionsInSession}
                            </span>
                        </div>
                    </div>
                    <Progress value={progressValue} className="h-2.5" />
                </div>

                {/* Exercise Content */}
                <div className="space-y-6">
                    {exercise.content?.passage && (
                        <div className="rounded-2xl border bg-card p-6 shadow-sm leading-relaxed text-sm prose prose-slate dark:prose-invert max-w-none">
                            {exercise.content.passage}
                        </div>
                    )}

                    <div className="rounded-2xl border bg-card p-6 shadow-sm">
                        <Component
                            question={question}
                            onAnswer={(ans: any) => handleAnswer(question.id, ans)}
                            selectedAnswer={answers[question.id]}
                            disabled={isChecked}
                        />
                    </div>
                </div>

                {/* Bottom Action Bar (Duolingo Style) */}
                <div className={`fixed bottom-0 left-0 right-0 border-t bg-background p-4 md:p-6 transition-colors duration-300 ${
                    isChecked ? (isCorrect ? 'bg-emerald-50 dark:bg-emerald-950/20 border-emerald-200' : 'bg-red-50 dark:bg-red-950/20 border-red-200') : ''
                }`}>
                    <div className="mx-auto flex max-w-3xl items-center justify-between">
                        <div className="flex-1">
                            {isChecked && (
                                <div className={`flex items-center gap-3 animate-in fade-in slide-in-from-bottom-2`}>
                                    <div className={`flex h-12 w-12 items-center justify-center rounded-full ${
                                        isCorrect ? 'bg-emerald-500 text-white' : 'bg-red-500 text-white'
                                    }`}>
                                        {isCorrect ? <Icon name="check" size={24} style={{ filter: 'brightness(0) invert(1)' }} /> : <Icon name="x" size={24} style={{ filter: 'brightness(0) invert(1)' }} />}
                                    </div>
                                    <div>
                                        <p className={`text-lg font-bold ${isCorrect ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-700 dark:text-red-400'}`}>
                                            {isCorrect ? t('exercise.correct') : t('exercise.incorrect')}
                                        </p>
                                        {!isCorrect && (
                                            <button className="text-sm font-medium underline text-red-600 dark:text-red-300 hover:text-red-700">
                                                {t('exercise.why_incorrect')}
                                            </button>
                                        )}
                                    </div>
                                </div>
                            )}
                        </div>
                        <Button
                            size="lg"
                            disabled={answers[question.id] === undefined || submitting}
                            onClick={isChecked ? nextStep : checkAnswer}
                            className={`min-w-[140px] h-12 rounded-xl font-bold shadow-md transition-all ${
                                isChecked 
                                    ? (isCorrect ? 'bg-emerald-500 hover:bg-emerald-600' : 'bg-red-500 hover:bg-red-600')
                                    : 'bg-primary'
                            }`}
                        >
                            {isChecked 
                                ? (currentExerciseIndex === exercises.length - 1 && currentQuestionIndex === questions.length - 1 ? t('exercise.finish') : t('exercise.next'))
                                : t('exercise.check')
                            }
                            {!isChecked && <Icon name="chevron-right" size={16} className="ml-2" style={{ filter: 'brightness(0) invert(1)' }} />}
                        </Button>
                    </div>
                </div>
            </div>
            {/* Add padding for fixed bottom bar */}
            <div className="h-24" />
        </AppLayout>
    );
}
