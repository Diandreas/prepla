import { useState } from 'react';
import { useAudioRecorder } from '@/hooks/use-audio-recorder';

interface DialogueTurn {
    speaker: 'examiner' | 'candidate';
    text?: string;
    prompt?: string;
}

interface RolePlayProps {
    question: {
        id: string;
        scenario: string;
        role: string;
        dialogue_turns: DialogueTurn[];
        prep_time?: number;
        speak_time_per_turn?: number;
    };
    onAnswer: (questionId: string, answer: string) => void;
    selectedAnswer?: string;
    disabled?: boolean;
}

export function RolePlay({ question, onAnswer, selectedAnswer, disabled }: RolePlayProps) {
    const [currentTurn, setCurrentTurn] = useState(0);
    const [recordings, setRecordings] = useState<string[]>([]);
    const { isRecording, audioUrl, startRecording, stopRecording, error } = useAudioRecorder();

    const turns = question.dialogue_turns;
    const candidateTurns = turns.filter((t) => t.speaker === 'candidate');
    const allDone = selectedAnswer || recordings.length >= candidateTurns.length;

    const handleRecord = async () => {
        if (isRecording) {
            stopRecording();
        } else {
            await startRecording();
        }
    };

    const handleNextTurn = () => {
        if (audioUrl) {
            setRecordings([...recordings, audioUrl]);
        }

        const nextTurnIdx = currentTurn + 1;
        if (nextTurnIdx >= turns.length) {
            // All turns done
            onAnswer(question.id, 'completed');
        } else {
            setCurrentTurn(nextTurnIdx);
        }
    };

    return (
        <div className="space-y-4">
            {/* Scenario */}
            <div className="rounded-xl border-2 border-primary/20 bg-primary/5 p-4">
                <p className="mb-1 text-[10px] font-bold uppercase tracking-wider text-primary">Scénario</p>
                <p className="text-sm">{question.scenario}</p>
                <p className="mt-2 text-sm font-medium italic text-primary">Votre rôle : {question.role}</p>
            </div>

            {error && (
                <div className="rounded-lg bg-red-50 p-3 text-sm text-red-600">{error}</div>
            )}

            {/* Dialogue */}
            <div className="space-y-3">
                {turns.map((turn, i) => {
                    const isPast = i < currentTurn;
                    const isCurrent = i === currentTurn;
                    const isFuture = i > currentTurn;
                    const isExaminer = turn.speaker === 'examiner';

                    if (isFuture && !allDone) return null;

                    return (
                        <div
                            key={i}
                            className={`flex ${isExaminer ? 'justify-start' : 'justify-end'} ${isFuture ? 'opacity-50' : ''}`}
                        >
                            <div
                                className={`max-w-[80%] rounded-2xl p-3 ${
                                    isExaminer
                                        ? 'bg-muted/50 rounded-tl-sm'
                                        : 'bg-primary/10 rounded-tr-sm'
                                }`}
                            >
                                <p className="mb-1 text-[10px] font-bold uppercase tracking-wider text-muted-foreground">
                                    {isExaminer ? 'Examinateur' : 'Vous'}
                                </p>

                                {isExaminer ? (
                                    <p className="text-sm">{turn.text}</p>
                                ) : isPast || allDone ? (
                                    <div className="space-y-2">
                                        {turn.prompt && <p className="text-xs italic text-muted-foreground">{turn.prompt}</p>}
                                        {recordings[turns.filter((t, idx) => t.speaker === 'candidate' && idx <= i).length - 1] && (
                                            <audio
                                                controls
                                                src={recordings[turns.filter((t, idx) => t.speaker === 'candidate' && idx <= i).length - 1]}
                                                className="h-8 w-full"
                                            />
                                        )}
                                    </div>
                                ) : isCurrent && !disabled ? (
                                    <div className="space-y-2">
                                        {turn.prompt && <p className="text-xs italic text-muted-foreground">{turn.prompt}</p>}
                                        <div className="flex items-center gap-2">
                                            <button
                                                onClick={handleRecord}
                                                className={`flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-bold text-white transition-all ${
                                                    isRecording ? 'bg-red-500 animate-pulse' : 'bg-primary'
                                                }`}
                                            >
                                                <div className={`h-3 w-3 rounded-full ${isRecording ? 'bg-white' : 'bg-white/60'}`} />
                                                {isRecording ? 'Stop' : 'Enregistrer'}
                                            </button>
                                            {audioUrl && !isRecording && (
                                                <button
                                                    onClick={handleNextTurn}
                                                    className="rounded-xl border border-primary bg-background px-4 py-2 text-sm font-bold text-primary"
                                                >
                                                    Suivant
                                                </button>
                                            )}
                                        </div>
                                        {audioUrl && !isRecording && (
                                            <audio controls src={audioUrl} className="h-8 w-full" />
                                        )}
                                    </div>
                                ) : (
                                    <p className="text-xs text-muted-foreground">En attente...</p>
                                )}
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
