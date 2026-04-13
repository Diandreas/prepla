import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import { AiChatFeedback } from '@/components/exercises/ai-chat-feedback';

// --- Import all exercise components ---
import { Mcq } from '@/components/exercises/mcq';
import { TrueFalseNg } from '@/components/exercises/true-false-ng';
import { GapFill } from '@/components/exercises/gap-fill';
import { Matching } from '@/components/exercises/matching';
import { EssayEditor } from '@/components/exercises/essay-editor';
import { FormCompletion } from '@/components/exercises/form-completion';
import { IntegratedTask } from '@/components/exercises/integrated-task';
import { DiagramLabeling } from '@/components/exercises/diagram-labeling';
import { SpeakingRecorder } from '@/components/exercises/speaking-recorder';
import { GappedText } from '@/components/exercises/gapped-text';
import { GraphDescription } from '@/components/exercises/graph-description';
import { NoteCompletion } from '@/components/exercises/note-completion';
import { Ordering } from '@/components/exercises/ordering';
import { ShortAnswer } from '@/components/exercises/short-answer';
import { SentenceCompletion } from '@/components/exercises/sentence-completion';
import { TableCompletion } from '@/components/exercises/table-completion';
import { WordFormation } from '@/components/exercises/word-formation';
import { FlowChartCompletion } from '@/components/exercises/flow-chart-completion';
import { SummaryCompletion } from '@/components/exercises/summary-completion';
import { AcademicDiscussion } from '@/components/exercises/academic-discussion';
import { KeyWordTransformation } from '@/components/exercises/key-word-transformation';
import { OpenCloze } from '@/components/exercises/open-cloze';
import { Synthesis } from '@/components/exercises/synthesis';
import { RolePlay } from '@/components/exercises/role-play';
import { MultipleMatching } from '@/components/exercises/multiple-matching';
import { Dictation } from '@/components/exercises/dictation';
import { ShortWriting } from '@/components/exercises/short-writing';

// --- Mock Data ---
const AUDIT_EXERCISES = [
    {
        type: 'mcq',
        name: 'Multiple Choice Question',
        question: {
            id: 'mcq-1',
            text: 'Welche Stadt ist die Hauptstadt von Deutschland?',
            options: ['München', 'Berlin', 'Hamburg', 'Frankfurt'],
            correct_answer: 'Berlin'
        }
    },
    {
        type: 'true-false-ng',
        name: 'True / False / Not Given',
        question: {
            id: 'tf-1',
            text: 'Der Berliner Fernsehturm ist das höchste Bauwerk in Deutschland.',
            correct_answer: 'True'
        }
    },
    {
        type: 'gap-fill',
        name: 'Gap Fill',
        question: {
            id: 'gf-1',
            text: 'Ich gehe heute [blank] Kino.',
            correct_answer: 'ins'
        }
    },
    {
        type: 'matching',
        name: 'Matching',
        question: {
            id: 'match-1',
            text: 'Associez les pays à leurs capitales :',
            pairs: [
                { left: 'Allemagne', right: 'Berlin' },
                { left: 'France', right: 'Paris' },
                { left: 'Italie', right: 'Rome' }
            ],
            correct_answer: { 'Allemagne': 'Berlin', 'France': 'Paris', 'Italie': 'Rome' }
        }
    },
    {
        type: 'essay-editor',
        name: 'Essay Editor',
        question: {
            id: 'essay-1',
            prompt: 'Schreiben Sie einen Aufsatz über die Vor- und Nachteile der Digitalisierung in der Schule.',
            min_words: 100
        }
    },
    {
        type: 'form-completion',
        name: 'Form Completion',
        question: {
            id: 'form-1',
            title: 'Anmeldeformular',
            fields: [
                { label: 'Name', blank: false, answer: 'Schmidt' },
                { label: 'Vorname', blank: true, type: 'text' },
                { label: 'Geburtsdatum', blank: true, type: 'date' },
                { label: 'Kurs', blank: true, type: 'select', options: ['Deutsch A1', 'Deutsch B1', 'Deutsch C1'] }
            ]
        }
    },
    {
        type: 'integrated-task',
        name: 'Integrated Task (Reading & Writing)',
        question: {
            id: 'int-1',
            reading_passage: { title: 'Studieren in Deutschland', content: 'In Deutschland gibt es viele Universitäten...' },
            writing_prompt: 'Fassen Sie den Text zusammen.',
            response_type: 'writing',
            min_words: 50
        }
    },
    {
        type: 'diagram-labeling',
        name: 'Diagram Labeling',
        question: {
            id: 'diag-1',
            text: 'Beschriften Sie das Diagramm.',
            image_url: 'https://placehold.co/600x400?text=Diagram+Map',
            labels: [
                { id: 'L1', x: 20, y: 30 },
                { id: 'L2', x: 70, y: 50 }
            ]
        }
    },
    {
        type: 'speaking-recorder',
        name: 'Speaking Recorder',
        question: {
            id: 'speak-1',
            text: 'Erzählen Sie uns etwas über Ihre Heimatstadt.',
            prep_time: 15,
            speak_time: 45
        }
    },
    {
        type: 'gapped-text',
        name: 'Gapped Text (Paragraph Placement)',
        question: {
            id: 'gt-1',
            passage_with_gaps: [
                'Berlin ist eine faszinierende Stadt.',
                null,
                'Es gibt viele Museen und Parks.'
            ],
            removed_paragraphs: ['Die Geschichte der Stadt ist überall spürbar.'],
            gap_count: 1
        }
    },
    {
        type: 'graph-description',
        name: 'Graph Description',
        question: {
            id: 'graph-1',
            text: 'Beschreiben Sie den Trend.',
            chart_data: {
                type: 'line',
                labels: ['Jan', 'Feb', 'Mar', 'Apr'],
                datasets: [{ label: 'Export', data: [10, 25, 15, 40] }]
            }
        }
    },
    {
        type: 'short-answer',
        name: 'Short Answer',
        question: {
            id: 'short-1',
            text: 'Wie heißt der aktuelle Bundeskanzler?',
            correct_answer: 'Olaf Scholz'
        }
    },
    {
        type: 'sentence-completion',
        name: 'Sentence Completion',
        question: {
            id: 'sent-1',
            text: 'Berlin ist die Hauptstadt [blank].',
            correct_answer: 'von Deutschland'
        }
    },
    {
        type: 'note-completion',
        name: 'Note Completion',
        question: {
            id: 'note-1',
            text: 'Berlin Info',
            notes: [
                { label: 'Bevölkerung', value: '[blank]' },
                { label: 'Fläche', value: '892 km²' }
            ],
            correct_answer: ['3.7 Millionen']
        }
    },
    {
        type: 'ordering',
        name: 'Ordering / Text Sequencing',
        question: {
            id: 'ord-1',
            items: ['Ich stehe auf.', 'Ich frühstücke.', 'Ich gehe zur Arbeit.'],
            correct_answer: ['Ich stehe auf.', 'Ich frühstücke.', 'Ich gehe zur Arbeit.']
        }
    },
    {
        type: 'role-play',
        name: 'AI Role Play (Beta)',
        question: {
            id: 'role-1',
            scenario: 'Im Restaurant: Reservieren Sie einen Tisch.',
            ai_role: 'Kellner',
            user_goal: 'Tisch für 2 Personen um 19 Uhr.'
        }
    },
    {
        type: 'academic-discussion',
        name: 'Academic Discussion',
        question: {
            id: 'acad-1',
            prompt: 'Discuss the impact of AI on Higher Education.',
            min_words: 100
        }
    },
    {
        type: 'dictation',
        name: 'Dictation',
        question: {
            id: 'dict-1',
            audio_text: 'Das Wetter in Berlin ist heute sehr schön.',
            correct_answer: 'Das Wetter in Berlin ist heute sehr schön.'
        }
    },
    {
        type: 'word-formation',
        name: 'Word Formation',
        question: {
            id: 'wf-1',
            text: 'Die [blank] (BEAUTY) der Stadt ist beeindruckend.',
            correct_answer: 'Schönheit'
        }
    },
    {
        type: 'open-cloze',
        name: 'Open Cloze (No Options)',
        question: {
            id: 'oc-1',
            text: 'Berlin ist [blank] Hauptstadt von Deutschland.',
            correct_answer: 'die'
        }
    },
    {
        type: 'key-word-transformation',
        name: 'Key Word Transformation',
        question: {
            id: 'kwt-1',
            original: 'I haven\'t seen him for ages.',
            key: 'TIME',
            transformed: 'It\'s been a [blank] saw him.',
            correct_answer: 'long time since I last'
        }
    },
    {
        type: 'multiple-matching',
        name: 'Multiple Matching',
        question: {
            id: 'mult-1',
            texts: [
                { id: 'A', title: 'Museum Island', content: 'A unique collection of museums.' },
                { id: 'B', title: 'Tiergarten', content: 'A huge park in the center.' }
            ],
            questions: [
                { id: 'q1', text: 'Where can you see art?', correct_answer: 'A' },
                { id: 'q2', text: 'Where can you walk?', correct_answer: 'B' }
            ]
        }
    },
    {
        type: 'synthesis',
        name: 'Synthesis / Summary task',
        question: {
            id: 'synth-1',
            texts: ['Berlin is old.', 'Berlin is new.'],
            prompt: 'Summarize both points.',
            min_words: 30
        }
    }
];

const COMPONENTS: Record<string, any> = {
    'mcq': Mcq,
    'true-false-ng': TrueFalseNg,
    'gap-fill': GapFill,
    'matching': Matching,
    'essay-editor': EssayEditor,
    'form-completion': FormCompletion,
    'integrated-task': IntegratedTask,
    'diagram-labeling': DiagramLabeling,
    'speaking-recorder': SpeakingRecorder,
    'gapped-text': GappedText,
    'graph-description': GraphDescription,
    'short-answer': ShortAnswer,
    'sentence-completion': SentenceCompletion,
    'note-completion': NoteCompletion,
    'ordering': Ordering,
    'role-play': RolePlay,
    'academic-discussion': AcademicDiscussion,
    'dictation': Dictation,
    'word-formation': WordFormation,
    'open-cloze': OpenCloze,
    'key-word-transformation': KeyWordTransformation,
    'multiple-matching': MultipleMatching,
    'synthesis': Synthesis,
    'table-completion': TableCompletion,
    'flow-chart-completion': FlowChartCompletion,
    'summary-completion': SummaryCompletion,
    'short-writing': ShortWriting
};

export default function AuditPage() {
    const [index, setIndex] = useState(0);
    const [answers, setAnswers] = useState<Record<string, any>>({});
    const [isChecked, setIsChecked] = useState(false);
    const [isCorrect, setIsCorrect] = useState<boolean | null>(null);
    const [showInspector, setShowInspector] = useState(false);
    const [isChatOpen, setIsChatOpen] = useState(false);
    const [isFinished, setIsFinished] = useState(false);
    const [results, setResults] = useState<Record<number, boolean>>({});

    const audit = AUDIT_EXERCISES[index];
    const Component = COMPONENTS[audit?.type] || Mcq;

    const handleAnswer = (qid: string, val: any) => {
        if (isChecked) return;
        setAnswers(prev => ({ ...prev, [qid]: val }));
    };

    const checkAnswer = () => {
        if (!audit || isChecked) return;
        
        const userValue = answers[audit.question.id];
        const correctValue = audit.question.correct_answer;
        
        let correct = false;
        if (typeof correctValue === 'object') {
            correct = JSON.stringify(userValue) === JSON.stringify(correctValue);
        } else {
            correct = String(userValue || '').trim().toLowerCase() === String(correctValue || '').trim().toLowerCase();
        }
        
        setIsCorrect(correct);
        setIsChecked(true);
        setResults(prev => ({ ...prev, [index]: correct }));
    };

    const next = () => {
        if (index < AUDIT_EXERCISES.length - 1) {
            setIndex(index + 1);
            setIsChecked(false);
            setIsCorrect(null);
        } else {
            setIsFinished(true);
        }
    };

    const prev = () => {
        if (index > 0) {
            setIndex(index - 1);
            setIsChecked(false);
            setIsCorrect(null);
        }
    };

    if (isFinished) {
        const correctCount = Object.values(results).filter(Boolean).length;
        return (
            <AppLayout>
                <div className="mx-auto max-w-2xl px-4 py-16 text-center">
                    <div className="mb-8 flex justify-center">
                        <div className="h-24 w-24 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">
                           <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/></svg>
                        </div>
                    </div>
                    <h1 className="text-4xl font-black text-slate-900 mb-4 tracking-tight">Audit Terminé !</h1>
                    <p className="text-slate-500 mb-10">Vous avez validé les composants de base avec un score de {correctCount} / {AUDIT_EXERCISES.length}.</p>
                    
                    <div className="grid grid-cols-1 gap-3 mb-10">
                        {AUDIT_EXERCISES.map((ex, i) => (
                            <div key={i} className={`flex items-center justify-between p-4 rounded-2xl border ${results[i] ? 'bg-emerald-50 border-emerald-100' : 'bg-red-50 border-red-100'}`}>
                                <span className="font-bold text-sm">{ex.name}</span>
                                <span className={`text-xs font-black uppercase tracking-widest ${results[i] ? 'text-emerald-600' : 'text-red-600'}`}>
                                    {results[i] ? 'Correct' : 'Incorrect'}
                                </span>
                            </div>
                        ))}
                    </div>

                    <button 
                        onClick={() => window.location.reload()}
                        className="px-8 py-4 bg-indigo-600 text-white rounded-2xl font-black uppercase tracking-widest shadow-xl shadow-indigo-600/20"
                    >
                        Recommencer l'Audit
                    </button>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout>
            <Head title={`Audit Mode: ${audit?.name}`} />
            
            <div className="mx-auto max-w-4xl px-4 py-8">
                {/* Progress Header */}
                <div className="mb-8 flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-black text-slate-800">Audit Pédagogique Interactif</h1>
                        <p className="text-sm font-medium text-slate-500">
                            {index + 1} / {AUDIT_EXERCISES.length} — <span className="text-indigo-600 font-bold">{audit?.name}</span>
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <button onClick={prev} disabled={index === 0} className="p-2 rounded-xl border bg-white hover:bg-slate-50 disabled:opacity-20"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M15 18l-6-6 6-6" /></svg></button>
                        <button onClick={next} className="p-2 rounded-xl bg-slate-800 text-white hover:bg-slate-700"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M9 18l6-6-6-6" /></svg></button>
                    </div>
                </div>

                {/* Main Card */}
                <div className={`relative overflow-hidden rounded-[40px] border-4 transition-all duration-300 ${
                    isChecked 
                        ? isCorrect ? 'border-emerald-500/20 bg-emerald-50/10' : 'border-red-500/20 bg-red-50/10' 
                        : 'border-slate-100 bg-white'
                } p-10 shadow-3xl shadow-slate-200/50`}>
                    
                    <div className="mb-8 flex items-center justify-between border-b pb-6 dark:border-slate-800">
                        <h2 className="text-2xl font-black text-slate-800 tracking-tight">{audit?.name}</h2>
                        <button 
                            onClick={() => setShowInspector(!showInspector)}
                            className={`px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all ${showInspector ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-500 hover:bg-slate-200'}`}
                        >
                            Inspecteur
                        </button>
                    </div>

                    <div className="min-h-[300px]">
                        <Component 
                            key={audit?.type}
                            question={audit?.question}
                            onAnswer={handleAnswer}
                            selectedAnswer={answers[audit?.question?.id]}
                            disabled={isChecked}
                            isChecked={isChecked}
                            isCorrect={isCorrect}
                        />
                    </div>

                    {/* Simulation Pedagogical Footer */}
                    {isChecked && (
                        <div className="mt-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
                            <div className={`rounded-3xl border-2 p-8 ${isCorrect ? 'border-emerald-100 bg-emerald-50/50' : 'border-red-100 bg-red-50/50'}`}>
                                <div className="flex items-start justify-between gap-6">
                                    <div className="space-y-4 flex-1">
                                        <div className="flex items-center gap-3">
                                            <div className={`p-2 rounded-xl text-white ${isCorrect ? 'bg-emerald-600' : 'bg-red-600'}`}>
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M12 2v20M2 12h20" /></svg>
                                            </div>
                                            <span className={`text-xs font-black uppercase tracking-widest ${isCorrect ? 'text-emerald-700' : 'text-red-700'}`}>
                                                {isCorrect ? 'Excellent Travail !' : 'Explication Pédagogique'}
                                            </span>
                                        </div>
                                        <p className={`text-[15px] font-medium leading-relaxed ${isCorrect ? 'text-emerald-900' : 'text-red-900'}`}>
                                            {isCorrect 
                                                ? `Vous avez parfaitement identifié la réponse : ${audit.question.correct_answer}.` 
                                                : `Attention ! La réponse correcte était "${audit.question.correct_answer}". Voulez-vous approfondir pour ne plus refaire cette erreur ?`}
                                        </p>
                                    </div>
                                    {!isCorrect && (
                                        <button 
                                            onClick={() => setIsChatOpen(true)}
                                            className="px-6 py-3 bg-red-600 text-white rounded-2xl font-black uppercase tracking-widest text-[11px] hover:scale-105 active:scale-95 transition-all shadow-lg shadow-red-600/20"
                                        >
                                            Discuter avec l'IA
                                        </button>
                                    )}
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                {/* Audit Actions */}
                <div className="mt-8 flex items-center justify-between gap-4">
                    <button 
                        onClick={isChecked ? () => setIsChecked(false) : checkAnswer}
                        className={`flex-1 rounded-[24px] py-5 text-sm font-black uppercase tracking-widest shadow-xl transition-all active:scale-95 ${
                            isChecked 
                            ? 'bg-slate-200 text-slate-600' 
                            : 'bg-indigo-600 text-white shadow-indigo-600/30 hover:scale-[1.01]'
                        }`}
                    >
                        {isChecked ? 'Réinitialiser' : 'Vérifier la réponse'}
                    </button>
                    <button 
                        onClick={next}
                        className="rounded-[24px] bg-slate-900 px-12 py-5 text-sm font-black uppercase tracking-widest text-white shadow-xl shadow-slate-900/30 transition-all hover:scale-[1.01] active:scale-95"
                    >
                        {index === AUDIT_EXERCISES.length - 1 ? 'Terminer' : 'Suivant'}
                    </button>
                </div>

                <AiChatFeedback 
                    isOpen={isChatOpen}
                    onClose={() => setIsChatOpen(false)}
                    context={{
                        prompt: audit.question.text || audit.question.prompt || '',
                        user_answer: String(answers[audit.question.id] || ''),
                        correct_answer: String(audit.question.correct_answer || ''),
                        language: 'German' // Default for audit
                    }}
                />

                {/* Inspector Panel */}
                {showInspector && (
                    <div className="mt-12 overflow-hidden rounded-[32px] border border-slate-900 bg-slate-950 p-10 text-indigo-300 font-mono text-xs shadow-3xl">
                        <div className="flex items-center justify-between mb-8">
                             <div className="flex items-center gap-3">
                                <div className="p-1.5 bg-indigo-500 rounded text-white shadow-lg shadow-indigo-500/20"><svg width="14" height="14" fill="none" stroke="currentColor" strokeWidth="3"><path d="M12 2v20M2 12h20" /></svg></div>
                                <span className="text-[11px] font-black uppercase tracking-widest text-white">Audit Inspector (Real-time Payload)</span>
                             </div>
                        </div>
                        <div className="grid grid-cols-2 gap-10">
                            <div className="space-y-4">
                                <span className="text-indigo-400 uppercase font-black text-[10px] tracking-widest">Mock Correct Data</span>
                                <pre className="bg-slate-900 p-6 rounded-2xl border border-white/5 max-h-[300px] overflow-y-auto shadow-inner">
                                    {JSON.stringify(audit?.question, null, 2)}
                                </pre>
                            </div>
                            <div className="space-y-4">
                                <span className="text-emerald-400 uppercase font-black text-[10px] tracking-widest">User Input State</span>
                                <pre className="bg-slate-900 p-6 rounded-2xl border border-white/5 max-h-[300px] overflow-y-auto shadow-inner">
                                    {JSON.stringify(answers[audit?.question?.id] || "No answer yet", null, 2)}
                                </pre>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
