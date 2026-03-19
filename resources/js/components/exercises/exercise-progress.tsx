interface ExerciseProgressProps {
    current: number;
    total: number;
}

export function ExerciseProgress({ current, total }: ExerciseProgressProps) {
    const percent = total > 0 ? (current / total) * 100 : 0;

    return (
        <div className="space-y-1">
            <div className="flex items-center gap-2 text-xs text-muted-foreground">
                <span className="font-medium">Question {current}/{total}</span>
                <span className="text-primary font-semibold">{Math.round(percent)}%</span>
            </div>
            <div className="h-2 rounded-full bg-muted">
                <div
                    className="h-2 rounded-full bg-primary transition-all"
                    style={{ width: `${percent}%` }}
                />
            </div>
        </div>
    );
}
