interface TrueFalseNgProps {
    question: {
        id: string;
        text: string;
    };
    onAnswer: (questionId: string, answer: string) => void;
    selectedAnswer?: string;
}

const options = ['True', 'False', 'Not Given'];

export function TrueFalseNg({ question, onAnswer, selectedAnswer }: TrueFalseNgProps) {
    return (
        <div className="space-y-4">
            <p className="text-lg font-medium">{question.text}</p>
            <div className="flex gap-3">
                {options.map((option) => (
                    <button
                        key={option}
                        onClick={() => onAnswer(question.id, option)}
                        className={`flex-1 rounded-lg border p-3 text-center font-medium transition-all ${
                            selectedAnswer === option
                                ? 'border-primary bg-primary/5 ring-1 ring-primary'
                                : 'border-border hover:border-primary/50'
                        }`}
                    >
                        {option}
                    </button>
                ))}
            </div>
        </div>
    );
}
