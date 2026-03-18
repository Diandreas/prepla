import { useEffect, useState } from 'react';
import { Clock } from 'lucide-react';

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
            <Clock className="h-4 w-4" />
            <span>{String(mins).padStart(2, '0')}:{String(secs).padStart(2, '0')}</span>
            {timeLimit && (
                <span className="text-xs">/ {Math.floor(timeLimit / 60)}:{String(timeLimit % 60).padStart(2, '0')}</span>
            )}
        </div>
    );
}
