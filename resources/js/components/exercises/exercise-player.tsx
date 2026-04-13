import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { ExerciseTimer } from './exercise-timer';
import { ExerciseProgress } from './exercise-progress';
import { useCallback, useRef, useState } from 'react';
import type { ExerciseRecord } from '@/types';

// Exercise Components
import { Mcq } from './mcq';
import { TrueFalseNg } from './true-false-ng';
import { GapFill } from './gap-fill';
import { Matching } from './matching';
import { EssayEditor } from './essay-editor';
import { SpeakingRecorder } from './speaking-recorder';
import { Dictation } from './dictation';
import { ShortAnswer } from './short-answer';
import { SentenceCompletion } from './sentence-completion';
import { NoteCompletion } from './note-completion';
import { SummaryCompletion } from './summary-completion';
import { TableCompletion } from './table-completion';
import { FlowChartCompletion } from './flow-chart-completion';
import { FormCompletion } from './form-completion';
import { DiagramLabeling } from './diagram-labeling';
import { GraphDescription } from './graph-description';
import { InsertText } from './insert-text';
import { OpenCloze } from './open-cloze';
import { WordFormation } from './word-formation';
import { KeyWordTransformation } from './key-word-transformation';
import { GappedText } from './gapped-text';
import { MultipleMatching } from './multiple-matching';
import { Ordering } from './ordering';
import { ShortWriting } from './short-writing';
import { IntegratedTask } from './integrated-task';
import { AcademicDiscussion } from './academic-discussion';
import { RolePlay } from './role-play';
import { Synthesis } from './synthesis';
import { VocabularyCard } from './vocabulary-card';

interface ExercisePlayerProps {
    exercise: ExerciseRecord;
}

const componentMap: Record<string, React.ComponentType<any>> = {
    'mcq': Mcq,
    'true-false-ng': TrueFalseNg,
    'gap-fill': GapFill,
    'matching': Matching,
    'essay-editor': EssayEditor,
    'speaking-recorder': SpeakingRecorder,
    'dictation': Dictation,
    'short-answer': ShortAnswer,
    'sentence-completion': SentenceCompletion,
    'note-completion': NoteCompletion,
    'summary-completion': SummaryCompletion,
    'table-completion': TableCompletion,
    'flow-chart-completion': FlowChartCompletion,
    'form-completion': FormCompletion,
    'diagram-labeling': DiagramLabeling,
    'graph-description': GraphDescription,
    'insert-text': InsertText,
    'open-cloze': OpenCloze,
    'word-formation': WordFormation,
    'key-word-transformation': KeyWordTransformation,
    'gapped-text': GappedText,
    'multiple-matching': MultipleMatching,
    'ordering': Ordering,
    'short-writing': ShortWriting,
    'integrated-task': IntegratedTask,
    'academic-discussion': AcademicDiscussion,
    'role-play': RolePlay,
    'synthesis': Synthesis,
    'vocabulary-card': VocabularyCard,
};

export function ExercisePlayer({ exercise }: ExercisePlayerProps) {
    const [currentIndex, setCurrentIndex] = useState(0);
    const [answers, setAnswers] = useState<Record<string, any>>({});
    const timeRef = useRef(0);
    const [submitting, setSubmitting] = useState(false);

    const questions = exercise.questions ?? [];
    const question = questions[currentIndex];
    const componentKey = exercise.exercise_type?.component_key ?? 'mcq';
    const Component = componentMap[componentKey] ?? Mcq;
    const isLast = currentIndex === questions.length - 1;
    const lang = exercise.exam?.language?.slug ?? 'en';

    const handleAnswer = useCallback((questionId: string, answer: any) => {
        setAnswers((prev) => ({ ...prev, [questionId]: answer }));
    }, []);

    const handleTimeUpdate = useCallback((seconds: number) => {
        timeRef.current = seconds;
    }, []);

    function handleNext() {
        if (isLast) {
            handleSubmit();
        } else {
            setCurrentIndex((prev) => prev + 1);
        }
    }

    function handleSubmit() {
        setSubmitting(true);
        // Force FormData if there are any Blobs/Files
        const hasFiles = Object.values(answers).some(a => a instanceof Blob || a instanceof File);
        
        router.post(route('exercise.submit', exercise.id), {
            answers,
            time_spent: timeRef.current,
        }, {
            forceFormData: hasFiles,
        });
    }

    if (!question) {
        return <p className="text-center text-muted-foreground">Aucune question dans cet exercice.</p>;
    }

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <ExerciseProgress current={currentIndex + 1} total={questions.length} />
                <ExerciseTimer onTimeUpdate={handleTimeUpdate} />
            </div>

            {/* Passage / Content */}
            {typeof exercise.content?.passage === 'string' && (
                <div className="rounded-lg border border-border bg-muted/30 p-4 text-sm leading-relaxed">
                    {exercise.content.passage}
                </div>
            )}

            <Component
                question={question}
                onAnswer={handleAnswer}
                selectedAnswer={answers[question.id]}
                lang={lang}
            />

            <div className="flex justify-between pt-4">
                <Button
                    variant="outline"
                    disabled={currentIndex === 0}
                    onClick={() => setCurrentIndex((prev) => prev - 1)}
                >
                    Précédent
                </Button>
                <Button
                    onClick={handleNext}
                    disabled={(!answers[question.id] && componentKey !== 'speaking-recorder') || submitting}
                >
                    {isLast ? (submitting ? 'Envoi...' : 'Terminer') : 'Suivant'}
                </Button>
            </div>
        </div>
    );
}
