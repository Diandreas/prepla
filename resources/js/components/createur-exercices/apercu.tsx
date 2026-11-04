import { ChevronLeft, ChevronRight, Eye, EyeOff, RotateCcw } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { composantDuLecteur } from '@/components/exercises/exercise-player';
import { ExerciseErrorBoundary } from '@/components/exercises/exercise-error-boundary';
import { SpeakButton } from '@/components/exercises/speak-button';
import { Button } from '@/components/ui/button';

/**
 * L'exercice tel que l'eleve le verra : chaque question est affichee par le
 * composant du lecteur lui-meme (composantDuLecteur), avec le texte a lire, le
 * son, et la correction a la demande. Rien n'est enregistre.
 */
// eslint-disable-next-line @typescript-eslint/no-explicit-any -- les questions suivent le schema de chaque format
export function Apercu({ componentKey, skillType, content, questions, lang, titreDuType }: {
    componentKey: string;
    skillType?: string | null;
    content: Record<string, any>;
    questions: any[];
    lang: string;
    titreDuType: string;
}) {
    const [index, setIndex] = useState(0);
    const [reponses, setReponses] = useState<Record<string, unknown>>({});
    const [correction, setCorrection] = useState(false);
    // Change a chaque « Recommencer » : les formats gardent leur saisie dans
    // leur propre etat, on les remonte donc.
    const [tour, setTour] = useState(0);

    const liste = useMemo(
        () => questions.map((q, i) => ({ ...q, id: String(q?.id ?? '').trim() || `q${i + 1}` })),
        [questions],
    );
    useEffect(() => {
        if (index > liste.length - 1) setIndex(Math.max(0, liste.length - 1));
    }, [liste.length, index]);

    const Composant = composantDuLecteur(componentKey);
    const question = liste[index];
    const ecoute = skillType === 'listening';
    const passage = typeof content?.passage === 'string' ? content.passage.trim() : '';
    const titre = typeof content?.title === 'string' && content.title.trim() !== '' ? content.title.trim() : titreDuType;
    const texteAEcouter = String(question?.audio_text ?? content?.audio_text ?? '').trim();
    const son = String(question?.audio_url ?? content?.audio_url ?? '').trim();
    const image = String(question?.image_url ?? content?.image_url ?? '').trim();

    if (!question) {
        return (
            <div className="rounded-2xl border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
                L’aperçu apparaîtra dès la première question écrite.
            </div>
        );
    }

    function recommencer() {
        setReponses({});
        setCorrection(false);
        setIndex(0);
        setTour((t) => t + 1);
    }

    return (
        <div className="space-y-4" aria-label="Aperçu élève">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <div className="min-w-0">
                    <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Ce que verra l’élève</p>
                    <p className="truncate text-base font-bold">{titre}</p>
                </div>
                <div className="flex gap-1">
                    <Button type="button" variant="outline" size="sm" onClick={() => setCorrection((c) => !c)} aria-pressed={correction}>
                        {correction ? <EyeOff className="h-4 w-4" aria-hidden="true" /> : <Eye className="h-4 w-4" aria-hidden="true" />}
                        {correction ? 'Masquer la correction' : 'Voir la correction'}
                    </Button>
                    <Button type="button" variant="ghost" size="sm" onClick={recommencer} aria-label="Recommencer l’aperçu">
                        <RotateCcw className="h-4 w-4" aria-hidden="true" />
                    </Button>
                </div>
            </div>

            <div className="rounded-2xl border border-border bg-background p-4 shadow-sm sm:p-5">
                {passage !== '' && !ecoute && (
                    <div className="mb-4 max-h-64 overflow-y-auto rounded-xl border border-border bg-card p-4">
                        <p className="lecteur-passage whitespace-pre-line text-foreground">{passage}</p>
                    </div>
                )}

                {image !== '' && <img src={image} alt="" className="mb-4 max-h-56 w-auto rounded-xl object-contain" />}

                {son !== '' && componentKey !== 'dictation' && <audio src={son} controls className="mb-4 w-full" />}
                {son === '' && ecoute && texteAEcouter !== '' && (
                    <div className="mb-4 flex min-h-14 items-center gap-3 rounded-2xl border-[1.5px] border-primary/40 bg-primary/5 px-4 py-2">
                        <SpeakButton text={texteAEcouter} lang={lang} label="Écouter l’enregistrement" />
                    </div>
                )}
                {son === '' && ecoute && texteAEcouter === '' && (
                    <p className="mb-4 rounded-xl border border-amber-300 bg-amber-50 px-3 py-2 text-xs text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100">
                        Aucun son pour l’instant : ajoutez un enregistrement ou le texte que la voix de synthèse lira.
                    </p>
                )}

                <ExerciseErrorBoundary resetKey={`${question.id}-${tour}-${JSON.stringify(question).length}`} onSkip={() => setIndex((i) => Math.min(i + 1, liste.length - 1))}>
                    <Composant
                        key={`${question.id}-${tour}-${correction}`}
                        question={question}
                        onAnswer={(id: string, reponse: unknown) => setReponses((r) => ({ ...r, [id]: reponse }))}
                        selectedAnswer={reponses[question.id]}
                        lang={lang}
                        disabled={correction}
                        revealed={correction}
                    />
                </ExerciseErrorBoundary>

                {correction && typeof question.explanation === 'string' && question.explanation.trim() !== '' && (
                    <p className="mt-4 rounded-xl bg-muted/60 px-3 py-2 text-sm"><span className="font-semibold">Explication : </span>{question.explanation}</p>
                )}
            </div>

            <div className="flex items-center justify-between gap-2">
                <Button type="button" variant="outline" size="sm" disabled={index === 0} onClick={() => setIndex((i) => i - 1)}>
                    <ChevronLeft className="h-4 w-4" aria-hidden="true" /> Précédente
                </Button>
                <span className="text-sm text-muted-foreground" aria-live="polite">Question {index + 1} sur {liste.length}</span>
                <Button type="button" variant="outline" size="sm" disabled={index >= liste.length - 1} onClick={() => setIndex((i) => i + 1)}>
                    Suivante <ChevronRight className="h-4 w-4" aria-hidden="true" />
                </Button>
            </div>
        </div>
    );
}
