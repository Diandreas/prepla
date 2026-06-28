import { Head, Link, router, usePage } from '@inertiajs/react';
import axios from 'axios';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useMemo, useState } from 'react';
import { EXERCISE_SCHEMAS, emptyQuestion, familyOf, type Family } from '@/lib/exercise-schemas';

interface ExerciseType { id: number; name: string; component_key: string; skill_type: string }
interface Exam { id: number; name: string }
interface Props {
    exerciseTypes: ExerciseType[];
    exams: Exam[];
    defaultExamId: number | null;
    exercise?: {
        id: number;
        exercise_type_id: number;
        difficulty: string;
        content: Record<string, any> | null;
        questions: any[];
    };
}

const input = 'w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary';

export default function ExerciseBuilder({ exerciseTypes, exams, defaultExamId, exercise }: Props) {
    const editing = !!exercise;
    const initialType = exercise
        ? exerciseTypes.find((t) => t.id === exercise.exercise_type_id)
        : undefined;

    const [typeId, setTypeId] = useState<number | ''>(exercise?.exercise_type_id ?? '');
    const [examId, setExamId] = useState<number | ''>(defaultExamId ?? (exams[0]?.id ?? ''));
    const [difficulty, setDifficulty] = useState(exercise?.difficulty ?? 'B1');
    const [content, setContent] = useState<Record<string, any>>(exercise?.content ?? {});
    const [questions, setQuestions] = useState<any[]>(exercise?.questions ?? []);
    const [aiLoading, setAiLoading] = useState(false);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const selectedType = useMemo(
        () => exerciseTypes.find((t) => t.id === typeId) ?? initialType,
        [typeId, exerciseTypes, initialType],
    );
    const componentKey = selectedType?.component_key ?? '';
    const family: Family | null = componentKey ? familyOf(componentKey) : null;
    const schema = componentKey ? EXERCISE_SCHEMAS[componentKey] : null;

    function onTypeChange(id: number) {
        setTypeId(id);
        const ck = exerciseTypes.find((t) => t.id === id)?.component_key ?? '';
        const fam = familyOf(ck);
        // reset questions to one empty of the right family when type changes
        if (!editing) setQuestions(fam ? [emptyQuestion(fam)] : []);
    }

    function addQuestion() {
        if (family) setQuestions((q) => [...q, emptyQuestion(family)]);
    }
    function updateQuestion(i: number, patch: Record<string, any>) {
        setQuestions((qs) => qs.map((q, idx) => (idx === i ? { ...q, ...patch } : q)));
    }
    function removeQuestion(i: number) {
        setQuestions((qs) => qs.filter((_, idx) => idx !== i));
    }

    async function generateWithAi() {
        if (!typeId || !examId) return;
        setAiLoading(true);
        setError(null);
        try {
            const res = await axios.post(route('center.exercises.ai-draft'), {
                exercise_type_id: typeId,
                exam_id: examId,
                difficulty,
            });
            setContent(res.data.content ?? {});
            setQuestions(res.data.questions ?? []);
        } catch (e: any) {
            setError(e?.response?.data?.error ?? 'La génération IA a échoué.');
        } finally {
            setAiLoading(false);
        }
    }

    function save() {
        if (!typeId || !examId) {
            setError('Choisissez un type et un examen.');
            return;
        }
        setSaving(true);
        setError(null);
        const payload = {
            exercise_type_id: typeId,
            exam_id: examId,
            difficulty,
            content,
            questions,
            is_ai_generated: false,
        };
        const opts = {
            onError: (errs: any) => setError(Object.values(errs)[0] as string),
            onFinish: () => setSaving(false),
        };
        if (editing) {
            router.patch(route('center.exercises.update', exercise!.id), payload, opts);
        } else {
            router.post(route('center.exercises.store'), payload, opts);
        }
    }

    return (
        <AppLayout>
            <Head title={editing ? 'Modifier un exercice' : 'Nouvel exercice'} />
            <div className="mx-auto w-full max-w-3xl space-y-5 p-4 md:p-6">
                <div>
                    <Link href={route('center.exercises.index')} className="text-sm text-muted-foreground hover:text-foreground">
                        ← Contenu
                    </Link>
                    <h1 className="mt-1 text-2xl font-bold tracking-tight">{editing ? 'Modifier un exercice' : 'Nouvel exercice'}</h1>
                </div>

                {error && <div className="rounded-lg border border-rose-300 bg-rose-50 p-3 text-sm text-rose-700 dark:bg-rose-950/30">{error}</div>}

                <Card>
                    <CardHeader><CardTitle className="text-base">Type & paramètres</CardTitle></CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-3">
                        <div className="sm:col-span-1">
                            <label className="text-sm font-medium">Type</label>
                            <select className={`mt-1 ${input}`} value={typeId} onChange={(e) => onTypeChange(Number(e.target.value))} disabled={editing}>
                                <option value="">—</option>
                                {exerciseTypes.map((t) => (
                                    <option key={t.id} value={t.id}>{t.name} ({t.skill_type})</option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="text-sm font-medium">Examen</label>
                            <select className={`mt-1 ${input}`} value={examId} onChange={(e) => setExamId(Number(e.target.value))}>
                                {exams.map((ex) => (<option key={ex.id} value={ex.id}>{ex.name}</option>))}
                            </select>
                        </div>
                        <div>
                            <label className="text-sm font-medium">Niveau</label>
                            <select className={`mt-1 ${input}`} value={difficulty} onChange={(e) => setDifficulty(e.target.value)}>
                                {['A1', 'A2', 'B1', 'B2', 'C1', 'C2'].map((l) => (<option key={l} value={l}>{l}</option>))}
                            </select>
                        </div>
                        {!editing && typeId && (
                            <div className="sm:col-span-3">
                                <Button type="button" variant="outline" onClick={generateWithAi} disabled={aiLoading}>
                                    {aiLoading ? 'Génération…' : '✨ Générer un brouillon avec l\'IA'}
                                </Button>
                                <span className="ml-2 text-xs text-muted-foreground">puis éditez librement avant d'enregistrer</span>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {schema && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Contenu commun</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div>
                                <label className="text-sm font-medium">Passage / consigne (facultatif)</label>
                                <textarea className={`mt-1 min-h-[80px] ${input}`} value={content.passage ?? ''} onChange={(e) => setContent({ ...content, passage: e.target.value })} />
                            </div>
                            {schema.media.includes('image') && (
                                <MediaPicker type="image" value={content.image_url} onChange={(url) => setContent({ ...content, image_url: url })} />
                            )}
                            {schema.media.includes('audio') && (
                                <div className="space-y-2">
                                    <MediaPicker type="audio" value={content.audio_url} onChange={(url) => setContent({ ...content, audio_url: url })} />
                                    <p className="text-xs text-muted-foreground">Ou laissez vide et renseignez « texte audio » par question pour une génération vocale automatique.</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                )}

                {family && (
                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <CardTitle className="text-base">Questions ({questions.length})</CardTitle>
                                <Button type="button" variant="outline" size="sm" onClick={addQuestion}>+ Ajouter</Button>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {questions.length === 0 && <p className="text-sm text-muted-foreground">Ajoutez au moins une question.</p>}
                            {questions.map((q, i) => (
                                <QuestionEditor
                                    key={i}
                                    index={i}
                                    family={family}
                                    componentKey={componentKey}
                                    question={q}
                                    onChange={(patch) => updateQuestion(i, patch)}
                                    onRemove={() => removeQuestion(i)}
                                />
                            ))}
                        </CardContent>
                    </Card>
                )}

                <Button onClick={save} disabled={saving || !family}>{saving ? 'Enregistrement…' : 'Enregistrer'}</Button>
            </div>
        </AppLayout>
    );
}

// ───────────────────────── per-family question editor ─────────────────────────

function QuestionEditor({
    index,
    family,
    componentKey,
    question,
    onChange,
    onRemove,
}: {
    index: number;
    family: Family;
    componentKey: string;
    question: any;
    onChange: (patch: Record<string, any>) => void;
    onRemove: () => void;
}) {
    return (
        <div className="rounded-lg border border-border p-3 space-y-3">
            <div className="flex items-center justify-between">
                <span className="text-xs font-bold uppercase text-muted-foreground">Question {index + 1}</span>
                <button onClick={onRemove} className="text-xs text-rose-500 hover:underline">Supprimer</button>
            </div>

            <textarea className={`min-h-[60px] ${input}`} placeholder="Énoncé de la question…" value={question.text ?? ''} onChange={(e) => onChange({ text: e.target.value })} />

            {family === 'exact-match' && componentKey !== 'true-false-ng' && (
                <ExactMatchFields question={question} onChange={onChange} />
            )}
            {family === 'exact-match' && componentKey === 'true-false-ng' && (
                <select className={input} value={question.correct_answer ?? ''} onChange={(e) => onChange({ correct_answer: e.target.value })}>
                    <option value="">Réponse correcte…</option>
                    <option value="True">Vrai</option>
                    <option value="False">Faux</option>
                    <option value="Not Given">Non mentionné</option>
                </select>
            )}
            {family === 'multi-field' && <KeyValueFields question={question} onChange={onChange} />}
            {family === 'order' && <OrderFields question={question} onChange={onChange} />}
            {family === 'ai-writing' && <WordRangeFields question={question} onChange={onChange} />}
            {family === 'ai-speaking' && <ExpectedPointsFields question={question} onChange={onChange} />}
        </div>
    );
}

function ExactMatchFields({ question, onChange }: { question: any; onChange: (p: any) => void }) {
    const options: string[] = question.options ?? ['', '', '', ''];
    const letters = ['A', 'B', 'C', 'D', 'E', 'F'];
    const hasOptions = options.length > 0;
    return (
        <div className="space-y-2">
            {hasOptions &&
                options.map((opt, i) => (
                    <div key={i} className="flex items-center gap-2">
                        <span className="w-5 text-center text-xs font-bold">{letters[i]}</span>
                        <input className={input} placeholder={`Option ${letters[i]}`} value={opt} onChange={(e) => {
                            const next = [...options];
                            next[i] = e.target.value;
                            onChange({ options: next });
                        }} />
                    </div>
                ))}
            <input className={input} placeholder="Réponse correcte (lettre A/B… ou texte exact)" value={question.correct_answer ?? ''} onChange={(e) => onChange({ correct_answer: e.target.value })} />
            <input className={input} placeholder="Explication (facultatif)" value={question.explanation ?? ''} onChange={(e) => onChange({ explanation: e.target.value })} />
        </div>
    );
}

function KeyValueFields({ question, onChange }: { question: any; onChange: (p: any) => void }) {
    const map: Record<string, string> = question.correct_answers ?? {};
    const entries = Object.entries(map);
    return (
        <div className="space-y-2">
            <p className="text-xs text-muted-foreground">Réponses attendues (clé du champ → bonne réponse)</p>
            {entries.map(([k, v], i) => (
                <div key={i} className="flex gap-2">
                    <input className={`${input} w-1/3`} placeholder="clé" value={k} onChange={(e) => {
                        const next = { ...map };
                        delete next[k];
                        next[e.target.value] = v;
                        onChange({ correct_answers: next });
                    }} />
                    <input className={input} placeholder="réponse" value={v} onChange={(e) => onChange({ correct_answers: { ...map, [k]: e.target.value } })} />
                </div>
            ))}
            <Button type="button" variant="outline" size="sm" onClick={() => onChange({ correct_answers: { ...map, [String(entries.length)]: '' } })}>+ Champ</Button>
        </div>
    );
}

function OrderFields({ question, onChange }: { question: any; onChange: (p: any) => void }) {
    const items: string[] = question.items ?? [];
    return (
        <div className="space-y-2">
            <p className="text-xs text-muted-foreground">Éléments dans le BON ordre (l'ordre saisi est la solution)</p>
            {items.map((it, i) => (
                <div key={i} className="flex items-center gap-2">
                    <span className="w-5 text-center text-xs font-bold">{i + 1}</span>
                    <input className={input} value={it} onChange={(e) => {
                        const next = [...items];
                        next[i] = e.target.value;
                        onChange({ items: next, correct_order: next });
                    }} />
                </div>
            ))}
            <Button type="button" variant="outline" size="sm" onClick={() => { const next = [...items, '']; onChange({ items: next, correct_order: next }); }}>+ Élément</Button>
        </div>
    );
}

function WordRangeFields({ question, onChange }: { question: any; onChange: (p: any) => void }) {
    return (
        <div className="flex gap-3">
            <label className="text-sm">Min mots <input type="number" className={`${input} mt-1`} value={question.min_words ?? 50} onChange={(e) => onChange({ min_words: Number(e.target.value) })} /></label>
            <label className="text-sm">Max mots <input type="number" className={`${input} mt-1`} value={question.max_words ?? 150} onChange={(e) => onChange({ max_words: Number(e.target.value) })} /></label>
        </div>
    );
}

function ExpectedPointsFields({ question, onChange }: { question: any; onChange: (p: any) => void }) {
    const points: string[] = question.expected_points ?? [''];
    return (
        <div className="space-y-2">
            <p className="text-xs text-muted-foreground">Points que la réponse orale doit couvrir (l'IA évalue la couverture)</p>
            {points.map((p, i) => (
                <div key={i} className="flex items-center gap-2">
                    <span className="text-xs">•</span>
                    <input className={input} value={p} onChange={(e) => {
                        const next = [...points];
                        next[i] = e.target.value;
                        onChange({ expected_points: next });
                    }} />
                </div>
            ))}
            <Button type="button" variant="outline" size="sm" onClick={() => onChange({ expected_points: [...points, ''] })}>+ Point</Button>
        </div>
    );
}

// ───────────────────────── media picker (upload local) ─────────────────────────

function MediaPicker({ type, value, onChange }: { type: 'image' | 'audio'; value?: string; onChange: (url: string) => void }) {
    const [uploading, setUploading] = useState(false);
    const [err, setErr] = useState<string | null>(null);

    async function upload(file: File) {
        setUploading(true);
        setErr(null);
        const fd = new FormData();
        fd.append('type', type);
        fd.append(type, file);
        try {
            const res = await axios.post(route('center.media.store'), fd, { headers: { 'Content-Type': 'multipart/form-data' } });
            onChange(res.data.url);
        } catch (e: any) {
            setErr(e?.response?.data?.message ?? "Échec de l'envoi.");
        } finally {
            setUploading(false);
        }
    }

    return (
        <div>
            <label className="text-sm font-medium">{type === 'image' ? 'Image' : 'Audio'} (facultatif)</label>
            <div className="mt-1 flex items-center gap-3">
                <input type="file" accept={type === 'image' ? 'image/*' : 'audio/*'} onChange={(e) => { const f = e.target.files?.[0]; if (f) upload(f); }} className="text-sm" />
                {uploading && <span className="text-xs text-muted-foreground">Envoi…</span>}
            </div>
            {err && <p className="mt-1 text-xs text-rose-500">{err}</p>}
            {value && type === 'image' && <img src={value} alt="" className="mt-2 h-24 rounded-md object-cover" />}
            {value && type === 'audio' && <audio src={value} controls className="mt-2 w-full" />}
        </div>
    );
}
