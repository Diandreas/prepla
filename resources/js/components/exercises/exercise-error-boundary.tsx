import { Component, type ReactNode } from 'react';

interface Props {
    children: ReactNode;
    /** Remounts/resets the boundary automatically when this changes (e.g. question.id) —
     *  so an error on one question doesn't stay stuck once the player moves on. */
    resetKey: string;
    onSkip: () => void;
}

interface State {
    hasError: boolean;
}

/**
 * Without this, ANY exercise component crashing during render (malformed AI
 * data, e.g. an object rendered where a string was expected — React error #31)
 * takes down the whole session to a blank screen, losing all progress. This is
 * the same class of bug already fixed on session-report.tsx, but that page
 * only affects the final report; a crash here happens mid-session.
 */
export class ExerciseErrorBoundary extends Component<Props, State> {
    state: State = { hasError: false };

    static getDerivedStateFromError(): State {
        return { hasError: true };
    }

    componentDidCatch(error: unknown, info: unknown) {
        console.error('ExerciseErrorBoundary caught an error:', error, info);
    }

    componentDidUpdate(prevProps: Props) {
        if (this.state.hasError && prevProps.resetKey !== this.props.resetKey) {
            this.setState({ hasError: false });
        }
    }

    render() {
        if (this.state.hasError) {
            return (
                <div className="space-y-4 rounded-xl border-2 border-destructive/30 bg-destructive/5 p-6 text-center">
                    <p className="text-sm font-medium text-foreground">
                        Un problème est survenu en affichant cette question.
                    </p>
                    <div className="flex justify-center gap-3">
                        <button
                            onClick={this.props.onSkip}
                            className="rounded-lg bg-primary px-4 py-2 text-xs font-bold text-primary-foreground"
                        >
                            Passer cette question
                        </button>
                        <button
                            onClick={() => window.location.reload()}
                            className="rounded-lg border border-border px-4 py-2 text-xs font-bold text-foreground"
                        >
                            Recharger la page
                        </button>
                    </div>
                </div>
            );
        }
        return this.props.children;
    }
}
