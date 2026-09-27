import { GapFill } from '@/components/exercises/gap-fill';
import { Matching } from '@/components/exercises/matching';
import { Mcq } from '@/components/exercises/mcq';
import { listAttempts, listPacks, newUuid, readOwner, saveAttempt, type OfflineAttempt, type OfflinePack } from '@/lib/offline/db';
import { isAnswerCorrect, scoreSession, type SessionScore } from '@/lib/scoring';
import { useCallback, useEffect, useState } from 'react';

// eslint-disable-next-line @typescript-eslint/no-explicit-any -- each renderer declares its own question and answer shape
const RENDERERS: Record<string, React.ComponentType<any>> = {
    mcq: Mcq,
    'gap-fill': GapFill,
    matching: Matching,
};

function useOnlineStatus(): boolean {
    const [online, setOnline] = useState(() => (typeof navigator === 'undefined' ? true : navigator.onLine));

    useEffect(() => {
        const update = () => setOnline(navigator.onLine);
        window.addEventListener('online', update);
        window.addEventListener('offline', update);
        return () => {
            window.removeEventListener('online', update);
            window.removeEventListener('offline', update);
        };
    }, []);

    return online;
}

function StatusBadge({ online }: { online: boolean }) {
    return (
        <span
            className={`inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold ${
                online
                    ? 'bg-emerald-100 text-emerald-900 dark:bg-emerald-900/50 dark:text-emerald-100'
                    : 'bg-amber-100 text-amber-900 dark:bg-amber-900/50 dark:text-amber-100'
            }`}
        >
            <span className={`h-2 w-2 rounded-full ${online ? 'bg-emerald-500' : 'bg-amber-500'}`} aria-hidden="true" />
            {online ? 'En ligne' : 'Hors ligne'}
        </span>
    );
}

function SessionRunner({ pack, onFinish, onQuit }: { pack: OfflinePack; onFinish: (score: SessionScore) => void; onQuit: () => void }) {
    const [index, setIndex] = useState(0);
    const [answers, setAnswers] = useState<Record<string, string>>({});
    const [checked, setChecked] = useState(false);

    const question = pack.questions[index];
    const Renderer = RENDERERS[pack.format] ?? Mcq;
    const answer = answers[question.id];
    const isLast = index === pack.questions.length - 1;
    const hasAnswer = typeof answer === 'string' && answer.trim() !== '';
    const correct = checked ? isAnswerCorrect(question, answer) : null;

    const advance = () => {
        if (isLast) {
            onFinish(scoreSession(pack.questions, answers));
            return;
        }
        setIndex((previous) => previous + 1);
        setChecked(false);
    };

    return (
        <section className="space-y-5">
            <div className="flex items-center justify-between gap-3">
                <button type="button" onClick={onQuit} className="text-muted-foreground text-sm font-semibold underline">
                    Quitter
                </button>
                <span className="text-muted-foreground text-sm font-bold">
                    {index + 1} / {pack.questions.length}
                </span>
            </div>

            <div className="bg-muted h-2 w-full overflow-hidden rounded-full">
                <div
                    className="bg-primary h-full rounded-full transition-all"
                    style={{ width: `${((index + (checked ? 1 : 0)) / pack.questions.length) * 100}%` }}
                />
            </div>

            {pack.passage && <p className="border-border bg-muted/30 rounded-2xl border p-4 text-sm leading-relaxed">{pack.passage}</p>}
            {pack.instructions && <p className="text-muted-foreground text-sm font-medium">{pack.instructions}</p>}

            <div className="border-border bg-card rounded-2xl border p-4">
                <Renderer
                    key={question.id}
                    question={question}
                    onAnswer={(_questionId: string, value: string) => setAnswers((previous) => ({ ...previous, [question.id]: value }))}
                    selectedAnswer={answer}
                    disabled={checked}
                />
            </div>

            {checked && (
                <div
                    role="status"
                    className={`rounded-2xl border p-4 text-sm leading-relaxed ${
                        correct
                            ? 'border-emerald-300 bg-emerald-50 text-emerald-950 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-100'
                            : 'border-red-300 bg-red-50 text-red-950 dark:border-red-800 dark:bg-red-950/40 dark:text-red-100'
                    }`}
                >
                    <p className="font-bold">{correct ? 'Bonne réponse' : `Réponse attendue : ${question.correct_answer ?? '—'}`}</p>
                    {question.explanation && <p className="mt-1">{question.explanation}</p>}
                </div>
            )}

            <button
                type="button"
                disabled={!hasAnswer}
                onClick={() => (checked ? advance() : setChecked(true))}
                className="bg-primary w-full rounded-2xl px-4 py-3 text-sm font-bold text-white disabled:opacity-50"
            >
                {checked ? (isLast ? 'Voir mon résultat' : 'Question suivante') : 'Vérifier'}
            </button>
        </section>
    );
}

function ResultView({ pack, score, onBack }: { pack: OfflinePack; score: SessionScore; onBack: () => void }) {
    return (
        <section className="space-y-5">
            <div className="border-border bg-card rounded-3xl border p-6 text-center">
                <p className="text-muted-foreground text-xs font-black tracking-widest uppercase">Score local</p>
                <p className="text-foreground mt-2 text-4xl font-black">
                    {score.score} / {score.total}
                </p>
                <p className="text-muted-foreground mt-3 text-sm leading-relaxed">
                    Enregistré sur cet appareil. C'est un repère d'entraînement : PrepLa confirmera ce résultat et tes XP lors d'une prochaine
                    connexion.
                </p>
            </div>

            <ol className="space-y-3">
                {score.details.map((detail, position) => {
                    const question = pack.questions[position];
                    return (
                        <li
                            key={detail.question_id}
                            className={`rounded-2xl border p-4 text-sm leading-relaxed ${
                                detail.correct
                                    ? 'border-emerald-300 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-950/30'
                                    : 'border-red-300 bg-red-50 dark:border-red-800 dark:bg-red-950/30'
                            }`}
                        >
                            <p className="font-bold">
                                {position + 1}. {question?.text}
                            </p>
                            <p className="mt-1">
                                Ta réponse : <strong>{String(detail.given_answer ?? '—')}</strong>
                                {!detail.correct && (
                                    <>
                                        {' · '}
                                        Attendu : <strong>{String(detail.correct_answer ?? '—')}</strong>
                                    </>
                                )}
                            </p>
                            {detail.explanation && <p className="text-muted-foreground mt-1">{detail.explanation}</p>}
                        </li>
                    );
                })}
            </ol>

            <button type="button" onClick={onBack} className="border-border w-full rounded-2xl border px-4 py-3 text-sm font-bold">
                Retour à mes téléchargements
            </button>
        </section>
    );
}

export function OfflineApp() {
    const online = useOnlineStatus();
    const [owner, setOwner] = useState<{ id: number; name: string } | null>(null);
    const [packs, setPacks] = useState<OfflinePack[]>([]);
    const [attempts, setAttempts] = useState<OfflineAttempt[]>([]);
    const [storageError, setStorageError] = useState<string | null>(null);
    const [loading, setLoading] = useState(true);
    const [activePack, setActivePack] = useState<OfflinePack | null>(null);
    const [result, setResult] = useState<SessionScore | null>(null);

    const load = useCallback(async () => {
        try {
            const localOwner = await readOwner();
            setOwner(localOwner);
            if (!localOwner) {
                setPacks([]);
                setAttempts([]);
                return;
            }
            setPacks(await listPacks(localOwner.id));
            setAttempts(await listAttempts(localOwner.id));
        } catch {
            // Private windows and blocked site data both land here.
            setStorageError(
                'Le stockage local est indisponible sur cet appareil (navigation privée ou données de site bloquées). Les téléchargements ne peuvent pas être lus.',
            );
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        void load();
    }, [load]);

    const finishSession = async (pack: OfflinePack, score: SessionScore) => {
        const attempt: OfflineAttempt = {
            uuid: newUuid(),
            user_id: pack.user_id,
            pack_key: pack.key,
            pack_id: pack.pack_id,
            exercise_id: pack.exercise_id,
            answers: Object.fromEntries(score.details.map((detail) => [detail.question_id, String(detail.given_answer ?? '')])),
            score: score.score,
            total: score.total,
            accuracy: score.accuracy,
            finished_at: new Date().toISOString(),
            synced: false,
        };

        try {
            await saveAttempt(attempt);
        } catch {
            setStorageError("Ta séance n'a pas pu être enregistrée sur cet appareil. Note ton score avant de fermer la page.");
        }
        setResult(score);
        setAttempts((previous) => [...previous, attempt]);
    };

    const pending = attempts.filter((attempt) => !attempt.synced).length;

    return (
        <div className="bg-background text-foreground min-h-dvh">
            <div className="mx-auto w-full max-w-2xl px-4 py-6 pb-16">
                <header className="mb-6 flex items-start justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-black tracking-tight">Mes téléchargements</h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            {owner ? `Contenu de ${owner.name}, disponible sans connexion.` : 'Espace hors ligne de PrepLa.'}
                        </p>
                    </div>
                    <StatusBadge online={online} />
                </header>

                {storageError && (
                    <p
                        role="alert"
                        className="mb-5 rounded-2xl border border-red-300 bg-red-50 p-4 text-sm font-medium text-red-900 dark:border-red-800 dark:bg-red-950/40 dark:text-red-100"
                    >
                        {storageError}
                    </p>
                )}

                {loading ? (
                    <p className="text-muted-foreground text-sm">Lecture des données locales…</p>
                ) : activePack && result ? (
                    <ResultView
                        pack={activePack}
                        score={result}
                        onBack={() => {
                            setResult(null);
                            setActivePack(null);
                        }}
                    />
                ) : activePack ? (
                    <SessionRunner pack={activePack} onFinish={(score) => void finishSession(activePack, score)} onQuit={() => setActivePack(null)} />
                ) : (
                    <section className="space-y-4">
                        {packs.length === 0 ? (
                            <div className="border-border bg-card rounded-3xl border p-6 text-center">
                                <p className="text-base font-bold">Aucun pack sur cet appareil</p>
                                <p className="text-muted-foreground mt-2 text-sm leading-relaxed">
                                    Connecte-toi à PrepLa, ouvre un format marqué « Prêt sans IA » et choisis « Télécharger pour hors ligne ». Le pack
                                    restera disponible ici, même en mode avion.
                                </p>
                                {online && (
                                    <a href="/practice" className="bg-primary mt-4 inline-block rounded-2xl px-4 py-3 text-sm font-bold text-white">
                                        Aller à l'entraînement
                                    </a>
                                )}
                            </div>
                        ) : (
                            packs.map((pack) => (
                                <article key={pack.key} className="border-border bg-card rounded-2xl border p-4">
                                    <p className="text-muted-foreground text-xs font-bold">
                                        {pack.exam_name} · {pack.level} · {pack.format_label}
                                    </p>
                                    <h2 className="mt-1 text-base font-extrabold">{pack.title}</h2>
                                    <p className="text-muted-foreground mt-1 text-sm leading-relaxed">{pack.description}</p>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setResult(null);
                                            setActivePack(pack);
                                        }}
                                        className="bg-primary mt-3 w-full rounded-2xl px-4 py-3 text-sm font-bold text-white"
                                    >
                                        Commencer ({pack.questions.length} questions)
                                    </button>
                                </article>
                            ))
                        )}

                        {attempts.length > 0 && (
                            <p className="text-muted-foreground border-border rounded-2xl border border-dashed p-4 text-sm leading-relaxed">
                                {attempts.length} séance{attempts.length > 1 ? 's' : ''} terminée{attempts.length > 1 ? 's' : ''} sur cet appareil,
                                dont {pending} en attente d'envoi à PrepLa. L'envoi automatique n'est pas encore en place : tes réponses restent
                                conservées ici en attendant.
                            </p>
                        )}
                    </section>
                )}
            </div>
        </div>
    );
}
