import { Head, Link } from '@inertiajs/react';
import { ArrowRight, Check, Sparkles } from 'lucide-react';
import { ArtIcon } from '@/components/art-icon';
import AppLayout from '@/layouts/app-layout';

const tools = [
    {
        title: 'Donne du relief à tes écrits',
        label: 'Correcteur de rédaction',
        description: 'Un regard neuf sur ton texte, pour comprendre tes erreurs et trouver les mots justes.',
        icon: 'writing',
        tone: 'blue' as const,
        href: '/ai-tools/writing-corrector',
        example: 'Ton idée. Tes mots. Une version plus claire.',
        details: ['Texte ou photo de ton cahier', 'Corrections expliquées, phrase par phrase'],
        action: 'Améliorer mon texte',
        accent: 'bg-blue-50 text-blue-800 dark:bg-blue-400/10 dark:text-blue-200',
    },
    {
        title: 'Fais le déclic',
        label: 'Explicateur IA',
        description: 'Une règle te résiste ? Pose ta question et explore une explication à ton rythme.',
        icon: 'help',
        tone: 'mint' as const,
        href: '/ai-tools/explainer',
        example: '« Explique-moi cette règle avec un exemple. »',
        details: ['Grammaire, vocabulaire et méthodes', 'Une conversation que tu peux approfondir'],
        action: 'Poser ma question',
        accent: 'bg-emerald-50 text-emerald-800 dark:bg-emerald-400/10 dark:text-emerald-200',
    },
];

export default function AiToolsIndex() {
    return (
        <AppLayout>
            <Head title="Outils IA" />
            <div className="studio-page mx-auto w-full max-w-5xl space-y-7 px-4 py-6 md:px-6 md:py-8">
                <header className="studio-hero relative overflow-hidden rounded-[2rem] border border-border p-5 sm:p-8">
                    <div aria-hidden="true" className="pointer-events-none absolute -right-8 -top-12 h-56 w-56 rounded-full bg-primary/10 blur-2xl" />
                    <div className="relative flex items-start justify-between gap-5">
                        <div className="max-w-xl">
                            <p className="studio-kicker mb-3 flex items-center gap-2 text-xs font-bold uppercase tracking-[0.16em] text-primary">
                                <Sparkles className="h-4 w-4" aria-hidden="true" /> Ton atelier de langues
                            </p>
                            <h1 className="text-2xl font-black leading-tight tracking-tight text-foreground sm:text-4xl">
                                Un coup de pouce.<br />Un vrai déclic.
                            </h1>
                            <p className="mt-4 max-w-lg text-sm leading-relaxed text-muted-foreground sm:text-base">
                                Trouve les mots, comprends les règles, puis reprends ton élan. Deux outils pour avancer là où tu en as besoin.
                            </p>
                        </div>
                        <ArtIcon name="lightbulb" size={86} tone="amber" className="hidden shrink-0 rotate-6 sm:flex" />
                    </div>
                </header>

                <section aria-labelledby="choose-tool">
                    <div className="mb-4 flex flex-wrap items-baseline justify-between gap-2">
                        <h2 id="choose-tool" className="text-lg font-bold tracking-tight text-foreground">De quoi as-tu besoin ?</h2>
                        <span className="text-xs font-medium text-muted-foreground">À utiliser en complément de ton parcours</span>
                    </div>
                    <div className="grid gap-5 md:grid-cols-2">
                        {tools.map((tool) => (
                            <Link
                                key={tool.href}
                                href={tool.href}
                                className="studio-card group flex flex-col rounded-[1.75rem] border border-border bg-card p-5 text-card-foreground transition duration-200 hover:border-primary/40 hover:shadow-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-4 focus-visible:ring-offset-background motion-safe:hover:-translate-y-1 sm:p-6"
                            >
                                <div className="mb-4 flex items-center justify-between gap-3 sm:mb-6">
                                    <ArtIcon name={tool.icon} size={56} tone={tool.tone} />
                                    <span className={`rounded-full px-3 py-1.5 text-[11px] font-bold ${tool.accent}`}>{tool.label}</span>
                                </div>
                                <h3 className="text-xl font-extrabold tracking-tight text-foreground">{tool.title}</h3>
                                <p className="mt-2 text-sm leading-relaxed text-muted-foreground">{tool.description}</p>
                                <div className={`my-5 hidden rounded-2xl px-4 py-4 text-sm font-medium leading-relaxed sm:block ${tool.accent}`}>
                                    {tool.example}
                                </div>
                                <ul className="mb-6 hidden space-y-2.5 text-xs leading-relaxed text-muted-foreground sm:block">
                                    {tool.details.map((detail) => (
                                        <li key={detail} className="flex items-start gap-2">
                                            <Check className="mt-0.5 h-3.5 w-3.5 shrink-0 text-primary" aria-hidden="true" />
                                            {detail}
                                        </li>
                                    ))}
                                </ul>
                                <div className="mt-4 flex items-center justify-between border-t border-border pt-4 text-sm font-bold text-foreground sm:mt-auto">
                                    {tool.action}
                                    <span className="flex h-9 w-9 items-center justify-center rounded-full bg-primary/10 text-primary transition-colors group-hover:bg-primary group-hover:text-primary-foreground">
                                        <ArrowRight className="h-4 w-4" aria-hidden="true" />
                                    </span>
                                </div>
                            </Link>
                        ))}
                    </div>
                </section>

                <aside className="flex items-start gap-3 rounded-2xl border border-border bg-card/60 px-5 py-4">
                    <ArtIcon name="book" size={36} tone="amber" className="shrink-0" />
                    <div className="min-w-0">
                        <p className="text-sm font-semibold text-foreground">Le bon réflexe : comprendre, puis réessayer.</p>
                        <p className="mt-1 text-xs leading-relaxed text-muted-foreground">
                            Garde ce qui t’aide, reformule avec tes mots et mets-le en pratique. Les réponses de l’IA peuvent contenir des erreurs ; les notes proposées restent indicatives.
                        </p>
                    </div>
                </aside>
            </div>
        </AppLayout>
    );
}
