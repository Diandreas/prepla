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
                        className={`flex-1 rounded-lg border p-3 text-center font-medium transition-all ${
                            selectedAnswer === value
                                ? 'border-primary bg-primary/5 ring-1 ring-primary'
                                : 'border-border hover:border-primary/50'
                        }`}
                    >
                        {label}
                    </button>
                ))}
            </div>
        </div>
    );
}
