import { Head, Link, router } from '@inertiajs/react';
import { X } from 'lucide-react';
import { createContext, PropsWithChildren, useContext, useMemo, useState } from 'react';
import { ExerciseProgress } from '@/components/exercises/exercise-progress';
import { useApparenceEnregistree } from '@/hooks/use-appearance';

interface ExerciseLayoutProps {
    title: string;
    examName?: string;
    backUrl?: string;
    /** Essai sans compte : une pastille « Essai » et le lien vers l'inscription. */
    essai?: boolean;
}

interface Progression {
    current: number;
    total: number;
}

/**
 * La progression est tenue par le lecteur mais affichee dans l'en-tete collant :
 * le lecteur la confie a ce contexte. Hors de cette mise en page, il l'affiche
 * lui-meme.
 */
const ProgressionContext = createContext<((p: Progression | null) => void) | null>(null);

export function useProgressionDansLEntete() {
    return useContext(ProgressionContext);
}

/**
 * L'en-tete du lecteur d'exercices : une seule bande fine et collante.
 *
 * Avant, trois bandeaux s'empilaient au-dessus de la question (mode essai,
 * grande carte de presentation, encadre « Bibliotheque ») : sur ordinateur, la
 * question tombait sous la ligne de flottaison. Tout tient maintenant sur une
 * ligne — croix, titre, pastille « Essai » — et la barre de progression court
 * sur toute la largeur, juste en dessous.
 */
export default function ExerciseLayout({
    title,
    examName,
    backUrl = '/practice',
    essai = false,
    children,
}: PropsWithChildren<ExerciseLayoutProps>) {
    const [progression, setProgression] = useState<Progression | null>(null);
    const fournir = useMemo(() => setProgression, []);
    useApparenceEnregistree();

    return (
        <>
            <Head title={title} />
            {/* Hauteur d'ecran en style en ligne : `.learning-canvas` (CSS hors
                couche, `min-height: 100%`) l'emportait sur `min-h-screen`. La racine
                ne faisait pas la hauteur de l'ecran, et sur telephone les actions
                « collees en bas » flottaient au milieu quand la question est courte. */}
            <div className="learning-canvas palette-lecteur flex flex-col" style={{ minHeight: '100dvh' }}>
                <header className="sticky top-0 z-30 border-b border-border/70 bg-background/90 backdrop-blur-xl">
                    <div className="mx-auto flex max-w-6xl items-center gap-2 px-2 py-1.5 sm:px-4">
                        <button
                            type="button"
                            aria-label="Quitter l’exercice"
                            onClick={() => router.visit(backUrl)}
                            className="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-full text-foreground transition-colors hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        >
                            <X className="h-5 w-5" aria-hidden="true" />
                        </button>
                        <div className="min-w-0 flex-1">
                            <h1 className="truncate text-[0.9375rem] font-semibold leading-tight">{title}</h1>
                            {examName && <p className="truncate text-[0.8125rem] leading-tight text-muted-foreground">{examName}</p>}
                        </div>
                        {essai && (
                            <div className="flex shrink-0 items-center gap-2">
                                <span className="rounded-full bg-[var(--essai)] px-2.5 py-1 text-[0.8125rem] font-bold text-[var(--sur-essai)]">Essai</span>
                                <Link
                                    href={route('register')}
                                    className="hidden min-h-11 items-center rounded-full px-3 text-[0.875rem] font-semibold text-primary underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring sm:inline-flex"
                                >
                                    Crée un compte
                                </Link>
                            </div>
                        )}
                    </div>
                    {progression && (
                        <div className="mx-auto max-w-6xl px-3 pb-2 sm:px-4">
                            <ExerciseProgress current={progression.current} total={progression.total} />
                        </div>
                    )}
                </header>

                {essai && (
                    <p className="mx-auto w-full max-w-6xl px-4 pt-3 text-[0.8125rem] text-muted-foreground">
                        Tes réponses sont corrigées, pas enregistrées.{' '}
                        <Link href={route('register')} className="font-semibold text-primary underline underline-offset-4 sm:no-underline sm:hover:underline">
                            Crée un compte
                        </Link>{' '}
                        pour suivre ta progression.
                    </p>
                )}

                {/* Pas de marge basse sur telephone : la barre d'actions du lecteur doit
                    toucher le bas de l'ecran (elle porte deja la zone de securite). */}
                <main className="mx-auto flex w-full max-w-6xl flex-1 flex-col px-4 pt-4 lg:pb-6">
                    <ProgressionContext.Provider value={fournir}>{children}</ProgressionContext.Provider>
                </main>
            </div>
        </>
    );
}
