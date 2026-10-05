import { Link } from '@inertiajs/react';
import { LearningModePicker } from './learning-mode-picker';

export interface JourneyAction { kind: string; title: string; description: string; url: string }
export function DailyMission({ action, reviewCount }: { action: JourneyAction; reviewCount: number }) {
    return <section className="mx-auto max-w-5xl px-4 pt-5" aria-labelledby="daily-mission-title">
        <div className="relative overflow-hidden rounded-3xl border border-primary/15 bg-gradient-to-br from-primary/10 via-card to-card p-5 sm:p-7">
            <div className="flex items-center gap-3 sm:gap-7">
                <div className="min-w-0 flex-1">
                    <p className="mb-2 text-xs font-bold uppercase tracking-widest text-primary">Un pas de plus, à ton rythme</p>
                    <h1 id="daily-mission-title" className="text-xl font-extrabold text-foreground sm:text-3xl">{action.title}</h1>
                    <p className="mt-2 max-w-xl text-sm leading-relaxed text-muted-foreground">{action.description}</p>
                    <Link href={action.url} className="mt-5 inline-flex min-h-12 items-center rounded-xl bg-primary px-5 py-3 font-bold text-primary-foreground shadow-sm">{action.kind === 'exam' ? 'Passer mon épreuve' : action.kind === 'onboarding' ? 'Créer mon parcours' : 'Continuer ma séance'} <span aria-hidden="true" className="ml-3">→</span></Link>
                </div>
                <img src="/illustrations/prepla-guide/welcome.png" width="160" height="160" alt="" className="h-28 w-24 shrink-0 object-contain sm:h-44 sm:w-40" />
            </div>
            <div className="mt-5 flex flex-wrap items-end justify-between gap-4 border-t border-border/70 pt-4">
                <LearningModePicker />
                <Link href={reviewCount > 0 ? route('dictionary.review_page') : route('dictionary.index')} className="text-sm font-semibold text-primary">{reviewCount > 0 ? `${Math.min(reviewCount, 5)} mots à retrouver · révision courte` : 'Mes mots et mes exemples'} →</Link>
            </div>
        </div>
    </section>;
}
