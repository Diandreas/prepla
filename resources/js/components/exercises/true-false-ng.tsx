interface TrueFalseNgProps {
    question: {
        id: string;
        text: string;
    };
    onAnswer: (questionId: string, answer: string) => void;
    selectedAnswer?: string;
}

const options = [
    { value: 'True', label: 'Vrai' },
    { value: 'False', label: 'Faux' },
    { value: 'Not Given', label: 'Non mentionné' },
];

export function TrueFalseNg({ question, onAnswer, selectedAnswer }: TrueFalseNgProps) {
    return (
        <div className="space-y-4">
            <p className="text-lg font-medium">{question.text}</p>
            <div className="flex gap-3">
                {options.map(({ value, label }) => (
                    <button
                        key={value}
                        onClick={() => onAnswer(question.id, value)}
                        className={`duo-press flex-1 rounded-lg border-2 p-3 text-center font-medium ${
                            selectedAnswer === value
                                ? 'border-primary bg-primary/5'
                                : 'border-border'
                        }`}
                        style={{ boxShadow: selectedAnswer === value ? '0 4px 0 0 var(--primary, #4A90E2)' : '0 3px 0 0 #e5e7eb' }}
                    >
                        {label}
                    </button>
                ))}
            </div>
        </div>
    );
}
