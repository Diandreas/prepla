import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { BookOpen, Headphones, PenLine, Puzzle, Sparkles } from 'lucide-react';
import { EssaiEntete } from '@/components/essai/essai-entete';

interface ExerciceCarte {
    id: number;
    titre: string;
    partie: string | null;
    niveau: string | null;
    competence: string | null;
    questions: number;
    officiel: boolean;
}

interface Examen {
    slug: string;
    nom: string;
    exercices: ExerciceCarte[];
}

interface Langue {
    slug: string;
    nom: string;
    examens: Examen[];
}

interface Props {
    langues: Langue[];
}

const NOMS: Record<string, string> = { english: 'Anglais', french: 'Français', german: 'Allemand' };

const COMPETENCES: Record<string, { nom: string; icone: typeof BookOpen }> = {
    reading: { nom: 'Lecture', icone: BookOpen },
    listening: { nom: 'Écoute', icone: Headphones },
    writing: { nom: 'Écrit', icone: PenLine },
    grammar: { nom: 'Grammaire', icone: Puzzle },
};

/**
 * Le choix d'un exercice a essayer, sans compte.
 *
 * Le visiteur choisit sa langue, puis l'examen qu'il vise, et lance un vrai
 * exercice. On dit ce qui est au format officiel de l'epreuve et ce qui est de
 * l'entrainement : promettre l'officiel partout serait mentir sur ce qu'il va
 * voir.
 */
export default function EssaiIndex({ langues }: Props) {
    const [choisie, setChoisie] = useState(langues[0]?.slug ?? '');
    const langue = langues.find((l) => l.slug === choisie);

    return (
        <>
            <Head title="Essayer sans compte" />
            <div className="min-h-screen bg-background">
                <EssaiEntete />

                <main className="mx-auto max-w-5xl px-4 pb-20 pt-10">
                    <section className="max-w-2xl">
                        <p className="inline-flex items-center gap-1.5 text-sm font-medium text-primary">
                            <Sparkles className="h-4 w-4" /> Sans inscription
                        </p>
                        <h1 className="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">
                            Essaie un vrai exercice, tout de suite.
                        </h1>
                        <p className="mt-3 text-muted-foreground">
                            Choisis ta langue et ton examen, réponds, et vois la correction avec ses
                            explications — exactement ce que reçoit un apprenant. Rien n'est enregistré :
                            pour suivre ta progression, il te faudra un compte.
                        </p>
                    </section>

                    {langues.length === 0 ? (
                        <p className="mt-12 text-muted-foreground">
                            Aucun exercice n'est ouvert à l'essai pour le moment.
                        </p>
                    ) : (
                        <>
                            <nav className="mt-8 flex flex-wrap gap-2" aria-label="Langue">
                                {langues.map((l) => (
                                    <Button
                                        key={l.slug}
                                        variant={l.slug === choisie ? 'default' : 'outline'}
                                        size="sm"
                                        className="rounded-full"
                                        aria-pressed={l.slug === choisie}
                                        onClick={() => setChoisie(l.slug)}
                                    >
                                        {NOMS[l.slug] ?? l.nom}
                                    </Button>
                                ))}
                            </nav>

                            <div className="mt-8 grid gap-6">
                                {langue?.examens.map((examen) => (
                                    <section key={examen.slug} className="rounded-2xl border bg-card p-5 sm:p-6">
                                        <h2 className="text-lg font-semibold">{examen.nom}</h2>
                                        <ul className="mt-4 grid gap-3 sm:grid-cols-2">
                                            {examen.exercices.map((ex) => {
                                                const competence = COMPETENCES[ex.competence ?? ''];
                                                const Icone = competence?.icone ?? BookOpen;

                                                return (
                                                    <li key={ex.id}>
                                                        <Link
                                                            href={route('essai.show', ex.id)}
                                                            className="group flex h-full flex-col rounded-xl border p-4 transition hover:-translate-y-0.5 hover:border-primary/50 hover:shadow-sm"
                                                        >
                                                            <div className="flex flex-wrap items-center gap-2 text-xs">
                                                                {ex.officiel ? (
                                                                    <Badge className="bg-emerald-600 hover:bg-emerald-600">Format officiel</Badge>
                                                                ) : (
                                                                    <Badge variant="secondary">Entraînement</Badge>
                                                                )}
                                                                {ex.niveau && <Badge variant="outline">{ex.niveau}</Badge>}
                                                                <span className="inline-flex items-center gap-1 text-muted-foreground">
                                                                    <Icone className="h-3.5 w-3.5" />
                                                                    {competence?.nom ?? ex.competence}
                                                                </span>
                                                            </div>
                                                            <p className="mt-2 font-medium leading-snug">{ex.titre}</p>
                                                            {ex.partie && <p className="mt-0.5 text-xs text-muted-foreground">{ex.partie}</p>}
                                                            <p className="mt-auto pt-3 text-sm font-medium text-primary">
                                                                {ex.questions} question{ex.questions > 1 ? 's' : ''} · Essayer →
                                                            </p>
                                                        </Link>
                                                    </li>
                                                );
                                            })}
                                        </ul>
                                    </section>
                                ))}
                            </div>
                        </>
                    )}
                </main>
            </div>
        </>
    );
}
