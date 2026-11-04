import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { useEffect } from 'react';
import { ConfettiBurst } from '@/components/confetti-burst';
import { playSound } from '@/hooks/use-sound';
import { motion } from 'framer-motion';
import { Check, CheckCircle2, Clock, Sparkles, TrendingUp, TriangleAlert, X } from 'lucide-react';
import { CeQuilFautRetenir } from '@/components/exercises/ce-quil-faut-retenir';
import { cn } from '@/lib/utils';

// AI-evaluated question types (essay/speaking/short-answer) return `explanation`
// as an object ({concept,evidence,hint,french_translation}), not a string.
// Rendering it directly as a React child crashes the whole page (error #31) —
// same class of bug already fixed on exercise/result.tsx via asText().
// eslint-disable-next-line @typescript-eslint/no-explicit-any
function asText(v: any): string {
    if (v == null) return '';
    if (typeof v === 'string') return v;
    if (typeof v === 'number' || typeof v === 'boolean') return String(v);
    if (Array.isArray(v)) return v.map(asText).filter(Boolean).join(' ');
    if (typeof v === 'object') return asText(v.concept ?? v.text ?? v.message ?? v.hint ?? v.value ?? Object.values(v)[0]);
    return '';
}

interface ReportDetail {
    exercise_id: number;
    type?: string;
    title?: string;
    accuracy?: number;
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    feedback?: any[];
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    questions?: any[];
}

interface Props {
    node: {
        id: number;
        title: string;
        exam?: {
            name: string;
            language?: { name: string }
        }
    };
    report: {
        node_title?: string;
        accuracy?: number;
        xp_earned?: number;
        time_spent?: number;
        details?: ReportDetail[];
        is_level_exam?: boolean;
        exam_passed?: boolean;
        pass_threshold?: number;
        remediation_count?: number;
        technical_failures?: number;
    };
    userLevel: string;
    /** La vraie leçon du concept : l'identifiant du nœud n'en est pas un (404). */
    lessonId?: number | null;
}

const POLICE_TITRES = "font-['Plus_Jakarta_Sans',system-ui,sans-serif]";

/**
 * L'anneau de score, comme sur la page de resultat de l'essai : vert si le
 * seuil est atteint, ambre sinon, et toujours le pourcentage ecrit au centre —
 * jamais la couleur seule.
 */
function AnneauDeScore({ pourcentage, reussi, seuil }: { pourcentage: number; reussi: boolean; seuil: number }) {
    const rayon = 52;
    const tour = 2 * Math.PI * rayon;
    const part = Math.max(0, Math.min(1, pourcentage / 100));

    return (
        <div className="relative h-36 w-36 shrink-0" role="img" aria-label={`Précision : ${pourcentage} %, seuil de validation ${seuil} %`}>
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
                <span className={cn(POLICE_TITRES, 'text-4xl font-extrabold tabular-nums leading-none')}>{pourcentage}<span className="text-2xl">%</span></span>
                <span className="mt-1 text-[0.8125rem] font-semibold text-muted-foreground">seuil {seuil} %</span>
            </div>
        </div>
    );
}

/** Une case de chiffre : icone, libelle lisible (13 px au moins), valeur. */
function Chiffre({ icone: Icone, libelle, valeur, ton }: { icone: typeof Clock; libelle: string; valeur: string; ton: string }) {
    return (
        <div className="flex items-center gap-3 rounded-2xl border-[1.5px] border-border bg-card p-4">
            <span aria-hidden="true" className={cn('flex h-11 w-11 shrink-0 items-center justify-center rounded-xl', ton)}>
                <Icone className="h-5 w-5" />
            </span>
            <div className="min-w-0">
                <p className="text-[0.8125rem] font-medium text-muted-foreground">{libelle}</p>
                <p className="truncate text-base font-bold text-foreground">{valeur}</p>
            </div>
        </div>
    );
}

/**
 * Le rapport de fin de seance, l'ecran de fin d'un apprenant dans son parcours.
 *
 * Meme direction que le lecteur et la page de resultat de l'essai : cartes aux
 * couleurs du theme (le blanc et les gris « slate » ecrits en dur ignoraient le
 * theme), titres en Plus Jakarta Sans, juste/faux toujours avec une icone, et
 * des boutons dont le texte se lit — blanc sur ambre ou sur vert clair, ils
 * passaient sous 4,5:1.
 */
export default function SessionReport({ node, report, userLevel, lessonId }: Props) {
    // Defensive fallbacks: a stale/partial report (old session shape left over
    // from before a deploy) must never crash this page to a blank screen.
    const accuracy = report?.accuracy ?? 0;
    const isExam = report?.is_level_exam ?? false;
    const threshold = report?.pass_threshold ?? (isExam ? 70 : 60);
    const unavailable = (report?.technical_failures ?? 0) > 0;
    const hasRemediation = (report?.remediation_count ?? 0) > 0;
    const passed = !unavailable && (isExam ? (report?.exam_passed ?? accuracy >= threshold) : accuracy >= threshold);
    const details = Array.isArray(report?.details) ? report.details : [];
    const xpEarned = report?.xp_earned ?? 0;
    const timeSpent = report?.time_spent ?? 0;
    const nodeTitle = report?.node_title ?? node?.title ?? '';
    const reussis = details.filter(d => (d.accuracy ?? 0) >= 60).length;

    // Victory sound + haptics on arrival (xp gained when the concept is mastered).
    useEffect(() => {
        playSound(accuracy >= 80 ? 'complete' : 'xp');
    }, [accuracy]);

    const container = {
        hidden: { opacity: 0 },
        show: {
            opacity: 1,
            transition: { staggerChildren: 0.1 }
        }
    };

    const item = {
        hidden: { opacity: 0, y: 20 },
        show: { opacity: 1, y: 0 }
    };

    const statut = unavailable ? 'Correction incomplète' : isExam ? (passed ? 'Palier validé' : 'Reprises personnalisées') : (passed ? 'Concept validé' : 'À consolider');
    const bouton = 'inline-flex min-h-12 w-full items-center justify-center rounded-full px-7 text-base font-bold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background sm:w-auto';
    const boutonSecondaire = cn(bouton, 'border-[1.5px] border-border bg-card text-foreground hover:bg-muted');

    // Actions — adapt CTA to whether user mastered the concept. Affichees avant
    // le detail (sur telephone, la suite du parcours ne doit pas attendre la fin
    // d'une longue correction), et de nouveau apres.
    const actions = (
        <motion.div variants={item} className="flex flex-col items-center justify-center gap-3 sm:flex-row">
            {passed ? (
                <>
                    <Link href="/lessons/next" className={cn(bouton, 'bg-emerald-700 text-white hover:bg-emerald-800')}>
                        {isExam ? 'Continuer mon parcours →' : 'Concept suivant →'}
                    </Link>
                    <Link href="/dashboard" className={boutonSecondaire}>
                        Retour au parcours
                    </Link>
                </>
            ) : (
                <>
                    <Link
                        href={!unavailable && (isExam || hasRemediation) ? '/lessons/next' : route('node.start', node.id)}
                        className={cn(bouton, 'bg-[var(--essai)] text-[var(--sur-essai)] hover:brightness-105')}
                    >
                        {unavailable ? 'Réessayer la séance →' : isExam || hasRemediation ? 'Comprendre mes erreurs →' : `↻ Refaire pour valider (≥${threshold} %)`}
                    </Link>
                    <Link href={isExam ? '/dashboard' : lessonId ? `/lessons/${lessonId}` : '/lessons/next'} className={boutonSecondaire}>
                        {isExam ? 'Voir mes reprises' : 'Revoir la leçon'}
                    </Link>
                </>
            )}
        </motion.div>
    );

    return (
        <AppLayout>
            <Head title={`Résultats : ${nodeTitle}`} />
            {passed && accuracy >= 80 && <ConfettiBurst />}

            <div className="palette-lecteur mx-auto max-w-4xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
                <motion.div
                    variants={container}
                    initial="hidden"
                    animate="show"
                    className="space-y-8"
                >
                    {/* En-tete : statut, titre, message, anneau de score */}
                    <motion.section variants={item} className="relative overflow-hidden rounded-3xl border-[1.5px] border-border bg-card p-6 sm:p-8">
                        <div className="flex flex-col items-center gap-6 md:flex-row md:items-center md:gap-8">
                            {/* Animated trophy / encouragement based on accuracy */}
                            <img
                                src={accuracy >= 80 ? '/animation/winner.gif' : passed ? '/animation/big-trophy.gif' : '/animation/Fire.gif'}
                                alt=""
                                width={120}
                                height={120}
                                className="shrink-0"
                            />
                            <div className="min-w-0 flex-1 text-center md:text-left">
                                <span
                                    className={cn(
                                        'mb-3 inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[0.8125rem] font-bold',
                                        passed
                                            ? 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-100'
                                            : 'bg-amber-100 text-amber-900 dark:bg-amber-950/60 dark:text-amber-100',
                                    )}
                                >
                                    {passed ? <Check aria-hidden="true" className="h-4 w-4" /> : <TriangleAlert aria-hidden="true" className="h-4 w-4" />}
                                    {statut}
                                </span>
                                <h1 className={cn(POLICE_TITRES, 'text-2xl font-extrabold leading-tight tracking-tight text-foreground sm:text-3xl')}>
                                    {nodeTitle}
                                </h1>
                                <p className="mt-2 text-[0.9375rem] leading-relaxed text-muted-foreground sm:text-base">
                                    {unavailable ? 'Une partie de la correction est indisponible. Ce problème technique ne compte pas comme une lacune : réessaie pour terminer la validation.' : hasRemediation ? 'Une reprise ciblée t’attend : revois la notion, entraîne-toi, puis valide à nouveau cet objectif.' : isExam
                                        ? passed
                                            ? `Examen validé (≥${threshold} %). Continue ton parcours au niveau ${userLevel}.`
                                            : `Il faut ${threshold} % pour valider ce palier. Revois les notions manquées avec une leçon ciblée et des exercices : l’examen se rouvrira ensuite.`
                                        : passed ? `Concept validé (≥${threshold} %). Tu peux passer au suivant.`
                                            : `Il te faut ${threshold} % pour valider ce concept. Tu es à ${Math.round(accuracy)} % — refais une session ou revois la leçon.`}
                                </p>
                            </div>
                            <AnneauDeScore pourcentage={Math.round(accuracy)} reussi={passed} seuil={threshold} />
                        </div>
                    </motion.section>

                    {/* Chiffres de la seance */}
                    <motion.div variants={item} className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <Chiffre
                            icone={Clock}
                            libelle="Temps total"
                            valeur={`${Math.floor(timeSpent / 60)} min ${timeSpent % 60} s`}
                            ton="bg-orange-100 text-orange-800 dark:bg-orange-950/60 dark:text-orange-200"
                        />
                        <Chiffre
                            icone={CheckCircle2}
                            libelle="Réussite"
                            valeur={`${reussis} / ${details.length} exercice${details.length > 1 ? 's' : ''}`}
                            ton="bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-200"
                        />
                        <Chiffre
                            icone={Sparkles}
                            libelle="XP gagnés"
                            valeur={`+${xpEarned}`}
                            ton="bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-200"
                        />
                        <Chiffre
                            icone={TrendingUp}
                            libelle="Niveau"
                            valeur={`${node?.exam?.language?.name ?? ''} · ${userLevel}`}
                            ton="bg-primary/10 text-primary"
                        />
                    </motion.div>

                    {actions}

                    {/* Detailed Analysis */}
                    {details.length > 0 && (
                        <motion.section variants={item} className="space-y-4">
                            <h2 className={cn(POLICE_TITRES, 'text-xl font-extrabold text-foreground')}>
                                Ta <em className="font-['Cormorant_Garamond',Georgia,serif] text-2xl font-bold italic">correction</em>, exercice par exercice
                            </h2>
                            {details.map((detail, idx) => {
                                const exerciceReussi = (detail.accuracy ?? 0) >= 60;
                                return (
                                    <article key={idx} className="overflow-hidden rounded-2xl border-[1.5px] border-border bg-card">
                                        <header className="flex items-center justify-between gap-3 border-b border-border p-4">
                                            <div className="flex min-w-0 items-center gap-3">
                                                <span aria-hidden="true" className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-[0.875rem] font-bold text-primary">
                                                    {idx + 1}
                                                </span>
                                                <h3 className="truncate text-[0.9375rem] font-bold text-foreground">
                                                    <span className="sr-only">Exercice {idx + 1} : </span>
                                                    {detail.type ?? detail.title ?? ''}
                                                </h3>
                                            </div>
                                            <span
                                                className={cn(
                                                    'inline-flex shrink-0 items-center gap-1 rounded-full px-3 py-1 text-[0.875rem] font-bold',
                                                    exerciceReussi
                                                        ? 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-100'
                                                        : 'bg-red-100 text-red-900 dark:bg-red-950/60 dark:text-red-100',
                                                )}
                                            >
                                                {exerciceReussi ? <Check aria-hidden="true" className="h-4 w-4" /> : <X aria-hidden="true" className="h-4 w-4" />}
                                                {Math.round(detail.accuracy ?? 0)}%
                                            </span>
                                        </header>
                                        <ol className="space-y-3 p-4 sm:p-5">
                                            {(Array.isArray(detail.feedback) ? detail.feedback : []).map((f, fIdx) => (
                                                <li
                                                    key={fIdx}
                                                    className={cn(
                                                        'rounded-xl border-[1.5px] p-4',
                                                        f.correct
                                                            ? 'border-emerald-200 bg-emerald-50/50 dark:border-emerald-900 dark:bg-emerald-950/20'
                                                            : 'border-red-200 bg-red-50/50 dark:border-red-900 dark:bg-red-950/20',
                                                    )}
                                                >
                                                    <div className="flex items-start gap-3">
                                                        <span
                                                            className={cn(
                                                                'mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-white',
                                                                f.correct ? 'bg-emerald-700' : 'bg-red-700',
                                                            )}
                                                        >
                                                            {f.correct ? <Check aria-hidden="true" className="h-4 w-4" /> : <X aria-hidden="true" className="h-4 w-4" />}
                                                            <span className="sr-only">{f.correct ? 'Bonne réponse' : 'Réponse incorrecte'}</span>
                                                        </span>
                                                        <div className="min-w-0 flex-1">
                                                            <p className="text-[0.9375rem] font-semibold text-foreground">
                                                                {f.question_text || `Question ${fIdx + 1}`}
                                                            </p>
                                                            {!f.correct && (
                                                                <div className="mt-2 space-y-1 text-[0.9375rem]">
                                                                    {f.given_answer ? (
                                                                        <p className="text-red-800 dark:text-red-200">
                                                                            <span className="text-muted-foreground">Ta réponse : </span>
                                                                            <span className="font-semibold line-through">{asText(f.given_answer)}</span>
                                                                        </p>
                                                                    ) : (
                                                                        <p className="italic text-muted-foreground">Aucune réponse donnée</p>
                                                                    )}
                                                                    {f.expected_answer && (
                                                                        <p className="text-emerald-800 dark:text-emerald-200">
                                                                            <span className="text-muted-foreground">Réponse attendue : </span>
                                                                            <span className="font-semibold">{asText(f.expected_answer)}</span>
                                                                        </p>
                                                                    )}
                                                                </div>
                                                            )}
                                                            {!f.correct && (f.explanation || f.model_answer) && (
                                                                <CeQuilFautRetenir explication={f.explanation} reponseType={f.model_answer} />
                                                            )}
                                                        </div>
                                                    </div>
                                                </li>
                                            ))}
                                        </ol>
                                    </article>
                                );
                            })}
                        </motion.section>
                    )}
                    {/* Les memes actions apres une longue correction : pas besoin de remonter. */}
                    {details.length > 0 && actions}
                </motion.div>
            </div>
        </AppLayout>
    );
}
