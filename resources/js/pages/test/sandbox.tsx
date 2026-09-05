import { AcademicDiscussion } from '@/components/exercises/academic-discussion';
import { BuildASentence } from '@/components/exercises/build-a-sentence';
import { CompleteTheWords } from '@/components/exercises/complete-the-words';
import { DiagramLabeling } from '@/components/exercises/diagram-labeling';
import { Dictation } from '@/components/exercises/dictation';
import { EssayEditor } from '@/components/exercises/essay-editor';
import { ExerciseErrorBoundary } from '@/components/exercises/exercise-error-boundary';
import { FlowChartCompletion } from '@/components/exercises/flow-chart-completion';
import { FormCompletion } from '@/components/exercises/form-completion';
import { GapFill } from '@/components/exercises/gap-fill';
import { GappedText } from '@/components/exercises/gapped-text';
import { GraphDescription } from '@/components/exercises/graph-description';
import { GuidedWriting } from '@/components/exercises/guided-writing';
import { InsertText } from '@/components/exercises/insert-text';
import { IntegratedTask } from '@/components/exercises/integrated-task';
import { KeyWordTransformation } from '@/components/exercises/key-word-transformation';
import { ListenChooseResponse } from '@/components/exercises/listen-choose-response';
import { ListenRepeat } from '@/components/exercises/listen-repeat';
import { Matching } from '@/components/exercises/matching';
import { Mcq } from '@/components/exercises/mcq';
import { MultipleMatching } from '@/components/exercises/multiple-matching';
import { NoteCompletion } from '@/components/exercises/note-completion';
import { OpenCloze } from '@/components/exercises/open-cloze';
import { Ordering } from '@/components/exercises/ordering';
import { PictureMcq } from '@/components/exercises/picture-mcq';
import { RolePlay } from '@/components/exercises/role-play';
import { SentenceCompletion } from '@/components/exercises/sentence-completion';
import { ShortAnswer } from '@/components/exercises/short-answer';
import { ShortWriting } from '@/components/exercises/short-writing';
import { SpeakingRecorder } from '@/components/exercises/speaking-recorder';
import { SummaryCompletion } from '@/components/exercises/summary-completion';
import { Synthesis } from '@/components/exercises/synthesis';
import { TableCompletion } from '@/components/exercises/table-completion';
import { TrueFalseNg } from '@/components/exercises/true-false-ng';
import { VocabularyCard } from '@/components/exercises/vocabulary-card';
import { WordFormation } from '@/components/exercises/word-formation';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import type { ElementType } from 'react';
import { useMemo, useState } from 'react';

type CatalogEntry = {
    key: string;
    label: string;
    family: 'Choix' | 'Saisie' | 'Organisation' | 'Écrit IA' | 'Oral & écoute' | 'Vocabulaire' | 'Legacy';
    Component: ElementType;
};

type SampleQuestion = { id: string } & Record<string, unknown>;

const CATALOG: CatalogEntry[] = [
    { key: 'mcq', label: 'QCM', family: 'Choix', Component: Mcq },
    { key: 'true-false-ng', label: 'Vrai / Faux / Non mentionné', family: 'Choix', Component: TrueFalseNg },
    { key: 'sentence-completion', label: 'Compléter la phrase', family: 'Choix', Component: SentenceCompletion },
    { key: 'picture-mcq', label: 'Choisir une image', family: 'Choix', Component: PictureMcq },
    { key: 'gap-fill', label: 'Texte à trous', family: 'Saisie', Component: GapFill },
    { key: 'short-answer', label: 'Réponse courte', family: 'Saisie', Component: ShortAnswer },
    { key: 'dictation', label: 'Dictée', family: 'Saisie', Component: Dictation },
    { key: 'open-cloze', label: 'Texte à trous numérotés', family: 'Saisie', Component: OpenCloze },
    { key: 'word-formation', label: 'Formation de mots', family: 'Saisie', Component: WordFormation },
    { key: 'key-word-transformation', label: 'Transformation', family: 'Saisie', Component: KeyWordTransformation },
    { key: 'note-completion', label: 'Compléter des notes', family: 'Saisie', Component: NoteCompletion },
    { key: 'form-completion', label: 'Compléter un formulaire', family: 'Saisie', Component: FormCompletion },
    { key: 'table-completion', label: 'Compléter un tableau', family: 'Saisie', Component: TableCompletion },
    { key: 'summary-completion', label: 'Compléter un résumé', family: 'Saisie', Component: SummaryCompletion },
    { key: 'flow-chart-completion', label: 'Diagramme de flux', family: 'Saisie', Component: FlowChartCompletion },
    { key: 'complete-the-words', label: 'Compléter les mots', family: 'Saisie', Component: CompleteTheWords },
    { key: 'matching', label: 'Association', family: 'Organisation', Component: Matching },
    { key: 'multiple-matching', label: 'Association multiple', family: 'Organisation', Component: MultipleMatching },
    { key: 'ordering', label: 'Remettre dans l’ordre', family: 'Organisation', Component: Ordering },
    { key: 'insert-text', label: 'Insérer une phrase', family: 'Organisation', Component: InsertText },
    { key: 'gapped-text', label: 'Replacer des paragraphes', family: 'Organisation', Component: GappedText },
    { key: 'build-a-sentence', label: 'Construire une phrase', family: 'Organisation', Component: BuildASentence },
    { key: 'short-writing', label: 'Rédaction courte', family: 'Écrit IA', Component: ShortWriting },
    { key: 'essay-editor', label: 'Rédaction / essai', family: 'Écrit IA', Component: EssayEditor },
    { key: 'graph-description', label: 'Décrire un graphique', family: 'Écrit IA', Component: GraphDescription },
    { key: 'academic-discussion', label: 'Discussion académique', family: 'Écrit IA', Component: AcademicDiscussion },
    { key: 'synthesis', label: 'Synthèse', family: 'Écrit IA', Component: Synthesis },
    { key: 'integrated-task', label: 'Tâche intégrée', family: 'Écrit IA', Component: IntegratedTask },
    { key: 'guided-writing', label: 'Écriture guidée', family: 'Écrit IA', Component: GuidedWriting },
    { key: 'speaking-recorder', label: 'Expression orale', family: 'Oral & écoute', Component: SpeakingRecorder },
    { key: 'role-play', label: 'Jeu de rôle / débat', family: 'Oral & écoute', Component: RolePlay },
    { key: 'listen-repeat', label: 'Écouter et répéter', family: 'Oral & écoute', Component: ListenRepeat },
    { key: 'listen-choose-response', label: 'Écouter et répondre', family: 'Oral & écoute', Component: ListenChooseResponse },
    { key: 'vocabulary-card', label: 'Carte de vocabulaire', family: 'Vocabulaire', Component: VocabularyCard },
    { key: 'diagram-labeling', label: 'Annoter un schéma', family: 'Legacy', Component: DiagramLabeling },
];

function sampleQuestion(key: string): SampleQuestion {
    switch (key) {
        case 'mcq':
            return { id: 'q1', text: 'Which city is the capital of Germany?', options: ['Munich', 'Berlin', 'Hamburg', 'Cologne'], correct_answer: 'B' };
        case 'true-false-ng':
            return { id: 'q1', text: 'The passage states that the museum opens on Sundays.', correct_answer: 'True' };
        case 'gap-fill':
            return { id: 'q1', text: 'She has lived here [blank] 2020.', correct_answer: 'since' };
        case 'matching':
            return { id: 'q1', text: 'France', options: ['Paris', 'Berlin', 'Madrid', 'Rome'], correct_answer: 'A' };
        case 'essay-editor':
            return { id: 'q1', prompt: 'Do digital tools improve learning outcomes?', min_words: 80, max_words: 180 };
        case 'sentence-completion':
            return { id: 'q1', text: 'Berlin is the capital [blank].', options: ['of Germany', 'of Austria', 'of Switzerland'], correct_answer: 'of Germany' };
        case 'short-answer':
            return { id: 'q1', text: 'What is the main idea of the passage?', correct_answer: 'Sustainable transport' };
        case 'note-completion':
            return { id: 'q1', title: 'Meeting notes', notes: [{ label: 'Date', value: '12 June' }, { label: 'Action', value: '' }], correct_answers: { 1: 'Send the report' } };
        case 'ordering':
            return { id: 'q1', text: 'Remets les étapes dans l’ordre.', items: ['First, collect data.', 'Then, analyse it.', 'Finally, present the results.'], correct_order: ['First, collect data.', 'Then, analyse it.', 'Finally, present the results.'] };
        case 'dictation':
            return { id: 'q1', audio_text: 'Practice makes progress.', correct_answer: 'Practice makes progress.' };
        case 'open-cloze':
            return { id: 'q1', text: 'Learning a language takes (1)___, regular practice and curiosity.', correct_answers: { 1: 'time' } };
        case 'word-formation':
            return { id: 'q1', text: 'Her [blank] helped the whole team. (KIND)', correct_answer: 'kindness' };
        case 'key-word-transformation':
            return { id: 'q1', original: "I haven't seen him for years.", key: 'TIME', transformed: "It's been a [blank] saw him.", correct_answer: 'long time since I last' };
        case 'short-writing':
            return { id: 'q1', text: 'Write a short message to postpone a meeting.', min_words: 30, max_words: 70 };
        case 'form-completion':
            return { id: 'q1', title: 'Course registration', fields: [{ label: 'Name', value: '' }, { label: 'Level', value: 'B2' }, { label: 'Start date', value: '' }], correct_answers: { 0: 'Alex Martin', 2: '15 September' } };
        case 'summary-completion':
            return { id: 'q1', summary_text: 'The project reduced ___ and improved ___.', word_list: ['costs', 'quality', 'delays'], gap_count: 2, correct_answers: { 0: 'costs', 1: 'quality' } };
        case 'table-completion':
            return { id: 'q1', title: 'Course options', headers: ['Course', 'Duration'], rows: [{ cells: [{ value: 'English B2', blank: false }, { value: '', blank: true }] }, { cells: [{ value: '', blank: true }, { value: '8 weeks', blank: false }] }], correct_answers: { '0-1': '12 weeks', '1-0': 'German A2' } };
        case 'flow-chart-completion':
            return { id: 'q1', title: 'Application process', steps: [{ text: 'Create an account', blank: false }, { text: '', blank: true }, { text: 'Receive confirmation', blank: false }], correct_answers: { 1: 'Upload documents' } };
        case 'multiple-matching':
            return { id: 'q1', texts: [{ id: 'A', title: 'Museum', content: 'A large modern-art collection.' }, { id: 'B', title: 'Park', content: 'A quiet place for long walks.' }], questions: [{ id: 's1', text: 'Where can you see paintings?', correct_answer: 'A' }, { id: 's2', text: 'Where can you walk?', correct_answer: 'B' }] };
        case 'insert-text':
            return { id: 'q1', passage: 'The team analysed the data. [A] It then published the results. [B]', sentence_to_insert: 'The findings were surprisingly positive.', correct_answer: 'A' };
        case 'gapped-text':
            return { id: 'q1', passage_with_gaps: ['Language learning takes time.', null, 'Regular feedback makes progress visible.'], removed_paragraphs: ['Small daily sessions are more effective than rare long ones.'], gap_count: 1, correct_order: ['0'] };
        case 'graph-description':
            return { id: 'q1', text: 'Describe the change in weekly study time.', chart_data: { type: 'bar', labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'], datasets: [{ label: 'Minutes', data: [45, 60, 85, 110] }] }, min_words: 40, max_words: 100 };
        case 'academic-discussion':
            return { id: 'q1', professor_prompt: 'Should universities use AI tutors?', student_posts: [{ name: 'Maya', text: 'They can make feedback faster.' }, { name: 'Leo', text: 'Human guidance is still essential.' }], writing_prompt: 'Add your view and support it with one example.', min_words: 60 };
        case 'speaking-recorder':
            return { id: 'q1', text: 'Describe a place that helps you concentrate.', prep_time: 15, speak_time: 45, expected_points: ['describe the place', 'explain why it helps'] };
        case 'role-play':
            return { id: 'q1', scenario: 'You are booking a language course.', role: 'Prospective student', dialogue_turns: [{ speaker: 'examiner', text: 'Hello! How can I help you?' }, { speaker: 'candidate', prompt: 'Ask about the next B2 course.' }, { speaker: 'examiner', text: 'The next course begins in September.' }, { speaker: 'candidate', prompt: 'Ask about the schedule and price.' }] };
        case 'diagram-labeling':
            return { id: 'q1', text: 'Annotate the main parts of the diagram.', image_url: '/icons/pwa-512-v4.png', labels: [{ id: 'l1', x: 35, y: 35 }, { id: 'l2', x: 65, y: 65 }], correct_answers: { l1: 'P', l2: 'A' } };
        case 'synthesis':
            return { id: 'q1', documents: [{ title: 'Source A', content: 'Daily practice strengthens long-term recall.' }, { title: 'Source B', content: 'Feedback helps learners correct misconceptions.' }], writing_prompt: 'Synthesize both ideas in one short paragraph.', min_words: 40, max_words: 90 };
        case 'integrated-task':
            return { id: 'q1', reading_passage: { title: 'Remote learning', content: 'Flexible schedules help many adult learners stay consistent.' }, audio_text: 'A teacher explains that live interaction remains valuable.', writing_prompt: 'Explain how the listening relates to the reading.', response_type: 'writing', min_words: 50, max_words: 100 };
        case 'vocabulary-card':
            return { id: 'q1', word: 'resilient', language: 'english', hint: 'Able to recover quickly', options: ['fragile', 'adaptable', 'temporary', 'silent'], correct_answer: 'B' };
        case 'listen-repeat':
            return { id: 'q1', audio_text: 'Consistent practice builds confidence over time.', correct_answer: 'Consistent practice builds confidence over time.' };
        case 'picture-mcq':
            return { id: 'q1', text: 'Which image represents the PrepLa learning app?', image_options: ['/icons/pwa-512-v4.png', '/icons/pwa-maskable-512-v4.png'], options: ['Main icon', 'Maskable icon'], correct_answer: 'A' };
        case 'complete-the-words':
            return { id: 'q1', text: 'Regular pra____ improves flu____ and confi_____.', correct_answers: { 0: 'practice', 1: 'fluency', 2: 'confidence' } };
        case 'build-a-sentence':
            return { id: 'q1', text: 'Respond politely to the invitation.', words: ['Thank', 'you', 'for', 'inviting', 'me.'], correct_answer: 'Thank you for inviting me.' };
        case 'listen-choose-response':
            return { id: 'q1', audio_text: 'Could you send me the report by Friday?', options: ['Yes, I will send it tomorrow.', 'The meeting room is upstairs.', 'I bought it last year.', 'No, Friday is blue.'], correct_answer: 'A' };
        case 'guided-writing':
            return { id: 'q1', source_text: 'Online study offers flexibility, but it also requires self-discipline.', text: 'Reformulate the idea and add one concrete example.', must_use: ['however', 'for example'], min_words: 35, max_words: 80 };
        default:
            return { id: 'q1', text: `Test de ${key}`, options: ['A', 'B', 'C'], correct_answer: 'A' };
    }
}

export default function Sandbox() {
    const [selectedKey, setSelectedKey] = useState(CATALOG[0].key);
    const [answers, setAnswers] = useState<Record<string, unknown>>({});
    const selected = CATALOG.find((entry) => entry.key === selectedKey) ?? CATALOG[0];
    const question = useMemo(() => sampleQuestion(selected.key), [selected.key]);
    const completed = answers[question.id] !== undefined;

    const select = (key: string) => {
        setSelectedKey(key);
        setAnswers({});
    };

    return (
        <AppLayout>
            <Head title="QA des exercices - PrePla" />
            <main className="mx-auto w-full max-w-6xl px-4 py-5 sm:px-6 sm:py-8">
                <header className="mb-5 overflow-hidden rounded-3xl border border-primary/15 bg-gradient-to-br from-primary/10 via-background to-amber-500/10 p-5 shadow-sm sm:p-7">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p className="text-xs font-black uppercase tracking-[0.22em] text-primary">Contrôle qualité interne</p>
                            <h1 className="mt-2 text-2xl font-black tracking-tight sm:text-3xl">Tous les exercices, au même endroit</h1>
                            <p className="mt-2 max-w-2xl text-sm leading-relaxed text-muted-foreground">Vérifie le rendu, l’interaction et la valeur renvoyée par chacun des {CATALOG.length} composants sans modifier la progression d’un élève.</p>
                        </div>
                        <div className="flex shrink-0 items-center gap-3 rounded-2xl border bg-background/80 px-4 py-3 shadow-sm backdrop-blur">
                            <span className={`h-2.5 w-2.5 rounded-full ${completed ? 'bg-emerald-500' : 'bg-amber-400'}`} />
                            <div><p className="text-xs font-bold">{selected.label}</p><p className="text-[11px] text-muted-foreground">{completed ? 'Réponse capturée' : 'Prêt à tester'}</p></div>
                        </div>
                    </div>
                </header>

                <div className="mb-4 md:hidden">
                    <label htmlFor="exercise-component" className="mb-1.5 block text-xs font-bold text-muted-foreground">Type d’exercice</label>
                    <select id="exercise-component" value={selected.key} onChange={(event) => select(event.target.value)} className="h-12 w-full rounded-xl border border-border bg-card px-3 text-sm font-semibold shadow-sm">
                        {CATALOG.map((entry) => <option key={entry.key} value={entry.key}>{entry.family} · {entry.label}</option>)}
                    </select>
                </div>

                <div className="grid gap-5 md:grid-cols-[260px_minmax(0,1fr)]">
                    <aside className="hidden max-h-[calc(100vh-9rem)] overflow-y-auto rounded-2xl border bg-card p-2 shadow-sm md:sticky md:top-4 md:block">
                        {Array.from(new Set(CATALOG.map((entry) => entry.family))).map((family) => (
                            <section key={family} className="mb-3 last:mb-0">
                                <h2 className="px-3 py-2 text-[10px] font-black uppercase tracking-[0.18em] text-muted-foreground">{family}</h2>
                                <div className="space-y-1">
                                    {CATALOG.filter((entry) => entry.family === family).map((entry) => (
                                        <button key={entry.key} type="button" onClick={() => select(entry.key)} className={`w-full rounded-xl px-3 py-2.5 text-left transition ${selected.key === entry.key ? 'bg-primary text-primary-foreground shadow-sm' : 'hover:bg-muted'}`}>
                                            <span className="block text-xs font-bold">{entry.label}</span>
                                            <span className={`block text-[10px] ${selected.key === entry.key ? 'text-primary-foreground/70' : 'text-muted-foreground'}`}>{entry.key}</span>
                                        </button>
                                    ))}
                                </div>
                            </section>
                        ))}
                    </aside>

                    <section className="min-w-0">
                        <div className="overflow-hidden rounded-2xl border bg-card shadow-sm">
                            <div className="flex flex-wrap items-center justify-between gap-2 border-b bg-muted/35 px-4 py-3 sm:px-5">
                                <div><p className="text-sm font-black">{selected.label}</p><p className="text-[11px] text-muted-foreground">{selected.key} · {selected.family}</p></div>
                                <span className="rounded-full border bg-background px-2.5 py-1 text-[10px] font-bold text-muted-foreground">{CATALOG.findIndex((entry) => entry.key === selected.key) + 1} / {CATALOG.length}</span>
                            </div>
                            <div className="p-4 sm:p-6">
                                <ExerciseErrorBoundary resetKey={selected.key} onSkip={() => setAnswers({ [question.id]: '__skipped__' })}>
                                    <selected.Component key={selected.key} question={question} lang="en" onAnswer={(id: string, answer: unknown) => setAnswers((current) => ({ ...current, [id]: answer }))} selectedAnswer={answers[question.id]} disabled={false} />
                                </ExerciseErrorBoundary>
                            </div>
                        </div>
                        <details className="mt-4 rounded-2xl border bg-slate-950 text-slate-100 shadow-sm">
                            <summary className="cursor-pointer px-4 py-3 text-xs font-bold">Inspecteur de réponse</summary>
                            <pre className="max-w-full overflow-x-auto border-t border-white/10 p-4 text-[11px] leading-relaxed text-emerald-300">{JSON.stringify(answers, null, 2)}</pre>
                        </details>
                    </section>
                </div>
            </main>
        </AppLayout>
    );
}
