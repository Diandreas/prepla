import { Head, Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Check, X } from 'lucide-react';
import { EssaiEntete } from '@/components/essai/essai-entete';

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
    exercise: { id: number; titre: string; examen: string; niveau: string | null; officiel: boolean };
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

export default function EssaiResultat({ exercise, score, questions }: Props) {
    const reussi = score.total > 0 && score.justes / score.total >= 0.6;

    return (
        <>
            <Head title={`Résultat — ${exercise.titre}`} />
            <div className="min-h-screen bg-background">
                <EssaiEntete />

                <main className="mx-auto max-w-3xl px-4 pb-24 pt-10">
                    <section className="rounded-2xl border bg-card p-6 text-center sm:p-8">
                        <div className="flex flex-wrap items-center justify-center gap-2 text-xs">
                            {exercise.officiel ? (
                                <Badge className="bg-emerald-600 hover:bg-emerald-600">Format officiel</Badge>
                            ) : (
                                <Badge variant="secondary">Entraînement</Badge>
                            )}
                            <span className="text-muted-foreground">
                                {exercise.examen}
                                {exercise.niveau ? ` · ${exercise.niveau}` : ''}
                            </span>
                        </div>
                        <p className="mt-4 text-5xl font-bold tabular-nums">
                            {score.justes}
                            <span className="text-2xl text-muted-foreground"> / {score.total}</span>
                        </p>
                        <p className="mt-2 text-muted-foreground">
                            {reussi
                                ? 'Belle base. Un parcours adapté te ferait viser plus haut, plus vite.'
                                : 'Chaque erreur ci-dessous a son explication. C’est exactement là qu’un parcours adapté fait la différence.'}
                        </p>

                        <div className="mt-6 flex flex-col items-center justify-center gap-3 sm:flex-row">
                            <Button asChild size="lg" className="w-full sm:w-auto">
                                <Link href={route('register')}>Créer mon compte — 7 jours gratuits</Link>
                            </Button>
                            <Button asChild size="lg" variant="outline" className="w-full sm:w-auto">
                                <Link href={route('essai.index')}>Essayer un autre exercice</Link>
                            </Button>
                        </div>
                        <p className="mt-3 text-xs text-muted-foreground">
                            Avec un compte : test de niveau, parcours construit pour ton examen, suivi de tes erreurs.
                        </p>
                    </section>

                    <h2 className="mt-10 text-lg font-semibold">La correction, question par question</h2>
                    <ol className="mt-4 space-y-4">
                        {questions.map((q, i) => (
                            <li
                                key={q.id ?? i}
                                className={`rounded-xl border p-4 ${q.juste ? 'border-emerald-300/70 dark:border-emerald-800' : 'border-red-300/70 dark:border-red-900'}`}
                            >
                                <div className="flex items-start gap-3">
                                    <span
                                        className={`mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-white ${q.juste ? 'bg-emerald-500' : 'bg-red-500'}`}
                                        aria-label={q.juste ? 'Juste' : 'Faux'}
                                    >
                                        {q.juste ? <Check className="h-4 w-4" /> : <X className="h-4 w-4" />}
                                    </span>
                                    <div className="min-w-0 flex-1">
                                        {q.texte && <p className="font-medium">{q.texte}</p>}
                                        <dl className="mt-2 grid gap-1 text-sm">
                                            <div className="flex flex-wrap gap-x-2">
                                                <dt className="text-muted-foreground">Ta réponse :</dt>
                                                <dd className={q.juste ? '' : 'line-through decoration-red-400'}>{lisible(q.ta_reponse, q.options)}</dd>
                                            </div>
                                            {!q.juste && (
                                                <div className="flex flex-wrap gap-x-2">
                                                    <dt className="text-muted-foreground">Bonne réponse :</dt>
                                                    <dd className="font-medium text-emerald-700 dark:text-emerald-400">{lisible(q.bonne_reponse, q.options)}</dd>
                                                </div>
                                            )}
                                        </dl>
                                        {q.explication && <p className="mt-2 text-sm text-muted-foreground">{q.explication}</p>}
                                    </div>
                                </div>
                            </li>
                        ))}
                    </ol>

                    <div className="mt-10 rounded-2xl bg-primary/5 p-6 text-center">
                        <p className="font-medium">Tu veux continuer sur ton examen ?</p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Un test de niveau, puis des exercices choisis pour toi, au format de ton épreuve.
                        </p>
                        <Button asChild className="mt-4">
                            <Link href={route('register')}>Commencer gratuitement</Link>
                        </Button>
                    </div>
                </main>
            </div>
        </>
    );
}
