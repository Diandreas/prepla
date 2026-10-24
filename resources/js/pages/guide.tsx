import { ArtIcon } from '@/components/art-icon';
import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { ArrowRight, CheckCircle2 } from 'lucide-react';

/**
 * « Comment apprendre ici » : la page qui manquait.
 *
 * La plateforme explique chaque écran une fois qu'on y est, mais personne ne
 * disait COMMENT s'y prendre. Un apprenant seul devant une liste d'exercices
 * refait ce qu'il réussit déjà et évite ce qui résiste.
 *
 * Tout tient en une page, et la dernière carte est la SIENNE.
 */
interface Portrait {
    prenom: string | null;
    niveau: string | null;
    examen_vise: string | null;
    jours_avant_examen: number | null;
    serie_en_cours: number;
    activite: { exercices_en_tout: number };
    prochaine_etape: { quoi: string; pourquoi: string; ou: string | null };
}

const ETAPES = [
    {
        icon: 'book',
        tone: 'blue' as const,
        titre: 'Commence par ton parcours',
        texte: "Il t'emmène dans l'ordre : une leçon courte, puis des exercices dessus. Tu n'as pas à choisir quoi faire — c'est fait pour ça.",
    },
    {
        icon: 'puzzle',
        tone: 'mint' as const,
        titre: "Entraîne-toi au format de l'examen",
        texte: "Dans « Pratiquer », chaque partie porte le nom qu'elle a le jour J. Fais-les une par une : le jour de l'examen, rien ne te surprendra.",
    },
    {
        icon: 'review',
        tone: 'rose' as const,
        titre: 'Reprends tes erreurs, toujours',
        texte: "C'est le geste qui fait progresser le plus vite, et celui qu'on saute le plus souvent. Dix minutes sur tes erreurs valent une heure d'exercices neufs.",
    },
    {
        icon: 'speaking',
        tone: 'amber' as const,
        titre: 'Parle à voix haute, même seul',
        texte: "Lire une réponse dans sa tête ne prépare pas à la dire. Enregistre-toi, écoute-toi, recommence. C'est inconfortable deux fois, puis ça devient normal.",
    },
];

const REGLES = [
    'Mieux vaut vingt minutes chaque jour qu’une matinée le dimanche.',
    'Un exercice raté t’apprend plus qu’un exercice réussi : ne le fuis pas.',
    'Si tu bloques, demande au coach — il sait où tu en es.',
    'Fais une épreuve blanche complète avant le jour J, en conditions réelles.',
];

export default function Guide({ portrait }: { portrait: Portrait | null }) {
    const prenom = portrait?.prenom;
    const debutant = (portrait?.activite?.exercices_en_tout ?? 0) === 0;

    return (
        <AppLayout>
            <Head title="Comment apprendre ici" />
            <div className="mx-auto w-full max-w-3xl space-y-7 px-4 py-6 sm:px-6 sm:py-8">
                <section className="studio-hero border-border relative overflow-hidden rounded-3xl border p-5 sm:p-7">
                    <p className="studio-kicker text-muted-foreground mb-3 text-xs font-bold tracking-widest uppercase">
                        Le mode d’emploi
                    </p>
                    <h1 className="text-foreground text-2xl font-black tracking-tight sm:text-3xl">
                        {prenom ? `${prenom}, voilà comment on avance ici.` : 'Voilà comment on avance ici.'}
                    </h1>
                    <p className="text-muted-foreground mt-3 max-w-xl text-sm leading-relaxed">
                        {debutant
                            ? 'Quatre gestes, pas un de plus. Lis-les une fois, puis commence — tu n’auras plus besoin de revenir sur cette page.'
                            : 'Quatre gestes, pas un de plus. Si tu as l’impression de tourner en rond, c’est souvent qu’il en manque un.'}
                    </p>
                    {portrait?.examen_vise && (
                        <p className="text-muted-foreground mt-3 text-xs font-semibold">
                            Ton objectif : {portrait.examen_vise}
                            {portrait.niveau ? ` · niveau ${portrait.niveau}` : ''}
                            {portrait.jours_avant_examen != null ? ` · dans ${portrait.jours_avant_examen} jours` : ''}
                        </p>
                    )}
                </section>

                <section aria-labelledby="guide-etapes">
                    <h2 id="guide-etapes" className="text-foreground mb-3 text-lg font-extrabold">
                        Les quatre gestes
                    </h2>
                    <ol className="space-y-3">
                        {ETAPES.map((etape, index) => (
                            <li
                                key={etape.titre}
                                className="studio-card border-border bg-card flex gap-4 rounded-2xl border p-4 sm:p-5"
                            >
                                <ArtIcon name={etape.icon} size={52} tone={etape.tone} />
                                <div className="min-w-0 flex-1">
                                    <h3 className="text-foreground text-base font-extrabold">
                                        <span className="text-muted-foreground mr-2 text-sm font-black">{index + 1}.</span>
                                        {etape.titre}
                                    </h3>
                                    <p className="text-muted-foreground mt-1 text-sm leading-relaxed">{etape.texte}</p>
                                </div>
                            </li>
                        ))}
                    </ol>
                </section>

                <section aria-labelledby="guide-regles">
                    <h2 id="guide-regles" className="text-foreground mb-3 text-lg font-extrabold">
                        Quatre choses qui changent tout
                    </h2>
                    <ul className="studio-card border-border bg-card divide-border divide-y rounded-2xl border">
                        {REGLES.map((regle) => (
                            <li key={regle} className="text-foreground flex items-start gap-3 px-4 py-3 text-sm leading-relaxed">
                                <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400" aria-hidden="true" />
                                <span>{regle}</span>
                            </li>
                        ))}
                    </ul>
                </section>

                {portrait?.prochaine_etape && (
                    <section
                        aria-labelledby="guide-maintenant"
                        className="border-primary/20 bg-primary/5 rounded-2xl border p-5"
                    >
                        <h2 id="guide-maintenant" className="text-foreground text-lg font-extrabold">
                            Et pour toi, maintenant
                        </h2>
                        <p className="text-foreground mt-2 text-sm leading-relaxed">
                            <span className="font-bold">{portrait.prochaine_etape.quoi}</span>
                            {' — '}
                            {portrait.prochaine_etape.pourquoi}.
                        </p>
                        <div className="mt-4 flex flex-wrap gap-2">
                            <Link
                                href="/practice"
                                className="bg-primary text-primary-foreground focus-visible:ring-ring inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold transition-opacity hover:opacity-90 focus-visible:ring-2 focus-visible:outline-none"
                            >
                                {portrait.prochaine_etape.ou ?? 'Pratiquer'}
                                <ArrowRight className="h-4 w-4" aria-hidden="true" />
                            </Link>
                            <Link
                                href="/ai-tools/explainer"
                                className="border-border text-foreground hover:bg-muted focus-visible:ring-ring inline-flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-bold transition-colors focus-visible:ring-2 focus-visible:outline-none"
                            >
                                En parler avec le coach
                            </Link>
                        </div>
                        {portrait.serie_en_cours > 1 && (
                            <p className="text-muted-foreground mt-3 text-xs font-semibold">
                                {portrait.serie_en_cours} jours d’affilée. Ne casse pas la série.
                            </p>
                        )}
                    </section>
                )}
            </div>
        </AppLayout>
    );
}
