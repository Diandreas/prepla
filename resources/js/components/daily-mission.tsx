import { Link } from '@inertiajs/react';
import { LearningModePicker } from './learning-mode-picker';
import { ArrowRight, BookOpen, Headphones, Sparkles } from 'lucide-react';

export interface JourneyAction { kind: string; title: string; description: string; url: string }
export function DailyMission({ action, reviewCount, name, level }: { action: JourneyAction; reviewCount: number; name?: string; level?: string }) {
    const missionLabel = action.kind === 'exam' ? 'Ton prochain palier' : action.kind === 'remedial' ? 'Un point à consolider' : action.kind === 'practice' ? 'À toi de jouer' : 'Ta prochaine mission';
    return <section className="mx-auto max-w-5xl px-4 pt-6 sm:pt-8" aria-labelledby="daily-mission-title">
        <header className="mb-5 flex items-start justify-between gap-3">
            <div><p className="text-xs font-semibold text-muted-foreground">TON ESPACE D’APPRENTISSAGE</p><p className="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl">Bonjour{name ? `, ${name.split(' ')[0]}` : ''} <span className="text-primary">!</span></p><p className="mt-1 text-sm text-muted-foreground">Une petite séance, un vrai pas en avant.</p></div>
            {level && <span className="mt-1 rounded-full border border-primary/20 bg-primary/5 px-3 py-1.5 text-xs font-bold text-primary">Niveau {level}</span>}
        </header>
        <div className="relative overflow-hidden rounded-[28px] bg-gradient-to-br from-[#193b67] via-[#173458] to-[#14243e] p-5 text-white shadow-[0_12px_32px_-16px_rgba(20,45,80,0.5)] sm:p-8">
            <div aria-hidden="true" className="pointer-events-none absolute -right-16 -top-20 h-64 w-64 rounded-full border-[32px] border-white/[0.04]" />
            <div className="relative flex items-center gap-3 sm:gap-7">
                <div className="min-w-0 flex-1">
                    <p className="mb-3 inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 text-[11px] font-semibold text-blue-100"><Sparkles size={13} aria-hidden="true" />{missionLabel}</p>
                    <h1 id="daily-mission-title" className="max-w-xl text-xl font-extrabold leading-tight tracking-tight text-white sm:text-3xl">{action.title}</h1>
                    <p className="mt-3 max-w-lg text-sm leading-relaxed text-blue-100/85">{action.description}</p>
                    <Link href={action.url} className="mt-5 inline-flex min-h-12 items-center justify-center gap-3 rounded-xl bg-white px-4 py-3 text-sm font-bold text-[#193b67] shadow-sm transition hover:bg-blue-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white sm:px-6">{action.kind === 'exam' ? 'Passer mon épreuve' : action.kind === 'onboarding' ? 'Créer mon parcours' : 'Continuer ma séance'} <ArrowRight size={17} aria-hidden="true" /></Link>
                </div>
                <div className="relative shrink-0"><div aria-hidden="true" className="absolute inset-3 rounded-full bg-blue-300/10 blur-xl" /><img src="/illustrations/prepla-guide/welcome.png" width="160" height="160" alt="" className="relative h-32 w-20 object-contain min-[400px]:w-28 sm:h-52 sm:w-48" /></div>
            </div>
        </div>
        <div className="mt-4 rounded-2xl border border-border/70 bg-card px-4 py-3"><LearningModePicker /></div>
        <div className="mb-3 mt-6 flex items-center justify-between"><h2 className="text-sm font-bold">Pour garder le rythme</h2><span className="text-xs text-muted-foreground">À ton choix</span></div>
        <div className="grid grid-cols-2 gap-3">
            <Link href={reviewCount > 0 ? route('dictionary.review_page') : route('dictionary.index')} className="group rounded-2xl border border-border/70 bg-card p-4 transition hover:border-primary/40 focus-visible:outline focus-visible:outline-primary">
                <span className="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-300"><BookOpen size={21} aria-hidden="true" /></span>
                <p className="text-sm font-bold">{reviewCount > 0 ? 'Retrouver mes mots' : 'Mes mots'}</p><p className="mt-1 text-xs leading-relaxed text-muted-foreground">{reviewCount > 0 ? `${Math.min(reviewCount, 5)} mots prêts à réviser` : 'Le sens, les exemples, la prononciation'}</p>
            </Link>
            <Link href={route('practice.index')} className="group rounded-2xl border border-border/70 bg-card p-4 transition hover:border-primary/40 focus-visible:outline focus-visible:outline-primary">
                <span className="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary"><Headphones size={21} aria-hidden="true" /></span>
                <p className="text-sm font-bold">Pratique libre</p><p className="mt-1 text-xs leading-relaxed text-muted-foreground">Choisis la compétence à travailler</p>
            </Link>
        </div>
    </section>;
}
