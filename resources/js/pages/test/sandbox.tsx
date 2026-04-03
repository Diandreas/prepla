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
            return { id: 'q1', text: 'Present yourself to the audience for 1 minute.' };
        case 'mcq':
            return { id: 'q1', text: 'Which of the following is correct?', options: ['Option A', 'Option B', 'Option C', 'Option D'], correct_answer: 'Option B' };
        case 'true-false-ng':
            return { id: 'q1', text: 'The sky is heavily blue today.', options: ['True', 'False', 'Not Given'] };
        case 'gap-fill':
            return { id: 'q1', text: 'The main goal of this exercise is to {gap} the correct word.', options: ['find', 'lose', 'ignore'] };
        case 'matching':
            return { id: 'q1', pairs: [{ left: 'Dog', right: 'Animal' }, { left: 'Car', right: 'Vehicle' }] };
        case 'essay-editor':
            return { id: 'q1', text: 'Write an essay about AI.', min_words: 100 };
        case 'table-completion':
            return {
                id: 'q1', title: 'User Information', headers: ['Name', 'Age'], 
                rows: [{ cells: [{value: "Alice", blank: false}, {value: "", blank: true}] }, { cells: [{value: "", blank: true}, {value: "30", blank: false}] }]
            };
        case 'flow-chart-completion':
            return { id: 'q1', title: 'Process', steps: [{text: 'Start', blank: false}, {text: '', blank: true}, {text: 'End', blank: false}] };
        case 'insert-text':
            return { id: 'q1', passage: 'This is the first sentence. [A] This is the second. [B] This is the third. [C]', sentence_to_insert: 'This is an inserted sentence.' };
        case 'multiple-matching':
            return { id: 'q1', texts: [{id: 'A', title: 'Text A', content: 'Lorem ipsum A.'}, {id: 'B', title: 'Text B', content: 'Lorem ipsum B.'}], statements: [{id: 's1', text: 'Statement 1'}, {id: 's2', text: 'Statement 2'}] };
        case 'gapped-text':
            return { id: 'q1', passage_with_gaps: ['First part.', null, 'Second part.', null], removed_paragraphs: ['Missing 1', 'Missing 2'], gap_count: 2 };
        case 'diagram-labeling':
            return { id: 'q1', text: 'Label this diagram', image_url: 'https://via.placeholder.com/400x200', labels: [{id: 'l1', x: 20, y: 30}, {id: 'l2', x: 80, y: 60}] };
        case 'synthesis':
            return { id: 'q1', documents: [{title: 'Doc 1', content: 'Text 1'}], sources: [{title: 'Doc 1', content: 'Text 1'}], writing_prompt: 'Synthesize the docs' };
        case 'academic-discussion':
            return { id: 'q1', professor_prompt: 'Discuss AI ethics.', student_posts: [{name: 'Bob', text: 'AI is good.'}, {name: 'Alice', text: 'AI is dangerous.'}], writing_prompt: 'What do you think?' };
        case 'summary-completion':
            return { id: 'q1', summary_text: 'The sky is ___ and the grass is ___.', word_list: ['blue', 'green', 'red'], gap_count: 2 };
        case 'role-play':
            return { id: 'q1', scenario: 'You are at a restaurant.', role: 'Customer', dialogue_turns: [{speaker: 'examiner', text: 'Welcome!'}, {speaker: 'candidate', prompt: 'Ask for the menu'}] };
        case 'form-completion':
            return { id: 'q1', title: 'Application Form', fields: [{label: 'Name', blank: true}, {label: 'Date', blank: false, answer: '2025-01-01'}] };
        case 'note-completion':
            return { id: 'q1', title: 'Meeting Notes', notes: [{label: 'Date', blank: false}, {label: 'Action item', blank: true}] };
        case 'ordering':
            return { id: 'q1', text: 'Order these events:', items: ['First', 'Second', 'Third'] };
        case 'short-writing':
        case 'short-answer':
        case 'dictation':
            return { id: 'q1', text: `Interact with ${key}` };
        case 'open-cloze':
        case 'word-formation':
            return { id: 'q1', text: 'The cat is sleeping on the {gap}.', options: ['mat'] };
        case 'key-word-transformation':
            return { id: 'q1', text: 'Transform sentence.', lead_in: 'He is tall.', gap_before: 'He is ', gap_after: ' than her.', keyword: 'TALLER' };
        case 'graph-description':
            return { id: 'q1', text: 'Describe the graph', image_url: 'https://via.placeholder.com/400x200', prompt: 'Write 150 words.' };
        case 'integrated-task':
            return { id: 'q1', reading_text: 'Read this.', audio_url: 'https://example.com/audio.mp3', writing_prompt: 'Write about the relationship.' };
        case 'vocabulary-card':
            return { id: 'q1', term: 'Hello', translation: 'Bonjour', part_of_speech: 'Greeting', example_sentence: 'Hello world.' };
        default:
            return {
                id: 'q1', text: `Generic prompt for ${key}.`, options: ['A', 'B', 'C']
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
