import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { ExerciseTimer } from './exercise-timer';
import { ExerciseProgress } from './exercise-progress';
import { useCallback, useEffect, useRef, useState } from 'react';
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
import { ListenRepeat } from './listen-repeat';
import { SpeakButton } from './speak-button';

interface ExercisePlayerProps {
    exercise: ExerciseRecord;
}

function AudioPlayer({ src }: { src: string }) {
    const audioRef = useRef<HTMLAudioElement>(null);
    const [playing, setPlaying] = useState(false);
    const [playCount, setPlayCount] = useState(0);

    useEffect(() => {
        setPlaying(false);
        setPlayCount(0);
    }, [src]);

    const toggle = () => {
        const el = audioRef.current;
        if (!el) return;
        if (playing) {
            el.pause();
            setPlaying(false);
        } else {
            el.currentTime = 0;
            el.play().catch(() => setPlaying(false));
            setPlaying(true);
        }
    };

    return (
        <div className="flex items-center gap-3 rounded-xl border-2 border-primary bg-primary/5 px-5 py-3">
            <audio
                ref={audioRef}
                src={src}
                onEnded={() => { setPlaying(false); setPlayCount(c => c + 1); }}
            />
            <button
                onClick={toggle}
                className="flex items-center gap-2 text-primary font-medium transition hover:opacity-80"
            >
                {playing ? (
                    <svg viewBox="0 0 20 20" fill="currentColor" className="h-5 w-5">
                        <path d="M5.75 3a.75.75 0 00-.75.75v12.5c0 .414.336.75.75.75h1.5a.75.75 0 00.75-.75V3.75A.75.75 0 007.25 3h-1.5zM12.75 3a.75.75 0 00-.75.75v12.5c0 .414.336.75.75.75h1.5a.75.75 0 00.75-.75V3.75a.75.75 0 00-.75-.75h-1.5z" />
                    </svg>
                ) : (
                    <svg viewBox="0 0 20 20" fill="currentColor" className="h-5 w-5">
                        <path d="M6.3 2.84A1.5 1.5 0 004 4.11v11.78a1.5 1.5 0 002.3 1.27l9.344-5.891a1.5 1.5 0 000-2.538L6.3 2.84z" />
                    </svg>
                )}
                <span className="text-sm">
                    {playing ? 'En cours...' : playCount === 0 ? 'Écouter' : `Réécouter (${playCount}×)`}
                </span>
            </button>
        </div>
    );
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
    'listen-repeat': ListenRepeat,
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
    const skillType = exercise.exercise_type?.skill_type;

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

            {/* Passage / Content — hidden for LISTENING (the passage is the transcript
                that must be heard, not read; audio is played via AudioPlayer/SpeakButton). */}
            {skillType !== 'listening' && typeof exercise.content?.passage === 'string' && (
                <div className="rounded-lg border border-border bg-muted/30 p-4 text-sm leading-relaxed">
                    {exercise.content.passage}
                </div>
            )}

            {/* Audio player for listening exercises — shown when question has a pre-generated audio_url */}
            {question.audio_url && componentKey !== 'dictation' && (
                <AudioPlayer src={question.audio_url} />
            )}

            {/* Fallback TTS : exercice d'écoute sans audio pré-généré mais avec un texte à dire.
                Évite un listening totalement muet ; lit le texte via Deepgram dans la bonne langue. */}
            {!question.audio_url && skillType === 'listening' && typeof question.audio_text === 'string' && question.audio_text.trim() !== '' && (
                <div className="flex items-center gap-3 rounded-xl border-2 border-primary bg-primary/5 px-5 py-3">
                    <SpeakButton text={question.audio_text} lang={lang} label="Écouter l'enregistrement" />
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
