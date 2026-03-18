import { Input } from '@/components/ui/input';

interface GapFillProps {
    question: {
        id: string;
        text: string;
    };
    onAnswer: (questionId: string, answer: string) => void;
    selectedAnswer?: string;
}

export function GapFill({ question, onAnswer, selectedAnswer }: GapFillProps) {
    return (
        <div className="space-y-4">
            <p className="text-lg font-medium">{question.text}</p>
            <Input
                placeholder="Type your answer..."
                value={selectedAnswer ?? ''}
                onChange={(e) => onAnswer(question.id, e.target.value)}
                className="max-w-md"
            />
        </div>
    );
}
