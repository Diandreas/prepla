import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { useState } from 'react';

// Import all components
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
import { ShortWriting } from '@/components/exercises/short-writing';
import { FormCompletion } from '@/components/exercises/form-completion';
import { SummaryCompletion } from '@/components/exercises/summary-completion';
import { TableCompletion } from '@/components/exercises/table-completion';
import { FlowChartCompletion } from '@/components/exercises/flow-chart-completion';
import { MultipleMatching } from '@/components/exercises/multiple-matching';
import { InsertText } from '@/components/exercises/insert-text';
import { GappedText } from '@/components/exercises/gapped-text';
import { GraphDescription } from '@/components/exercises/graph-description';
import { AcademicDiscussion } from '@/components/exercises/academic-discussion';
import { SpeakingRecorder } from '@/components/exercises/speaking-recorder';
import { RolePlay } from '@/components/exercises/role-play';
import { DiagramLabeling } from '@/components/exercises/diagram-labeling';
import { Synthesis } from '@/components/exercises/synthesis';
import { IntegratedTask } from '@/components/exercises/integrated-task';
import { VocabularyCard } from '@/components/exercises/vocabulary-card';

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
    'short-writing': ShortWriting,
    'form-completion': FormCompletion,
    'summary-completion': SummaryCompletion,
    'table-completion': TableCompletion,
    'flow-chart-completion': FlowChartCompletion,
    'multiple-matching': MultipleMatching,
    'insert-text': InsertText,
    'gapped-text': GappedText,
    'graph-description': GraphDescription,
    'academic-discussion': AcademicDiscussion,
    'speaking-recorder': SpeakingRecorder,
    'role-play': RolePlay,
    'diagram-labeling': DiagramLabeling,
    'synthesis': Synthesis,
    'integrated-task': IntegratedTask,
    'vocabulary-card': VocabularyCard,
};

// Dummy Questions Factory
const getDummyQuestion = (key: string) => {
    switch (key) {
        case 'speaking-recorder':
            return {
                id: 'q1',
                text: 'Present yourself to the audience for 1 minute.',
            };
        case 'mcq':
            return {
                id: 'q1',
                text: 'Which of the following is correct?',
                options: ['Option A', 'Option B', 'Option C', 'Option D'],
                correct_answer: 'Option B'
            };
        case 'true-false-ng':
            return {
                id: 'q1',
                text: 'The sky is heavily blue today.',
            };
        case 'gap-fill':
            return {
                id: 'q1',
                text: 'The main goal of this exercise is to {gap} the correct word.',
            };
        case 'matching':
            return {
                id: 'q1',
                pairs: [
                    { left: 'Dog', right: 'Animal' },
                    { left: 'Car', right: 'Vehicle' }
                ]
            };
        case 'essay-editor':
            return {
                id: 'q1',
                text: 'Write an essay about AI.',
            };
        case 'table-completion':
            return {
                id: 'q1',
                text: 'Complete the table below.',
                table_headers: ['Name', 'Age'],
                table_rows: [
                    ['Alice', '{gap}'],
                    ['{gap}', '30']
                ]
            };
        default:
            return {
                id: 'q1',
                text: `This is a generic prompt for ${key}. Please interact with the component.`,
                options: ['A', 'B', 'C']
            };
    }
};

export default function Sandbox() {
    const [selectedComponent, setSelectedComponent] = useState('speaking-recorder');
    const [answers, setAnswers] = useState<Record<string, any>>({});

    const Component = componentMap[selectedComponent] || Mcq;
    const dummyQuestion = getDummyQuestion(selectedComponent);

    const handleAnswer = (qid: string, ans: any) => {
        setAnswers(prev => ({ ...prev, [qid]: ans }));
    };

    return (
        <AppLayout>
            <Head title="Test Sandbox" />
            <div className="mx-auto max-w-5xl px-4 py-8 relative">
                <div className="mb-6 flex items-center justify-between">
                    <h1 className="text-3xl font-black text-indigo-900 tracking-tight">Component Sandbox</h1>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <div className="md:col-span-1 rounded-2xl border bg-white shadow flex flex-col min-h-[500px]">
                        <div className="p-4 border-b font-bold text-gray-700 bg-gray-50 rounded-t-2xl">
                            Select Component
                        </div>
                        <div className="overflow-y-auto w-full p-2" style={{ maxHeight: 'calc(100vh - 250px)' }}>
                            <div className="flex flex-col gap-1 w-full">
                                {Object.keys(componentMap).map(key => (
                                    <button
                                        key={key}
                                        onClick={() => {
                                            setSelectedComponent(key);
                                            setAnswers({});
                                        }}
                                        className={`px-3 py-2 text-left rounded-lg text-sm transition-colors ${selectedComponent === key ? 'bg-indigo-600 text-white font-bold' : 'hover:bg-gray-100 text-gray-700'}`}
                                    >
                                        {key}
                                    </button>
                                ))}
                            </div>
                        </div>
                    </div>

                    <div className="md:col-span-3">
                        <div className="rounded-2xl border bg-white p-6 shadow min-h-[400px]">
                            <h2 className="text-xl font-bold mb-6 pb-2 border-b uppercase tracking-widest text-gray-400 text-sm">Preview: {selectedComponent}</h2>
                            <div className="player-card" style={{ padding: '24px', border: '1px solid #e2e8f0', borderRadius: '16px', background: '#f8fafc' }}>
                                <Component 
                                    key={selectedComponent}
                                    question={dummyQuestion}
                                    onAnswer={handleAnswer}
                                    selectedAnswer={answers[dummyQuestion.id]}
                                    disabled={false}
                                />
                            </div>

                            <div className="mt-8 p-4 bg-gray-900 text-green-400 rounded-xl font-mono text-sm overflow-x-auto shadow-inner">
                                <div className="text-gray-500 mb-2 border-b border-gray-800 pb-1">Current State Result:</div>
                                <pre>{JSON.stringify(answers, null, 2)}</pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
