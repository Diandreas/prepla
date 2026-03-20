import { useEffect, useState } from 'react';

interface ExerciseTimerProps {
    onTimeUpdate: (seconds: number) => void;
    timeLimit?: number; // in seconds
}

export function ExerciseTimer({ onTimeUpdate, timeLimit }: ExerciseTimerProps) {
    const [seconds, setSeconds] = useState(0);

    useEffect(() => {
        const interval = setInterval(() => {
            setSeconds((prev) => {
                const next = prev + 1;
                onTimeUpdate(next);
                return next;
            });
        }, 1000);
        return () => clearInterval(interval);
    }, [onTimeUpdate]);

    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    const isOverTime = timeLimit ? seconds > timeLimit : false;

    return (
        <div className={`flex items-center gap-1.5 text-sm ${isOverTime ? 'text-red-500' : 'text-muted-foreground'}`}>
            <img src="/icons/clock.png" alt="" width={16} height={16} style={{ objectFit: 'contain' }} />
            <span>{String(mins).padStart(2, '0')}:{String(secs).padStart(2, '0')}</span>
            {timeLimit && (
                <span className="text-xs">/ {Math.floor(timeLimit / 60)}:{String(timeLimit % 60).padStart(2, '0')}</span>
            )}
        </div>
    );
}
