import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { LearningModePicker } from './learning-mode-picker';
import { Renard } from './renard';
import { ArrowRight, BookOpen, Headphones, Loader2, Sparkles, RotateCcw } from 'lucide-react';

export interface JourneyAction {
    kind: string;
    title: string;
    description: string;
    url: string;
    /** Pour l'élève d'un centre : le devoir à faire (kind « devoir »). */
    devoir?: { classe: string | null; echeance: string | null; en_retard: boolean; fait: number; total: number };
    /** Pour l'élève d'un centre au parcours créé par le centre : l'étape suivante (kind « etape »). */
    etape?: { classe: string | null; numero: number; total: number; competence: string | null; libelle: string };
}
export function DailyMission({ action, name, level, serie = 0, seriePerdue = false }: { action: JourneyAction; reviewCount: number; name?: string; level?: string; serie?: number; seriePerdue?: boolean }) {
    const missionLabel = action.kind === 'exam' ? 'Ton prochain palier' : action.kind === 'remedial' ? 'Un point à consolider' : action.kind === 'practice' ? 'À toi de jouer'
        : action.kind === 'devoir' ? 'Ton prochain devoir' : action.kind === 'devoirs_finis' ? 'Tes devoirs' : action.kind === 'sans_classe' ? 'Ton centre' : action.kind === 'entrainement' ? 'En attendant ton prochain devoir'
        : action.kind === 'etape' ? 'Ton parcours' : action.kind === 'parcours_fini' ? 'Ton parcours' : 'Ta prochaine mission';
    // Un devoir s'ouvre sans attendre : ses exercices sont déjà écrits par l'enseignant.
    // Pareil pour une étape du parcours du centre : l'exercice existe déjà.
    const sansPreparation = ['devoir', 'devoirs_finis', 'sans_classe', 'etape', 'parcours_fini'].includes(action.kind);
    // Ouvrir une étape dont les exercices ne sont pas encore écrits demande une
    // trentaine de secondes : l'IA les rédige à la demande. Sans état visible, le
    // bouton restait muet tout ce temps et la séance paraissait ne pas démarrer.
    const [preparing, setPreparing] = useState(false);
    const actionLabel = action.kind === 'exam' ? 'Passer mon épreuve' : action.kind === 'onboarding' ? 'Créer mon parcours'
        : action.kind === 'devoir' ? 'Faire mon devoir' : action.kind === 'devoirs_finis' ? 'Voir mes résultats' : action.kind === 'sans_classe' ? 'Entrer le code de ma classe' : action.kind === 'entrainement' ? 'M’entraîner'
        : action.kind === 'etape' ? 'Faire cette étape' : action.kind === 'parcours_fini' ? 'Voir mon parcours' : 'Continuer ma séance';
    const devoir = action.devoir;
    const etape = action.etape;
    return <section className="mx-auto max-w-5xl px-4 pt-4 sm:pt-5" aria-labelledby="daily-mission-title">
        <header className="mb-3 flex items-start justify-between gap-3">
            <p className="text-lg font-bold tracking-tight sm:text-xl">Bonjour{name ? `, ${name.split(' ')[0]}` : ''} <span className="text-primary">!</span></p>
            {level && <span className="mt-1 rounded-full border border-primary/20 bg-primary/5 px-3 py-1.5 text-xs font-bold text-primary">Niveau {level}</span>}
        </header>
        <div className="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#193b67] via-[#173458] to-[#14243e] p-4 text-white shadow-[0_12px_32px_-16px_rgba(20,45,80,0.5)] sm:p-6">
            <div aria-hidden="true" className="pointer-events-none absolute -right-16 -top-20 h-64 w-64 rounded-full border-[32px] border-white/[0.04]" />
            <div className="relative flex items-center gap-3 sm:gap-7">
                <div className="min-w-0 flex-1">
                    <p className="mb-2 inline-flex items-center gap-2 text-[11px] font-semibold text-blue-100"><Sparkles size={13} aria-hidden="true" />{missionLabel}</p>
                    <h1 id="daily-mission-title" className="max-w-xl text-xl font-extrabold leading-tight tracking-tight text-white sm:text-3xl">{action.title}</h1>
                    <p className="mt-2 max-w-lg text-xs leading-relaxed text-blue-100/85 sm:text-sm">{action.description}</p>
                    {devoir && (
                        <p className="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs font-semibold text-blue-50">
                            {devoir.classe && <span>Classe « {devoir.classe} »</span>}
                            {devoir.echeance && (
                                <span className={devoir.en_retard ? 'rounded-full bg-rose-500/25 px-2 py-0.5 text-rose-100' : ''}>
                                    {devoir.classe && <span aria-hidden="true" className="mr-2">·</span>}
                                    {devoir.echeance}
                                </span>
                            )}
                            <span>
                                <span aria-hidden="true" className="mr-2">·</span>
                                {devoir.fait}/{devoir.total} exercice{devoir.total > 1 ? 's' : ''}
                            </span>
                        </p>
                    )}
                    {etape && (
                        <p className="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs font-semibold text-blue-50">
                            <span>{etape.libelle}</span>
                            {etape.classe && (
                                <span>
                                    <span aria-hidden="true" className="mr-2">·</span>
                                    Classe « {etape.classe} »
                                </span>
                            )}
                        </p>
                    )}
                    <Link
                        href={action.url}
                        onStart={() => setPreparing(!sansPreparation)}
                        onFinish={() => setPreparing(false)}
                        aria-busy={preparing}
                        aria-live="polite"
                        className="mt-5 inline-flex min-h-12 items-center justify-center gap-3 rounded-xl bg-white px-4 py-3 text-sm font-bold text-[#193b67] shadow-sm transition hover:bg-blue-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white aria-busy:pointer-events-none aria-busy:opacity-80 sm:px-6"
                    >
                        {preparing
                            ? <>Préparation de ta séance… <Loader2 size={17} aria-hidden="true" className="animate-spin" /></>
                            : <>{actionLabel} <ArrowRight size={17} aria-hidden="true" /></>}
                    </Link>
                    {preparing && <p className="mt-2 text-xs text-blue-100/85">Tes exercices sont en cours d’écriture, cela peut prendre une trentaine de secondes.</p>}
                </div>
                <div className="relative shrink-0"><div aria-hidden="true" className="absolute inset-3 rounded-full bg-blue-300/10 blur-xl" />{/* Une serie en cours : le renard la fete. Retombee a zero : il console. Sinon, il accueille. */}<Renard pose={serie > 0 ? 'serie' : seriePerdue ? 'serie-perdue' : 'welcome'} size={160} decoratif className="relative h-24 w-16 min-[400px]:w-20 sm:h-36 sm:w-32" /></div>
            </div>
        </div>
    </section>;
}

export function MissionExtras({ reviewCount, dueErrorsCount = 0, centreSeul = false }: { reviewCount: number; dueErrorsCount?: number; centreSeul?: boolean }) {
    return <section aria-label="Entraînements et préférences" className="space-y-4">
        {/* Le centre qui crée le parcours de ses classes ferme la pratique libre :
            les liens « Pratiquer à l’oral » menaient à une page fermée, et le
            choix « Dans mon parcours, autoriser » ne réglait rien pour lui. */}
        {!centreSeul && <LearningModePicker />}
        <div className="mb-2 mt-4 flex items-center justify-between"><h2 className="text-xs font-bold">À ton rythme</h2><span className="text-xs text-muted-foreground">Facultatif</span></div>
        <div className="grid grid-cols-2 gap-3">
            {/* Reprendre ses erreurs est le geste qui fait le plus progresser,
                et celui qu'on saute le plus souvent. Il etait dans un bloc que
                sa condition n'affichait jamais. */}
            <Link
                href="/errors"
                className="group rounded-2xl border border-border/70 bg-card p-4 transition hover:border-primary/40 focus-visible:outline focus-visible:outline-primary"
            >
                <span className="mb-1 inline-flex text-rose-600 dark:text-rose-300"><RotateCcw size={17} aria-hidden="true" /></span>
                <p className="text-sm font-bold">Reprendre mes erreurs</p>
                <p className="mt-1 text-xs leading-relaxed text-muted-foreground">
                    {dueErrorsCount > 0 ? `${dueErrorsCount} à revoir` : 'Rien à revoir pour l’instant'}
                </p>
            </Link>
            <Link href={reviewCount > 0 ? route('dictionary.review_page') : route('dictionary.index')} className="group rounded-2xl border border-border/70 bg-card p-4 transition hover:border-primary/40 focus-visible:outline focus-visible:outline-primary">
                <span className="mb-1 inline-flex text-amber-600 dark:text-amber-300"><BookOpen size={17} aria-hidden="true" /></span>
                <p className="text-sm font-bold">{reviewCount > 0 ? 'Réviser mes mots' : 'Mes mots'}</p><p className="mt-1 text-xs leading-relaxed text-muted-foreground">{reviewCount > 0 ? `${Math.min(reviewCount, 5)} mots à retrouver` : 'Tes mots et exemples'}</p>
            </Link>
            {/* Le centre qui crée le parcours de ses classes ferme la pratique libre. */}
            {centreSeul ? (
                <Link href={route('parcours.index')} className="group rounded-2xl border border-border/70 bg-card p-4 transition hover:border-primary/40 focus-visible:outline focus-visible:outline-primary">
                    <span className="mb-1 inline-flex text-primary"><Headphones size={17} aria-hidden="true" /></span>
                    <p className="text-sm font-bold">Mon parcours</p><p className="mt-1 text-xs leading-relaxed text-muted-foreground">Les étapes de ta classe</p>
                </Link>
            ) : (
                <Link href={route('practice.index')} className="group rounded-2xl border border-border/70 bg-card p-4 transition hover:border-primary/40 focus-visible:outline focus-visible:outline-primary">
                    <span className="mb-1 inline-flex text-primary"><Headphones size={17} aria-hidden="true" /></span>
                    <p className="text-sm font-bold">Pratique libre</p><p className="mt-1 text-xs leading-relaxed text-muted-foreground">Choisir un exercice</p>
                </Link>
            )}
        </div>
    </section>;
}
