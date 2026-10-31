import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent } from '@/components/ui/card';
import { Check, Minus, X } from 'lucide-react';
import { competence, dateHeure, duree } from '@/lib/suivi';

interface Question {
    id: string;
    texte: string | null;
    options: unknown[];
    reponse: unknown;
    bonne_reponse: unknown;
    juste: boolean | null;
    precision: number | null;
    explication: string | null;
}

interface Tentative {
    id: number;
    date: string | null;
    exercice: string;
    examen: string | null;
    niveau: string | null;
    partie: string | null;
    competence: string | null;
    justes: number | null;
    total: number | null;
    precision: number | null;
    temps_secondes: number;
    exercice_disponible: boolean;
    consigne: string | null;
    texte: string | null;
    questions: Question[];
}

function texteOption(option: unknown): string {
    if (option && typeof option === 'object') {
        const o = option as Record<string, unknown>;
        return String(o.text ?? o.label ?? Object.values(o)[0] ?? '');
    }
    return String(option ?? '');
}

/** « B » devient « B — le texte de l'option » ; un formulaire, « champ : valeur ». */
function lisible(valeur: unknown, options: unknown[]): string {
    if (valeur === null || valeur === undefined || valeur === '') return '—';
    if (Array.isArray(valeur)) return valeur.map((v) => lisible(v, options)).join(' · ');
    if (typeof valeur === 'object') {
        return Object.entries(valeur as Record<string, unknown>).map(([k, v]) => `${k} : ${String(v)}`).join(' · ');
    }
    const texte = String(valeur).trim();
    if (/^[A-Z]$/.test(texte) && options.length > 0) {
        const option = options[texte.charCodeAt(0) - 65];
        if (option !== undefined) return `${texte} — ${texteOption(option)}`;
    }
    return texte;
}

/**
 * Une copie en detail, cote centre : ce que l'apprenant a repondu a chaque
 * question, la bonne reponse, et l'explication qu'il a recue. C'est ce qu'un
 * enseignant regarde pour comprendre une erreur, et ce qu'il montre a un parent.
 */
export default function TentativeShow({ student, tentative }: { student: { id: number; name: string }; tentative: Tentative }) {
    return (
        <AppLayout>
            <Head title={`${student.name} — ${tentative.exercice}`} />
            <div className="mx-auto w-full max-w-3xl space-y-5 p-4 md:p-6">
                <div>
                    <Link href={route('center.students.show', student.id)} className="text-sm text-muted-foreground hover:text-foreground">
                        ← {student.name}
                    </Link>
                    <h1 className="mt-1 text-xl font-bold tracking-tight">{tentative.exercice}</h1>
                    <p className="text-sm text-muted-foreground">
                        {[tentative.examen, tentative.niveau, tentative.partie, competence(tentative.competence)].filter(Boolean).join(' · ')}
                    </p>
                </div>

                <div className="grid gap-3 sm:grid-cols-3">
                    <Card><CardContent className="p-4"><p className="font-semibold">{dateHeure(tentative.date)}</p><p className="text-sm text-muted-foreground">Fait le</p></CardContent></Card>
                    <Card><CardContent className="p-4"><p className="font-semibold">{duree(tentative.temps_secondes)}</p><p className="text-sm text-muted-foreground">Temps passé</p></CardContent></Card>
                    <Card>
                        <CardContent className="p-4">
                            <p className="font-semibold">
                                {tentative.total !== null ? `${tentative.justes} / ${tentative.total}` : '—'}
                                {tentative.precision !== null && <span className="text-muted-foreground"> · {Math.round(tentative.precision)}%</span>}
                            </p>
                            <p className="text-sm text-muted-foreground">Résultat</p>
                        </CardContent>
                    </Card>
                </div>

                {(tentative.consigne || tentative.texte) && (
                    <Card>
                        <CardContent className="space-y-2 p-4 text-sm">
                            {tentative.consigne && <p className="italic text-muted-foreground">{tentative.consigne}</p>}
                            {tentative.texte && (
                                <details>
                                    <summary className="cursor-pointer font-medium text-primary">Voir le texte de l'exercice</summary>
                                    <p className="mt-2 whitespace-pre-wrap leading-relaxed">{tentative.texte}</p>
                                </details>
                            )}
                        </CardContent>
                    </Card>
                )}

                <ol className="space-y-3">
                    {tentative.questions.length === 0 && (
                        <p className="text-sm text-muted-foreground">
                            {tentative.exercice_disponible
                                ? 'Aucune réponse n’a été enregistrée pour cette tentative.'
                                : 'Cet exercice a été retiré depuis : le détail des questions n’est plus disponible.'}
                        </p>
                    )}
                    {tentative.questions.map((q, i) => {
                        const etat = q.juste === null ? 'neutre' : q.juste ? 'juste' : 'faux';
                        return (
                            <li
                                key={q.id || i}
                                className={`rounded-xl border p-4 ${etat === 'juste' ? 'border-emerald-300/70 dark:border-emerald-800' : etat === 'faux' ? 'border-rose-300/70 dark:border-rose-900' : 'border-border'}`}
                            >
                                <div className="flex items-start gap-3">
                                    <span
                                        className={`mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-white ${etat === 'juste' ? 'bg-emerald-500' : etat === 'faux' ? 'bg-rose-500' : 'bg-muted-foreground'}`}
                                        aria-label={etat === 'juste' ? 'Juste' : etat === 'faux' ? 'Faux' : 'Non corrigé'}
                                    >
                                        {etat === 'juste' ? <Check className="h-4 w-4" /> : etat === 'faux' ? <X className="h-4 w-4" /> : <Minus className="h-4 w-4" />}
                                    </span>
                                    <div className="min-w-0 flex-1 text-sm">
                                        {q.texte && <p className="font-medium">{q.texte}</p>}
                                        <dl className="mt-2 grid gap-1">
                                            <div className="flex flex-wrap gap-x-2">
                                                <dt className="text-muted-foreground">Sa réponse :</dt>
                                                <dd className="whitespace-pre-wrap">{lisible(q.reponse, q.options)}</dd>
                                            </div>
                                            {etat === 'faux' && q.bonne_reponse !== null && (
                                                <div className="flex flex-wrap gap-x-2">
                                                    <dt className="text-muted-foreground">Bonne réponse :</dt>
                                                    <dd className="font-medium text-emerald-700 dark:text-emerald-400">{lisible(q.bonne_reponse, q.options)}</dd>
                                                </div>
                                            )}
                                        </dl>
                                        {q.explication && <p className="mt-2 text-muted-foreground">{q.explication}</p>}
                                    </div>
                                </div>
                            </li>
                        );
                    })}
                </ol>
            </div>
        </AppLayout>
    );
}
