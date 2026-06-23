/**
 * Branded loading animation (flying character) for waits that take a moment —
 * mainly AI generation (lessons, exercises). Falls back gracefully if the gif
 * is missing.
 */
export function LoadingAnimation({
    label,
    size = 120,
    className = '',
}: {
    label?: string;
    size?: number;
    className?: string;
}) {
    return (
        <div className={`flex flex-col items-center justify-center gap-4 ${className}`}>
            <img
                src="/animation/loading.gif"
                alt=""
                width={size}
                height={size}
                className="object-contain"
                style={{ width: size, height: size }}
            />
            {label && (
                <p className="text-sm font-bold text-muted-foreground animate-pulse">{label}</p>
            )}
        </div>
    );
}
