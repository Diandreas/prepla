export interface AnswerEvaluation {
    correct: boolean;
    accuracy: number;
}

export interface SessionDetail {
    question_id: string;
    correct: boolean;
    given_answer: unknown;
    correct_answer: unknown;
    explanation: string;
}

export interface SessionScore {
    score: number;
    total: number;
    accuracy: number;
    details: SessionDetail[];
}

/** Any question record: the rules read only the fields they need. */
export type ScorableQuestion = object;

export function normalizeAnswer(value: unknown): string;

export function needsServerEvaluation(question: ScorableQuestion | null | undefined, fallbackType?: string): boolean;

export function evaluateAnswer(question: ScorableQuestion | null | undefined, answer: unknown): AnswerEvaluation;

export function isAnswerCorrect(question: ScorableQuestion | null | undefined, answer: unknown): boolean;

export function expectedAnswerText(question: ScorableQuestion | null | undefined): string;

export function scoreSession(questions: Array<{ id: string; correct_answer?: unknown; explanation?: string }>, answers: Record<string, unknown>): SessionScore;
