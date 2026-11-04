interface ExerciseProgressProps {
    current: number;
    total: number;
}

/**
 * Ou en est l'eleve : « Question n/N » et une barre sur toute la largeur.
 *
 * La barre ne faisait que la largeur de son libelle (une centaine de pixels).
 * Le libelle est annonce poliment aux lecteurs d'ecran a chaque question, et la
 * barre porte son role et ses valeurs.
 */
export function ExerciseProgress({ current, total }: ExerciseProgressProps) {
    const percent = total > 0 ? Math.min(100, (current / total) * 100) : 0;

    return (
        <div className="flex items-center gap-3">
            <span aria-live="polite" className="shrink-0 text-[0.8125rem] font-semibold tabular-nums text-muted-foreground">
                Question {current}/{total}
            </span>
            <div
                role="progressbar"
                aria-label="Progression dans l’exercice"
                aria-valuemin={0}
                aria-valuemax={total}
                aria-valuenow={current}
                aria-valuetext={`Question ${current} sur ${total}`}
                className="h-2.5 flex-1 overflow-hidden rounded-full bg-muted"
            >
                <div className="h-full rounded-full bg-primary transition-[width] duration-300 motion-reduce:transition-none" style={{ width: `${percent}%` }} />
            </div>
        </div>
    );
}
