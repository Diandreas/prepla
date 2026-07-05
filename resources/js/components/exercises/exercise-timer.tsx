import { useEffect, useRef, useState } from 'react';

interface ExerciseTimerProps {
    onTimeUpdate: (seconds: number) => void;
    onExpire?: () => void;
    timeLimit?: number; // in seconds — if provided, counts DOWN
}

export function ExerciseTimer({ onTimeUpdate, onExpire, timeLimit }: ExerciseTimerProps) {
    const isCountdown = !!timeLimit;
    const [seconds, setSeconds] = useState(isCountdown ? timeLimit! : 0);
    const expiredRef = useRef(false);

    useEffect(() => {
        expiredRef.current = false;
        setSeconds(isCountdown ? timeLimit! : 0);
    }, [timeLimit]);

    useEffect(() => {
        // Ticking on a hidden tab would count wall-clock time the user isn't
        // actually engaged with (backgrounded tab, phone locked, switched app) as
        // "time spent" — inflating the exercise's reported duration to absurd
        // values after a long-idle session. Pause the interval while hidden and
        // resume on visibility restore instead of ticking through the gap.
        let interval: ReturnType<typeof setInterval> | null = null;

        const tick = () => {
            setSeconds((prev) => isCountdown ? Math.max(0, prev - 1) : prev + 1);
        };

        const start = () => {
            if (interval || document.hidden) return;
            interval = setInterval(tick, 1000);
        };
        const stop = () => {
            if (interval) { clearInterval(interval); interval = null; }
        };

        const onVisibilityChange = () => (document.hidden ? stop() : start());

        start();
        document.addEventListener('visibilitychange', onVisibilityChange);
        return () => {
            stop();
            document.removeEventListener('visibilitychange', onVisibilityChange);
        };
    }, [isCountdown]);

    useEffect(() => {
        if (isCountdown) {
            onTimeUpdate(timeLimit! - seconds);
            if (seconds <= 0 && !expiredRef.current) {
                expiredRef.current = true;
                onExpire?.();
            }
        } else {
            onTimeUpdate(seconds);
        }
    }, [seconds, isCountdown, timeLimit, onTimeUpdate, onExpire]);

    const display = isCountdown ? seconds : seconds;
    const mins = Math.floor(display / 60);
    const secs = display % 60;

    // Warning thresholds for countdown
    const isWarning = isCountdown && seconds <= timeLimit! * 0.2 && seconds > 0;
    const isCritical = isCountdown && seconds <= 10 && seconds > 0;
    const isExpired = isCountdown && seconds === 0;

    return (
        <div
            className={`flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-sm font-semibold tabular-nums transition-all duration-300 ${
                isExpired
                    ? 'bg-red-100 text-red-600 dark:bg-red-900/30'
                    : isCritical
                    ? 'bg-red-50 text-red-500 dark:bg-red-900/20 animate-pulse'
                    : isWarning
                    ? 'bg-amber-50 text-amber-600 dark:bg-amber-900/20'
                    : 'text-muted-foreground'
            }`}
        >
            <img
                src="/icons/clock.png"
                alt=""
                width={15}
                height={15}
                style={{
                    objectFit: 'contain',
                    filter: isExpired || isCritical
                        ? 'brightness(0) saturate(100%) invert(30%) sepia(80%) saturate(600%) hue-rotate(330deg)'
                        : isWarning
                        ? 'brightness(0) saturate(100%) invert(55%) sepia(60%) saturate(500%) hue-rotate(15deg)'
                        : undefined,
                }}
            />
            <span>
                {isCountdown && <span className="mr-0.5 text-xs opacity-60">−</span>}
                {String(mins).padStart(2, '0')}:{String(secs).padStart(2, '0')}
            </span>
        </div>
    );
}
