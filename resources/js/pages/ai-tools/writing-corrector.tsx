import { Head, router, usePage } from '@inertiajs/react';
import axios from 'axios';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useRef, useState } from 'react';

function Icon({ name, size = 16, className, style }: { name: string; size?: number; className?: string; style?: React.CSSProperties }) {
    return <img src={`/icons/${name}.png`} alt="" width={size} height={size} style={{ objectFit: 'contain', ...style }} className={className} />;
}

interface Correction {
    original: string;
    corrected: string;
    explanation: string;
}

interface CorrectionResult {
    score?: number;
    band_scores: Record<string, number>;
    corrections?: Correction[];
    feedback: string;
    word_count?: number;
    submitted_text?: string;
}

function scoreColor(value: number): string {
    if (value >= 7) return 'text-emerald-500';
    if (value >= 5.5) return 'text-amber-500';
    return 'text-rose-500';
}

/**
 * Renders the learner's text with each corrected span highlighted in place.
 * We locate every `original` substring and wrap it; hovering shows the fix.
 */
function HighlightedText({ text, corrections }: { text: string; corrections: Correction[] }) {
    // Build a list of {start, end, correction} matches, longest first so nested
    // spans don't get clobbered, then sort by position and render sequentially.
    const matches: { start: number; end: number; correction: Correction }[] = [];
    const used: boolean[] = new Array(text.length).fill(false);

    [...corrections]
        .filter((c) => c.original && c.original.trim())
        .sort((a, b) => b.original.length - a.original.length)
        .forEach((c) => {
            let from = 0;
            // place the first not-yet-used occurrence of this span
            while (from <= text.length) {
                const idx = text.indexOf(c.original, from);
                if (idx === -1) break;
                const free = !used.slice(idx, idx + c.original.length).some(Boolean);
                if (free) {
                    for (let i = idx; i < idx + c.original.length; i++) used[i] = true;
                    matches.push({ start: idx, end: idx + c.original.length, correction: c });
                    break;
                }
                from = idx + 1;
            }
        });

    matches.sort((a, b) => a.start - b.start);

    const nodes: React.ReactNode[] = [];
    let cursor = 0;
    matches.forEach((m, i) => {
        if (m.start > cursor) nodes.push(<span key={`t${i}`}>{text.slice(cursor, m.start)}</span>);
        nodes.push(
            <span
                key={`h${i}`}
                title={`${m.correction.corrected}${m.correction.explanation ? ` — ${m.correction.explanation}` : ''}`}
                className="group/hl relative cursor-help rounded bg-rose-100 px-0.5 text-rose-700 underline decoration-rose-400 decoration-wavy underline-offset-2 dark:bg-rose-950/40 dark:text-rose-300"
            >
                {text.slice(m.start, m.end)}
                <span className="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 hidden w-max max-w-xs -translate-x-1/2 rounded-md bg-foreground px-2 py-1 text-xs text-background shadow-lg group-hover/hl:block">
                    ✓ {m.correction.corrected}
                </span>
            </span>,
        );
        cursor = m.end;
    });
    if (cursor < text.length) nodes.push(<span key="tail">{text.slice(cursor)}</span>);

    return <p className="whitespace-pre-wrap text-sm leading-loose">{nodes}</p>;
}

export default function WritingCorrector() {
    const { flash } = usePage().props as any;
    const correction = flash?.correction as CorrectionResult | undefined;

    const [text, setText] = useState('');
    const [task, setTask] = useState('');
    const [submitting, setSubmitting] = useState(false);

    // Image → OCR state
    const [imagePreview, setImagePreview] = useState<string | null>(null);
    const [extracting, setExtracting] = useState(false);
    const [ocrError, setOcrError] = useState<string | null>(null);
    const cameraInputRef = useRef<HTMLInputElement>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const wordCount = text.trim() ? text.trim().split(/\s+/).length : 0;

    function handleSubmit() {
        if (!text.trim()) return;
        setSubmitting(true);
        router.post(
            route('ai-tools.writing-corrector.store'),
            { text, task_description: task },
            { onFinish: () => setSubmitting(false) },
        );
    }

    async function handleImage(file: File) {
        setOcrError(null);
        setImagePreview(URL.createObjectURL(file));
        setExtracting(true);

        const formData = new FormData();
        formData.append('image', file);

        try {
            const res = await axios.post(route('ai-tools.writing-corrector.extract'), formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            const extracted = (res.data?.text ?? '').trim();
            if (!extracted) {
                setOcrError("Aucun texte n'a pu être lu sur l'image. Réessayez avec une photo plus nette.");
            } else {
                // Append to any existing text so multiple pages can be captured.
                setText((prev) => (prev.trim() ? `${prev.trim()}\n\n${extracted}` : extracted));
            }
        } catch (err: any) {
            setOcrError(
                err?.response?.data?.error ??
                    "Impossible de lire l'image. Vérifie ta connexion et réessaie.",
            );
        } finally {
            setExtracting(false);
        }
    }

    function onInputChange(e: React.ChangeEvent<HTMLInputElement>) {
        const file = e.target.files?.[0];
        if (file) handleImage(file);
        e.target.value = ''; // allow re-selecting the same file
    }

    return (
        <AppLayout>
            <Head title="Correcteur de rédaction" />
            <div className="mx-auto w-full max-w-4xl space-y-6 p-4 md:p-6">
                {/* Header */}
                <div className="flex items-start gap-3">
                    <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary/10">
                        <Icon name="file-edit" size={22} className="text-primary" />
                    </div>
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Correcteur de rédaction</h1>
                        <p className="text-sm text-muted-foreground">
                            Tape ton texte ou prends-le en photo — l'IA le corrige et te note.
                        </p>
                    </div>
                </div>

                {/* Capture from photo */}
                <Card className="overflow-hidden border-dashed">
                    <CardContent className="p-4 md:p-6">
                        <div className="flex flex-col gap-4 md:flex-row md:items-center">
                            <div className="flex-1">
                                <div className="flex items-center gap-2">
                                    <CameraGlyph className="h-5 w-5 text-primary" />
                                    <h2 className="text-sm font-semibold">Photographie ton cahier</h2>
                                </div>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Filme ou prends en photo la page où tu as écrit ta rédaction. Le texte
                                    sera lu automatiquement et ajouté ci-dessous, prêt à être corrigé.
                                </p>
                            </div>
                            <div className="flex shrink-0 gap-2">
                                <Button
                                    type="button"
                                    onClick={() => cameraInputRef.current?.click()}
                                    disabled={extracting}
                                    className="gap-2"
                                >
                                    <CameraGlyph className="h-4 w-4" />
                                    Prendre en photo
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => fileInputRef.current?.click()}
                                    disabled={extracting}
                                    className="gap-2"
                                >
                                    <Icon name="download" size={16} className="rotate-180" />
                                    Importer
                                </Button>
                            </div>
                        </div>

                        {/* Hidden inputs: one opens the camera, the other the gallery/files */}
                        <input
                            ref={cameraInputRef}
                            type="file"
                            accept="image/*"
                            capture="environment"
                            className="hidden"
                            onChange={onInputChange}
                        />
                        <input
                            ref={fileInputRef}
                            type="file"
                            accept="image/png,image/jpeg,image/webp"
                            className="hidden"
                            onChange={onInputChange}
                        />

                        {(imagePreview || extracting || ocrError) && (
                            <div className="mt-4 flex items-center gap-4 rounded-lg border border-border bg-muted/30 p-3">
                                {imagePreview && (
                                    <img
                                        src={imagePreview}
                                        alt="Aperçu de la rédaction photographiée"
                                        className="h-20 w-20 rounded-md object-cover"
                                    />
                                )}
                                <div className="min-w-0 flex-1 text-sm">
                                    {extracting && (
                                        <p className="flex items-center gap-2 text-muted-foreground">
                                            <Icon name="loader" size={16} className="animate-spin" />
                                            Lecture du texte en cours…
                                        </p>
                                    )}
                                    {!extracting && ocrError && (
                                        <p className="flex items-start gap-2 text-rose-500">
                                            <Icon name="alert-circle" size={16} className="mt-0.5 shrink-0" />
                                            <span>{ocrError}</span>
                                        </p>
                                    )}
                                    {!extracting && !ocrError && imagePreview && (
                                        <p className="flex items-center gap-2 text-emerald-600">
                                            <Icon name="check-circle" size={16} />
                                            Texte ajouté à ta rédaction ci-dessous.
                                        </p>
                                    )}
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Editor */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Ta rédaction</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div>
                            <label className="text-sm font-medium">Consigne / sujet (facultatif)</label>
                            <input
                                className="mt-1 w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                placeholder="ex. : IELTS Tâche 2 — Discutez des avantages et inconvénients…"
                                value={task}
                                onChange={(e) => setTask(e.target.value)}
                            />
                        </div>
                        <div>
                            <textarea
                                className="min-h-[260px] w-full rounded-lg border border-border bg-background p-4 text-sm leading-relaxed focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                placeholder="Colle, rédige ton texte ici — ou prends-le en photo ci-dessus…"
                                value={text}
                                onChange={(e) => setText(e.target.value)}
                            />
                            <div className="mt-1 flex items-center justify-between">
                                <p className="text-xs text-muted-foreground">{wordCount} mots</p>
                                {text.trim() && (
                                    <button
                                        type="button"
                                        onClick={() => setText('')}
                                        className="text-xs text-muted-foreground hover:text-foreground"
                                    >
                                        Effacer
                                    </button>
                                )}
                            </div>
                        </div>
                        <Button
                            onClick={handleSubmit}
                            disabled={!text.trim() || submitting}
                            size="lg"
                            className="w-full gap-2 sm:w-auto"
                        >
                            {submitting ? (
                                <>
                                    <Icon name="loader" size={16} className="animate-spin" />
                                    Analyse en cours…
                                </>
                            ) : (
                                <>
                                    <Icon name="sparkles" size={16} />
                                    Analyser la rédaction
                                </>
                            )}
                        </Button>
                    </CardContent>
                </Card>

                {/* Results */}
                {correction && (
                    <Card>
                        <CardHeader>
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <CardTitle className="text-base">Retours de l'IA</CardTitle>
                                {typeof correction.score === 'number' && (
                                    <span className="flex items-baseline gap-1 rounded-full bg-primary/10 px-3 py-1">
                                        <span className={`text-lg font-bold ${scoreColor(correction.score)}`}>
                                            {correction.score}
                                        </span>
                                        <span className="text-xs text-muted-foreground">/ 9 global</span>
                                    </span>
                                )}
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            {/* Band scores */}
                            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                {Object.entries(correction.band_scores).map(([key, value]) => (
                                    <div key={key} className="rounded-lg border border-border p-3 text-center">
                                        <p className={`text-2xl font-bold ${scoreColor(value)}`}>{value}</p>
                                        <p className="mt-1 text-xs capitalize text-muted-foreground">
                                            {key.replace(/_/g, ' ')}
                                        </p>
                                    </div>
                                ))}
                            </div>

                            {/* Your text with the problem parts highlighted in place */}
                            {correction.submitted_text && correction.corrections && correction.corrections.length > 0 && (
                                <div className="rounded-lg border border-border p-4">
                                    <div className="mb-2 flex items-center gap-2">
                                        <Icon name="file-edit" size={16} className="text-primary" />
                                        <h3 className="text-sm font-semibold">Ton texte annoté</h3>
                                        <span className="text-xs text-muted-foreground">
                                            (survolez les parties soulignées pour voir la correction)
                                        </span>
                                    </div>
                                    <HighlightedText text={correction.submitted_text} corrections={correction.corrections} />
                                </div>
                            )}

                            {/* Overall feedback */}
                            <div className="rounded-lg border border-border bg-muted/30 p-4">
                                <div className="mb-2 flex items-center gap-2">
                                    <Icon name="lightbulb" size={16} className="text-primary" />
                                    <h3 className="text-sm font-semibold">Commentaire général</h3>
                                </div>
                                <p className="text-sm leading-relaxed text-foreground/90">{correction.feedback}</p>
                            </div>

                            {/* Line-by-line corrections */}
                            {correction.corrections && correction.corrections.length > 0 && (
                                <div className="space-y-3">
                                    <h3 className="text-sm font-semibold">
                                        Corrections détaillées ({correction.corrections.length})
                                    </h3>
                                    {correction.corrections.map((c, i) => (
                                        <div key={i} className="rounded-lg border border-border p-3">
                                            <div className="flex flex-col gap-1 text-sm sm:flex-row sm:items-center sm:gap-3">
                                                <span className="text-rose-500 line-through decoration-rose-400/60">
                                                    {c.original}
                                                </span>
                                                <Icon name="arrow-right" size={14} className="hidden shrink-0 opacity-50 sm:block" />
                                                <span className="font-medium text-emerald-600">{c.corrected}</span>
                                            </div>
                                            {c.explanation && (
                                                <p className="mt-2 text-xs text-muted-foreground">{c.explanation}</p>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}

/** Inline camera glyph — no PNG icon exists for "camera" in /public/icons. */
function CameraGlyph({ className }: { className?: string }) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={2}
            strokeLinecap="round"
            strokeLinejoin="round"
            className={className}
            aria-hidden="true"
        >
            <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z" />
            <circle cx="12" cy="13" r="3" />
        </svg>
    );
}
