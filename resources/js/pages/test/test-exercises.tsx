import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { Mcq } from '@/components/exercises/mcq';
import { TrueFalseNg } from '@/components/exercises/true-false-ng';
import { GapFill } from '@/components/exercises/gap-fill';
import { Matching } from '@/components/exercises/matching';
import { EssayEditor } from '@/components/exercises/essay-editor';
import { NoteCompletion } from '@/components/exercises/note-completion';
import { Ordering } from '@/components/exercises/ordering';
import { ShortAnswer } from '@/components/exercises/short-answer';
import { SentenceCompletion } from '@/components/exercises/sentence-completion';
import { FormCompletion } from '@/components/exercises/form-completion';
import { TableCompletion } from '@/components/exercises/table-completion';
import { WordFormation } from '@/components/exercises/word-formation';
import { FlowChartCompletion } from '@/components/exercises/flow-chart-completion';
import { SummaryCompletion } from '@/components/exercises/summary-completion';
import { DiagramLabeling } from '@/components/exercises/diagram-labeling';
import { IntegratedTask } from '@/components/exercises/integrated-task';
import { Synthesis } from '@/components/exercises/synthesis';
import { AcademicDiscussion } from '@/components/exercises/academic-discussion';

// Mock data generator for different exercise types
const MOCK_DATA: Record<string, any> = {
    'mcq': {
        question: {
            id: 'q1',
            text: 'Quelle est la capitale de l\'Allemagne ?',
            options: ['Berlin', 'Munich', 'Hambourg', 'Francfort'],
            correct_answer: 'Berlin'
        }
    },
    'true-false-ng': {
        question: {
            id: 'q1',
            text: 'Le Mur de Berlin est tombé en 1989.',
            correct_answer: 'True'
        }
    },
    'gap-fill': {
        question: {
            id: 'q1',
            text: 'Le chancelier actuel de l\'Allemagne est [blank].',
            correct_answer: 'Olaf Scholz'
        }
    },
    'matching': {
        question: {
            id: 'q1',
            text: 'Associez les villes à leurs Länder :',
            pairs: [
                { left: 'Munich', right: 'Bavière' },
                { left: 'Stuttgart', right: 'Bade-Wurtemberg' },
                { left: 'Dresde', right: 'Saxe' }
            ]
        }
    },
    'essay-editor': {
        question: {
            id: 'q1',
            prompt: 'Décrivez l\'importance de l\'Union Européenne pour l\'économie allemande.',
            min_words: 150
        }
    },
    'note-completion': {
        question: {
            id: 'q1',
            text: 'History of Berlin',
            notes: [
                { label: 'Founded in', value: '[blank]' },
                { label: 'Population', value: '[blank] million' }
            ],
            correct_answer: ['1237', '3.7']
        }
    },
    'short-answer': {
        question: {
            id: 'q1',
            text: 'Who is the president of Germany?',
            correct_answer: 'Frank-Walter Steinmeier'
        }
    },
    'sentence-completion': {
        question: {
            id: 'q1',
            text: 'Berlin is the largest city in [blank].',
            correct_answer: 'Germany'
        }
    },
    'ordering': {
        question: {
            id: 'q1',
            items: ['Morning', 'Afternoon', 'Evening', 'Night'],
            correct_answer: ['Morning', 'Afternoon', 'Evening', 'Night']
        }
    }
};

const COMPONENTS: Record<string, any> = {
    'mcq': Mcq,
    'true-false-ng': TrueFalseNg,
    'gap-fill': GapFill,
    'matching': Matching,
    'essay-editor': EssayEditor,
    'note-completion': NoteCompletion,
    'short-answer': ShortAnswer,
    'sentence-completion': SentenceCompletion,
    'ordering': Ordering,
    'form-completion': FormCompletion,
    'table-completion': TableCompletion,
    'word-formation': WordFormation,
    'flow-chart-completion': FlowChartCompletion,
    'summary-completion': SummaryCompletion,
    'diagram-labeling': DiagramLabeling,
    'integrated-task': IntegratedTask,
    'synthesis': Synthesis,
    'academic-discussion': AcademicDiscussion,
};

export default function TestExercises() {
    const [selectedType, setSelectedType] = useState('mcq');
    const [answers, setAnswers] = useState<Record<string, any>>({});
    const [isChecked, setIsChecked] = useState(false);

    const Component = COMPONENTS[selectedType] || Mcq;
    const mock = MOCK_DATA[selectedType] || MOCK_DATA['mcq'];

    return (
        <AppLayout>
            <Head title="Audit de Composants" />
            
            <div className="flex h-[calc(100vh-64px)] overflow-hidden">
                {/* Sidebar */}
                <div className="w-64 border-r bg-slate-50 p-4 overflow-y-auto">
                    <h3 className="text-xs font-black uppercase tracking-widest text-slate-400 mb-4 px-2">Composants</h3>
                    <div className="space-y-1">
                        {Object.keys(COMPONENTS).map(type => (
                            <button
                                key={type}
                                onClick={() => {
                                    setSelectedType(type);
                                    setAnswers({});
                                    setIsChecked(false);
                                }}
                                className={`w-full text-left px-3 py-2 rounded-lg text-sm font-bold transition-all ${
                                    selectedType === type 
                                    ? 'bg-indigo-600 text-white shadow-md' 
                                    : 'text-slate-600 hover:bg-slate-200'
                                }`}
                            >
                                {type.replace(/-/g, ' ')}
                            </button>
                        ))}
                    </div>
                </div>

                {/* Preview Area */}
                <div className="flex-1 overflow-y-auto bg-slate-100 p-8">
                    <div className="mx-auto max-w-2xl">
                        <div className="mb-6 flex items-center justify-between">
                            <div>
                                <h1 className="text-2xl font-black text-slate-800 capitalize">
                                    {selectedType.replace(/-/g, ' ')}
                                </h1>
                                <p className="text-sm text-slate-500 font-medium">Test de rendu et d'interaction</p>
                            </div>
                            <div className="flex gap-2">
                                <button 
                                    onClick={() => setIsChecked(!isChecked)}
                                    className="px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm font-bold shadow-sm hover:bg-slate-50"
                                >
                                    {isChecked ? 'Réinitialiser' : 'Vérifier'}
                                </button>
                            </div>
                        </div>

                        <div className="bg-white border border-slate-200 rounded-3xl p-8 shadow-xl shadow-slate-200/50">
                            <Component
                                question={mock.question}
                                onAnswer={(id: string, val: any) => setAnswers(prev => ({ ...prev, [id]: val }))}
                                answers={answers}
                                isChecked={isChecked}
                                isCorrect={isChecked ? true : null}
                            />
                        </div>

                        {/* Data inspector */}
                        <div className="mt-12 bg-slate-900 rounded-2xl p-6 text-indigo-300 font-mono text-xs overflow-x-auto">
                            <h4 className="text-white mb-4 font-sans font-bold">État du composant</h4>
                            <pre>{JSON.stringify({ 
                                type: selectedType,
                                answers,
                                isChecked
                            }, null, 2)}</pre>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
