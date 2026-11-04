import { Head, Link } from '@inertiajs/react';
import { Check, Lightbulb, X } from 'lucide-react';
import { Fragment, type ReactNode } from 'react';
import { EssaiEntete } from '@/components/essai/essai-entete';
import { Badge } from '@/components/ui/badge';
import { useApparenceEnregistree } from '@/hooks/use-appearance';

interface QuestionCorrigee {
    id: string | null;
    texte: string | null;
    options: unknown[];
    ta_reponse: unknown;
    bonne_reponse: unknown;
    juste: boolean;
    explication: string | null;
}

interface Props {
    exercise: { id: number; titre: string; examen: string; niveau: string | null; officiel: boolean; langue?: string | null };
    score: { justes: number; total: number; pourcentage: number };
    questions: QuestionCorrigee[];
}

/** Le texte d'une option, quelle que soit sa forme dans le contenu. */
function texteOption(option: unknown): string {
    if (option && typeof option === 'object') {
        const o = option as Record<string, unknown>;
        return String(o.text ?? o.label ?? Object.values(o)[0] ?? '');
    }
    return String(option ?? '');
}

/**
 * Une reponse lisible : « B » devient « B — le texte de l'option », un
 * formulaire devient « champ : valeur ». Une lettre seule ne dit rien a quelqu'un
 * qui relit sa copie.
 */
function lisible(valeur: unknown, options: unknown[]): string {
    if (valeur === null || valeur === undefined || valeur === '') return '—';
    if (Array.isArray(valeur)) return valeur.map((v) => lisible(v, options)).join(' · ');
    if (typeof valeur === 'object') {
        return Object.entries(valeur as Record<string, unknown>)
            .map(([cle, v]) => `${cle} : ${String(v)}`)
            .join(' · ');
    }
    const texte = String(valeur).trim();
    if (/^[A-Z]$/.test(texte) && options.length > 0) {
        const option = options[texte.charCodeAt(0) - 65];
        if (option !== undefined) return `${texte} — ${texteOption(option)}`;
    }
    return texte;
}

const BLANC = /\(\d+\)_{2,}|_{2,}/;

/**
 * La phrase a trous reconstituee, le mot juste surligne a la place du blanc.
 * Relire « Ich ___ zwanzig » ne montre pas la phrase a retenir ; « Ich bin
 * zwanzig », si. Rien si la phrase n'a pas de blanc ou si la reponse manque.
 */
function phraseReconstituee(texte: string | null, bonne: unknown, options: unknown[]): ReactNode | null {
    if (!texte || !BLANC.test(texte)) return null;
    const morceaux = texte.split(BLANC);
    const blancs = morceaux.length - 1;

    let reponses: string[];
    if (Array.isArray(bonne)) {
        // Un seul blanc : le tableau liste des variantes acceptees, on montre la premiere.
        reponses = blancs === 1 ? [String(bonne[0] ?? '')] : bonne.map((v) => String(v ?? ''));
    } else if (bonne && typeof bonne === 'object') {
        const carte = bonne as Record<string, unknown>;
        reponses = Object.keys(carte)
            .sort((a, b) => Number(a) - Number(b))
            .map((cle) => String(carte[cle] ?? ''));
    } else {
        reponses = [String(bonne ?? '')];
    }
    reponses = reponses.map((r) => {
        const t = r.trim();
        if (/^[A-Z]$/.test(t) && options.length > 0 && options[t.charCodeAt(0) - 65] !== undefined) {
            return texteOption(options[t.charCodeAt(0) - 65]);
        }
        return t;
    });
    if (reponses.length < blancs || reponses.slice(0, blancs).some((r) => r === '')) return null;

    return morceaux.map((morceau, i) => (
        <Fragment key={i}>
            {morceau}
            {i < blancs && (
                <mark className="rounded-md bg-emerald-100 px-1.5 font-semibold text-emerald-900 dark:bg-emerald-900/60 dark:text-emerald-50">
                    {reponses[i]}
                </mark>
            )}
        </Fragment>
    ));
}

/** L'anneau de score : vert si c'est reussi, orange sinon, jamais la couleur seule. */
function AnneauDeScore({ justes, total, reussi }: { justes: number; total: number; reussi: boolean }) {
    const rayon = 52;
    const tour = 2 * Math.PI * rayon;
    const part = total > 0 ? justes / total : 0;

    return (
        <div className="relative h-36 w-36 shrink-0" role="img" aria-label={`${justes} bonnes réponses sur ${total}`}>
            <svg viewBox="0 0 120 120" className="h-full w-full -rotate-90" aria-hidden="true">
                <circle cx="60" cy="60" r={rayon} fill="none" strokeWidth="10" className="stroke-muted" />
                <circle
                    cx="60"
                    cy="60"
                    r={rayon}
                    fill="none"
                    strokeWidth="10"
                    strokeLinecap="round"
                    strokeDasharray={tour}
                    strokeDashoffset={tour * (1 - part)}
                    className={reussi ? 'stroke-emerald-600 dark:stroke-emerald-400' : 'stroke-amber-600 dark:stroke-amber-400'}
                />
            </svg>
            <div aria-hidden="true" className="absolute inset-0 flex flex-col items-center justify-center">
                <span className="font-['Plus_Jakarta_Sans',system-ui,sans-serif] text-4xl font-extrabold tabular-nums leading-none">{justes}</span>
                <span className="mt-1 text-[0.875rem] font-semibold text-muted-foreground">sur {total}</span>
            </div>
        </div>
    );
}

export default function EssaiResultat({ exercise, score, questions }: Props) {
    useApparenceEnregistree();
    const reussi = score.total > 0 && score.justes / score.total >= 0.6;
    const erreurs = Math.max(0, score.total - score.justes);
    // « Essayer un autre exercice » rouvre la liste dans la langue de l'exercice.
    const autreExercice = exercise.langue ? route('essai.index', { langue: exercise.langue }) : route('essai.index');

    return (
        <>
            <Head title={`Résultat — ${exercise.titre}`} />
            {/* Style en ligne : `.learning-canvas` (hors couche) ecrase `min-h-screen`. */}
            <div className="palette-lecteur learning-canvas" style={{ minHeight: '100dvh' }}>
                <EssaiEntete />

                <main className="mx-auto max-w-3xl px-4 pt-8 pb-24 sm:pt-10">
                    <section className="rounded-3xl border-[1.5px] border-border bg-card p-6 sm:p-8">
                        <div className="flex flex-wrap items-center gap-2 text-[0.8125rem]">
                            {exercise.officiel ? (
                                <Badge className="bg-emerald-700 text-white hover:bg-emerald-700">Format officiel</Badge>
                            ) : (
                                <Badge variant="secondary">Entraînement</Badge>
                            )}
                            <span className="text-muted-foreground">
                                {exercise.examen}
                                {exercise.niveau ? ` · ${exercise.niveau}` : ''}
                            </span>
                        </div>
                        <h1 className="mt-3 font-['Plus_Jakarta_Sans',system-ui,sans-serif] text-2xl font-extrabold leading-tight tracking-tight sm:text-3xl">
                            {exercise.titre}
                        </h1>

                        <div className="mt-6 flex flex-col items-center gap-5 sm:flex-row sm:items-center sm:gap-7">
                            <AnneauDeScore justes={score.justes} total={score.total} reussi={reussi} />
                            <div className="text-center sm:text-left">
                                <p className="text-lg font-bold">
                                    {erreurs === 0 ? 'Aucune erreur.' : erreurs === 1 ? '1 erreur à revoir' : `${erreurs} erreurs à revoir`}
                                </p>
                                <p className="mt-1.5 text-[0.9375rem] leading-relaxed text-muted-foreground">
                                    {reussi
                                        ? 'Belle base. Un parcours adapté te ferait viser plus haut, plus vite.'
                                        : 'Chaque erreur ci-dessous a son explication. C’est exactement là qu’un parcours adapté fait la différence.'}
                                </p>
                            </div>
                        </div>

                        <div className="mt-7 flex flex-col gap-3 sm:flex-row">
                            <Link
                                href={route('register')}
                                className="inline-flex min-h-13 items-center justify-center rounded-full bg-[var(--essai)] px-6 text-base font-extrabold text-[var(--sur-essai)] transition hover:brightness-105 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
                            >
                                Créer mon compte — 7 jours gratuits
                            </Link>
                            <Link
                                href={autreExercice}
                                className="inline-flex min-h-12 items-center justify-center rounded-full border-[1.5px] border-primary/50 px-6 text-[0.9375rem] font-bold text-foreground transition-colors hover:bg-primary/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
                            >
                                Essayer un autre exercice
                            </Link>
                        </div>
                        <p className="mt-3 text-[0.8125rem] text-muted-foreground">
                            Avec un compte : test de niveau, parcours construit pour ton examen, suivi de tes erreurs.
                        </p>
                    </section>

                    <h2 className="mt-10 font-['Plus_Jakarta_Sans',system-ui,sans-serif] text-xl font-extrabold">
                        Ta <em className="font-['Cormorant_Garamond',Georgia,serif] text-2xl font-bold italic">correction</em>, question par question
                    </h2>
                    <ol className="mt-4 space-y-4">
                        {questions.map((q, i) => {
                            const phrase = q.bonne_reponse === null ? null : phraseReconstituee(q.texte, q.bonne_reponse, q.options);
                            return (
                                <li
                                    key={q.id ?? i}
                                    className={`rounded-2xl border-[1.5px] bg-card p-4 sm:p-5 ${q.juste ? 'border-emerald-300 dark:border-emerald-800' : 'border-red-300 dark:border-red-900'}`}
                                >
                                    <div className="flex items-start gap-3">
                                        <span
                                            className={`mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-white ${q.juste ? 'bg-emerald-700' : 'bg-red-700'}`}
                                        >
                                            {q.juste ? <Check aria-hidden="true" className="h-4 w-4" /> : <X aria-hidden="true" className="h-4 w-4" />}
                                            <span className="sr-only">{q.juste ? 'Juste' : 'Faux'}</span>
                                        </span>
                                        <div className="min-w-0 flex-1">
                                            <p className="text-[0.8125rem] font-semibold text-muted-foreground">Question {i + 1}</p>
                                            {q.texte && <p className="mt-0.5 text-base font-semibold leading-relaxed whitespace-pre-line">{q.texte}</p>}
                                            <dl className="mt-2 grid gap-1 text-[0.9375rem]">
                                                <div className="flex flex-wrap gap-x-2">
                                                    <dt className="text-muted-foreground">Ta réponse :</dt>
                                                    <dd className={q.juste ? '' : 'line-through decoration-red-500 decoration-2'}>{lisible(q.ta_reponse, q.options)}</dd>
                                                </div>
                                                {!q.juste && (
                                                    <div className="flex flex-wrap gap-x-2">
                                                        <dt className="text-muted-foreground">Bonne réponse :</dt>
                                                        <dd className="font-semibold text-emerald-800 dark:text-emerald-300">{lisible(q.bonne_reponse, q.options)}</dd>
                                                    </div>
                                                )}
                                            </dl>
                                            {phrase && (
                                                <p className="mt-3 rounded-xl border-[1.5px] border-emerald-200 bg-emerald-50/60 px-3 py-2 text-base leading-relaxed dark:border-emerald-900 dark:bg-emerald-950/30">
                                                    <span className="sr-only">La phrase complète : </span>
                                                    {phrase}
                                                </p>
                                            )}
                                            {q.explication && (
                                                <div className="mt-3 rounded-xl bg-muted/60 px-4 py-3">
                                                    <p className="flex items-center gap-1.5 text-[0.8125rem] font-bold text-foreground">
                                                        <Lightbulb aria-hidden="true" className="h-4 w-4 text-amber-600 dark:text-amber-400" />
                                                        Pourquoi
                                                    </p>
                                                    <p className="mt-1 text-[0.9375rem] leading-relaxed text-foreground">{q.explication}</p>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </li>
                            );
                        })}
                    </ol>

                    <div className="mt-10 rounded-3xl border-[1.5px] border-primary/20 bg-primary/5 p-6 text-center">
                        <p className="text-lg font-bold">Tu veux continuer sur ton examen ?</p>
                        <p className="mt-1 text-[0.9375rem] text-muted-foreground">
                            Un test de niveau, puis des exercices choisis pour toi, au format de ton épreuve.
                        </p>
                        <Link
                            href={route('register')}
                            className="mt-4 inline-flex min-h-12 items-center justify-center rounded-full bg-primary px-6 text-[0.9375rem] font-bold text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
                        >
                            Commencer gratuitement
                        </Link>
                    </div>
                </main>
            </div>
        </>
    );
}
